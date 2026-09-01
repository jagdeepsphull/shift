// @ts-check
/**
 * "Assigned To" on the employer's All Jobs list.
 *
 * A booking is one thing - `sj_is_approved = 1` - but it is written at two
 * different `sj_status` values: 1 when an applicant applied and was approved,
 * and 6 when the administrator placed them on the shift outright. This screen
 * used to ask for status 1 as well, so a shift booked the second way showed
 * Booked with nobody against it, which is exactly what an owner cannot explain.
 * Both are seeded below, and both must name the person.
 */
const { test, expect } = require('@playwright/test');
const { query, scalar } = require('../helpers/db');
const { seedShiftFixture, removeShiftFixture, loginAsAgency, SHIFTS } = require('../helpers/front');

/** SHIFTS[1] is the earliest date, so it heads the list; [2] follows it. */
const PLACED = SHIFTS[1].title; // booked by the administrator: sj_status 6
const APPLIED = SHIFTS[2].title; // applied for, then approved: sj_status 1
const UNBOOKED = SHIFTS[0].title;

const MESSAGE = 'Agreed on the phone, $65/hr.';

/** The applicant's licence, as the fixture writes it. */
const LICENCE = 'E2E-LIC';

/** Filled in by the fixture: the licence province the applicant is given. */
let licenceProvince = '';

const rowOf = (page, title) => page.locator('#joblist tbody tr', { hasText: title });

/**
 * Put the fixture's applicant on one of the fixture's shifts.
 *
 * @param {string} title  shift to book
 * @param {number} status the sj_status the booking is written at
 * @param {string} comment the administrator's message
 */
const book = (title, status, comment) => {
  const pid = scalar(`SELECT p_id FROM post_job WHERE p_job_title = '${title}';`);

  expect(pid, `${title} should have been seeded`).not.toBe('');

  query(`UPDATE stu_saved_applied_jobs
            SET sj_status = ${status}, sj_is_approved = 1,
                sj_admin_comment = '${comment}', sj_accept_date = NOW()
          WHERE p_id = ${pid};`);
  query(`UPDATE post_job SET p_approved = 3 WHERE p_id = ${pid};`);

  // The screen is only worth looking at once the booking is really there.
  // Said here, so a fixture that did not take cannot read as the page under
  // test getting it wrong.
  expect(
    query(`SELECT ssa.sj_status, ssa.sj_is_approved, u.u_fname
             FROM stu_saved_applied_jobs ssa
             JOIN users u ON u.u_id = ssa.u_id
            WHERE ssa.p_id = ${pid};`),
    `${title} should be booked at status ${status}`,
  ).toBe(`${status}\t1\tPharmacist`);
};

test.beforeEach(async ({ page }) => {
  const { applicantId } = seedShiftFixture();

  // The fixture leaves the licence province unset, and the cell under test
  // only prints the lines it has. Give the applicant a real one.
  const provinceId = scalar('SELECT p_id FROM province WHERE p_status = 1 LIMIT 1;');
  licenceProvince = scalar(`SELECT p_name FROM province WHERE p_id = ${provinceId};`);
  query(`UPDATE users SET u_l_provice = ${provinceId} WHERE u_id = ${applicantId};`);

  book(PLACED, 6, MESSAGE);
  book(APPLIED, 1, '');

  await loginAsAgency(page);
  await page.goto('employer/all_jobs');
});

test.afterAll(removeShiftFixture);

test('a booking made by the administrator names the applicant', async ({ page }) => {
  await expect(rowOf(page, PLACED)).toContainText('Pharmacist E2E');
  await expect(rowOf(page, PLACED)).toContainText('Booked');
});

test('so does one the applicant applied for', async ({ page }) => {
  await expect(rowOf(page, APPLIED)).toContainText('Pharmacist E2E');
});

test('a shift nobody is on says so', async ({ page }) => {
  const cell = rowOf(page, UNBOOKED).locator('td').nth(5);

  await expect(cell).toHaveText('-');
});

test('the shift details carry the booking and its message', async ({ page }) => {
  await rowOf(page, PLACED).locator('.view-btn').click();

  await expect(page.locator('#viewModal')).toBeVisible();
  await expect(page.locator('#modalAssigned')).toHaveValue('Pharmacist E2E');
  await expect(page.locator('#modalMessage')).toHaveValue(MESSAGE);
});

test('the applicant details are spelled out in the cell, not behind a button', async ({ page }) => {
  const cell = rowOf(page, PLACED).locator('td').nth(5);

  // Everything the old View button opened, now read straight off the row.
  await expect(cell).toContainText('Pharmacist E2E');
  await expect(cell).toContainText(`Licence No.: ${LICENCE}`);
  await expect(cell).toContainText(`Licence Province: ${licenceProvince}`);
  await expect(cell).toContainText('Shift Requested For:');
  await expect(cell.locator('.applicant-btn')).toHaveCount(0);
});

test('an unbooked shift is shown as unbooked in the shift details', async ({ page }) => {
  await rowOf(page, UNBOOKED).locator('.view-btn').click();

  await expect(page.locator('#viewModal')).toBeVisible();
  await expect(page.locator('#modalAssignedGroup')).toBeHidden();
  await expect(page.locator('#modalMessageGroup')).toBeHidden();
});
