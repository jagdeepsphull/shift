// @ts-check
/**
 * The pharmacist's Documents page.
 *
 * A link in the side menu opens a page of download buttons - one so far, the
 * agency's invoice template. The file lives in uploads/documents/ and the
 * button fetches it from there, so the test takes the download all the way
 * through: a button whose href points at a file that is not on disk is exactly
 * the failure a look at the markup alone would pass.
 */
const fs = require('fs');
const path = require('path');
const { test, expect } = require('@playwright/test');
const { settle, expectNoServerError } = require('../helpers/admin');
const { loginAsFrontUser } = require('../helpers/front');
const { query } = require('../helpers/db');

const PREFIX = 'e2e.documents.';
const APPLICANT = { user: `${PREFIX}pharmacist@example.com`, pass: 'E2eTest@12345' };

const INVOICE = 'Invoice_template_PAS_v5.xls';
const ON_DISK = path.join(__dirname, '..', '..', '..', 'uploads', 'documents', INVOICE);

function removeFixtures() {
  query(`DELETE FROM users WHERE u_userid LIKE '${PREFIX}%';`);
}

test.beforeAll(() => {
  removeFixtures();

  query(`
    INSERT INTO users
      (u_usertype, u_usersubtype, u_emp_role, u_parent_id, u_store_id, u_userid, u_fname, u_lname,
       u_pass, u_comp_name, u_l_provice, u_licence_no, u_company_logo, u_photo, u_provice, u_city,
       u_address1, u_pincode, u_phone, u_email, u_terms, u_status, u_collartype,
       created, modified, u_login_attempt, u_login_attempt_dt, u_ipaddress, reset_token, token_expiry)
    VALUES
      (2, 0, 0, 0, 0, '${APPLICANT.user}', 'Documents', 'E2E',
       MD5('${APPLICANT.pass}'), 'E2E Documents Pharmacy', 0, 'E2E-DOC', '', '',
       (SELECT c_province FROM city WHERE c_status = 1 ORDER BY c_id LIMIT 1),
       (SELECT c_id FROM city WHERE c_status = 1 ORDER BY c_id LIMIT 1),
       '1 Documents Street', 'M5A 1A1', '4160000830', '${APPLICANT.user}', 1, 1, 0,
       NOW(), NOW(), 0, NOW(), '127.0.0.1', '', '1970-01-01 00:00:00');
  `);
});

test.afterAll(removeFixtures);

test('the side menu links to Documents, which offers the invoice for download', async ({ page }) => {
  await loginAsFrontUser(page, APPLICANT);
  await page.goto('applicant/applied_jobs');
  await settle(page);

  const link = page.locator('.ps-menu-panel a', { hasText: 'Documents' });
  await expect(link, 'the menu has a Documents link').toHaveCount(1);
  await expect(link).toHaveAttribute('href', /\/applicant\/documents$/);

  await page.goto('applicant/documents');
  await settle(page);
  await expectNoServerError(page);

  await expect(page.locator('.ps-menu-panel li.active a')).toHaveText(/Documents/);

  const button = page.locator('.dashboard-body a.btn', { hasText: 'Invoice' });
  await expect(button, 'an Invoice button').toHaveCount(1);
  await expect(button).toHaveAttribute('href', new RegExp(`/uploads/documents/${INVOICE.replace(/\./g, '\\.')}$`));

  const [download] = await Promise.all([page.waitForEvent('download'), button.click()]);
  expect(download.suggestedFilename()).toBe(INVOICE);

  const saved = await download.path();
  expect(fs.statSync(saved).size, 'the file served is the one in uploads/documents').toBe(fs.statSync(ON_DISK).size);
});

test('the page is not open to someone signed out', async ({ page }) => {
  await page.goto('applicant/documents');
  await expect(page).toHaveURL(/\/front\/login/);
});
