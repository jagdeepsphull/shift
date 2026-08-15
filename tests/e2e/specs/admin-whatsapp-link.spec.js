// @ts-check
/**
 * The WhatsApp icons beside the phone numbers on an application detail screen.
 *
 * The interesting part is the number, not the icon: WhatsApp resolves it as
 * given, so a link built from the bare ten digits the phone field holds would
 * open a chat with nobody. `whatsappNumber()` puts the configured country code
 * on the front, and these check what actually reaches the href.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, expectNoServerError } = require('../helpers/admin');
const { scalar } = require('../helpers/db');

/** The country code AppSettings::$phoneCountryCode holds - Canada. */
const COUNTRY = '1';

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
    'SELECT s.sj_id FROM stu_saved_applied_jobs s' +
      ' JOIN users a ON a.u_id = s.u_id JOIN users e ON e.u_id = s.agency_id' +
      " WHERE a.u_phone <> '' AND e.u_phone <> '' ORDER BY s.sj_id DESC LIMIT 1;",
  );
});

test.beforeEach(async ({ page }) => {
  test.skip(!application, 'no application with both phone numbers on file');
  await loginAsAdmin(page);
});

test('both phone numbers carry a WhatsApp link with the country code on', async ({ page }) => {
  const applicant = scalar(
    `SELECT u.u_phone FROM stu_saved_applied_jobs s JOIN users u ON u.u_id = s.u_id WHERE s.sj_id = ${application};`,
  );
  const employer = scalar(
    `SELECT u.u_phone FROM stu_saved_applied_jobs s JOIN users u ON u.u_id = s.agency_id WHERE s.sj_id = ${application};`,
  );

  await page.goto(`sadmin/applications/view/${application}`);

  const links = page.locator('a[href*="web.whatsapp.com"]');
  await expect(links).toHaveCount(2);

  await expect(links.nth(0)).toHaveAttribute(
    'href', `https://web.whatsapp.com/send?phone=${expectedNumber(applicant)}`,
  );
  await expect(links.nth(1)).toHaveAttribute(
    'href', `https://web.whatsapp.com/send?phone=${expectedNumber(employer)}`,
  );

  // The bare ten digits would open a chat with nobody - the whole point.
  await expect(links.nth(0)).not.toHaveAttribute('href', `https://web.whatsapp.com/send?phone=${applicant}`);

  // The number itself is the link, not an icon beside it: the digits on file
  // are the anchor's own text, and no dead "#!" phone link is left over.
  await expect(links.nth(0)).toContainText(String(applicant));
  await expect(links.nth(1)).toContainText(String(employer));

  for (const card of [0, 1]) {
    const phoneLine = page.locator('.col-lg-4').nth(card).locator('ul li').nth(1);
    await expect(phoneLine.locator('a[href*="web.whatsapp.com"]')).toHaveCount(1);
    await expect(phoneLine.locator('a[href="#!"]'), 'the dead link is gone').toHaveCount(0);
  }

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
