// @ts-check
/**
 * Every list that prints somebody's mobile offers it as a WhatsApp chat.
 *
 * An owner's, a manager's and an applicant's alike - the three kinds of account
 * on the site, wherever a screen shows their number. The store's own line is
 * the exception and stays plain: that is the counter phone, and a WhatsApp mark
 * on it would promise a chat nobody is ever going to read.
 *
 * The interesting part is the number, not the icon. WhatsApp resolves the
 * number as given, so a link built from the bare ten digits the phone column
 * holds would open a chat with nobody; `whatsappNumber()` puts the configured
 * country code on the front, and what these check is what reaches the href.
 *
 * Two areas, two sets of icons. The back office has Font Awesome and uses its
 * handset and WhatsApp marks; the portal behind a login loads line-icons, which
 * has no WhatsApp mark at all, so that one is drawn as an inline SVG. Both are
 * checked, because a link whose mark silently fails to render is a link nobody
 * clicks.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, settle, expectNoServerError } = require('../helpers/admin');
const { AGENCY, seedShiftFixture, removeShiftFixture, loginAsAgency } = require('../helpers/front');
const { query, scalar } = require('../helpers/db');

/** The country code AppSettings::$phoneCountryCode holds - Canada. */
const COUNTRY = '1';

const PREFIX = 'e2e.wa.';

/** Ten local digits, the form the phone field actually holds. */
const OWNER = { user: `${PREFIX}owner@example.com`, company: 'E2E WA Owner Group', phone: '4165550143' };
const MANAGER = { user: `${PREFIX}manager@example.com`, company: 'E2E WA Manager Store', phone: '4165550178' };
const APPLICANT = { user: `${PREFIX}applicant@example.com`, phone: '4165550199' };

function removeFixtures() {
  query(`DELETE FROM users WHERE u_userid LIKE '${PREFIX}%';`);
}

function seedUser(account, usertype, empRole, fname) {
  query(`
    INSERT INTO users
      (u_usertype, u_usersubtype, u_emp_role, u_userid, u_fname, u_lname, u_pass,
       u_comp_name, u_l_provice, u_licence_no, u_company_logo, u_photo, u_provice,
       u_city, u_address1, u_pincode, u_phone, u_email, u_terms, u_status,
       u_collartype, created, modified, u_login_attempt, u_login_attempt_dt,
       u_ipaddress, reset_token, token_expiry)
    VALUES
      (${usertype}, 0, ${empRole}, '${account.user}', '${fname}', 'E2E-WA', MD5('E2eTest@12345'),
       '${account.company || ''}', 0, 'E2E-WA-LIC', '', '', 0,
       0, '', '', '${account.phone}', '${account.user}', 1, 1,
       0, NOW(), NOW(), 0, NOW(),
       '127.0.0.1', '', '1970-01-01 00:00:00');
  `);

  return Number(scalar(`SELECT u_id FROM users WHERE u_userid = '${account.user}';`));
}

test.beforeAll(() => {
  removeFixtures();
  seedUser(OWNER, 1, 1, 'Owner');
  seedUser(MANAGER, 1, 2, 'Manager');
  seedUser(APPLICANT, 2, 0, 'Applicant');
});

test.afterAll(removeFixtures);

/**
 * The one row a search has left, and the WhatsApp link in it.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} term what to filter the list down to
 */
async function rowLink(page, term) {
  await page.locator('#example1_filter input').fill(term);

  const row = page.locator('#example1 tbody tr').first();
  await expect(row).toContainText('E2E-WA');

  return row.locator('a[href*="web.whatsapp.com"]');
}

test.describe('the back office', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('an applicant mobile opens a chat, country code and all', async ({ page }) => {
    await page.goto('sadmin/applicant');
    await settle(page);

    const link = await rowLink(page, APPLICANT.phone);

    await expect(link).toHaveCount(1);
    await expect(link).toHaveAttribute('href', `https://web.whatsapp.com/send?phone=${COUNTRY}${APPLICANT.phone}`);

    // The number is printed as it is held, not as it is dialled.
    await expect(link).toContainText(APPLICANT.phone);

    // A new tab, and no window handed our session to whatsapp.com.
    await expect(link).toHaveAttribute('target', '_blank');
    await expect(link).toHaveAttribute('rel', /noopener/);

    // Font Awesome is loaded here, so the marks come out of the font.
    await expect(link.locator('i.fa-whatsapp')).toHaveCount(1);
    await expect(link.locator('i.fa-mobile-alt')).toHaveCount(1);

    await expectNoServerError(page);
  });

  test('an owner and a manager get the same treatment on their own lists', async ({ page }) => {
    for (const [screen, account] of [
      ['sadmin/employer/owner', OWNER],
      ['sadmin/employer/manager', MANAGER],
      ['sadmin/employer', OWNER],
    ]) {
      await page.goto(screen);
      await settle(page);

      const link = await rowLink(page, account.phone);

      await expect(link, `${screen} links the number`).toHaveCount(1);
      await expect(link).toHaveAttribute('href', `https://web.whatsapp.com/send?phone=${COUNTRY}${account.phone}`);

      await expectNoServerError(page);
    }
  });

  test('the dashboard panels link the numbers they list', async ({ page }) => {
    await page.goto('sadmin/dashboard');
    await settle(page);

    // The panels list accounts registered in the last few days, which is what
    // the fixtures above are. An employer is listed twice over - once on the
    // tab for every kind and once on their own - so this asks what the links
    // point at rather than how many there are.
    const links = page.locator('a[href*="web.whatsapp.com"]');

    for (const account of [OWNER, APPLICANT]) {
      const found = links.filter({ hasText: account.phone });

      await expect(found.first(), 'the panel lists the number as a chat').toBeAttached();

      for (const link of await found.all()) {
        await expect(link).toHaveAttribute('href', `https://web.whatsapp.com/send?phone=${COUNTRY}${account.phone}`);
      }
    }

    await expectNoServerError(page);
  });
});

test.describe('the portal behind a login', () => {
  test.beforeAll(() => {
    seedShiftFixture();
  });

  test.afterAll(() => {
    removeShiftFixture();
  });

  test('an owner can message a pharmacist who applied', async ({ page }) => {
    // The screen lists the booked applicant, not everyone who applied - so the
    // fixture's first application is booked here to put a row on it.
    const shiftId = scalar(`
      SELECT s.p_id FROM stu_saved_applied_jobs s
       JOIN users u ON u.u_id = s.agency_id
      WHERE u.u_userid = '${AGENCY.user}' LIMIT 1;
    `);

    query(`UPDATE stu_saved_applied_jobs SET sj_is_approved = 1 WHERE p_id = ${shiftId};`);

    await loginAsAgency(page);
    await page.goto(`employer/applied_applicants/${shiftId}`);
    await settle(page);

    const link = page.locator('a.ps-wa-link[href*="web.whatsapp.com"]').first();
    await expect(link).toHaveCount(1);

    const applicantPhone = String(scalar(`
      SELECT u.u_phone FROM stu_saved_applied_jobs s
       JOIN users u ON u.u_id = s.u_id
      WHERE s.p_id = ${shiftId} LIMIT 1;
    `)).trim();

    await expect(link).toHaveAttribute('href', `https://web.whatsapp.com/send?phone=${COUNTRY}${applicantPhone}`);

    // line-icons has no WhatsApp mark, so it is drawn. Without this the link
    // would still work and read as nothing but a phone number.
    await expect(link.locator('.ps-wa-mark svg')).toHaveCount(1);
    await expect(link.locator('.ps-wa-mark svg')).toBeVisible();

    await expectNoServerError(page);
  });
});

test('a number too short to be real is printed, and links nowhere', async ({ page }) => {
  // Five digits is an extension, not a handset, and whatsappNumber() will not
  // build a chat out of one - the phone field is ten digits and guarded, but
  // the column is free text and rows from before that guard are not.
  const stub = { user: `${PREFIX}stub@example.com`, company: 'E2E WA Stub Group', phone: '12345' };

  seedUser(stub, 1, 1, 'Stub');

  await loginAsAdmin(page);
  await page.goto('sadmin/employer/owner');
  await settle(page);

  await page.locator('#example1_filter input').fill(stub.company);

  const row = page.locator('#example1 tbody tr').first();
  await expect(row).toContainText(stub.company);

  // Still worth reading, so it is shown - just not as a chat.
  await expect(row).toContainText(stub.phone);
  await expect(row.locator('a[href*="web.whatsapp.com"]')).toHaveCount(0);
  await expect(row.locator('i.fa-whatsapp')).toHaveCount(0);

  await expectNoServerError(page);
});
