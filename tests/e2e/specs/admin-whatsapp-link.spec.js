// @ts-check
/**
 * The WhatsApp icons beside the phone numbers on an application detail screen.
 *
 * The interesting part is the number, not the icon: WhatsApp resolves it as
 * given, so a link built from the bare ten digits the phone field holds would
 * open a chat with nobody. `whatsappNumber()` puts the configured country code
 * on the front, and these check what actually reaches the href.
 *
 * Three numbers are on the screen - the applicant, the store the shift is at,
 * and the account that store belongs to. The store's line is a counter phone,
 * so the owner's is the one that answers out of hours; both have to be there.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, expectNoServerError } = require('../helpers/admin');
const { scalar } = require('../helpers/db');

/** The country code AppSettings::$phoneCountryCode holds - Canada. */
const COUNTRY = '1';

/** The three parties, joined the way the screen resolves them. */
const JOINS =
  ' FROM stu_saved_applied_jobs s' +
  ' JOIN users a ON a.u_id = s.u_id' +
  ' JOIN post_job pj ON pj.p_id = s.p_id' +
  ' JOIN store st ON st.s_id = pj.p_store_id' +
  ' JOIN users o ON o.u_id = st.u_id';

/** Mirror of the helper, for what the href is expected to carry. */
function expectedNumber(phone) {
  const raw = String(phone || '').trim();
  const digits = raw.replace(/\D+/g, '');

  if (raw.startsWith('+')) return digits;
  if (digits.length === 10) return COUNTRY + digits;
  if (digits.startsWith(COUNTRY) && digits.length === COUNTRY.length + 10) return digits;

  return digits.length >= 11 ? digits : '';
}

let application;

test.beforeAll(() => {
  application = scalar(
    'SELECT s.sj_id' + JOINS +
      " WHERE a.u_phone <> '' AND st.s_phone <> '' AND o.u_phone <> ''" +
      ' ORDER BY s.sj_id DESC LIMIT 1;',
  );
});

test.beforeEach(async ({ page }) => {
  test.skip(!application, 'no application with all three phone numbers on file');
  await loginAsAdmin(page);
});

test('all three phone numbers carry a WhatsApp link with the country code on', async ({ page }) => {
  const applicant = scalar(`SELECT a.u_phone${JOINS} WHERE s.sj_id = ${application};`);
  const store = scalar(`SELECT st.s_phone${JOINS} WHERE s.sj_id = ${application};`);
  const owner = scalar(`SELECT o.u_phone${JOINS} WHERE s.sj_id = ${application};`);

  await page.goto(`sadmin/applications/view/${application}`);

  const links = page.locator('a[href*="web.whatsapp.com"]');
  await expect(links).toHaveCount(3);

  for (const [index, phone] of [applicant, store, owner].entries()) {
    await expect(links.nth(index)).toHaveAttribute(
      'href', `https://web.whatsapp.com/send?phone=${expectedNumber(phone)}`,
    );

    // The number itself is the link, not an icon beside it: the digits on file
    // are the anchor's own text.
    await expect(links.nth(index)).toContainText(String(phone));
  }

  // The bare ten digits would open a chat with nobody - the whole point.
  await expect(links.nth(0)).not.toHaveAttribute('href', `https://web.whatsapp.com/send?phone=${applicant}`);

  // Where each one sits, so a rearranged card cannot quietly drop one: the
  // applicant's is under their e-mail, the store's is its first line, and the
  // owner's is under theirs in the block below.
  const applicantCard = page.locator('.col-lg-4').nth(0);
  const employerCard = page.locator('.col-lg-4').nth(1);

  const lines = [
    applicantCard.locator('ul li').nth(1),
    employerCard.locator('ul').nth(0).locator('li').nth(0),
    employerCard.locator('ul').nth(1).locator('li').nth(1),
  ];

  for (const line of lines) {
    await expect(line.locator('a[href*="web.whatsapp.com"]')).toHaveCount(1);
    await expect(line.locator('a[href="#!"]'), 'the dead link is gone').toHaveCount(0);
  }

  await expectNoServerError(page);
});

test('the store card names the branch and the account it belongs to', async ({ page }) => {
  const storeName = scalar(`SELECT st.s_name${JOINS} WHERE s.sj_id = ${application};`);
  const storeNumber = scalar(`SELECT st.s_number${JOINS} WHERE s.sj_id = ${application};`);
  const ownerCompany = scalar(`SELECT o.u_comp_name${JOINS} WHERE s.sj_id = ${application};`);

  await page.goto(`sadmin/applications/view/${application}`);

  const employerCard = page.locator('.col-lg-4').nth(1);

  // The branch, not the posting account: for a chain those are different
  // addresses, and the applicant is being booked into the branch.
  await expect(employerCard).toContainText(storeName);
  if (storeNumber !== '') await expect(employerCard).toContainText(storeNumber);

  await expect(employerCard).toContainText('Store belongs to');
  await expect(employerCard).toContainText(ownerCompany);

  await expectNoServerError(page);
});

test('the icon opens WhatsApp in a new tab and leaves the page where it was', async ({ page, context }) => {
  await page.goto(`sadmin/applications/view/${application}`);

  const link = page.locator('a[href*="web.whatsapp.com"]').first();
  await expect(link).toHaveAttribute('target', '_blank');
  await expect(link).toHaveAttribute('rel', /noopener/);
  await expect(link.locator('i.fa-whatsapp')).toHaveCount(1);
  await expect(link.locator('i.fa-mobile-alt')).toHaveCount(1);

  // Clicking the digits, not the icon - the number is what the admin aims at.
  const digits = (await link.innerText()).trim();
  const here = page.url();
  const [opened] = await Promise.all([
    context.waitForEvent('page'),
    link.getByText(digits, { exact: true }).click().catch(() => link.click()),
  ]);

  expect(opened.url()).toContain('web.whatsapp.com/send?phone=');
  expect(page.url(), 'the admin stays on the application').toBe(here);

  await opened.close();
  await expectNoServerError(page);
});
