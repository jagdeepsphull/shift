// @ts-check
/**
 * Every list of shifts is ordered by the shift date, soonest first - except the
 * admin's own list, which reads latest first (the last test here).
 *
 * The seeded shifts (helpers/front.js) are inserted in an order that is neither
 * chronological nor the order their `dd-mm-yyyy` text sorts in, so a list that
 * falls back to the record number or to a string comparison fails here.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, settle, expectNoServerError } = require('../helpers/admin');
const {
  EXPECTED,
  seedShiftFixture,
  removeShiftFixture,
  loginAsAgency,
  loginAsApplicant,
} = require('../helpers/front');
const { query, scalar } = require('../helpers/db');

test.beforeAll(() => {
  seedShiftFixture();
});

test.afterAll(() => {
  removeShiftFixture();
});

/**
 * The seeded shift titles, in the order they appear down the page.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} selector
 */
async function seededOrder(page, selector) {
  const text = await page.locator(selector).allTextContents();

  return text
    .map((t) => (t.match(/E2E-SHIFT-[ABC]/) || [])[0])
    .filter((t) => t !== undefined);
}

test('public home page lists shifts soonest first', async ({ page }) => {
  // The home page renders `.wz-job` cards in the order the server produced;
  // the tabs and "Load More" only ever hide cards, so every card is in the
  // DOM to be read even before "Load More" is pressed.
  await page.goto('');
  await settle(page);

  expect(await seededOrder(page, '#wz-jobs .wz-job .wz-job-title')).toEqual(EXPECTED);

  // And the whole list, not just the seeded rows: walking down the page, the
  // shift dates never go backwards, and every unreadable date is at the end.
  // (Cards themselves carry no ISO date, so look each title's date up once.)
  const titles = (await page.locator('#wz-jobs .wz-job .wz-job-title').allTextContents()).map((t) =>
    t.trim(),
  );

  const dateOf = new Map(
    query(`
      SELECT p_job_title, COALESCE(p_date_start, '9999-12-31') FROM post_job
       WHERE p_status = 1 AND p_approved = 1;
    `)
      .split('\n')
      .filter((line) => line !== '')
      .map((line) => line.split('\t'))
      .map(([title, date]) => [title, date]),
  );

  const dates = titles.map((t) => dateOf.get(t) ?? '');

  expect(dates.length).toBeGreaterThan(1);
  expect(dates).not.toContain(''); // every card matched a database row
  expect(dates, 'shift dates ascend down the page').toEqual([...dates].sort());
  await expectNoServerError(page);
});

test('related shifts on a shift detail page are soonest first', async ({ page }) => {
  const pid = scalar("SELECT p_id FROM post_job WHERE p_job_title = 'E2E-SHIFT-A';");

  await page.goto(`front/job_detail/${pid}`);
  await settle(page);

  // The carousel clones slides, so read the source items only.
  expect(await seededOrder(page, '#rel-jobs .item:not(.cloned) h2')).toEqual(EXPECTED);
  await expectNoServerError(page);
});

test('agency dashboard lists recent shifts soonest first, not by record number', async ({ page }) => {
  await loginAsAgency(page);
  await page.goto('employer/dashboard');
  await settle(page);

  expect(await seededOrder(page, '.dashboard-gravity-list li h4')).toEqual(EXPECTED);
  await expectNoServerError(page);
});

test('agency shift list is soonest first and sorts its date column chronologically', async ({ page }) => {
  await loginAsAgency(page);
  await page.goto('employer/all_jobs');
  await settle(page);

  expect(await seededOrder(page, '#joblist tbody tr td:nth-child(2)')).toEqual(EXPECTED);

  const dates = await page
    .locator('#joblist tbody tr td:nth-child(5)')
    .evaluateAll((cells) => cells.map((c) => c.getAttribute('data-order')));

  expect(dates.length).toBeGreaterThan(1);
  expect(dates, 'shift dates ascend down the page').toEqual([...dates].sort());
  await expectNoServerError(page);
});

test('pharmacist applied shifts are soonest first, not by application number', async ({ page }) => {
  await loginAsApplicant(page);
  await page.goto('applicant/applied_jobs');
  await settle(page);

  expect(await seededOrder(page, '#joblist tbody tr td:nth-child(2)')).toEqual(EXPECTED);

  const dates = await page
    .locator('#joblist tbody tr td:nth-child(4)')
    .evaluateAll((cells) => cells.map((c) => c.getAttribute('data-order')));

  expect(dates.length).toBeGreaterThan(1);
  expect(dates, 'shift dates ascend down the page').toEqual([...dates].sort());
  await expectNoServerError(page);
});

// The admin list is the one exception to the rule this file otherwise checks:
// it reads latest first. An unreadable date still belongs at the end of it.
test('admin shift list is latest first and puts an unreadable date last', async ({ page }) => {
  await loginAsAdmin(page);
  await page.goto('sadmin/postjobs');
  await settle(page);

  const first = await page
    .locator('#example1 tbody tr td:nth-child(6)')
    .evaluateAll((cells) => cells.map((c) => c.getAttribute('data-order')));

  expect(first.length).toBeGreaterThan(1);
  expect(first, 'shift dates descend down the page').toEqual([...first].sort().reverse());

  // Last page of the list - where a shift with no usable date belongs.
  const last = page.locator('#example1_paginate .paginate_button:not(.next):not(.previous)').last();
  await last.click();
  await settle(page);

  const undated = Number(scalar('SELECT COUNT(*) FROM post_job WHERE p_date_start IS NULL;'));
  test.skip(undated === 0, 'every shift has a readable date');

  const order = await page
    .locator('#example1 tbody tr td:nth-child(6)')
    .evaluateAll((cells) => cells.map((c) => c.getAttribute('data-order')));

  expect(order.at(-1), 'a shift with no readable date sorts last, not first').toBe('0000-01-01');
});

test('the candidate list keeps its name column and does not borrow the shift sort', async ({ page }) => {
  await loginAsAgency(page);

  const pid = scalar("SELECT p_id FROM post_job WHERE p_job_title = 'E2E-SHIFT-A';");
  await page.goto(`employer/applied_applicants/${pid}`);
  await settle(page);

  // It used to share the shift list's table id, which hid column 0 - the
  // candidate's name - and ordered the table by Status.
  const header = page.locator('#candidatelist thead th').first();
  await expect(header, 'the candidate name column is visible').toBeVisible();
  await expect(header).toHaveText('Candidate');

  await expectNoServerError(page);
});
