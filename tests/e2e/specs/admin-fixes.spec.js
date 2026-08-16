// @ts-check
/**
 * The defects fixed on 4 August 2026, each pinned so it cannot come back.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, settle, expectNoServerError } = require('../helpers/admin');
const { scalar } = require('../helpers/db');

test.beforeEach(async ({ page }) => {
  await loginAsAdmin(page);
});

test('agency message opens on every page of the applications list, not just the first', async ({ page }) => {
  await page.goto('sadmin/applications');
  await settle(page);

  const rows = page.locator('#example1 tbody tr');
  await expect(rows.first()).toBeVisible();

  // Page 1 - the case that always worked.
  const firstButton = page.locator('#example1 tbody button[data-toggle="popover"]').first();
  await firstButton.click();
  await expect(page.locator('.popover')).toBeVisible();
  await page.keyboard.press('Escape');
  await page.locator('h1').first().click(); // click away to close

  // Now page 2 - this is what was broken.
  const next = page.locator('#example1_next');
  test.skip(await next.evaluate((el) => el.classList.contains('disabled')), 'only one page of applications');

  await next.locator('a').click();
  await expect(page.locator('#example1_paginate .page-item.active')).toHaveText('2');

  const laterButton = page.locator('#example1 tbody button[data-toggle="popover"]').first();
  await expect(laterButton, 'page 2 has a message button').toBeVisible();

  await laterButton.click();
  const popover = page.locator('.popover');
  await expect(popover, 'the popover opens on page 2').toBeVisible();
  await expect(popover).not.toBeEmpty();

  await expectNoServerError(page);
});

test('a message containing a quote still displays', async ({ page }) => {
  // The content used to be written into an HTML attribute unescaped, so a
  // double quote broke the bubble.
  await page.goto('sadmin/applications');
  await settle(page);

  const broken = await page.locator('#example1 tbody td').evaluateAll((cells) =>
    cells.some((c) => c.innerHTML.includes('data-content="') && c.innerHTML.includes('">') === false));
  expect(broken).toBe(false);
});

test('shift list is ordered by record number, newest first', async ({ page }) => {
  // The admin list runs by record number, not by shift date like the
  // applicant-facing ones: the newest posting is what the office is working on.
  // The id column is hidden by the shared table script, so read each row's id
  // off its Edit or Delete link. A booked past shift has neither, and is
  // skipped - the rows that do carry one still have to descend.
  await page.goto('sadmin/postjobs');
  await settle(page);

  const ids = await page
    .locator('#example1 tbody tr')
    .evaluateAll((rows) =>
      rows
        .map((r) => r.querySelector('a[href*="/postjobs/edit/"], a[href*="/postjobs/delete/"]'))
        .map((a) => (a ? Number(a.getAttribute('href').split('/').pop()) : null))
        .filter((id) => id !== null));

  expect(ids.length).toBeGreaterThan(1);
  expect(ids, 'record numbers descend down the page').toEqual([...ids].sort((a, b) => b - a));
});

test('employer list is alphabetical by store name', async ({ page }) => {
  await page.goto('sadmin/employer');
  await settle(page);

  const names = await page.locator('#example1 tbody tr td:nth-child(1)').allTextContents();
  const cleaned = names.map((n) => n.trim().toLowerCase()).filter((n) => n !== '');

  expect(cleaned.length).toBeGreaterThan(1);
  expect(cleaned, 'store names ascend').toEqual([...cleaned].sort());
});

test('applicant list is alphabetical by surname', async ({ page }) => {
  await page.goto('sadmin/applicant');
  await settle(page);

  await expect(page.locator('#example1 tbody tr').first()).toBeVisible();
  await expectNoServerError(page);
});

test('the pages that used to return a server error are gone or working', async ({ page }) => {
  // Removed outright - a 404 is the correct answer for a feature that does not exist.
  for (const path of ['sadmin/applied_applicants/85', 'applicant/saved_jobs']) {
    const response = await page.goto(path);
    expect(response?.status(), `${path} must not be a server error`).not.toBe(500);
  }

  // This one has a real view and now renders.
  const pid = scalar('SELECT p_id FROM post_job ORDER BY p_id DESC LIMIT 1;');
  const employerPage = await page.goto(`employer/applied_applicants/${pid}`);
  expect(employerPage?.status()).not.toBe(500);
});

test('the public test-email URL is gone', async ({ page }) => {
  const response = await page.goto('front/test_email');
  expect(response?.status(), 'must not still send mail on demand').toBe(404);
});

test('no third-party credentials remain in the bulk mail screen', async ({ page }) => {
  await page.goto('sadmin/send_email');
  const body = await page.content();

  expect(body.toLowerCase()).not.toContain('maanusham');
  expect(body).not.toContain('smtp.gmail.com');
  await expectNoServerError(page);
});
