// @ts-check
/**
 * Reading the verification code out of the CodeIgniter session.
 *
 * Every login form on the site shows a GD-rendered 6 digit code and compares
 * the posted value with `captcha_code` in the session. A browser cannot read
 * that image, so the tests read the expected value straight out of the session
 * file that the same request just wrote - the code path under test (generate ->
 * store -> compare) is still exercised end to end.
 */
const fs = require('fs');
const path = require('path');

const SESSION_DIR =
  process.env.SESSION_DIR || path.resolve(__dirname, '..', '..', '..', 'writable', 'session');

const COOKIE_NAME = process.env.SESSION_COOKIE || 'ci_session';

/**
 * @param {import('@playwright/test').BrowserContext} context
 * @returns {Promise<string|null>}
 */
async function sessionId(context) {
  const cookies = await context.cookies();
  const cookie = cookies.find((c) => c.name === COOKIE_NAME);
  return cookie ? cookie.value : null;
}

/**
 * The current verification code, or null when the session has none yet.
 *
 * @param {import('@playwright/test').BrowserContext} context
 * @returns {Promise<string|null>}
 */
async function readCaptchaCode(context) {
  const id = await sessionId(context);
  if (!id) return null;

  const file = path.join(SESSION_DIR, `${COOKIE_NAME}${id}`);

  // The session is written when the captcha request shuts down; give it a
  // moment on a slow disk rather than failing the whole test run. On Windows a
  // read landing while PHP still holds the file raises EBUSY, so that is another
  // reason to wait rather than a failure.
  for (let attempt = 0; attempt < 20; attempt++) {
    if (fs.existsSync(file)) {
      try {
        const raw = fs.readFileSync(file, 'latin1');
        const match = raw.match(/captcha_code\|s:\d+:"(\d+)"/);
        if (match) return match[1];
      } catch (error) {
        if (error.code !== 'EBUSY' && error.code !== 'EPERM' && error.code !== 'ENOENT') throw error;
      }
    }
    await new Promise((r) => setTimeout(r, 100));
  }

  return null;
}

module.exports = { readCaptchaCode, sessionId, SESSION_DIR, COOKIE_NAME };
