# Admin end-to-end tests (Playwright)

Browser tests for the PickAShift back office, added alongside the CodeIgniter 3
→ 4 migration to prove the admin area still works end to end.

## Running them

The tests drive the site as served by **WAMP/Apache** — they do not start a
server, because the app needs `mod_rewrite` and the real MySQL database.

```bash
# 1. Start WAMP (Apache + MySQL) and make sure http://localhost/pickashift/ loads.
# 2. Then:
cd tests/e2e
npm install          # first time only
npm test             # headless
npm run test:headed  # watch it in a browser
npm run report       # open the HTML report of the last run
```

### Configuration

| Variable      | Default                                          | Purpose                        |
| ------------- | ------------------------------------------------ | ------------------------------ |
| `BASE_URL`    | `http://localhost/pickashift`                    | where the site is served       |
| `BROWSER_CHANNEL` | *(empty — bundled Chromium)*                 | `chrome` runs the suite in the installed Google Chrome |
| `MYSQL_BIN`   | `C:/wamp64/bin/mysql/mysql9.1.0/bin/mysql.exe`    | client used for fixtures       |
| `DB_NAME`     | `pickashift`                                     | database                       |
| `DB_USER`     | `root`                                           | database user                  |
| `DB_PASS`     | *(empty)*                                        | database password              |
| `SESSION_DIR` | `<project>/writable/session`                     | CI4 session files              |
| `ADMIN_USER` / `ADMIN_PASS` | `e2e.admin@example.com` / `E2eTest@12345` | seeded test administrator |

## What is covered

`specs/admin-auth.spec.js`
* the login page renders and its verification image really is an image
* signed-out visitors are pushed to the login screen from every admin URL
* wrong password, wrong verification code and non-admin account are all rejected
* a valid login reaches the dashboard, and logout genuinely ends the session

`specs/admin-pages.spec.js`
* the dashboard renders its summary tiles
* all eleven list screens return 200, show their heading and their table
* all ten "add" forms render with their fields and submit control
* all ten "edit" forms render with the row already loaded
* the application detail screen renders
* the `?filter=new` / `?filter=booked` list filters work
* settings, send-email and change-password screens render
* the sidebar contains the module links and navigating one of them works

`specs/admin-city-crud.spec.js` — the whole CRUD pattern the back office shares,
driven through the UI on the City module:
* create from the list screen, with the row asserted in the database and in the
  (paginated, searched) table
* a duplicate name is rejected by the `is_unique` rule and inserts nothing
* an empty name is rejected **by the server** (client-side validation is
  switched off first, so the request really reaches the controller)
* rename → toggle status → delete, each verified against the database
* a city that other tables still reference cannot be deleted

`specs/employer-multi-store.spec.js` — change request B4, "one login for
multiple stores" (`plan/change-requests.html#B4`):
* one login lists its three stores, and another employer cannot see them
* a shift is posted against a chosen store, and edit can move it to another
* a shift with no store reference still shows the owner's address, as before
* the booked applicant sees the chosen store's address and phone, not a sibling store's

The table and column names are collected in `helpers/stores.js`. If the schema
is missing — most likely `php spark migrate` has not been run — every test in
the file **skips** with a message naming what is absent, rather than failing.

## Two things worth knowing

**The verification code.** Every login form shows a GD-rendered 6-digit code and
compares it with `captcha_code` in the session. A browser cannot read that image,
so `helpers/session.js` reads the expected value out of the session file the same
request just wrote. The generate → store → compare path is still exercised for
real; only the human's eyes are replaced.

**Flash messages are easy to lose.** CodeIgniter ages flash data once per
request, and every admin page fires an `ajax_getcitylist` request from its
footer. If that request lands between a redirect and the page meant to display
the message, the message is swallowed — the same was true under CI3. The helpers
therefore wait for the page to go idle (`settle()`) before an action that relies
on a flash message.

## Fixtures

`helpers/global-setup.js` seeds a throwaway administrator; `global-teardown.js`
removes it along with any `E2E %` rows the tests created. The suite runs with a
single worker because those fixtures are shared.
