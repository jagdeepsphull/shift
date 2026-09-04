// @ts-check
/**
 * The Owners list names its first column for what that column holds.
 *
 * An owner is a group, not a shop: what they own are the rows in the stores
 * list, each with its own name and number, and the `u_licence_no` on the owner
 * record is the single store number the account was signed up with - one shop's
 * number standing in for a chain of them, or blank. So Owners reads Group Name
 * and carries no store number, and the two lists that are still per shop -
 * All Employers, and Managers, who run one - are untouched.
 *
 * Three lists share app/Views/admin/employer/index.php, and DataTables throws
 * if the head, the body and the foot disagree about how many columns there are,
 * so every one of them is loaded here and checked for a clean render.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, settle, expectNoServerError } = require('../helpers/admin');
const { query, scalar } = require('../helpers/db');

const OWNER = { user: 'e2e.groupcol.owner@example.com', company: 'E2E Group Column Owner' };
const MANAGER = { user: 'e2e.groupcol.manager@example.com', company: 'E2E Group Column Store' };
const STORE_NUMBER = 'E2E-GC-4471';

function removeAccounts() {
  query(`DELETE FROM users WHERE u_userid IN ('${OWNER.user}', '${MANAGER.user}');`);
}

/** One of each kind, both carrying a store number, so its absence is a choice. */
function seedAccount(account, empRole) {
  query(`
    INSERT INTO users
      (u_usertype, u_usersubtype, u_emp_role, u_userid, u_fname, u_lname, u_pass,
       u_comp_name, u_l_provice, u_licence_no, u_company_logo, u_photo, u_provice,
       u_city, u_address1, u_pincode, u_phone, u_email, u_terms, u_status,
       u_collartype, created, modified, u_login_attempt, u_login_attempt_dt,
       u_ipaddress, reset_token, token_expiry)
    VALUES
      (1, 0, ${empRole}, '${account.user}', 'Group', 'Column', MD5('E2eTest@12345'),
       '${account.company}', 0, '${STORE_NUMBER}', '', '', 0,
       0, '', '', '4160000000', '${account.user}', 1, 1,
       0, NOW(), NOW(), 0, NOW(),
       '127.0.0.1', '', '1970-01-01 00:00:00');
  `);
}

test.beforeAll(() => {
  removeAccounts();
  seedAccount(OWNER, 1);
  seedAccount(MANAGER, 2);
});

test.afterAll(removeAccounts);

test.beforeEach(async ({ page }) => {
  await loginAsAdmin(page);
});

/** The headings the table is showing, trimmed. */
async function headings(page) {
  const cells = await page.locator('#example1 thead th').allInnerTexts();

  return cells.map((t) => t.trim());
}

test('Owners reads Group Name and leaves the store number off', async ({ page }) => {
  await page.goto('sadmin/employer/owner');
  await settle(page);

  const head = await headings(page);

  expect(head).toContain('Group Name');
  expect(head, 'the group is not a shop, so it has no store name').not.toContain('Store Name');
  expect(head, 'nor one store number standing for the whole chain').not.toContain('Store No.');

  // The premise: this owner does carry a number, and it is still not shown.
  const seeded = Number(scalar(`SELECT COUNT(*) FROM users WHERE u_userid = '${OWNER.user}' AND u_licence_no = '${STORE_NUMBER}';`));
  expect(seeded, 'the fixture owner carries a store number').toBe(1);

  await page.locator('#example1_filter input').fill(OWNER.company);
  await expect(page.locator('#example1 tbody tr').first()).toContainText(OWNER.company);
  await expect(page.locator('#example1 tbody')).not.toContainText(STORE_NUMBER);

  // The head, the body and the foot have to agree on the count, or DataTables
  // throws over it and the list never draws.
  const columns = head.length;
  await expect(page.locator('#example1 tfoot th')).toHaveCount(columns);
  await expect(page.locator('#example1 tbody tr').first().locator('td')).toHaveCount(columns);

  await expectNoServerError(page);
});

test('the lists that are still per shop keep both columns', async ({ page }) => {
  for (const [screen, company] of [
    ['sadmin/employer', OWNER.company],
    ['sadmin/employer/manager', MANAGER.company],
  ]) {
    await page.goto(screen);
    await settle(page);

    const head = await headings(page);

    expect(head, `${screen} keeps Store Name`).toContain('Store Name');
    expect(head, `${screen} keeps Store No.`).toContain('Store No.');
    expect(head, `${screen} is not the owners list`).not.toContain('Group Name');

    await page.locator('#example1_filter input').fill(company);
    await expect(page.locator('#example1 tbody')).toContainText(STORE_NUMBER);

    await expect(page.locator('#example1 tfoot th')).toHaveCount(head.length);

    await expectNoServerError(page);
  }
});

test('sorting still runs on the name column, whatever it is called', async ({ page }) => {
  await page.goto('sadmin/employer/owner');
  await settle(page);

  // `data-order-col="1"` is the name column on both lists - dropping the store
  // number moves what follows it, not the name itself.
  await expect(page.locator('#example1')).toHaveAttribute('data-order-col', '1');

  // Scoped to the wrapper, not to the table: `scrollX` is on for every admin
  // list, and DataTables then puts the header an admin actually clicks in a
  // cloned table of its own above the scrolling body.
  const sorted = page.locator('#example1_wrapper th.sorting_asc');
  await expect(sorted).toHaveCount(1);
  await expect(sorted).toHaveText('Group Name');

  await expectNoServerError(page);
});
