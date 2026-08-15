// @ts-check
/**
 * The back office splits employers by the user type chosen at registration.
 *
 * Registration offers Owner, Manager and Applicant. Both employer kinds are
 * one `users.u_usertype` row and differ only by `u_emp_role` - 1 for an owner,
 * 2 for a manager. The sidebar has to list them apart so an admin can find a
 * new sign-up of a given kind, and the listing has to activate it without
 * opening the record.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, expectNoServerError, filterTable } = require('../helpers/admin');
const { query, scalar } = require('../helpers/db');

/** One pending sign-up per kind, so each list has exactly one row to find. */
const SEED = {
  owner: { email: 'e2e.kind.multi@example.com', company: 'E2E Kind Owner' },
  manager: { email: 'e2e.kind.manager@example.com', company: 'E2E Kind Manager' },
  applicant: { email: 'e2e.kind.applicant@example.com', company: '' },
};

/** @type {Record<string, number>} */
const ids = {};

/**
 * @param {string} email
 * @param {{usertype: number, role: number, parent: number, company: string}} opts
 * @returns {number} users.u_id
 */
function seedUser(email, opts) {
  query(`
    INSERT INTO users
      (u_usertype, u_usersubtype, u_emp_role, u_parent_id, u_userid, u_fname, u_lname, u_pass,
       u_comp_name, u_l_provice, u_licence_no, u_company_logo, u_photo, u_provice, u_city,
       u_address1, u_pincode, u_phone, u_email, u_terms, u_status, u_collartype,
       created, modified, u_login_attempt, u_login_attempt_dt, u_ipaddress,
       reset_token, token_expiry)
    VALUES
      (${opts.usertype}, 0, ${opts.role}, ${opts.parent}, '${email}', 'Kind', 'E2E',
       MD5('E2eTest@12345'), '${opts.company}', 0, '', '', '', 0, 0,
       '', '', '0000000000', '${email}', 1, 0, 0,
       NOW(), NOW(), 0, NOW(), '127.0.0.1', '', '1970-01-01 00:00:00');
  `);

  return Number(scalar(`SELECT u_id FROM users WHERE u_userid = '${email}';`));
}

function removeSeeded() {
  query(`DELETE FROM users WHERE u_userid LIKE 'e2e.kind.%@example.com';`);
}

test.beforeAll(() => {
  removeSeeded();

  // Order matters: a manager answers to a multi-store owner, so the owner has
  // to exist before its `u_parent_id` can point at one.
  ids.owner = seedUser(SEED.owner.email, {
    usertype: 1,
    role: 1,
    parent: 0,
    company: SEED.owner.company,
  });


  ids.manager = seedUser(SEED.manager.email, {
    usertype: 1,
    role: 2,
    parent: ids.owner,
    company: SEED.manager.company,
  });

  ids.applicant = seedUser(SEED.applicant.email, {
    usertype: 2,
    role: 0,
    parent: 0,
    company: '',
  });
});

test.afterAll(() => {
  removeSeeded();
});

test.beforeEach(async ({ page }) => {
  await loginAsAdmin(page);
});

test('the sidebar lists every employer kind under Manage Employers', async ({ page }) => {
  await page.goto('sadmin/dashboard');

  const sidebar = page.locator('aside.main-sidebar');

  // The treeview is collapsed away from the employer screens, so the entries
  // are matched in the markup rather than by what is on screen.
  for (const href of [
    '/sadmin/employer',
    '/sadmin/employer/owner',
    '/sadmin/employer/manager',
  ]) {
    await expect(sidebar.locator(`a[href$="${href}"]`), href).toHaveCount(1);
  }

  // Two pending employers were seeded, one of each kind, plus whatever the
  // database already held - the badge has to count at least the seeded ones.
  const badge = sidebar.locator('li.nav-item', { hasText: 'Manage Employers' }).locator('.badge').first();
  expect(Number(await badge.textContent())).toBeGreaterThanOrEqual(2);

  await expectNoServerError(page);
});

test('opening a kind marks it as the current screen', async ({ page }) => {
  await page.goto('sadmin/employer/owner');

  const sidebar = page.locator('aside.main-sidebar');
  const link = sidebar.locator('a[href$="/sadmin/employer/owner"]');

  await expect(link).toBeVisible();
  await expect(link).toHaveClass(/active/);

  await expect(page.locator('.content-header h1')).toContainText('Owners');

  await expectNoServerError(page);
});

test('each kind lists only its own accounts', async ({ page }) => {
  /** @type {Array<[string, string, string[]]>} shown, and what must not be there */
  const cases = [
    ['owner', SEED.owner.company, [SEED.manager.company]],
    ['manager', SEED.manager.company, [SEED.owner.company]],
  ];

  for (const [slug, shown, hidden] of cases) {
    const response = await page.goto(`sadmin/employer/${slug}`);
    expect(response?.status(), `${slug} status`).toBe(200);

    const body = await page.content();
    expect(body, `${slug} shows its own row`).toContain(shown);

    for (const other of hidden) {
      expect(body, `${slug} hides ${other}`).not.toContain(other);
    }

    await expectNoServerError(page);
  }
});

test('All Employers still shows every kind, including pre-B4 rows', async ({ page }) => {
  await page.goto('sadmin/employer');

  const body = await page.content();

  for (const company of [
    SEED.owner.company,
    SEED.manager.company,
  ]) {
    expect(body, `All Employers shows ${company}`).toContain(company);
  }

  // The kind is named on each row, so an account that predates the split is
  // visible here rather than silently absent from all three lists.
  for (const label of ['Owner', 'Manager']) {
    expect(body, `All Employers names the ${label} kind`).toContain(label);
  }

  await expectNoServerError(page);
});

test('a pending employer is activated from its own list', async ({ page }) => {
  expect(scalar(`SELECT u_status FROM users WHERE u_id = ${ids.manager};`)).toBe('0');

  await page.goto('sadmin/employer/manager');
  await filterTable(page, SEED.manager.company);

  page.once('dialog', (d) => d.accept());
  await page.click(`a[href*="/employer/changestatus/${ids.manager}"]`);

  // Back on the same kind, not dropped into All Employers.
  await expect(page).toHaveURL(/\/sadmin\/employer\/manager$/);
  await expect(page.locator('.content-header h1')).toContainText('Managers');

  expect(scalar(`SELECT u_status FROM users WHERE u_id = ${ids.manager};`)).toBe('1');

  await expectNoServerError(page);
});

test('a pending applicant is activated from the applicant list', async ({ page }) => {
  expect(scalar(`SELECT u_status FROM users WHERE u_id = ${ids.applicant};`)).toBe('0');

  await page.goto('sadmin/applicant');
  await filterTable(page, SEED.applicant.email);

  page.once('dialog', (d) => d.accept());
  await page.click(`a[href*="/applicant/changestatus/${ids.applicant}"]`);

  await expect(page).toHaveURL(/\/sadmin\/applicant$/);

  expect(scalar(`SELECT u_status FROM users WHERE u_id = ${ids.applicant};`)).toBe('1');

  await expectNoServerError(page);
});
