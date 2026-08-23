// @ts-check
/**
 * The CSRF token, for a test that posts without a form.
 *
 * Every POST the application accepts must carry the session's token, and the
 * pages get theirs from `App\Filters\CsrfTokenInjector` - a hidden field in
 * each form and a `<meta name="csrf-token">` for the ajax calls. A test driving
 * a real form needs nothing from here: it submits the field along with
 * everything else, which is the point of injecting it.
 *
 * What needs this is a test that posts straight at an endpoint to check who is
 * allowed to call it. Those are asking an authorisation question, and without
 * the token they never reach the code that answers it - they get a 403 from the
 * filter and pass or fail for the wrong reason.
 */

/**
 * Headers that carry the token from whatever page is currently open.
 *
 * @param {import('@playwright/test').Page} page a page already loaded from the site
 * @returns {Promise<Record<string, string>>}
 */
async function csrfHeaders(page) {
  const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');

  if (!token) {
    throw new Error(
      'No csrf-token meta on the open page. Either nothing is loaded yet, or the '
      + "'csrftoken' filter is missing from Config\\Filters - in which case every "
      + 'form on the site is failing too.',
    );
  }

  return { 'X-CSRF-TOKEN': token };
}

module.exports = { csrfHeaders };
