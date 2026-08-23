// @ts-check
/**
 * The support number, on every side of the login.
 *
 * The card under the side menu used to end on Logout. The site's own number
 * now sits below it, linked into WhatsApp, and it has to be there for all
 * three kinds of account - an owner, one of their managers, and a pharmacist -
 * because the owner's screens and the pharmacist's are served by two separate
 * header files. A block added to one and forgotten in the other is exactly the
 * drift this suite exists to catch.
 *
 * The link is checked, not merely the digits: the number shown carries
 * brackets and dashes, and the one inside the href must be the bare
 * international form or the chat opens on nobody.
 */
const { test, expect } = require('@playwright/test');
const { settle, expectNoServerError } = require('../helpers/admin');
const { loginAsFrontUser } = require('../helpers/front');
const { query, scalar } = require('../helpers/db');

const PASSWORD = 'E2eTest@12345';
const PREFIX = 'e2e.support.';

const OWNER = { user: `${PREFIX}owner@example.com`, pass: PASSWORD };
const MANAGER = { user: `${PREFIX}manager@example.com`, pass: PASSWORD };
const APPLICANT = { user: `${PREFIX}pharmacist@example.com`, pass: PASSWORD };

/** As printed in the sidebar, and as it must read inside the WhatsApp link. */
const SHOWN = '+1 (905) 304-7303';
const DIALLED = '19053047303';

/** @type {{owner: number, manager: number, applicant: number}} */
let user;

function removeFixtures() {
  query(`
    DELETE FROM store WHERE u_id IN (
      SELECT u_id FROM (SELECT u_id FROM users WHERE u_userid LIKE '${PREFIX}%') x);
  `);
  query(`DELETE FROM users WHERE u_userid LIKE '${PREFIX}%';`);
}

/**
 * @param {{user: string, pass: string}} account
 * @param {{type: number, role?: number, parent?: number, storeId?: number, company: string}} shape
 * @returns {number} users.u_id
 */
function seedUser(account, shape) {
  query(`
    INSERT INTO users
      (u_usertype, u_usersubtype, u_emp_role, u_parent_id, u_store_id, u_userid, u_fname, u_lname,
       u_pass, u_comp_name, u_l_provice, u_licence_no, u_company_logo, u_photo, u_provice, u_city,
       u_address1, u_pincode, u_phone, u_email, u_terms, u_status, u_collartype,
       created, modified, u_login_attempt, u_login_attempt_dt, u_ipaddress, reset_token, token_expiry)
    VALUES
      (${shape.type}, 0, ${shape.role || 0}, ${shape.parent || 0}, ${shape.storeId || 0},
       '${account.user}', 'Support', 'E2E',
       MD5('${account.pass}'), '${shape.company}', 0, 'E2E-SUP', '', '',
       (SELECT c_province FROM city WHERE c_status = 1 ORDER BY c_id LIMIT 1),
       (SELECT c_id FROM city WHERE c_status = 1 ORDER BY c_id LIMIT 1),
       '1 Support Street', 'M5A 1A1', '4160000820', '${account.user}', 1, 1, 0,
       NOW(), NOW(), 0, NOW(), '127.0.0.1', '', '1970-01-01 00:00:00');
  `);

  return Number(scalar(`SELECT u_id FROM users WHERE u_userid = '${account.user}';`));
}

/** @returns {number} store.s_id */
function seedStore(ownerId, name) {
  query(`
    INSERT INTO store (u_id, s_name, s_number, s_province, s_city, s_address, s_pincode, s_phone, s_status)
    VALUES (${ownerId}, '${name}', '${name.replace(/[^0-9A-Za-z]/g, '')}',
            (SELECT c_province FROM city WHERE c_status = 1 ORDER BY c_id LIMIT 1),
            (SELECT c_id FROM city WHERE c_status = 1 ORDER BY c_id LIMIT 1),
            '1 Support Street', 'M5A 1A1', '4160000821', 1);
  `);

  return Number(scalar(`SELECT MAX(s_id) FROM store WHERE s_name = '${name}';`));
}

test.beforeAll(() => {
  removeFixtures();

  user = { owner: 0, manager: 0, applicant: 0 };
  user.owner = seedUser(OWNER, { type: 1, role: 1, company: 'E2E Support Group' });

  const branch = seedStore(user.owner, 'E2E Support Branch');

  user.manager = seedUser(MANAGER, {
    type: 1,
    role: 2,
    parent: user.owner,
    storeId: branch,
    company: 'E2E Support Branch',
  });
  user.applicant = seedUser(APPLICANT, { type: 2, company: 'E2E Support Pharmacy' });
});

test.afterAll(removeFixtures);

/**
 * The one link, asserted the same way whoever is signed in.
 *
 * @param {import('@playwright/test').Page} page
 */
async function expectSupportLink(page) {
  const support = page.locator('.ps-side .ps-support-link');

  await expect(support, 'the sidebar offers support').toHaveCount(1);
  await expect(support).toContainText(SHOWN);
  await expect(support).toHaveAttribute('href', `https://web.whatsapp.com/send?phone=${DIALLED}`);
  await expect(support).toHaveAttribute('target', '_blank');

  // Outside the collapsing panel, so a phone with the menu shut still shows it.
  await expect(page.locator('.ps-menu-panel .ps-support-link')).toHaveCount(0);

  await expectNoServerError(page);
}

test('an owner is given the support number under their menu', async ({ page }) => {
  await loginAsFrontUser(page, OWNER);
  await page.goto('employer/all_jobs');
  await settle(page);
  await expectSupportLink(page);
});

test('a manager is given it on the same screens', async ({ page }) => {
  await loginAsFrontUser(page, MANAGER);
  await page.goto('employer/stores');
  await settle(page);
  await expectSupportLink(page);
});

test('a pharmacist is given it on theirs', async ({ page }) => {
  await loginAsFrontUser(page, APPLICANT);
  await page.goto('applicant/applied_jobs');
  await settle(page);
  await expectSupportLink(page);
});

test('it is reachable on a phone, where the menu starts shut', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 780 });

  await loginAsFrontUser(page, APPLICANT);
  await page.goto('applicant/applied_jobs');
  await settle(page);

  await expect(page.locator('.ps-menu-panel'), 'the menu is collapsed').not.toBeVisible();
  await expect(page.locator('.ps-side .ps-support-link'), 'support is not').toBeVisible();
  await expectNoServerError(page);
});
