// @ts-check
/**
 * The contact details on an application detail screen, and the numbers behind
 * them.
 *
 * Four parties reach that page: the applicant, the store the shift is at,
 * whoever manages that store, and the account the store belongs to. Three of
 * those numbers are somebody's own handset and carry a WhatsApp link. The
 * store's is not - it is the counter landline, so it is offered to dial and
 * nothing more, and the manager beneath it is who to message instead.
 *
 * The interesting part of a WhatsApp link is the number, not the icon:
 * WhatsApp resolves it as given, so a link built from the bare ten digits the
 * phone field holds would open a chat with nobody. `whatsappNumber()` puts the
 * configured country code on the front, and these check what actually reaches
 * the href.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, expectNoServerError } = require('../helpers/admin');
const { query, scalar } = require('../helpers/db');

/** The country code AppSettings::$phoneCountryCode holds - Canada. */
const COUNTRY = '1';

/** The parties, joined the way the screen resolves them. */
const JOINS =
  ' FROM stu_saved_applied_jobs s' +
  ' JOIN users a ON a.u_id = s.u_id' +
  ' JOIN post_job pj ON pj.p_id = s.p_id' +
  ' JOIN store st ON st.s_id = pj.p_store_id' +
  ' JOIN users o ON o.u_id = st.u_id';

/** The manager accounts a store can have, as `storeManagers()` counts them. */
const MANAGER_OF =
  ' FROM users m WHERE m.u_store_id = st.s_id AND m.u_usertype = 1 AND m.u_emp_role = 2';

const PREFIX = 'e2e.appview.';

/** The manager this spec puts on the store, so the filled case is exercised. */
const MANAGER = {
  userid: `${PREFIX}manager@example.com`,
  fname: 'Ravinder',
  lname: 'E2E-Manager',
  phone: '4160000812',
};

const MANAGER_NAME = `${MANAGER.fname} ${MANAGER.lname}`;

/** An application whose store this spec gives a manager. */
let application;

/** An application whose store has none, for the other half of the block. */
let managerless;

function removeFixtures() {
  query(`DELETE FROM users WHERE u_userid LIKE '${PREFIX}%';`);
}

/** Put a manager account on the store the chosen application's shift is at. */
function seedManager() {
  query(`
    INSERT INTO users
      (u_usertype, u_usersubtype, u_emp_role, u_parent_id, u_store_id, u_userid, u_fname, u_lname,
       u_pass, u_comp_name, u_l_provice, u_licence_no, u_company_logo, u_photo, u_provice, u_city,
       u_address1, u_pincode, u_phone, u_email, u_terms, u_status, u_collartype,
       created, modified, u_login_attempt, u_login_attempt_dt, u_ipaddress, reset_token, token_expiry)
    SELECT
      1, 0, 2, st.u_id, st.s_id, '${MANAGER.userid}', '${MANAGER.fname}', '${MANAGER.lname}',
      MD5('E2eTest@12345'), st.s_name, 0, 'E2E-APPVIEW', '', '',
      st.s_province, st.s_city, st.s_address, st.s_pincode, '${MANAGER.phone}',
      '${MANAGER.userid}', 1, 1, 0,
      NOW(), NOW(), 0, NOW(), '127.0.0.1', '', '1970-01-01 00:00:00'
      FROM stu_saved_applied_jobs s
      JOIN post_job pj ON pj.p_id = s.p_id
      JOIN store st ON st.s_id = pj.p_store_id
     WHERE s.sj_id = ${application};
  `);
}

/** Mirror of the helper, for what a WhatsApp href is expected to carry. */
function expectedNumber(phone) {
  const raw = String(phone || '').trim();
  const digits = raw.replace(/\D+/g, '');

  if (raw.startsWith('+')) return digits;
  if (digits.length === 10) return COUNTRY + digits;
  if (digits.startsWith(COUNTRY) && digits.length === COUNTRY.length + 10) return digits;

  return digits.length >= 11 ? digits : '';
}

/** Mirror of safeUrl(), for a map link pasted without a scheme in front of it. */
function expectedUrl(url) {
  const raw = String(url || '').trim();

  if (raw === '') return '';

  return /^[a-z][a-z0-9+.-]*:/i.test(raw) ? raw : 'https://' + raw.replace(/^\/+/, '');
}

test.beforeAll(() => {
  removeFixtures();

  application = scalar(
    'SELECT s.sj_id' + JOINS +
      " WHERE a.u_phone <> '' AND st.s_phone <> '' AND o.u_phone <> ''" +
      ' ORDER BY s.sj_id DESC LIMIT 1;',
  );

  if (application) seedManager();

  // A different branch, left as it was found. Read after the seeding above, so
  // the store this spec just gave a manager cannot be the one chosen.
  managerless = scalar(
    'SELECT s.sj_id' + JOINS +
      ` WHERE NOT EXISTS (SELECT 1${MANAGER_OF})` +
      ' ORDER BY s.sj_id DESC LIMIT 1;',
  );
});

test.afterAll(removeFixtures);

test.beforeEach(async ({ page }) => {
  test.skip(!application, 'no application with all three phone numbers on file');
  await loginAsAdmin(page);
});

test('every mobile number carries a WhatsApp link with the country code on', async ({ page }) => {
  const applicant = scalar(`SELECT a.u_phone${JOINS} WHERE s.sj_id = ${application};`);
  const owner = scalar(`SELECT o.u_phone${JOINS} WHERE s.sj_id = ${application};`);

  await page.goto(`sadmin/applications/view/${application}`);

  const applicantCard = page.locator('.col-lg-4').nth(0);
  const employerCard = page.locator('.col-lg-4').nth(1);

  // Three, not four: the store's landline is deliberately not among them.
  await expect(page.locator('a[href*="web.whatsapp.com"]')).toHaveCount(3);

  // Where each one sits, so a rearranged card cannot quietly drop one: the
  // applicant's is under their e-mail, the manager's under their name, and the
  // owner's in the block below that.
  const lines = [
    { line: applicantCard.locator('ul li').nth(1), phone: applicant },
    { line: employerCard.locator('ul').nth(1).locator('li').nth(0), phone: MANAGER.phone },
    { line: employerCard.locator('ul').last().locator('li').nth(1), phone: owner },
  ];

  for (const { line, phone } of lines) {
    const link = line.locator('a[href*="web.whatsapp.com"]');

    await expect(link).toHaveCount(1);
    await expect(link).toHaveAttribute(
      'href', `https://web.whatsapp.com/send?phone=${expectedNumber(phone)}`,
    );

    // The number itself is the link, not an icon beside it: the digits on file
    // are the anchor's own text.
    await expect(link).toContainText(String(phone));
    await expect(line.locator('a[href="#!"]'), 'the dead link is gone').toHaveCount(0);
  }

  // The bare ten digits would open a chat with nobody - the whole point.
  await expect(
    page.locator('a[href*="web.whatsapp.com"]').nth(0),
  ).not.toHaveAttribute('href', `https://web.whatsapp.com/send?phone=${applicant}`);

  await expectNoServerError(page);
});

test("the store's landline is offered to dial, not to message", async ({ page }) => {
  const landline = scalar(`SELECT st.s_phone${JOINS} WHERE s.sj_id = ${application};`);

  await page.goto(`sadmin/applications/view/${application}`);

  // The first list in the employer card is the branch's own: its number, then
  // its address.
  const branchList = page.locator('.col-lg-4').nth(1).locator('ul').nth(0);
  const line = branchList.locator('li').nth(0);

  await expect(line).toContainText(landline);
  await expect(line.locator('a')).toHaveAttribute('href', `tel:${landline.replace(/[^0-9+]/g, '')}`);

  // A counter phone wearing a WhatsApp mark invites a message into a chat
  // nobody reads, which is the reason this number is treated apart.
  await expect(branchList.locator('a[href*="web.whatsapp.com"]')).toHaveCount(0);
  await expect(branchList.locator('i.fa-whatsapp')).toHaveCount(0);

  await expectNoServerError(page);
});

test('the store card names the branch, its manager, and the account it belongs to', async ({ page }) => {
  const storeName = scalar(`SELECT st.s_name${JOINS} WHERE s.sj_id = ${application};`);
  const storeNumber = scalar(`SELECT st.s_number${JOINS} WHERE s.sj_id = ${application};`);
  const ownerCompany = scalar(`SELECT o.u_comp_name${JOINS} WHERE s.sj_id = ${application};`);

  await page.goto(`sadmin/applications/view/${application}`);

  const employerCard = page.locator('.col-lg-4').nth(1);

  // The branch, not the posting account: for a chain those are different
  // addresses, and the applicant is being booked into the branch.
  await expect(employerCard).toContainText(storeName);
  if (storeNumber !== '') await expect(employerCard).toContainText(storeNumber);

  // Who to speak to at that branch - the point of the block, since neither the
  // counter phone nor the owner's head office is the person arranging this.
  await expect(employerCard).toContainText('Store Manager');
  await expect(employerCard).toContainText(MANAGER_NAME);

  await expect(employerCard).toContainText('Store belongs to');
  await expect(employerCard).toContainText(ownerCompany);

  await expectNoServerError(page);
});

test('a store with no manager account says so rather than leaving a gap', async ({ page }) => {
  test.skip(!managerless, 'every store in this database has a manager');

  await page.goto(`sadmin/applications/view/${managerless}`);

  const employerCard = page.locator('.col-lg-4').nth(1);

  await expect(employerCard).toContainText('Store Manager');
  await expect(employerCard).toContainText('No manager account on this store.');

  // Nothing left behind pointing at a manager who is not there.
  await expect(employerCard.locator('ul')).toHaveCount(2);

  await expectNoServerError(page);
});

test('both the applicant type and the type the shift asked for are on the screen', async ({ page }) => {
  const applicantType = scalar(
    'SELECT sf.sf_name' + JOINS +
      ' JOIN shift_for sf ON sf.sf_id = a.u_usersubtype AND sf.sf_status = 1' +
      ` WHERE s.sj_id = ${application};`,
  );
  const shiftFor = scalar(
    'SELECT sf.sf_name' + JOINS +
      ' JOIN shift_for sf ON sf.sf_id = pj.p_shift_for AND sf.sf_status = 1' +
      ` WHERE s.sj_id = ${application};`,
  );

  await page.goto(`sadmin/applications/view/${application}`);

  // Two different questions on two different cards: who this person is, and
  // what the employer asked for. They agree in the ordinary case, and the
  // screen has to be able to show when they do not.
  const applicantCard = page.locator('.col-lg-4').nth(0);
  const shiftCard = page.locator('.col-lg-3').first();

  await expect(applicantCard).toContainText('Applicant Type');
  await expect(applicantCard).toContainText(applicantType !== '' ? applicantType : '-');

  await expect(shiftCard).toContainText('Shift Information');
  await expect(shiftCard).toContainText('Shift Requested For');
  await expect(shiftCard).toContainText(shiftFor !== '' ? shiftFor : '-');

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

test('every address opens on Google Maps in a tab of its own', async ({ page }) => {
  const applicantAddress = scalar(`SELECT a.u_address1${JOINS} WHERE s.sj_id = ${application};`);
  const storeAddress = scalar(`SELECT st.s_address${JOINS} WHERE s.sj_id = ${application};`);
  const storeMap = scalar(`SELECT st.s_map_url${JOINS} WHERE s.sj_id = ${application};`);
  const ownerAddress = scalar(`SELECT o.u_address1${JOINS} WHERE s.sj_id = ${application};`);

  await page.goto(`sadmin/applications/view/${application}`);

  const applicantCard = page.locator('.col-lg-4').nth(0);
  const employerCard = page.locator('.col-lg-4').nth(1);

  // The address closes every list it belongs to: the applicant's, the branch's,
  // and the account the branch belongs to.
  const lines = [
    { link: applicantCard.locator('ul li').last().locator('a'), address: applicantAddress },
    { link: employerCard.locator('ul').nth(0).locator('li').last().locator('a'), address: storeAddress },
    { link: employerCard.locator('ul').last().locator('li').last().locator('a'), address: ownerAddress },
  ];

  for (const { link } of lines) {
    await expect(link).toHaveCount(1);
    await expect(link.locator('i.fa-map-marker-alt')).toHaveCount(1);

    // The back office reads this beside the shift it is checking, so a map has
    // to arrive somewhere else.
    await expect(link).toHaveAttribute('target', '_blank');
    await expect(link).toHaveAttribute('rel', /noopener/);
  }

  // Every one of these was `href="#!"` - a link that stayed put and did
  // nothing - so what the href carries is the whole of the point.
  for (const { link, address } of [lines[0], lines[2]]) {
    const href = await link.getAttribute('href');

    expect(href).toContain('google.com/maps/search/?api=1&query=');
    expect(decodeURIComponent(href)).toContain(address);
  }

  const storeHref = await lines[1].link.getAttribute('href');

  if (expectedUrl(storeMap) !== '') {
    // A pin somebody chose beats a search for a street address, which will not
    // reliably find one unit of four in a plaza.
    expect(storeHref).toBe(expectedUrl(storeMap));
  } else {
    expect(storeHref).toContain('google.com/maps/search/?api=1&query=');
    expect(decodeURIComponent(storeHref)).toContain(storeAddress);
  }

  await expect(page.locator('a[href="#!"]')).toHaveCount(0);

  await expectNoServerError(page);
});
