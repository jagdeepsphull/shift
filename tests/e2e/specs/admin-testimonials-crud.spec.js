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

/**
 * A real 1x1 PNG, inline rather than a file on disk - `fileupload()` runs
 * getimagesize() over what it is handed, so a renamed .txt would be rejected
 * and the test would prove nothing about the upload path.
 */
const PNG = Buffer.from(
  'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
  'base64',
);

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

    // Everything about the person is optional, so a title and a quote on their
    // own still save - that is the whole of what a testimonial was before those
    // columns existed.
    expect(scalar(`SELECT t_name FROM testimonial WHERE t_title = '${TITLE}';`)).toBe('');

    // NULL rather than '': a file input is not something the browser posts back
    // when nothing was picked, so the column is never written at all. Both read
    // as "no photo" to `testimonialPhoto()`.
    expect(scalar(`SELECT t_image FROM testimonial WHERE t_title = '${TITLE}';`)).toBe('NULL');

    // The two selects come pre-set, so a save that touches neither has to land
    // Active and five stars rather than needing a second visit to be shown.
    expect(scalar(`SELECT t_status FROM testimonial WHERE t_title = '${TITLE}';`)).toBe('1');
    expect(scalar(`SELECT t_rating FROM testimonial WHERE t_title = '${TITLE}';`)).toBe('5');

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

  test('saves the whole card - who said it, their stars and a photo', async ({ page }) => {
    await page.goto('sadmin/testimonials/add');

    await page.fill('input[name="t_title"]', TITLE);
    await page.fill('textarea[name="t_description"]', BODY);
    await page.fill('input[name="t_name"]', 'E2E Sarah M.');
    await page.fill('input[name="t_role"]', 'Job Seeker');
    await page.fill('input[name="t_location"]', 'Toronto, ON');
    await page.selectOption('select[name="t_rating"]', '4');
    await page.selectOption('select[name="t_status"]', '1');

    // A real image, not a renamed text file: `fileupload()` reads the header
    // with getimagesize() and turns away anything that is not actually one.
    await page.setInputFiles('input[name="t_image"]', {
      name: 'e2e-face.png',
      mimeType: 'image/png',
      buffer: PNG,
    });

    await page.click('button[name="savedata"]');
    await expect(page).toHaveURL(/\/sadmin\/testimonials$/);

    const id = scalar(`SELECT t_id FROM testimonial WHERE t_title = '${TITLE}';`);
    expect(scalar(`SELECT t_name FROM testimonial WHERE t_id = ${id};`)).toBe('E2E Sarah M.');
    expect(scalar(`SELECT t_role FROM testimonial WHERE t_id = ${id};`)).toBe('Job Seeker');
    expect(scalar(`SELECT t_location FROM testimonial WHERE t_id = ${id};`)).toBe('Toronto, ON');
    expect(scalar(`SELECT t_rating FROM testimonial WHERE t_id = ${id};`)).toBe('4');

    // The upload happens after the insert, because the filename is written back
    // onto a row that has to exist first.
    const stored = scalar(`SELECT t_image FROM testimonial WHERE t_id = ${id};`);
    expect(stored, 'a filename was stored').toMatch(/\.png$/);

    // ... and the file is really there, which is what the front end checks
    // before it decides between the photo and the placeholder thumb.
    await expect(page.request.get(`uploads/testimonial/${stored}`)).resolves.toBeOK();

    // The list draws it back.
    await filterTable(page, TITLE);
    const row = page.locator('#example1 tbody tr', { hasText: TITLE });
    await expect(row.locator('img')).toHaveAttribute('src', new RegExp(`uploads/testimonial/${stored}$`));
    await expectNoServerError(page);
  });

  test('keeps the photo through an edit, and lets it be taken off again', async ({ page }) => {
    query(`INSERT INTO testimonial (t_title, t_description, t_status) VALUES ('${TITLE}', '${BODY}', 1);`);
    const id = scalar(`SELECT t_id FROM testimonial WHERE t_title = '${TITLE}';`);

    // No photo yet: nothing to remove, and the circle is the placeholder.
    await page.goto(`sadmin/testimonials/edit/${id}`);
    await expect(page.locator('#remove_image')).toBeDisabled();
    await expect(page.locator('.card-body img')).toHaveAttribute('src', /thumb\.svg$/);

    await page.setInputFiles('input[name="t_image"]', {
      name: 'e2e-face.png',
      mimeType: 'image/png',
      buffer: PNG,
    });
    await page.click('button[name="updatedata"]');
    await expect(page).toHaveURL(/\/sadmin\/testimonials$/);

    const stored = scalar(`SELECT t_image FROM testimonial WHERE t_id = ${id};`);
    expect(stored).toMatch(/\.png$/);

    // A save that touches neither the file input nor the checkbox has to leave
    // the photo alone - the browser will not pre-fill a file input, so an empty
    // one cannot be read as "clear it".
    await page.goto(`sadmin/testimonials/edit/${id}`);
    await page.fill('input[name="t_role"]', 'Pharmacist');
    await page.click('button[name="updatedata"]');
    expect(scalar(`SELECT t_image FROM testimonial WHERE t_id = ${id};`), 'photo kept').toBe(stored);

    // Taking it off is its own instruction. AdminLTE hides the box itself and
    // draws it on the label, so the label is what a person clicks and what the
    // test has to click too - `check()` on the input finds it covered.
    await page.goto(`sadmin/testimonials/edit/${id}`);
    await page.click('label[for="remove_image"]');
    await expect(page.locator('#remove_image')).toBeChecked();
    await page.click('button[name="updatedata"]');
    expect(scalar(`SELECT t_image FROM testimonial WHERE t_id = ${id};`), 'photo cleared').toBe('');
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

  test('draws the person behind the quote, and a thumb where there is no photo', async ({ page }) => {
    query(`
      INSERT INTO testimonial (t_title, t_description, t_name, t_role, t_location, t_rating, t_status) VALUES
        ('${TITLE}', '${BODY}', 'E2E Sarah M.', 'Job Seeker', 'Toronto, ON', 4, 1),
        ('E2E Nameless Testimonial', 'A quote with nobody attached to it.', '', '', '', 5, 1);
    `);

    await page.goto('');

    const card = page.locator('.wz-testimonial', { hasText: TITLE });
    await expect(card.locator('.wz-testimonial-name')).toHaveText('E2E Sarah M.');
    await expect(card.locator('.wz-testimonial-role')).toHaveText('Job Seeker');
    await expect(card.locator('.wz-testimonial-place')).toContainText('Toronto, ON');

    // Four of five, and the strip says so to a screen reader as well as showing
    // it - the stars themselves are decorative.
    await expect(card.locator('.wz-testimonial-stars')).toHaveAttribute('aria-label', 'Rated 4 out of 5');
    await expect(card.locator('.wz-testimonial-stars svg.is-on')).toHaveCount(4);
    await expect(card.locator('.wz-testimonial-stars svg')).toHaveCount(5);

    // Neither row has an uploaded photo, so both fall back to the thumb rather
    // than rendering an empty src.
    await expect(card.locator('.wz-testimonial-photo')).toHaveAttribute('src', /thumb\.svg$/);

    // The one with nobody attached keeps the card it always had: no ruled-off
    // strip with nothing in it.
    const nameless = page.locator('.wz-testimonial', { hasText: 'E2E Nameless Testimonial' });
    await expect(nameless.locator('.wz-testimonial-by')).toHaveCount(0);
    await expect(nameless.locator('.wz-testimonial-photo')).toHaveAttribute('src', /thumb\.svg$/);

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
