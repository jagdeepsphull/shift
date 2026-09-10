// @ts-check
/**
 * The social links the front end shows are settings now, not markup.
 *
 * They used to be written into two views by hand and had drifted on to
 * different accounts, so what this guards is the join: what the admin panel
 * saves is what both the footer and the contact page link to, a box left empty
 * takes its icon off the page, and an address typed without "https://" still
 * leaves the site rather than pointing at a page on it.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, expectNoServerError } = require('../helpers/admin');
const { query, scalar } = require('../helpers/db');

const COLUMNS = ['s_facebook_url', 's_twitter_url', 's_instagram_url'];

/** What the row held before the run, put back afterwards. */
const original = {};

test.beforeAll(() => {
  for (const column of COLUMNS) {
    original[column] = scalar(`SELECT COALESCE(${column}, '') FROM settings WHERE s_id = 1;`);
  }
});

test.afterAll(() => {
  const quote = (value) => `'${String(value).replace(/'/g, "''")}'`;
  const set = COLUMNS.map((c) => `${c} = ${quote(original[c] ?? '')}`).join(', ');
  query(`UPDATE settings SET ${set} WHERE s_id = 1;`);
});

test.beforeEach(async ({ page }) => {
  await loginAsAdmin(page);
});

test('the settings screen edits the three links and no longer edits the legal text', async ({ page }) => {
  await page.goto('sadmin/settings');

  for (const column of COLUMNS) {
    await expect(page.locator(`[name="${column}"]`)).toBeVisible();
  }

  // The editors are off the screen; the columns behind them stay in the table.
  for (const gone of ['s_disclaimer', 's_terms_conditions', 's_privacy_policy']) {
    await expect(page.locator(`[name="${gone}"]`)).toHaveCount(0);
  }

  await expectNoServerError(page);
});

test('a saved link reaches the footer and the contact page, and an empty one hides its icon', async ({ page }) => {
  await page.goto('sadmin/settings');

  // Facebook typed the way somebody would say it, X in full, Instagram left
  // blank - the three cases the front end has to handle between them.
  await page.fill('[name="s_facebook_url"]', 'facebook.com/e2e-pickashift');
  await page.fill('[name="s_twitter_url"]', 'https://x.com/e2e-pickashift');
  await page.fill('[name="s_instagram_url"]', '');
  await page.click('[name="updatedata"]');

  await expect(page.locator('[name="s_facebook_url"]')).toHaveValue('https://facebook.com/e2e-pickashift');
  expect(scalar("SELECT s_instagram_url FROM settings WHERE s_id = 1;")).toBe('');

  for (const path of ['', 'contact']) {
    await page.goto(path);

    // The contact page carries the footer as well as its own block, so the
    // count differs by page - what matters is that both links are on both.
    expect(await page.locator('a[href="https://facebook.com/e2e-pickashift"]').count()).toBeGreaterThan(0);
    expect(await page.locator('a[href="https://x.com/e2e-pickashift"]').count()).toBeGreaterThan(0);

    // Somebody following one of these is leaving for another site, so the tab
    // they were reading has to still be there when they come back.
    for (const url of ['https://facebook.com/e2e-pickashift', 'https://x.com/e2e-pickashift']) {
      for (const link of await page.locator(`a[href="${url}"]`).all()) {
        await expect(link).toHaveAttribute('target', '_blank');
        await expect(link).toHaveAttribute('rel', /noopener/);
      }
    }

    // Nothing is left linking to the old hard-coded accounts, and the blank
    // Instagram box leaves no icon behind.
    await expect(page.locator('.lni-instagram-filled')).toHaveCount(0);
    await expect(page.locator('a[href="#"] .lni-facebook-filled')).toHaveCount(0);
  }
});

test('a link that is not http is not put in the page as it was typed', async ({ page }) => {
  await page.goto('sadmin/settings');

  await page.fill('[name="s_facebook_url"]', 'javascript:alert(1)');
  await page.click('[name="updatedata"]');

  const saved = scalar('SELECT s_facebook_url FROM settings WHERE s_id = 1;');
  expect(saved.startsWith('https://')).toBe(true);
  expect(saved).not.toContain('javascript:');
});
