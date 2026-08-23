// @ts-check
/**
 * The Messages column on the pharmacist's Applied Shifts screen.
 *
 * Both buttons there are Bootstrap popovers, and the whole column is only as
 * alive as the JavaScript behind it: Bootstrap 4's tooltip - which popover is
 * built on - throws outright when `window.Popper` is missing, and the footer
 * used to name a Popper file that does not exist. The delegated popover was
 * registered on the line that threw, so the buttons rendered, took a click and
 * did nothing at all.
 *
 * These tests therefore check the mechanism (Popper present, no page error)
 * as well as the behaviour, because the behaviour has exactly one interesting
 * way of failing and it leaves no mark on the server.
 */
const { test, expect } = require('@playwright/test');
const { settle, expectNoServerError } = require('../helpers/admin');
const { seedShiftFixture, removeShiftFixture, loginAsApplicant } = require('../helpers/front');
const { query, scalar } = require('../helpers/db');

/**
 * The messages seeded onto the three applications.
 *
 * The text carries an apostrophe, an ampersand and a double quote on purpose:
 * the content travels to the browser inside a `data-content="..."` attribute,
 * so an unescaped quote would end the attribute early and truncate the message
 * - or break the markup around it. `<b>` is in there for the other half of the
 * same question: the popover is configured `html: false`, so it must arrive as
 * four visible characters and not as bold text.
 */
const APPLICANT_MESSAGE = `I'm free 9-5 & "on call" <b>weekends</b>`;
const AGENCY_MESSAGE = `Confirmed - please bring the store's key & sign in`;

/** SQL string literal: the client takes '' for a quote. */
const sqlText = (s) => `'${s.replace(/'/g, "''")}'`;

/**
 * Set the two message columns on one seeded application.
 *
 * `IN`, not `=`: a run interrupted between the fixture's delete and its insert
 * can leave two shifts under one title, and `=` would abort the whole hook with
 * "Subquery returns more than 1 row" rather than message both of them.
 */
function setMessages(title, applicantMessage, agencyMessage) {
  query(`
    UPDATE stu_saved_applied_jobs
       SET sj_applied_desc = ${sqlText(applicantMessage)},
           sj_admin_comment = ${sqlText(agencyMessage)}
     WHERE p_id IN (SELECT p_id FROM post_job WHERE p_job_title = '${title}');
  `);
}

// Per test, not once per file. The shared fixture inserts its applications with
// both message columns empty, and Playwright re-runs a file's `beforeAll` when
// it restarts the worker - which it does after any failure. A file-level seed
// therefore gets quietly wiped part way through a run, and the tests after it
// fail on an empty Messages column for a reason that has nothing to do with
// what they are checking.
test.beforeEach(() => {
  seedShiftFixture();

  // A: both messages. B: only the pharmacist's own. C: neither - the column is
  // meant to stay empty rather than offer a button onto nothing.
  setMessages('E2E-SHIFT-A', APPLICANT_MESSAGE, AGENCY_MESSAGE);
  setMessages('E2E-SHIFT-B', APPLICANT_MESSAGE, '');
  setMessages('E2E-SHIFT-C', '', '');
});

test.afterAll(() => {
  removeShiftFixture();
});

/**
 * Open Applied Shifts as the pharmacist, collecting anything the page threw.
 *
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<{errors: string[], missing: string[]}>}
 */
async function openAppliedShifts(page) {
  /** @type {string[]} */
  const errors = [];
  /** @type {string[]} */
  const missing = [];

  page.on('pageerror', (e) => {
    // One error on this page is older than anything here and belongs to the
    // theme: `onePageNav` reads `.attr('href').split('#')` off every link in
    // the header nav, and one of them carries no href. It is thrown from its
    // own ready handler, so it takes nothing else down with it - but it would
    // otherwise sit in this list for ever and hide the next real one.
    if ((e.stack || '').includes('jquery.nav.js')) return;

    errors.push(e.message);
  });

  page.on('response', (r) => {
    if (r.status() === 404) missing.push(r.url());
  });

  // The public login is raced: the page asks for a verification image twice
  // (once for the login tab, once for register) and the session keeps whichever
  // answered last, so the code read out of the session file is occasionally
  // already stale by the time it is posted. The login is then refused and this
  // screen redirects straight back to it - which shows up here as an empty
  // table and reads as if the buttons had gone missing. Signing in again gets a
  // fresh code, so try twice before believing it.
  for (let attempt = 1; attempt <= 2; attempt += 1) {
    await loginAsApplicant(page);
    await page.goto('applicant/applied_jobs');
    await settle(page);

    if (!page.url().includes('front/login')) break;
  }

  expect(page.url(), 'signed in and on Applied Shifts').toContain('applicant/applied_jobs');
  await expect(page.locator('#joblist tbody tr').first()).toBeVisible();

  return { errors, missing };
}

/** The row for one seeded shift. */
const rowFor = (page, title) => page.locator('#joblist tbody tr', { hasText: title });

test('the shifts screen loads its scripts and throws nothing', async ({ page }) => {
  const { errors, missing } = await openAppliedShifts(page);

  // The regression itself: a script tag pointing at a file that is not there.
  expect(missing, 'every script and stylesheet the page asks for exists').toEqual([]);

  // Popper is what Bootstrap's popover needs, and the only reason it was ever
  // absent is that its path was wrong.
  expect(await page.evaluate(() => typeof window.Popper), 'Popper.js is loaded').not.toBe(
    'undefined',
  );

  expect(errors, 'no script on the page threw').toEqual([]);
  await expectNoServerError(page);
});

test('My Message reveals the message the pharmacist applied with', async ({ page }) => {
  await openAppliedShifts(page);

  const row = rowFor(page, 'E2E-SHIFT-A');
  const button = row.getByRole('button', { name: 'My Message' });

  await expect(button).toBeVisible();
  await expect(page.locator('.popover'), 'nothing is open to begin with').toHaveCount(0);

  await button.click();

  const popover = page.locator('.popover');
  await expect(popover, 'a popover opens on the click').toBeVisible();

  // The exact text, quotes and all - not a truncation at the first quote, and
  // not `<b>` swallowed as markup.
  await expect(popover.locator('.popover-body')).toHaveText(APPLICANT_MESSAGE);
  await expect(popover.locator('b'), 'the message is text, not markup').toHaveCount(0);

  // And it is that row's message, taken from the database rather than assumed.
  const stored = scalar(`
    SELECT sj_applied_desc FROM stu_saved_applied_jobs
     WHERE p_id = (SELECT p_id FROM post_job WHERE p_job_title = 'E2E-SHIFT-A');
  `);
  expect(await popover.locator('.popover-body').innerText()).toBe(stored);
});

test('Agency Message opens in its place, and clicking away closes it', async ({ page }) => {
  await openAppliedShifts(page);

  const row = rowFor(page, 'E2E-SHIFT-A');

  await row.getByRole('button', { name: 'My Message' }).click();
  await expect(page.locator('.popover')).toBeVisible();

  // The second button replaces the first rather than joining it.
  await row.getByRole('button', { name: 'Agency Message' }).click();
  await expect(page.locator('.popover'), 'only one message is open at a time').toHaveCount(1);
  await expect(page.locator('.popover-body')).toHaveText(AGENCY_MESSAGE);

  // Clicking off the buttons puts it away.
  await page.locator('.dashboard-caption-header').click();
  await expect(page.locator('.popover')).toHaveCount(0);
});

test('a second row opens its own message, and a row with none offers no button', async ({
  page,
}) => {
  await openAppliedShifts(page);

  // B has the pharmacist's message but no reply, so it offers one button.
  const b = rowFor(page, 'E2E-SHIFT-B');
  await expect(b.getByRole('button', { name: 'My Message' })).toBeVisible();
  await expect(b.getByRole('button', { name: 'Agency Message' })).toHaveCount(0);

  await b.getByRole('button', { name: 'My Message' }).click();
  await expect(page.locator('.popover-body')).toHaveText(APPLICANT_MESSAGE);

  // C has neither, and an empty column beats a button that reveals nothing.
  const c = rowFor(page, 'E2E-SHIFT-C');
  await expect(c.getByRole('button', { name: 'My Message' })).toHaveCount(0);
  await expect(c.getByRole('button', { name: 'Agency Message' })).toHaveCount(0);
});
