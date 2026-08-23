// @ts-check
/**
 * The key the cron URLs want.
 *
 * `/cron/remind_shifts` and `/cron/expire_jobs` answer 404 unless the request
 * carries `?key=` matching `cron.key` in `.env` - the reminder one sends mail
 * to every applicant booked for tomorrow, and it used to be open to anyone who
 * typed the URL.
 *
 * A development machine's `.env` is per-machine and git-ignored, so a fresh
 * clone has no key and every test that drives these URLs would fail with a 404
 * that says nothing about why. Rather than skip those tests - which is how
 * coverage quietly disappears - this writes a key into the local `.env` the
 * first time it is asked for. Production's key comes from the build, which
 * generates its own; nothing here touches that.
 */
const fs = require('fs');
const path = require('path');

const ENV_FILE = path.join(__dirname, '../../../.env');

/** @returns {string} the configured key, creating one if the file has none */
function cronKey() {
  let text = '';

  try {
    text = fs.readFileSync(ENV_FILE, 'utf8');
  } catch {
    throw new Error(`No .env at ${ENV_FILE} - the site cannot be running against this checkout.`);
  }

  const found = text.match(/^\s*cron\.key\s*=\s*(\S+)\s*$/m);

  if (found) {
    return found[1];
  }

  // A fixed value rather than a random one: re-running the suite must not keep
  // rewriting the developer's .env, and this key protects nothing on a machine
  // that is only reachable from itself.
  const key = 'e2e-local-cron-key';
  const line = `\n# Added by the end-to-end suite so the cron URLs answer it. See helpers/cron.js.\ncron.key = ${key}\n`;

  fs.appendFileSync(ENV_FILE, text.endsWith('\n') ? line.slice(1) : line);
  console.log(`[e2e] wrote cron.key to ${ENV_FILE}`);

  return key;
}

/**
 * A cron path with the key on it.
 *
 * @param {string} route e.g. 'cron/remind_shifts'
 */
const cronUrl = (route) => `${route}?key=${encodeURIComponent(cronKey())}`;

module.exports = { cronKey, cronUrl };
