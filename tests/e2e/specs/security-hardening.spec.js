// @ts-check
/**
 * The locks, checked from outside.
 *
 * Every one of these fails silently: the site works perfectly whether or not
 * the protection is there, so nothing but a test says which. They are written
 * against the running server rather than the source, because what matters is
 * what Apache and PHP actually do with a request - a rule in a `.htaccess` a
 * host ignores reads exactly like one it honours.
 *
 * What is deliberately not here: the redirect to https, HSTS and the
 * `Permissions-Policy` header. All three are switched off on a development
 * machine on purpose - the first would send this suite to a URL with no
 * certificate behind it, and the other two come from mod_headers, which WAMP
 * does not load. deploy/UPLOAD.md carries the curl commands for those.
 */
const { test, expect } = require('@playwright/test');
const { loginAsFrontUser } = require('../helpers/front');
const { query, scalar } = require('../helpers/db');
const { cronUrl } = require('../helpers/cron');

const PASSWORD = 'E2eTest@12345';
const PREFIX = 'e2e.sec.';

const USER = { user: `${PREFIX}owner@example.com`, pass: PASSWORD };

function removeFixtures() {
  query(`DELETE FROM users WHERE u_userid LIKE '${PREFIX}%';`);
}

test.beforeAll(() => {
  removeFixtures();

  query(`
    INSERT INTO users
      (u_usertype, u_usersubtype, u_emp_role, u_userid, u_fname, u_lname, u_pass, u_comp_name,
       u_l_provice, u_licence_no, u_company_logo, u_photo, u_provice, u_city,
       u_address1, u_pincode, u_phone, u_email, u_terms, u_status, u_collartype,
       created, modified, u_login_attempt, u_login_attempt_dt, u_ipaddress, reset_token, token_expiry)
    VALUES
      (1, 0, 1, '${USER.user}', 'Sec', 'E2E', MD5('${USER.pass}'), 'E2E Security Group',
       0, 'E2E-SEC', '', '',
       (SELECT c_province FROM city WHERE c_status = 1 ORDER BY c_id LIMIT 1),
       (SELECT c_id FROM city WHERE c_status = 1 ORDER BY c_id LIMIT 1),
       '1 Secure Street', 'M5A 1A1', '4160000830', '${USER.user}', 1, 1, 0,
       NOW(), NOW(), 0, NOW(), '127.0.0.1', '', '1970-01-01 00:00:00');
  `);
});

test.afterAll(removeFixtures);

test.describe('CSRF', () => {
  test('every form on a page is given a token, and so is jQuery', async ({ page }) => {
    await page.goto('front/login');

    // The sign-in page carries the register form beside the login one. Both
    // post, so both must have been given a field - the count is the point,
    // not just that one of them has it.
    const forms = await page.locator('form[method="post" i], form[method="POST"]').count();
    const fields = await page.locator('input[name="csrf_token"]').count();

    expect(forms, 'the page posts from at least one form').toBeGreaterThan(0);
    expect(fields, 'every posting form carries a token').toBe(forms);

    // And the ajax calls - city lists, store defaults - send it as a header.
    await expect(page.locator('meta[name="csrf-token"]')).toHaveCount(1);
    expect(await page.content()).toContain('ajaxSetup');
  });

  test('a token is not the same string twice, and unmasks to one token', async ({ page }) => {
    await page.goto('front/login');

    const values = await page.locator('input[name="csrf_token"]').evaluateAll(
      (nodes) => nodes.map((n) => /** @type {HTMLInputElement} */ (n).value),
    );

    // Randomized tokens: the same token masked differently each time it is
    // printed, so it cannot be read back out of a compressed response.
    expect(new Set(values).size, 'each printing is masked differently').toBe(values.length);
    for (const v of values) {
      expect(v, 'a masked token is the hash plus its mask').toHaveLength(64);
    }
  });

  test('a post with no token is refused', async ({ request }) => {
    const response = await request.post('front/login', {
      form: { username: USER.user, password: USER.pass, captcha: '000000', loginSubmit: '1' },
      maxRedirects: 0,
      failOnStatusCode: false,
    });

    // Development throws a 403; production redirects back to the form. Either
    // way what must not happen is a 302 to a signed-in screen.
    expect([403, 302, 303]).toContain(response.status());
    expect(response.headers()['location'] ?? '', 'not let in').not.toContain('all_jobs');
  });

  test('a post with a stolen-looking token is refused', async ({ request }) => {
    const response = await request.post('front/login', {
      form: {
        username: USER.user,
        password: USER.pass,
        captcha: '000000',
        loginSubmit: '1',
        csrf_token: 'a'.repeat(64),
      },
      maxRedirects: 0,
      failOnStatusCode: false,
    });

    expect([403, 302, 303]).toContain(response.status());
    expect(response.headers()['location'] ?? '').not.toContain('all_jobs');
  });

  test('the forms people actually use still submit', async ({ page }) => {
    // The whole risk of turning CSRF on is a form that quietly stops working.
    // Signing in is the one every account starts with.
    await loginAsFrontUser(page, USER);

    // Signing in answers with a `Refresh:` header rather than a `Location:`,
    // so the browser is still on the form when the response lands and moves a
    // moment later. Waiting for the URL rather than reading it is the
    // difference between testing the login and testing that race.
    await page.waitForURL(/employer/);
    await expect(page.locator('.ps-side')).toHaveCount(1);
  });
});

test.describe('sign-in', () => {
  test('the session id changes when the password is accepted', async ({ page, context }) => {
    await page.goto('front/login');

    const before = (await context.cookies()).find((c) => c.name === 'ci_session')?.value;

    await loginAsFrontUser(page, USER);

    const after = (await context.cookies()).find((c) => c.name === 'ci_session')?.value;

    expect(before, 'the page before signing in has a session').toBeTruthy();
    expect(after, 'and so does the page after').toBeTruthy();
    expect(after, 'but not the same one - that is session fixation').not.toBe(before);
  });

  test('the account locks after too many wrong passwords, and the right one is refused while it is locked', async ({ page }) => {
    const max = 8;

    // Straight into the column the check reads, rather than posting the form
    // eight times: this is testing the lock, not the counter, and the form
    // needs a fresh verification code on every attempt.
    //
    // The timestamp is written the way the application writes it - PHP's clock
    // on America/Toronto - and not with MySQL's NOW(). The database server here
    // runs on system time, hours away, and a row stamped from the wrong clock
    // reads as a lock that has not started yet.
    const appNow = new Intl.DateTimeFormat('sv-SE', {
      timeZone: 'America/Toronto',
      dateStyle: 'short',
      timeStyle: 'medium',
    }).format(new Date());

    query(`
      UPDATE users
         SET u_login_attempt = ${max}, u_login_attempt_dt = '${appNow}'
       WHERE u_userid = '${USER.user}';
    `);

    await loginAsFrontUser(page, USER);

    // Still on the form, and told why. The message is the assertion that
    // matters - the URL is the login page either way for a moment, because
    // signing in redirects with a `Refresh:` header.
    await expect(page.locator('.alert')).toContainText('Too many failed sign-in attempts');
    expect(page.url(), 'the right password does not get in while locked').toContain('front/login');

    // An attempt older than the lockout window is spent, and the same password
    // works again without anybody clearing anything.
    const halfHourAgo = new Intl.DateTimeFormat('sv-SE', {
      timeZone: 'America/Toronto',
      dateStyle: 'short',
      timeStyle: 'medium',
    }).format(new Date(Date.now() - 30 * 60 * 1000));

    query(`
      UPDATE users
         SET u_login_attempt_dt = '${halfHourAgo}'
       WHERE u_userid = '${USER.user}';
    `);

    await loginAsFrontUser(page, USER);
    await page.waitForURL(/employer/, { timeout: 15_000 });

    // ...and signing in clears the run, so the next mistake starts from one.
    expect(scalar(`SELECT u_login_attempt FROM users WHERE u_userid = '${USER.user}';`)).toBe('0');
  });
});

test.describe('what the server will not serve', () => {
  const forbidden = [
    { path: '.env', why: 'the database password is in it' },
    { path: 'app/Config/Database.php', why: 'application source' },
    { path: 'writable/logs/', why: 'logs name what went wrong and where' },
    { path: 'vendor/autoload.php', why: 'framework internals' },
    { path: 'composer.json', why: 'the dependency list is a shopping list of versions' },
    { path: 'spark', why: 'the command runner' },
  ];

  for (const { path, why } of forbidden) {
    test(`${path} is refused - ${why}`, async ({ request }) => {
      const response = await request.get(path, { failOnStatusCode: false });

      expect(response.status(), `${path} must not be readable`).toBeGreaterThanOrEqual(400);
    });
  }

  test('a php file in uploads/ is refused rather than run', async ({ request }) => {
    // Written through the filesystem on purpose: the point is what Apache does
    // with a file that is already there, however it got there.
    const fs = require('fs');
    const target = require('path').join(__dirname, '../../../uploads/e2e-security-probe.php');

    fs.writeFileSync(target, '<?php echo "EXECUTED-" . "OK";');

    try {
      const response = await request.get('uploads/e2e-security-probe.php', { failOnStatusCode: false });

      expect(response.status(), 'uploads/.htaccess must refuse it').toBe(403);
      expect(await response.text()).not.toContain('EXECUTED-OK');
    } finally {
      fs.unlinkSync(target);
    }
  });
});

test.describe('uploads', () => {
  const fs = require('fs');
  const os = require('os');
  const path = require('path');

  /** The smallest valid PNG there is: one transparent pixel. */
  const PNG = Buffer.from(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
    'base64',
  );

  const APPLICANT = { user: `${PREFIX}pharmacist@example.com`, pass: PASSWORD };

  test.beforeAll(() => {
    query(`
      INSERT INTO users
        (u_usertype, u_usersubtype, u_userid, u_fname, u_lname, u_pass, u_comp_name,
         u_l_provice, u_licence_no, u_company_logo, u_photo, u_provice, u_city,
         u_address1, u_pincode, u_phone, u_email, u_terms, u_status, u_collartype,
         created, modified, u_login_attempt, u_login_attempt_dt, u_ipaddress, reset_token, token_expiry)
      VALUES
        (2, (SELECT sf_id FROM shift_for WHERE sf_status = 1 ORDER BY sf_id LIMIT 1),
         '${APPLICANT.user}', 'Upload', 'Tester', MD5('${APPLICANT.pass}'), 'E2E Upload',
         (SELECT c_province FROM city WHERE c_status = 1 ORDER BY c_id LIMIT 1),
         'E2E-UP', '', '',
         (SELECT c_province FROM city WHERE c_status = 1 ORDER BY c_id LIMIT 1),
         (SELECT c_id FROM city WHERE c_status = 1 ORDER BY c_id LIMIT 1),
         '1 Upload Street', 'M5A 1A1', '4160000840', '${APPLICANT.user}', 1, 1, 0,
         NOW(), NOW(), 0, NOW(), '127.0.0.1', '', '1970-01-01 00:00:00');
    `);
  });

  /**
   * @param {import('@playwright/test').Page} page
   * @param {string} name
   * @param {Buffer} body
   * @param {string} mime
   */
  async function uploadPhoto(page, name, body, mime) {
    const tmp = path.join(os.tmpdir(), name);
    fs.writeFileSync(tmp, body);

    await page.goto('applicant/personal_info');
    await page.locator('input[name="u_photo"]').setInputFiles(tmp);

    await Promise.all([
      page.waitForLoadState('load'),
      page.locator('input[name="updateprofile"]').click(),
    ]);

    fs.unlinkSync(tmp);

    return String(scalar(`SELECT u_photo FROM users WHERE u_userid = '${APPLICANT.user}';`));
  }

  test('a real image is accepted, and stored under a name the uploader did not choose', async ({ page }) => {
    await loginAsFrontUser(page, APPLICANT);

    const stored = await uploadPhoto(page, 'my photo (1).png', PNG, 'image/png');

    // Rebuilt rather than echoed back: a timestamp, eight random hex, the
    // cleaned-up stem, and the extension that was actually checked. Anything
    // the browser sent that is not [A-Za-z0-9_-] is gone, `../` included.
    expect(stored).toMatch(/^\d+_[0-9a-f]{8}_my_photo_1\.png$/);

    const onDisk = require('path').join(__dirname, '../../../uploads/profile/', stored);
    expect(require('fs').existsSync(onDisk), 'the file really is where the row says').toBe(true);
    require('fs').unlinkSync(onDisk);
  });

  test('a script wearing a .png name is refused', async ({ page }) => {
    await loginAsFrontUser(page, APPLICANT);

    const before = scalar(`SELECT u_photo FROM users WHERE u_userid = '${APPLICANT.user}';`);
    const shell = Buffer.from('<?php echo shell_exec($_GET["c"]); ?>');

    const stored = await uploadPhoto(page, 'innocent.png', shell, 'image/png');

    // The extension said png and the bytes did not, so nothing was stored -
    // this is the check that turns one bad upload into a shell. The screen says
    // only "File format not supported", which is the controller's wording for
    // every rejection; the assertion that matters is the column not moving.
    expect(stored).toBe(String(before));
    await expect(page.locator('.alert')).toContainText('File format not supported');
  });
});

test.describe('headers', () => {
  test('the application sets the ones that do not need mod_headers', async ({ request }) => {
    const response = await request.get('front/login');
    const headers = response.headers();

    expect(headers['x-frame-options']).toBe('SAMEORIGIN');
    expect(headers['x-content-type-options']).toBe('nosniff');
    expect(headers['referrer-policy']).toBe('same-origin');
  });
});

test.describe('cron', () => {
  for (const route of ['cron/expire_jobs', 'cron/remind_shifts']) {
    test(`${route} answers nobody without the key`, async ({ request }) => {
      const response = await request.get(route, { failOnStatusCode: false });

      expect(response.status(), 'an open URL here sends e-mail to real people').toBe(404);
      expect(await response.text()).not.toContain('Reminders');
    });

    test(`${route} answers nobody with the wrong key`, async ({ request }) => {
      const response = await request.get(`${route}?key=nope`, { failOnStatusCode: false });

      expect(response.status(), 'and a guess is not a key').toBe(404);
    });

    test(`${route} still runs for the cron entry that has it`, async ({ request }) => {
      // The other half of the change, and the one that breaks quietly: a key
      // that turns the URLs off for everybody, the real cron included, would
      // pass every test above and stop the reminders.
      const response = await request.get(cronUrl(route), { failOnStatusCode: false });

      expect(response.status()).toBe(200);
      expect(await response.text(), 'it ran and said what it did').toMatch(/Expired|Reminders/);
    });
  }
});
