// @ts-check
/**
 * The employer's All Jobs list on a phone.
 *
 * DataTables measured the columns and wrote the total width onto the table, so
 * a list wider than the screen was simply cut off at the right: the columns
 * past the fold were unreachable, and the search box beside them sat off the
 * screen. The fold control on each row could not help, because as far as the
 * plugin was concerned every column already fitted.
 *
 * A phone width is therefore its own case, and this is what it must hold to:
 * nothing reaches past the screen, and everything taken out of the row is in
 * the panel the row opens.
 */
const { test, expect } = require('@playwright/test');
const { query, scalar } = require('../helpers/db');
const { seedShiftFixture, removeShiftFixture, loginAsAgency, SHIFTS } = require('../helpers/front');

const PHONE = { width: 360, height: 780 };

/** The shift the applicant is put on, so the row has something to fold away. */
const BOOKED = SHIFTS[1].title;

test.use({ viewport: PHONE });

test.beforeEach(async ({ page }) => {
  const { applicantId } = seedShiftFixture();

  const pid = scalar(`SELECT p_id FROM post_job WHERE p_job_title = '${BOOKED}';`);
  const province = scalar('SELECT p_id FROM province WHERE p_status = 1 LIMIT 1;');

  query(`UPDATE users SET u_l_provice = ${province} WHERE u_id = ${applicantId};`);
  query(`UPDATE stu_saved_applied_jobs
            SET sj_status = 6, sj_is_approved = 1, sj_accept_date = NOW()
          WHERE p_id = ${pid};`);
  query(`UPDATE post_job SET p_approved = 3 WHERE p_id = ${pid};`);

  await loginAsAgency(page);
  await page.goto('employer/all_jobs');
  await page.waitForSelector('#joblist tbody tr');
});

test.afterAll(removeShiftFixture);

test('nothing on the screen reaches past its right edge', async ({ page }) => {
  const spilling = await page.evaluate(() => {
    const edge = document.documentElement.clientWidth;

    return [...document.querySelectorAll('body *')]
      .filter((el) => el.getBoundingClientRect().right > edge + 1)
      .map((el) => `${el.tagName}.${el.className}`)
      .slice(0, 5);
  });

  expect(spilling, 'elements past the right edge of the screen').toEqual([]);
});

test('the columns taken out of the row are in the panel it opens', async ({ page }) => {
  const row = page.locator('#joblist tbody tr', { hasText: BOOKED });

  // The row itself keeps the shift and its button; the rest folds away. The
  // folded cells stay in the markup, hidden, so they are counted rather than
  // read - matching on the row's text would find them either way.
  await expect(row.locator('td.dtr-control')).toHaveCount(1);

  const inTheRow = await row
    .locator('td')
    .evaluateAll((cells) => cells.filter((c) => getComputedStyle(c).display !== 'none').length);

  expect(inTheRow, 'columns still in the row on a phone').toBe(2);

  await row.locator('td.dtr-control').click();

  const panel = page.locator('#joblist tbody tr.child');

  await expect(panel).toContainText('Location');
  await expect(panel).toContainText('Shift Date');
  await expect(panel).toContainText('Pharmacist E2E');
  await expect(panel).toContainText('Licence No.: E2E-LIC');
});
