// @ts-check
/**
 * The Testimonials master: the same add -> validate -> edit -> toggle -> delete
 * lifecycle the other lists are covered for, plus the half that is visible to
 * the public - the carousel the home page rotates them through.
 *
 * Like Additional Details it starts empty, so the tests create what they need
 * and clean up after themselves.
 */
const { test, expect } = require('@playwright/test');
const { loginAsAdmin, settle, expectNoServerError, filterTable } = require('../helpers/admin');
const { query, scalar, count } = require('../helpers/db');

const TITLE = 'E2E Playwright Testimonial';
const TITLE_RENAMED = 'E2E Playwright Testimonial Renamed';
const BODY = 'Shifts were filled the same day, and every credential was checked before we saw the name.';

test.beforeEach(() => {
  query("DELETE FROM testimonial WHERE t_title LIKE 'E2E %';");
});

test.afterAll(() => {
  query("DELETE FROM testimonial WHERE t_title LIKE 'E2E %';");
});

/**
 * The back office. Only these need an admin session - the home page tests
 * further down are public, and signing in for them would only add a login to go
 * wrong.
 */
test.describe('the back-office list', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('is reachable from the sidebar', async ({ page }) => {
    await page.click('.nav-sidebar a:has-text("Main Master")');
    await page.click('.nav-sidebar a:has-text("Testimonials")');

    await expect(page).toHaveURL(/\/sadmin\/testimonials\/index$/);
    await expect(page.locator('.content-header h1')).toContainText(/testimonial list/i);
    await expectNoServerError(page);
  });

  test('creates a testimonial from the add screen', async ({ page }) => {
    await page.goto('sadmin/testimonials');
    await page.click('a.btn-primary:has-text("Add Testimonial")');
    await expect(page).toHaveURL(/\/sadmin\/testimonials\/add$/);

    await page.fill('input[name="t_title"]', TITLE);
    await page.fill('textarea[name="t_description"]', BODY);
    await page.click('button[name="savedata"]');

    await expect(page).toHaveURL(/\/sadmin\/testimonials$/);
    await expect(page.locator('.alert')).toContainText(/inserted successfully/i);

    expect(count('testimonial', `t_title = '${TITLE}'`), 'row should exist').toBe(1);
    expect(scalar(`SELECT t_description FROM testimonial WHERE t_title = '${TITLE}';`)).toBe(BODY);

    // The add form posts the two text fields and nothing else, so the column
    // default decides the status - it has to land Active and show on the home
    // page, not need a second click first.
    expect(scalar(`SELECT t_status FROM testimonial WHERE t_title = '${TITLE}';`)).toBe('1');

    await filterTable(page, TITLE);
    await expect(page.locator('#example1 tbody tr', { hasText: TITLE })).toHaveCount(1);
    await expectNoServerError(page);
  });

  test('lets two testimonials share a title', async ({ page }) => {
    query(`INSERT INTO testimonial (t_title, t_description, t_status) VALUES ('${TITLE}', '${BODY}', 1);`);

    await page.goto('sadmin/testimonials/add');
    await page.fill('input[name="t_title"]', TITLE);
    await page.fill('textarea[name="t_description"]', 'A different quote under the same heading.');
    await page.click('button[name="savedata"]');

    await expect(page).toHaveURL(/\/sadmin\/testimonials$/);
    expect(count('testimonial', `t_title = '${TITLE}'`), 'both kept').toBe(2);
    await expectNoServerError(page);
  });

  test('refuses an empty title or description on the server, not just in the browser', async ({ page }) => {
    await page.goto('sadmin/testimonials/add');

    // Both inputs carry the HTML `required` attribute; drop client-side
    // validation so the request actually reaches the controller.
    await page.evaluate(() => {
      document.querySelectorAll('form').forEach((f) => f.setAttribute('novalidate', 'novalidate'));
    });

    await page.click('button[name="savedata"]');

    const errors = page.locator('.alert-danger');
    await expect(errors).toContainText(/title field is required/i);
    await expect(errors).toContainText(/description field is required/i);
    expect(count('testimonial', "t_title = ''"), 'nothing inserted').toBe(0);
    await expectNoServerError(page);
  });

  test('edits, toggles and deletes a testimonial', async ({ page }) => {
    query(`INSERT INTO testimonial (t_title, t_description, t_status) VALUES ('${TITLE}', '${BODY}', 1);`);
    const id = scalar(`SELECT t_id FROM testimonial WHERE t_title = '${TITLE}';`);

    // --- edit ---
    await page.goto(`sadmin/testimonials/edit/${id}`);
    await expect(page.locator('input[name="t_title"]')).toHaveValue(TITLE);
    await expect(page.locator('textarea[name="t_description"]')).toHaveValue(BODY);

    await page.fill('input[name="t_title"]', TITLE_RENAMED);
    await page.click('button[name="updatedata"]');

    await expect(page).toHaveURL(/\/sadmin\/testimonials$/);
    await expect(page.locator('.alert')).toContainText(/updated successfully/i);
    expect(scalar(`SELECT t_title FROM testimonial WHERE t_id = ${id};`)).toBe(TITLE_RENAMED);

    // --- change status ---
    await filterTable(page, TITLE_RENAMED);
    await page.locator('#example1 tbody tr', { hasText: TITLE_RENAMED })
      .locator('a:has-text("Change Status")')
      .click();

    await expect(page).toHaveURL(/\/sadmin\/testimonials$/);
    expect(scalar(`SELECT t_status FROM testimonial WHERE t_id = ${id};`), 'now inactive').toBe('0');

    // --- delete (the link asks for confirmation) ---
    await settle(page);
    page.once('dialog', (dialog) => dialog.accept());

    await filterTable(page, TITLE_RENAMED);
    await page.locator('#example1 tbody tr', { hasText: TITLE_RENAMED })
      .locator('a:has-text("Delete")')
      .click();

    await expect(page).toHaveURL(/\/sadmin\/testimonials$/);
    await expect(page.locator('.alert')).toContainText(/has been deleted/i);
    expect(count('testimonial', `t_id = ${id}`), 'row is gone').toBe(0);
    await expectNoServerError(page);
  });
});

test.describe('the home page carousel', () => {
  // These count what the page renders, so the site's own testimonials have to be
  // out of the way. Note exactly which were on and switch only those back
  // afterwards - a blanket "set them all to 1" would activate rows the admin had
  // deliberately retired.
  /** @type {string[]} */
  let wasActive = [];

  test.beforeEach(() => {
    wasActive = query("SELECT t_id FROM testimonial WHERE t_status = 1 AND t_title NOT LIKE 'E2E %';")
      .split('\n')
      .filter(Boolean);

    if (wasActive.length) {
      query(`UPDATE testimonial SET t_status = 0 WHERE t_id IN (${wasActive.join(',')});`);
    }
  });

  test.afterEach(() => {
    if (wasActive.length) {
      query(`UPDATE testimonial SET t_status = 1 WHERE t_id IN (${wasActive.join(',')});`);
    }
  });

  test('shows three to a slide under the services tiles', async ({ page }) => {
    // Four active, so the fourth has to start a second slide, plus one switched
    // off that must not be counted at all.
    query(`
      INSERT INTO testimonial (t_title, t_description, t_status) VALUES
        ('${TITLE}', '${BODY}', 1),
        ('E2E Second Testimonial', 'The second quote.', 1),
        ('E2E Third Testimonial', 'The third quote, filling the first slide.', 1),
        ('E2E Fourth Testimonial', 'The fourth, which starts a slide of its own.', 1),
        ('E2E Deactive Testimonial', 'This one is switched off and must never reach the home page.', 0);
    `);

    await page.goto('');

    const carousel = page.locator('#wz-testimonials');
    await expect(carousel).toBeVisible();

    // Under "What Makes Us Stand Out", which is where it was asked for.
    const follows = await page.evaluate(() => {
      const services = document.querySelector('#services');
      const testimonials = document.querySelector('#testimonials');
      if (!services || !testimonials) return null;
      // Node.DOCUMENT_POSITION_FOLLOWING
      return Boolean(services.compareDocumentPosition(testimonials) & 4);
    });
    expect(follows, 'testimonials follow the services section').toBe(true);

    const slides = carousel.locator('.carousel-item');
    await expect(slides, 'four testimonials, three to a slide').toHaveCount(2);
    await expect(carousel).not.toContainText('E2E Deactive Testimonial');

    // The first slide holds three, all of them on screen together.
    const first = slides.first();
    await expect(first).toHaveClass(/active/);
    await expect(first.locator('.wz-testimonial')).toHaveCount(3);
    await expect(first).toContainText(TITLE);
    await expect(first).toContainText(BODY);
    await expect(first).toContainText('E2E Second Testimonial');
    await expect(first).toContainText('E2E Third Testimonial');

    // Bootstrap is wired up: the next control actually advances the carousel.
    await carousel.locator('.carousel-control-next').click();
    await expect(slides.nth(1)).toHaveClass(/active/);
    await expect(slides.nth(1).locator('.wz-testimonial')).toHaveCount(1);
    await expect(slides.nth(1)).toContainText('E2E Fourth Testimonial');

    await expectNoServerError(page);
  });

  test('needs no controls when three or fewer fill the single slide', async ({ page }) => {
    query(`
      INSERT INTO testimonial (t_title, t_description, t_status) VALUES
        ('${TITLE}', '${BODY}', 1),
        ('E2E Second Testimonial', 'The second quote.', 1),
        ('E2E Third Testimonial', 'The third quote.', 1);
    `);

    await page.goto('');

    const carousel = page.locator('#wz-testimonials');
    await expect(carousel.locator('.carousel-item')).toHaveCount(1);
    await expect(carousel.locator('.wz-testimonial')).toHaveCount(3);

    // Nothing to move to, so no arrows and no dots.
    await expect(carousel.locator('.carousel-control-next')).toHaveCount(0);
    await expect(carousel.locator('.carousel-indicators')).toHaveCount(0);
    await expectNoServerError(page);
  });

  // The set-up above has already deactivated the site's own rows, and this test
  // adds none of its own - so nothing is active.
  test('is left out entirely when no testimonial is active', async ({ page }) => {
    expect(count('testimonial', 't_status = 1'), 'nothing active to show').toBe(0);

    await page.goto('');

    await expect(page.locator('#services')).toBeVisible();
    await expect(page.locator('#testimonials')).toHaveCount(0);
    await expectNoServerError(page);
  });
});
