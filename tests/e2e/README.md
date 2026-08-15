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
* registration asks each account type for the right fields, and a manager is
  asked for a corporate group and then for which of its stores they run
* the store list holds exactly that group's active stores, and a deactivated
  one is not offered
* a group that has added no store is still offered, and the store list says so
  rather than the group being hidden from the list entirely
* a manager works from their group's store without owning it: it is listed,
  it is offered on the shift form, and it carries no Edit button
* a shift is posted against a chosen store, and edit can move it to another
* a shift with no store reference still shows the owner's address, as before
* the booked applicant sees the chosen store's address and phone, not a sibling store's

The table and column names are collected in `helpers/stores.js`. If the schema
is missing — most likely `php spark migrate` has not been run — every test in
the file **skips** with a message naming what is absent, rather than failing.

`specs/registration.spec.js` — public registration, once for each of the four
account types, filled in and read back out of the database:
* each type becomes the right combination of `u_usertype`, `u_emp_role`,
  `u_parent_id` and `u_store_id`, and is held for the administrator's approval
* a manager picks one of their corporate group's existing stores. No store row
  is created for them — the row stays the group's — and the store's name,
  number and address are copied onto the login, which is where a dozen screens,
  exports and e-mails still read them from
* a store belonging to another group is refused even when posted directly, so
  the picker is not the only thing standing between a manager and someone
  else's location
* the password is stored as a modern hash, an unapproved account cannot sign
  in, an approved one lands on the screen its type belongs on, and a duplicate
  e-mail or a wrong verification code is refused

`specs/admin-create-accounts.spec.js` — an account made in the back office has
to be able to log in:
* an employer and an applicant are each added through the real form and then
  sign in on the public site, which is what the account was created for
* the employer's address becomes their first store, without which no shift can
  ever be posted for them
* an e-mail that is already somebody's login id is refused and inserts nothing
* no row anywhere is left without a login id

`specs/account-parity.spec.js` — the same account, made two ways. An Owner, a
Manager and an Applicant are created once on public registration and once in the
back office, and the two `users` rows are compared column by column:
* every column that says *what kind of account this is* must match — id, e-mail,
  timestamps, password hash, status and IP are excluded on purpose
* a Manager carries the store they were given: its name, number, address,
  postcode, province and city are copied onto the login, because that is what
  the employer list, the shift forms' employer dropdown and the booking e-mail
  read. The back office used to leave all of them blank
* neither path gives a Manager a store of their own; it stays the group's
* all six accounts sign in once approved and land on their own screen

`specs/admin-employer-kinds.spec.js` — the employer form offers the three kinds
registration does, and converts between them:
* the add form offers all three, starts on the kind it was reached from, and
  asks each one for the right fields
* a multi-store owner is saved owning no store; a manager is attached to the
  chosen group and gets one
* a group id that was never offered is refused
* the **edit** form asks a manager exactly what the add form asks — the store
  they run as a dropdown of their group's locations, opened on the one they
  hold, and no name of their own
* saving a manager there keeps that store, keeps the blank name, and creates no
  store from their (empty) address columns; choosing another moves them to it
* an employer from before multi-store is converted to a multi-store owner,
  keeping its store and its address
* the two conversions that would contradict the data are refused: a chain
  holding several stores becoming a single store, and a group that managers
  still answer to ceasing to be one

`specs/admin-stores.spec.js` — sub-outlets, maintained by an administrator:
* the list is reachable from the sidebar and can be scoped to one chain
* a branch is added to a multi-store owner; a single-store employer is refused
  a second location
* a pasted map link is normalised (`maps.google.com/…` → `https://…`) and a
  `javascript:` one is dropped
* a store with no pasted link still offers a map search built from its address
* a store that shifts point at cannot be deleted, only deactivated

`specs/store-location-links.spec.js` — where a shift actually is:
* the shift page shows the branch address, the location label and the pin
* the store's own website wins over the employer's, and falls back to it
* the booking e-mail and the day-before reminder name the branch, **not** the
  employer's login address — which for a multi-store owner is a head office
* a shift with no store still shows the address it always showed
* every lifecycle stage renders without a missing-variable error

`specs/admin-book-on-add.spec.js` — booking an applicant straight from the admin's
Add Shift form, for a shift already agreed off the site:
* naming somebody writes the approved booking row and closes the shift, exactly
  as approving an application does
* leaving the section alone posts an ordinary shift, with the status dropdown
  still deciding
* an applicant deactivated between opening the form and saving it saves nothing
  at all, rather than a shift that was meant to be booked and is not
* the booking reaches the applications list under its shift id

`specs/admin-book-on-edit.spec.js` — changing the booking on a shift that already
has one, for the applicant who rings up to say they cannot make it:
* the shift list offers **Edit** on a booked shift while its date is ahead, and
  never offers Delete
* the form opens on whoever is booked, and leaving the picker alone leaves the
  booking alone
* choosing somebody else moves the booking: the first applicant's row is
  rejected, the second holds the only approved one, and the shift stays closed
* clearing the picker takes the shift off them and puts it back to **Live**
* on the day of the shift and after it nothing may be touched — the URL redirects
  with the shift unchanged

`specs/admin-form-parity.spec.js` — the back-office forms accept what the public
ones accept:
* a name with a hyphen, an apostrophe and brackets can be **typed** (character
  by character, so an `onkeydown` filter would fail the test) and saved
* a name full of digits is refused by the server, as on the public form
* the applicant form offers the same categories the public form does

### Rendering an e-mail without sending one

`store-location-links.spec.js` reads the real message bodies through
`php spark email:preview <template> <shiftId>`, which renders a lifecycle e-mail
from a real row and prints it to stdout. It sends nothing, so it is also the way
to review the wording and layout of each stage:

```bash
php spark email:preview booking-applicant 412 > booking.html
```

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
