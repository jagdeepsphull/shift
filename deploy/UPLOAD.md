# Deploying PickAShift

Two commands, two bundles:

```
php deploy/build.php staging      ->  deploy/build/staging/
php deploy/build.php production   ->  deploy/build/production/
```

Upload the **contents** of the folder, not the folder itself. `deploy/build/` is
git-ignored and safe to delete and rebuild at any time.

The build is a whitelist. `.git`, `plan/`, `tests/`, the CI3 backup and your
local `.env` cannot end up on the server by accident, and the script fails the
build if any of them appear. Dev packages (PHPUnit, Faker, vfsStream) are not
copied either — `vendor/` is rebuilt with `composer install --no-dev`.

---

## Before the first upload

Fill in the blanks in the generated `.env`. The build cannot do it, because real
credentials must never live in the repository.

| Setting | Where it comes from |
|---|---|
| `database.default.*` | cPanel → MySQL Databases |
| `email.SMTPUser` / `email.SMTPPass` | Mailgun → Sending → Domain settings → SMTP |

`reliefshifts.com` already publishes SPF (`include:mailgun.org`), DKIM (`k1`) and
DMARC (`p=quarantine`), so mail sent through Mailgun authenticates. Mail sent any
other way — including PHP `mail()` — does not, and gets quarantined. That is why
`email.protocol` is `smtp` and must stay that way.

### Changing the mail settings later

**There is no screen for this.** The back office has no SMTP page and the
`settings` table has no SMTP columns — mail configuration lives in `.env` and
nowhere else. On a split deploy that file is
`/home/m50dt2r0daoy/pickashift_app/.env`; on a flat one it sits beside
`index.php`. Edit it in cPanel's File Manager, save, and the next page load uses
it: nothing to restart, no cache to clear.

| Line in `.env` | What it changes |
|---|---|
| `email.SMTPHost` | the server the mail is sent *through* |
| `email.SMTPUser` / `email.SMTPPass` | the credentials for it |
| `email.SMTPPort` / `email.SMTPCrypto` | `587` pairs with `tls`, `465` pairs with `ssl` |
| `email.protocol` | leave it `smtp` — see the DKIM note above |
| `appsettings.mailFromEmail` | the address the mail is **from** |
| `appsettings.mailFromName` | the name beside it |
| `appsettings.shiftEmailFallback` | where a shift e-mail goes when the ticked side cannot be reached |

The sender is `appsettings.` and not `email.`, which is worth knowing before you
spend an afternoon on it. Every send site in the application calls `setFrom()`
with `Config\AppSettings::$mailFromEmail`, so an `email.fromEmail` line is read
into `Config\Email` and then never looked at. Bundles built before 22 August
2026 carried exactly that line — it looked like the sender setting and changed
nothing.

Check the result without booking a real shift:

```
php spark email:test you@gmail.com      # run from pickashift_app/, needs SSH
```

Without SSH, the back office's bulk e-mail screen sends through the same
configuration. When sending fails, `writable/logs/` names the reason — a wrong
password reads as *"Failed to authenticate password"*, a wrong host as a
connection timeout.

---

## Staging → `/staging/`

1. `php deploy/build.php staging`
2. Fill in `deploy/build/staging/.env`.
3. Upload the contents to `/staging/` on the server.
4. Set permissions: `writable/` and `uploads/` need to be writable by the web
   server (`755`, or `775` on some hosts).
5. Run the database migrations (see below).
6. Visit `https://reliefshifts.com/staging/`.

Three things the staging bundle does differently, all of them deliberate:

- **`RewriteBase /staging/`** is inserted into `.htaccess`. Without it mod_rewrite
  resolves the front controller against the document root and every URL under
  `/staging/` returns 404. This is the single most common reason a subfolder
  deploy appears broken.
- **`robots.txt` is `Disallow: /`** so Google does not index a half-approved copy
  of the site sitting on the live domain.
- **`session.cookieName` is `ci_session_staging`** so signing into staging does
  not sign you out of the live site, and vice versa.

**Use a separate database for staging.** Pointing staging at the live database
defeats the purpose of having staging: an approval test would book real shifts
and e-mail real applicants. For the same reason, either leave `email.SMTPHost`
blank while you click through screens (sending then fails visibly in the admin
panel and nothing leaves the server) or use a Mailgun sandbox domain.

### Seeding the staging database

A newly created database is **empty** — roughly 4 KB in cPanel's list. The site
cannot boot against it: the first page load queries `settings` and throws, which
with `CI_ENVIRONMENT = production` shows nothing but "Whoops!". If staging shows
that page, check the database size before anything else.

1. **phpMyAdmin → live database → Export → Quick, SQL.**
   Untick `visit_log` if the export is close to the upload limit — it is 86,000
   rows that nothing reads, and it is most of the file size.
2. **phpMyAdmin → staging database → Import →** choose that file.
   If it exceeds the upload limit, gzip it first; phpMyAdmin accepts `.sql.gz`.
3. **Run `deploy/staging-scrub.sql` against the staging database.**
   It redirects every user's `u_email` to an unroutable `.invalid` address, so
   filling in SMTP credentials later cannot e-mail real pharmacists about shifts
   that do not exist. Sign-in is unaffected — the login field is `u_userid`,
   which it leaves alone. The script refuses to run on a database whose name
   does not end in `n`.
4. Apply the migrations (below).

With SSH the first two steps are one line:

```
mysqldump -u USER -p pickashift --ignore-table=pickashift.visit_log | mysql -u USER -p pickashiftn
```

---

## Production → `pickashift.ca`

The live site is the **pickashift.ca** folder in cPanel's file manager
(`/home/m50dt2r0daoy/pickashift.ca`), which is that domain's document root — not
`public_html/`, which is `reliefshifts.com`. Uploading into the wrong one of the
two is the mistake to watch for: nothing errors, the files simply appear under a
domain nobody was told to visit.

Once staging is approved:

1. `php deploy/build.php production --split --zip`
   (drop `--split` for the single-folder layout — see **Where the application
   files go** below; split is the recommended one)
2. Fill in the `.env` — database and SMTP. It is at
   `deploy/build/production/private/pickashift_app/.env` in a split build, and
   `deploy/build/production/.env` in a flat one. `cron.key` is already
   generated; note the value the build prints, the cron entry needs it.
3. **Back up first** — database export *and* a copy of the current files.
4. Upload: `pickashift-site.zip` into `/pickashift.ca/` and
   `pickashift-private.zip` into the home directory, extracting each in place.
5. Set `pickashift.ca/uploads/` and `pickashift_app/writable/` to 755 (775 on
   some hosts).
6. Run the migrations.
7. Work down **After deploying, verify**, starting with the security checks.

Do **not** copy the `/staging/` folder into place. Its `.env` and `.htaccess`
are built for a subfolder and would break the live site. Build the production
bundle instead — that is what the second command is for.

### Before the DNS is pointed

`app.baseURL` is `https://pickashift.ca/`, so every link the site writes names
that domain whether or not it resolves yet. Open the site by its real name and
check the padlock before announcing it: with `forceGlobalSecureRequests` on and
HSTS being sent, a browser that meets the site once over a broken certificate
remembers the refusal for a year.

Run cPanel → **SSL/TLS Status** → *Run AutoSSL* for pickashift.ca first, and
confirm the certificate covers **both** `pickashift.ca` and `www.pickashift.ca`.
The `.htaccess` sends www to the bare domain, and that redirect is itself served
over https.

### Where the application files go: two layouts

`pickashift.ca/` is the document root of an addon domain, and the home directory
above it — `/home/m50dt2r0daoy/` — is not the document root of anything. That
makes a second, better layout possible, and `--split` builds it:

```
php deploy/build.php production --split --zip
```

|  | Flat (default) | Split (`--split`, recommended) |
|---|---|---|
| Document root holds | `index.php` `.htaccess` `robots.txt` `assets/` `uploads/` **and** `app/` `vendor/` `writable/` `.env` | `index.php` `.htaccess` `robots.txt` `assets/` `uploads/` |
| Beside it, `pickashift_app/` | — | `app/` `vendor/` `writable/` `.env` `spark` |
| What keeps `app/` and `.env` private | the rules in `.htaccess` | nothing has to — they have no URL |
| Upload | one zip | two zips, two places |

Both are safe **today**: the `.htaccess` rules are real and the test suite checks
them. The difference is what happens the day they stop being honoured — an
`AllowOverride` changed during a server move, a migration to nginx, a host
tightening something. In the flat layout that is the whole application and the
database password becoming readable over the web. In the split layout there is
nothing under any document root to read, so there is no rule left to fail.

The cost is one extra upload. That is the whole trade.

#### Uploading the split build

```
pickashift-site.zip     ->  /home/m50dt2r0daoy/pickashift.ca/   (Extract there)
pickashift-private.zip  ->  /home/m50dt2r0daoy/                 (Extract there;
                                                                 it creates pickashift_app/)
```

The private zip carries its own folder name inside it, so extracting it in the
home directory creates `pickashift_app/` rather than scattering the application
across the home directory. The site zip does not — its contents go straight into
the document root, which already exists.

**They have to stay siblings.** `index.php` looks for `../pickashift_app/`, and
nothing else in the application knows or cares where it is: `Config\Paths` is
written in `__DIR__`-relative terms, so the framework, `writable/` and `.env`
all follow the folder wherever it goes. `uploads/` and `assets/` deliberately
stay in the document root, because the browser fetches those by URL.

If the halves are separated, every page is a **503** saying the application
files were not found — a clear failure rather than a broken-looking site.

Rename the private folder with `--private=NAME` if you prefer something else;
the front controller is built to match.

#### What changes for the rest of this document

- **Migrations:** `php spark migrate` is run from `pickashift_app/`, not the
  document root. With no SSH, `deploy/migrate.php` reads the `.env` beside it —
  upload it into `pickashift_app/`, open it via… it has no URL there. So on a
  split deploy without SSH, run the release `.sql` in phpMyAdmin instead, or
  drop `migrate.php` and a copy of `.env` into the document root temporarily and
  **delete both** the moment it has run.
- **Permissions:** `pickashift_app/writable/` needs to be writable (755, or 775
  on some hosts). `uploads/` stays in the document root and still needs it too.
- **`.env` checks:** `https://pickashift.ca/.env` returns **404** rather than
  403 on a split deploy. Both are fine — 404 because there is genuinely nothing
  there.

### Two folders to leave alone

`uploads/` and `writable/` ship as empty skeletons so a first deploy has
somewhere to write. On an existing site they already hold real data:

- **`uploads/`** holds user-uploaded files — CVs, logos. Overwriting or deleting
  it destroys them, and nothing else has a copy.
- **`writable/`** holds live sessions. Replacing it signs everyone out.

Upload *around* them, or exclude both from the transfer after the first deploy.

---

## What is locked down, and how to check it

The production bundle is not just the development site with a different
`.env`. Ten things are different, and most of them have a way to check from
outside — do that once after the first upload, because every one of them fails
silently. The site works perfectly either way; it is simply less safe than it
reads.

`tests/e2e/specs/security-hardening.spec.js` checks the same things against a
running copy, so `DB_NAME=… npx playwright test specs/security-hardening.spec.js`
answers most of this list in half a minute — against staging, at least. Three of
them (https, HSTS, `Permissions-Policy`) only exist on a real server and have to
be checked with the curl lines below.

### 1. Every form carries a CSRF token

`Config\Security` is on `session` protection, and two filters in
`Config\Filters` do the work: `csrf` refuses any POST, PUT, PATCH or DELETE
without a valid token, and `csrftoken` (`App\Filters\CsrfTokenInjector`) puts
one into every `<form method="post">` on the way out, adds a
`<meta name="csrf-token">`, and hands it to jQuery so the ajax calls send it as
a header.

Injected rather than written into fifty views, so a form added later cannot be
forgotten. **The two filters are one feature** — `csrf` without `csrftoken`
locks every form on the site, and the build refuses to produce a bundle that
has one and not the other.

*Check it:* view source on the sign-in page. There is a
`<input type="hidden" name="csrf_token" ...>` immediately after the `<form>`
tag. Then sign in, edit a store, change a dropdown that loads cities — all
three still work.

*If a form starts failing:* the symptom is being bounced back to the same page
with nothing saved, and `writable/logs/` naming `SecurityException`. The usual
cause is a page left open past the session expiry — reload and resubmit.

### 2. Nothing in `uploads/` can be executed

`uploads/.htaccess` refuses any request for a `.php`, `.phar`, `.cgi`, `.pl`,
`.sh` or dotfile in there, strips the handlers that would run one, and turns
off directory listing. This is the file that decides whether one bad upload is
a nuisance or a shell.

`fileupload()` was also tightened: the extension must match what the bytes
actually are (an image has to load as an image), and the stored name is built
from scratch — `time()_<random>_<cleaned name>.<checked extension>` — rather
than from what the browser sent, which could contain `../`.

*Check it:* put a text file called `test.php` into `uploads/` with the file
manager and open `https://pickashift.ca/uploads/test.php`. It must give **403**,
not a blank page and not the file. Delete it afterwards.

### 3. `writable/` is closed twice

The root `.htaccess` refuses the folder, and the bundle drops a second
`.htaccess` inside it. It holds live sessions — a readable session file is a
signed-in account.

*Check it:* `https://pickashift.ca/writable/logs/` → 403.

### 4. The session cookie is https-only, and the id changes at sign-in

`cookie.secure`, `cookie.httponly` and `cookie.samesite = Lax` are set in the
production `.env`. Both sign-in paths now call `sess_regenerate(true)` the
moment the password is accepted, so the signed-in session never keeps the id
the browser arrived with.

*Check it:* sign in, then in devtools → Application → Cookies, `ci_session`
shows Secure ✓ and HttpOnly ✓.

### 5. Eight wrong passwords locks the account for fifteen minutes

Per account, not per address — an attacker works one list against one address,
while a real pharmacy signs in from a dozen places. The counter lives in
`users.u_login_attempt` / `u_login_attempt_dt`, columns that have been on the
table since the CI3 site and were never read until now. A correct password
clears it; the lock lifts by itself.

Both numbers are in `app/Config/AppSettings.php` (`loginMaxAttempts`,
`loginLockoutMinutes`); either set to 0 switches it off.

*If a real user is locked out:* wait it out, or clear
`u_login_attempt` for that row in phpMyAdmin.

### 6. Security headers on every response

`Strict-Transport-Security` (one year, https only), `X-Content-Type-Options`,
`X-Frame-Options`, `Referrer-Policy` and `Permissions-Policy`, set in
`.htaccess` so they cover the assets Apache serves without PHP, and again by
the `secureheaders` filter so they cover the pages on a host where
`mod_headers` is missing. `X-Powered-By` is unset — it announces the exact PHP
version.

*Check it:* `curl -sI https://pickashift.ca/ | grep -i strict` returns a line.

### 7. The cron URLs take a key

`/cron/remind_shifts` sends e-mail to every applicant booked for tomorrow, and
it was open to anyone who typed it. It now wants `?key=` matching `cron.key` in
`.env`, and returns a plain 404 without it.

The build generates that key and prints it — a different one in every bundle —
so there is nothing to fill in, only something to copy into the cron entry:

```
curl -s "https://pickashift.ca/cron/remind_shifts?key=<the key the build printed>"
```

**If the cron line is left on the old keyless URL the reminders stop,
silently.** The command-line twins (`php spark shifts:remind`, `jobs:expire`)
need no key and are unaffected.

### 8. Names and addresses are escaped where they are shown

An account's own fields — first and last name, company, e-mail, phone, licence
number, address, store name and number, shift title and time, the note on a
booking — are printed through `esc()` now, on roughly 150 lines across the
admin screens and the two portals.

The attack this closes is not against the person who types it. It is: register
with `<script>…</script>` as your first name, wait for an administrator to open
Manage Employers, and the script runs inside *their* session, which can approve
accounts and read every booking. Nothing before this release stopped that.

**What is deliberately not escaped** is the rich text — a shift's description,
a testimonial, the resource pages. Those are written in the back office and are
meant to contain markup; escaping them would print the tags on screen. Treat
the editors that write them as trusted, because they are.

*Check it:* register a test account with `<b>Bold</b>` as the first name and
open it in Manage Employers. The list must show the tags as text, not a bold
name. Delete the account afterwards.

### 9. Every screen behind a login needs one, and keeps needing it

Nothing in either portal — Post New Shift, All Shifts, My Stores, Edit Profile,
Change Password, Applied Shifts, and the ajax endpoints the forms call — opens
without a session, and an applicant typing an employer's address gets the login
page rather than the screen. That was already true; what is new is that
`tests/e2e/specs/portal-requires-login.spec.js` **reads the list of URLs out of
the controllers** instead of having one written down, so a method added later is
checked the day it is written. Auto-routing gives every public method a URL, and
the `setup()` line at the top of each one is the only lock on it.

**One thing that was not true before:** deactivating an account in the back
office now takes effect on that person's next page, not whenever their session
happens to end. It read the account row on every page already and never looked
at `u_status`, so somebody switched off kept working normally in the tab they
had open — for up to the two hours a session lasts. Applies to administrators
too.

*Check it:* sign in as a test employer in one browser, deactivate that account
in the back office from another, then click anything in the first. It must land
on the login page saying the account is no longer active.

### 10. Not in the bundle at all

`.git/`, `plan/`, `docs/`, `tests/`, the CI3 backup, `deploy/` and your local
`.env` are excluded by the build's whitelist, and it fails rather than ships if
any of them appear. The root `.htaccess` also refuses `.sql`, `.zip`, `.bak`,
`.log`, `.md` and every dotfile except `.well-known/`, which is what keeps
AutoSSL renewing.

`deploy/migrate.php` and `deploy/diagnose.php` are the exception you upload by
hand. **Delete both the moment they have done their job** — their keys are in
the repository, so they are protection against a passer-by and nothing more.

### Still worth knowing

- **Auto-routing is on**, so every public controller method has a URL whether or
  not `Routes.php` names one. Access control is the `setup()` call at the top of
  each method, and it is there in all of them today. A new public method with no
  `setup()` is a page with no lock — that is the review to do on any controller
  change.
- **`/front/email_check` and `/sadmin/email_check`** answer whether an address is
  registered. Registration has to tell you that anyway, so it is not a leak so
  much as a list somebody could build slowly.
- **The old MD5 password hashes** are re-hashed to bcrypt on each user's next
  successful sign-in. Accounts that never sign in again keep theirs; a database
  dump is worth less over time, not immediately.

---

## What this release changes in the database

Everything below is applied by the migrations — there is nothing to type into
phpMyAdmin. It is listed so you know what to expect, and what to check
afterwards.

**Two new tables**

| Table | Columns | Holds |
|---|---|---|
| `additional_details` | `ad_id`, `ad_name`, `ad_status` | The Additional Details master, maintained at Main Master → Additional Details. `ad_status` defaults to 1, so a newly added entry is Active. |
| `testimonial` | `t_id`, `t_title`, `t_description`, `t_status` | The Testimonials master, maintained in the back office and drawn by the home page carousel. Two text columns because a testimonial is a heading plus the quote. `t_status` defaults to 1, so a newly added one is Active. |

**Four new columns**, all `VARCHAR(255) NULL`, all holding a comma-separated
list of ids in the same shape as the existing `p_skills` / `p_services`

| Column | Points at | Meaning |
|---|---|---|
| `post_job.p_additional_details` | `additional_details.ad_id` | Which additional details this shift offers |
| `store.s_skills` | `software_skills.ss_id` | The store's default software, copied onto a new shift |
| `store.s_services` | `store_service.st_id` | The store's default details |
| `store.s_additional_details` | `additional_details.ad_id` | The store's default additional details |

**One more new column**, which is not a list of ids like the four above

| Column | Holds |
|---|---|
| `post_job.p_email_to` | `VARCHAR(32) NOT NULL DEFAULT ''` — who the "your shift is live" e-mail goes to, as the words `owner`, `manager`, both, or neither. Ticked on the shift form. |

Empty is a real answer there and means *send it to the fallback address*, not
*send nothing* — so the migration sets every shift that already exists to
`owner`, which is exactly who it mailed before the column existed. Deploying
therefore changes nothing about a shift already on the site.

**Employer kinds are now two, not three**

"Owner (Individual Store)" is gone and "Owner (Multi Store)" is simply
**Owner**. The dropdowns are drawn from `Config\AppSettings` and post the
number the database stores, rather than a word:

| Code | Kind | `users.u_emp_role` |
|---|---|---|
| 1 | Owner | 1 |
| 2 | Manager | 2 |

No migration is needed for it. Those are the numbers `u_emp_role` already
held — the third kind was role 2 with no group, and no account on live is one
once `MakeEmployersMultiStore` has run. Public registration posts the same
codes plus 3 for Applicant (`registerTypes` in the same config file). URLs keep
readable slugs: `/sadmin/employer/owner`, `/sadmin/employer/manager`.

**Nothing at all for "Book an Applicant"**

The new booking section on the Add Shift form needs no migration. It writes the
row the Applications screen has always written — `stu_saved_applied_jobs` with
`sj_is_approved = 1` — and closes the shift by setting `post_job.p_approved = 3`.
Both are columns that already exist on live, which is the point: every screen,
export and report reads a booking made this way without knowing it was made any
differently.

One thing to be aware of if the host runs MySQL in **strict mode**:
`sj_applied_desc`, `sj_resubmit_comments` and `sj_rejected_comments` are
`NOT NULL` with no default, so an insert that leaves them out is refused. The
booking fills all three in, so it is safe either way — but if you ever see
*"Field 'sj_applied_desc' doesn't have a default value"* in `writable/logs/`, it
came from one of the older paths (saving a shift from the front end, or an
employer shortlisting somebody), not from this one.

**Nothing at all for the booked-shift changes either**

A booked shift can now be edited until the day it is worked, so the applicant on
it can be swapped or taken off when they drop out. Like the booking section it
writes only columns that already exist: the old applicant's
`stu_saved_applied_jobs` row goes to `sj_is_approved = 2` (the same code the
Applications screen uses for everybody who did not get the shift), the new one's
goes to 1, and clearing the booking altogether puts `post_job.p_approved` back
to 1. The applicant losing the shift is e-mailed from a new template,
`app/Views/emails/booking-cancelled.php` — a file, not a table.

**Nor for the Manage Email change**

The permissions page now offers each account only the e-mails it can actually
be sent — an applicant is no longer given a switch for "your shift is live" or
for the employer's half of a booking, and an employer is no longer given one for
the applicant's half or the day-before reminder. Which side each e-mail belongs
to is `audience` in `Config\AppSettings::$emailTypes`, so it is configuration,
not a column.

`users.u_email_blocked` keeps the shape it already has, and existing values stay
valid: a code in there for a type the account is no longer shown is carried
through untouched on every save, because a form changes only what it displayed.
Administrators are recipients of none of these, so their page says so instead of
showing six switches that do nothing.

**Nothing at all for the latest round of screen changes**

The Store and Location columns on the employer's All Shifts, the applicant's
details spelled out in Assigned To, the booking note being kept from a manager,
the phone layout of both portal lists, the Google Maps links on the shift page
and in the applicant's own list - none of it is a column. Every one of them
reads `post_job`, `store` and `stu_saved_applied_jobs` as they already are.
Upload the files; there is nothing to run behind them.

**Two data changes, no schema**

`MakeEmployersMultiStore` sets `u_emp_role = 1` on every `u_usertype = 1` row.
On live that is every employer that registered before the three account kinds
existed. Two consequences worth knowing before you run it:

- Every employer becomes eligible to hold more than one store, and appears
  under **Owners** in the User Type filters.
- The **Corporate Group** dropdown on registration lists all of them, because a
  corporate group *is* a multi-store owner. It listed only a handful before.

It does not reverse: the column does not record what a row held beforehand, so
`down()` deliberately does nothing.

`BackfillManagerStoreSnapshot` fills in the Managers that the back-office
employer form left blank. A Manager runs one of their corporate group's stores,
and that store's name, number, address, postcode, province and city are copied
onto their own `users` row — a dozen screens read those columns straight off the
login rather than joining the store: the employer list, the employer dropdown on
both shift forms, the booking e-mail, the profile page. Public registration
always did the copy; the back office did not, so a Manager added there showed as
an empty row everywhere an employer is named. Both forms do it now, and this
repairs the accounts made before they did.

It touches **blank columns only**, and only from the store the account is
already attached to, so a Manager whose details were corrected by hand keeps
them and one with no store is left alone. Like the above it does not reverse.

### What does *not* travel with the migrations

Migrations carry schema and the one role change — **not content you typed in
locally**. After deploying you have to enter these on the live site by hand:

| What | Where | Note |
|---|---|---|
| Additional Details entries | Main Master → Additional Details | The table arrives empty. Until you add entries, that tick-box group is an empty box on the shift and store forms — harmless, but it looks broken. |
| Testimonials | The Testimonials screen in the back office | The table arrives empty, so the home page carousel has nothing to show. Add a few before anyone looks at the live front page. |
| Each store's shift defaults | Manage Employers → Stores → Edit → Shift defaults, or the employer's own My Stores → Edit | Blank on every store, so a new shift starts empty exactly as it does today. Set them per store as you go — the employer can now do this themselves, which is usually who knows. |

---

## Migrations

**Seventeen** migrations exist. They are safe to run more than once — CodeIgniter
tracks which have been applied. A staging database imported from live will have
**none** of them: live is still on the CI3 code, so nothing has ever applied them
there either.

**With SSH:**

```
cd /path/to/site && php spark migrate
```

**Without SSH**, upload `deploy/migrate.php` next to `index.php`, open it, then
delete it:

```
https://reliefshifts.com/staging/migrate.php?key=b7f4c1e93a2d6058
```

It applies all seventeen over mysqli and writes the same rows into `migrations` that
spark would, so a later `php spark migrate` sees them as done instead of
re-running them into a "duplicate column" error. Every step checks the schema
first, so running it twice is safe — the second run prints `[SKIP]` for each and
changes nothing. It prints what it did, including counts: how many stores it
built, how many shifts it pointed at one, and anything it had to leave alone.

This is now clearly the right route without SSH. Six of the seventeen are no longer
a column you could reasonably type by hand: `AddStoreTable` creates a table *and*
runs two data statements, and two more repair data rather than change the schema.

### What each one costs you if it is skipped

| Migration | Skipping it means |
|---|---|
| `AddShiftDateColumn` | Every page 500s — the front page orders by `p_date_start`. |
| `AddAgencyCopyEmailSetting` | The admin Settings page breaks; the form posts a column that does not exist. |
| `AddShiftReminderSentAt` | `shifts:remind` errors, and with nowhere to record that it ran it would re-mail the same people nightly. |
| `AddStoreTable` | The loudest of all. Manage Stores, the employer's own store screens and the shift form all query `store`, so all of them 500 and **no shift can be posted at all**. |
| `AddUserParentId` | The employer type selector cannot save a Manager — a Manager is a role plus a pharmacy group, and the group has nowhere to go. |
| `ClarifyEmpRoleComment` | Nothing visible. It rewords a column comment. |
| `BackfillBlankUserLoginId` | Accounts an administrator created still cannot sign in. The complaint arrives as "it says my password is wrong". |
| `AddLocationAndWebsiteFields` | Saving any store, or any employer, fails on an unknown column — both forms now post a web address. |
| `AddUserStoreId` | Registering as a Manager fails on an unknown column, and any Manager already created is left unable to see a store or post a shift. It also points existing Managers at the store they already owned, so skipping it strands them. |
| `AddAdditionalDetailsTable` | Creates the `additional_details` master. Without it the new Additional Details screen 500s, and so do **the add-shift form and both store forms** — all three read the table to draw their tick boxes. |
| `AddJobAdditionalDetails` | Saving a shift fails on an unknown column: the shift form posts `p_additional_details`. |
| `AddStoreShiftDefaults` | Saving a store fails on three unknown columns, and the shift form's "fill from the store" call returns nothing. |
| `MakeEmployersMultiStore` | Nothing breaks, but every employer that registered before the kinds existed stays kind-less: absent from every User Type filter, and refused a second store. Data only — no schema change. |
| `AddUserEmailBlocked` | Manage Email 500s on an unknown column — and so does **every guarded send site**: registration, activation, posting a shift, both halves of a booking and the nightly reminder all read `u_email_blocked`. Unlike the other columns this one is on the *sending* path, so skipping it stops mail rather than breaking one screen. |
| `BackfillManagerStoreSnapshot` | Nothing breaks, but any Manager added from the back office before this release stays nameless: a blank row on the employer list, a blank entry in the employer dropdown on both shift forms, and no address in their booking e-mails. Data only — no schema change. |
| `AddShiftEmailRecipients` | Saving a shift from the back office fails on an unknown column — both shift forms post `p_email_to`. Add Shift and Edit Shift are the whole of the back office's shift handling, so skipping this one stops the administrator posting shifts at all. |
| `AddTestimonialTable` | Creates the `testimonial` master. Without it the new Testimonials screen in the back office 500s, and so does **the public home page**, which reads the table to draw its carousel. That second one is the whole site, not one screen — check it first if the front page is blank after deploying. |
| `AddUserAgreementDone` | Saving from any of the four back-office user forms — applicant add/edit, employer add/edit — fails on an unknown column: all four post `u_agreement_done` for the Agreement Done tick box. Those four are how the office creates and corrects accounts, so skipping it stops both. |

### Checking which have run

`deploy/diagnose.php` reads the migration files and compares them against the
`migrations` table, so it names the ones outstanding:

```
  [FAIL] migrations applied   5 NOT applied: AddStoreTable, AddUserParentId, ... - run them
```

**Hand-typing them in phpMyAdmin is no longer advised.** It leaves the
`migrations` table untouched, so a later `spark migrate` re-runs everything into
a "duplicate column" error, and `AddStoreTable`'s two data statements are the
part that matters most: without them the `store` table is empty, every employer
signs in to an empty store list, and every shift already on the site loses the
address it was posted against. If you have no way to run `migrate.php` at all,
the exact SQL for all seventeen is in `deploy/migrate.php` itself — read the
`$migrations` array, which is ordered and commented.

### The phpMyAdmin route, for a database that is only a few releases behind

`deploy/release-2026-08-20.sql` is that SQL, written out and ready to paste. It
carries the **last five** migrations — `AddUserEmailBlocked`,
`BackfillManagerStoreSnapshot`, `AddTestimonialTable`,
`AddShiftEmailRecipients` and `AddUserAgreementDone` — with the `migrations`
rows that go with them, so `spark migrate` afterwards sees them as done.

Use it only on a database that is already at `2026-08-14-160000` or later; the
file's first query prints where the database actually is, so run that on its own
if you are not sure. Anything further behind than that — a staging copy imported
from live, which has applied none of the seventeen — needs `migrate.php`, not
this.

It is safe to run twice: every step checks the schema first, and the one backfill
that is not naturally repeatable (`p_email_to`, which sets existing shifts to
`owner`) only runs on the pass that adds the column, so a second run cannot
rewrite a shift somebody deliberately saved as "tell neither side".

`release-2026-08-20.sql` replaces `release-2026-08-19.sql`, which is the same
file without the last section — so a database updated with that one is only
missing `users.u_agreement_done`. Run the 08-20 file whether or not either
earlier one was run; it is the 08-19 file with one guarded section added.

Both replace `release-2026-08-18.sql`, which carried only two of the five and
left `users.u_email_blocked` out — the one column on the *sending* path, so a
database updated with that file alone stops mailing rather than breaking a
screen.

---

## Scheduled jobs

Three jobs need a daily cron. All three are safe to run more than once a day.

| Job | With SSH | Without SSH |
|---|---|---|
| Mark passed shifts Closed | `php spark jobs:expire` | `GET /cron/expire_jobs` |
| Remind applicants booked tomorrow | `php spark shifts:remind` | `GET /cron/remind_shifts` |
| Back up the database and e-mail it | `php backup-database.php --quiet` | — |

Run the reminder in the **morning** — it e-mails people about tomorrow, so a
09:00 run reads sensibly and a 23:50 run does not. Run the backup in the small
hours, when a dump costs the site nothing.

The URL versions exist for hosts without command-line cron. They are reachable
by anyone who knows the address; neither exposes data or is destructive, but if
that matters, block them by IP in `.htaccess` and use the SSH form.

The backup has no URL version, deliberately. It e-mails the entire database, so
a URL that produced one would be the whole site to whoever found it — the script
refuses to run over the web at all, whatever the server does with the request.

---

## The nightly backup

`backup-database.php` dumps every table, zips the dump, e-mails the zip, and
deletes its own old ones. It stands on its own: nothing else in the application
calls it, and it reads the database credentials and the SMTP settings out of the
same `.env` the site uses, so there is no second copy of either to keep in step.

### Setting it up in cPanel

**Cron Jobs → Add New Cron Job.** Common Settings → *Once Per Day (0 0 \* \* \*)*,
or set the minute and hour by hand — 2am is a good time.

The command, for a **split** production deploy (`app/` in a private folder):

```
/usr/local/bin/php /home/USERNAME/pickashift_app/backup-database.php --quiet
```

and for a **flat** one, where the application sits in the document root:

```
/usr/local/bin/php /home/USERNAME/pickashift.ca/backup-database.php --quiet
```

Replace `USERNAME` with the cPanel account name. If `/usr/local/bin/php` is not
the PHP on that server, `which php` over SSH says what is, and cPanel's own
"Command" examples use whatever the host prefers.

`--quiet` prints nothing on a good run. cPanel e-mails you whatever a cron job
prints, so without it you get two messages every morning — the backup and a copy
of its output — and two a day is how people learn to ignore both. With it, cron
writes to you only when the job itself fails to start; the backup message
arrives either way, and says so when the backup could not be taken.

### Who it goes to

`backup.to` in `.env`. More than one address, separated by commas:

```
backup.to = 'pharmacyrelief@gmail.com, someone@example.com'
```

The first is the `To:`, the rest are bcc, so one administrator's address is not
shown to all the others every morning.

Two more settings sit with it. `backup.keep` is how many days of zips stay on
the server before the job deletes its own older ones — it only ever deletes
files it wrote itself, matched by name, because a backups folder is somewhere
people also put things by hand. `backup.maxAttachMB` is the most it will attach:
Gmail refuses a message over 25 MB and counts the base64 encoding, which adds
about a third, so above this the message says where the file is on the server
rather than carrying it.

### What it produces

`writable/backups/pickashift-<database>-<date>-<time>.zip`, holding one `.sql`
file. That folder is closed to the web by the `.htaccess` in `writable/`, and on
a split deploy it is not under any document root at all.

Restore it the usual way — unzip, then phpMyAdmin's Import, or over SSH:

```
mysql -u USER -p DATABASE < pickashift-....sql
```

### If mysqldump is not available

Most cPanel accounts have it. Where `exec()` is disabled or the binary is
missing, the script dumps in PHP instead and says so in its output — the same
tables, structure and data, streamed a row at a time so a large table does not
have to fit in memory. What the PHP dump cannot carry is stored routines,
triggers and events; this database has none, and if any are ever added the dump
says out loud that they were left out rather than quietly shipping without them.

To check which one a server will use before you need it:

```
php backup-database.php --php-dump --no-mail
```

That forces the PHP dump and sends nothing, so it proves the fallback works
while the good path is still there to fall back from.

### Other switches

| Switch | What it does |
|---|---|
| `--to=a@b.com,c@d.com` | send to these instead of `backup.to` |
| `--keep=30` | keep 30 days instead of what `.env` says |
| `--no-mail` | write the zip and send nothing |
| `--php-dump` | skip mysqldump, to test the fallback |
| `--quiet` | print only failures |

---

## Commands you run by hand

These are **not** cron jobs — putting `shifts:backfill-dates --write` on a
schedule would be actively wrong. Each is run once, by you, when it is called for.

| Command | When |
|---|---|
| `php spark shifts:backfill-dates` | After migrating, if any shift shows no date |
| `php spark email:preview <template> <shiftId>` | To read an e-mail without sending one |
| `php spark email:test you@gmail.com` | After filling in SMTP credentials |

`shifts:backfill-dates` finishes what `AddShiftDateColumn` starts. The migration
reads the `dd-mm-yyyy` text the date picker writes; a handful of older shifts
were typed by hand ("Feb 21, 2026") and it leaves those blank rather than
guessing. Run it with no flags first — it only reports. Add `--write` when the
list looks right. Anything with no year in it is listed separately and left
alone; fix those by editing the shift.

`email:preview` renders a real message for a real shift to the screen and sends
nothing, so you can check that a booking or reminder carries the right store
address and directions link without booking a shift on live data to find out:

```
php spark email:preview booking-applicant 412 > booking.html
```

Templates it knows: `welcome`, `account-approved`, `reset-password`,
`shift-posted`, `booking-applicant`, `booking-employer`, `shift-reminder`,
`booking-cancelled`. The first three describe an account and need no shift id.

---

## After deploying, verify

**Start with the six checks in "What is locked down"** — the padlock, a hidden
`csrf_token` field in the sign-in page's source, a 403 on `/uploads/<something>.php`,
a 403 on `/.env`, `Secure` on the session cookie, and a `Strict-Transport-Security`
header. Each is one look, and each one fails silently if it fails at all.

```
curl -sI https://pickashift.ca/ | grep -iE 'strict|x-frame|x-content'
curl -s -o /dev/null -w '%{http_code}\n' https://pickashift.ca/.env      # 403
curl -s -o /dev/null -w '%{http_code}\n' https://pickashift.ca/writable/ # 403
php spark email:test you@gmail.com      # SMTP works and DKIM signs
```

Then, in a browser:

- Front page lists shifts, soonest first, and the date-range box narrows them.
  Clicking anywhere on a shift card opens it, not only the title.
- Sign in as admin → dashboard shows the four "new" tabs.
- Admin → Reports loads and the CSV export downloads.
- A shift whose date has passed reads **Closed (Expired)** and the employer
  has no Edit or Delete button on it.
- Post a shift: Software and Details are tick boxes, not Ctrl-click lists.
- Admin → Manage Employers splits into **Owners** and **Managers**, each badged
  with how many are still deactivated. After `MakeEmployersMultiStore` every
  employer carried over from live sits under **Owners** — before that migration
  they sat under neither, because they registered before the kinds existed.
- Add an employer from the back office: Employer Type is required, and choosing
  Manager makes Pharmacy Group required too. Saving a Manager with no group must
  be refused, not saved as an independent store.
- **Sign in as an account you just created in the back office.** It must let you
  in. That is the one item on this list you cannot check by looking at a page,
  and it is what the login-id backfill repaired.
- Admin → Employers → Stores lists every location. Give a multi-store owner a
  second one. Then try to give a Manager a second — it
  must refuse. Try to delete a store that is on a shift — it must refuse that
  too, and offer deactivating instead.
- On a store row, the green **Map pin** badge opens Google Maps. A grey **Map
  search** badge means no link has been pasted and it is searching the address
  instead — a fallback, not a fault.
- Open a shift on the front page **as the applicant booked on it**: the address
  carries a **Get directions** link. Before that booking is confirmed there is
  no address on the page to carry one — see the three tiers further down. Check
  the same link reaches the booking e-mail with `email:preview` rather than by
  booking a real shift.

New in this release:

- Main Master → **Additional Details**: add an entry, rename it, change its
  status, delete it. A new entry must land **Active**, not Deactive.
- Admin → Stores → Edit: the **Shift defaults** block shows Software, Details
  and Additional Details. Tick some and press Update Store, then reopen — they
  must come back ticked. *If Update appears to do nothing, see the note below on
  province and city.*
- Post a shift: the form asks for **Employer (Group)** and **Store (Location)**
  and nothing else about who the shift is for. Pick the company on the left and
  the dropdown beside it holds only that company's stores. Choosing a store
  fills all three tick-box groups from it, and the shift is saved for whoever
  owns it — the User Type and Choose Employer dropdowns that used to sit in
  front of this are still gone, because a store belongs to one employer and
  naming it names them. The group dropdown does not change that: nothing is
  posted for it, it only narrows the list.
- Untick something on that shift and save. The **store's** defaults must be
  unchanged — the shift keeps its own copy from the moment it is saved.
- Post a shift with **Book an Applicant** filled in: pick somebody from the
  dropdown, type a message, save. The shift must land in the list reading
  **Booked**, Admin → Applications → Booked must show it, and two e-mails must
  go out — one to the applicant carrying your message, one to the employer. Then
  post a second shift leaving that section on *"Nobody yet"*: it must behave
  exactly as before, with the Shift Approval dropdown still deciding its status.
- On staging, do that **only after `staging-scrub.sql` has run**. Booking sends
  both e-mails the instant you press Save — there is no confirmation step, and
  on an unscrubbed copy of live they go to a real pharmacist and a real pharmacy.
- Admin → Applications → open one: both phone numbers are WhatsApp links, and
  the number itself is the link. Check the address bar shows a country code in
  front of the digits (`...send?phone=1905...`) — without it the chat opens on
  nobody. Set `phoneCountryCode` in `app/Config/AppSettings.php` to `91` if you
  are testing with Indian numbers; live should stay `1`.
- `/sadmin/login` and `/front/login` are compact cards now. Sign in through both.
- The public footer is the social icons, the address and the copyright line —
  the four link columns are gone by request. Terms and Privacy are still linked
  above the Register button and in the top navigation.

- Admin → Shifts lists the **newest posting first** rather than the latest shift
  date. Clicking the Shift Date header still sorts chronologically, and a shift
  with an unreadable date sorts to the end of that.
- **Post a shift as the employer**, from their own side rather than the back
  office. The form now has the same three tick-box groups the admin one has —
  the third is **Additional Details** — and the free-text box under them is
  **Additional details for agency**, named apart from the group above it.
- On that same form, click **Shift Date**: a calendar must open, and clicking a
  day must fill the box. Click **Shift Time**: a time picker must open. Both
  were dead on the employer side, and so was the formatting toolbar on the
  editor, which showed as a bare second box under the label. One cause for all
  three: `popper.min.js` was requested from a path that does not exist,
  Bootstrap's tooltip throws without it, and the throw took out everything
  initialised after it. If they are dead again after this deploy, look in the
  browser console for *"Bootstrap tooltips require Popper.js"* before anything
  else, and check `assets/front/plugins/popper/umd/popper.min.js` arrived.
- **Register a Manager** for a store that already has one: it must be refused
  with *"This store already has a manager registered"*, and on the store
  dropdown that branch reads *"- already has a manager"* and cannot be chosen.
  An account still waiting for approval holds its store, which is the point:
  two people cannot claim the same branch on the same afternoon and both be
  approved later. The back office is exempt — an administrator still needs to
  be able to move a manager onto a branch whose manager they are about to
  remove.
- **Who may manage a shift**, from the employer's own screens. A manager edits
  and deletes what they posted, and whatever stands against the branch they run;
  their owner can do the same to those, and they now appear in the owner's **All
  Shifts**; a manager of another branch, and any other employer, is turned away.
  Check the negative case by hand: paste another employer's shift id into
  `/employer/edit_job/…` and `/employer/delete_job/…`. Both must bounce you to
  All Shifts having changed nothing. Before this release both worked, and saving
  moved the shift — its owner, its store and its address — to whoever opened it.
  Editing also no longer resets a shift's posted date, which the front page reads
  to decide what counts as recently posted.
- Three routes that took an id or a form and trusted it are now checked. Nothing
  to click for these — they are listed so you know what changed underneath:
  the employer shift form and the employer profile form both write only the
  fields they display (an employer could otherwise post `p_approved` and approve
  their own shift, or post `u_emp_role` and `u_store_id` and appoint themselves
  manager of anybody's branch, which would have handed them that branch's
  shifts), and `employer/ajax_shortlist` now checks the shift is one the caller
  manages and the person invited is a real applicant.
- Employer → **Applications** now lists the bookings for every shift the login
  manages, so an owner sees the bookings on their managers' shifts. It was
  scoped to the login's own id, which left an owner able to open a manager's
  shift and its applicant list but not the booking made on it.

Newest changes — **no migration for any of the four**, so there is nothing extra
to run for them:

- **Every mobile number field takes ten digits and nothing else**, on the public
  site and in the back office alike. Registration, both profile pages, the store
  forms and the employer/applicant forms in the admin all cap at ten and refuse
  letters, spaces and brackets as you type; pasting `(905) 304-7303` leaves
  `9053047303`. The server re-checks, so this holds against a hand-made request
  as well as a typed one. Check one field on each side — the front registration
  form and Manage Employers → Edit will do.
- **Website boxes no longer demand `http://`.** Typing `example.com` is accepted
  and stored as `https://example.com`; that is what the field always did on
  save, but the browser used to refuse to submit until you typed a scheme.
  Applies to the employer's website on registration and in the back office, and
  to a store's website and its Google Maps link. Addresses that run code rather
  than navigate are still dropped — paste `javascript:alert(1)` into a map link
  and it must save as blank.
- **An employer can set their own store's shift defaults.** My Stores → Add or
  Edit now carries the same Software / Details / Additional Details block the
  back office has. Tick some, save, then post a shift against that store from
  the employer's side: the three groups must arrive already ticked. Do the same
  as a **manager** of that store — they own no store row of their own, and
  getting the branch's defaults is the point of the change.
- **Store (Location) on the admin shift form is two dropdowns**, employer group
  then store. See the bullet above about posting a shift.

- **Details is no longer a required tick-box group** on any of the four shift
  forms — the employer's Post and Edit, and the back office's. Software is still
  required; Additional Details always was optional. Post a shift with Details
  left empty on both sides: it must save.
- **Testimonials** are maintained in the back office and drawn by the home page
  carousel. **This is the one item on this list with a migration** — see the
  `testimonial` table above. Add an entry, check it appears on the front page,
  then deactivate it and check it goes.
- **Assigned To** on the employer's All Shifts names the applicant booked on
  each shift. It reads `stu_saved_applied_jobs` on `sj_is_approved = 1` alone,
  so an applicant the administrator placed by hand (whose row is written at
  `sj_status = 6`) is named too — before, only a self-applied booking was, and a
  shift the same screen reported as Booked showed nobody against it. No schema.
- **The employer's own store list names whoever manages each branch**, so an
  owner can see which of their stores has somebody on it. No schema.
- **The back-office shift form asks who to tell.** Add Shift and Edit Shift now
  carry **Send shift e-mail to** beside Shift Approval: *Owner*, *Manager*,
  both, or neither. **This is the second item with a migration** — see
  `post_job.p_email_to` above. Add a shift with only *Owner* ticked, reopen it,
  and the boxes must come back as you left them; untick both, save, reopen, and
  they must still be clear. A new shift opens with both ticked.
  - Sent when the shift **goes Live** — saved as Live, or approved later — so
    editing a shift that is already live sends nothing. A shift saved straight
    to Live from the back office now announces itself, which it did not before
    this release: only approving one on the edit screen did.
  - **Neither ticked sends to `AppSettings::$shiftEmailFallback`**
    (`team@pickashift.ca`), not to nobody. Same if the ticked side cannot be
    reached — a store with no manager account on it, or a recipient who turned
    this e-mail off in Manage Email. `writable/logs/` names which it was.
  - The applicant's booking e-mail is untouched by any of this and still goes
    out on every booking.
- **A shift page now tells three different readers three different amounts.**
  This is the change most worth checking by hand, because two of the three
  tiers are about what must *not* be on the page:

  | Reader | Sees |
  |---|---|
  | Signed out | Role, **town**, date, time, Software, Details. Rate reads *To be disclosed*. |
  | Signed in | The above **plus the hourly rate** (`CAD$ n/hour`) and the pharmacy's Additional details. |
  | Booking confirmed | The above **plus the pharmacy**: store name, street address, Get directions, *Where to find it*, its website and its phone. |

  Open a shift from Browse Shifts signed out, then signed in, then as somebody
  whose booking on that shift is approved. At the first two tiers the store
  name, address, Get directions link, location label, website and phone must
  **not** be on the page — check the page **source**, not only the screen: a
  field hidden in CSS is still a field that has been published. The rate shown
  is `p_ac_hourly_rate`, what the applicant is paid; `p_hourly_rate`, what the
  employer is billed, must never appear. A shift posted from the employer's own
  form has no applicant rate — that form asks for one number — so those keep
  reading *To be disclosed* even signed in, which is correct, not a fault.
  No schema.
- **The shift page is laid out in cards**, like the rest of the front end, with
  the facts in a two-column grid rather than a column of large icon discs. The
  sidebar is now a plain **Related shifts** list: it was rendering *empty* on
  live, because nothing initialises owl carousel any more and its stylesheet
  keeps an uninitialised carousel hidden. It also lists at most six other
  shifts and no longer offers the shift you are reading as related to itself.
  Look at it on a phone as well as a desktop. No schema.
- **The employer area now wears the home page's colours** — the lavender-grey
  ground, white cards, the purple-to-orange gradient on the current menu item
  and the section headings, and Plus Jakarta Sans. Post New Shift is also laid
  out in three numbered sections and works on a phone; the side menu collapses
  behind a Menu button below 768px. Nothing to migrate, but it is the most
  visible change in the release, so look at it on a phone as well as a desktop.

This batch - **no migration for any of it**, so uploading the files is the whole
of it:

- **All Shifts, on the employer's side, reads as a list of shifts again.** City
  and province are one **Location** column; a **Store** column names the branch
  each shift was raised for, which is the point of it for an owner with more
  than one; and the applicant booked on a shift is written into **Assigned To** -
  name, licence number, licence province and what was requested - where a View
  button used to open a panel for the same four lines. Check an owner with
  several branches: every row must name one.
- **The note on a booking is the owner's.** Open a shift's details panel as a
  **manager**: there must be no Message box, and the note must not be in the
  page source either - it usually states what the shift was agreed at, which is
  the group's business for the same reason the rate is. As the owner it is there
  exactly as before.
- **Both portal lists work on a phone.** All Shifts and My Stores fold the
  columns that do not fit into a panel under each row, and the search box takes
  its own line instead of hanging off the right of the screen. My Stores had no
  fold at all before - nine columns of address and contact detail simply ran off
  the side. At phone width, tap the **+** on a store row: its number, address,
  city, province, phone, manager and status must all be in the panel.
- **A Google Maps link on the shift page for anyone signed in.** It searches the
  town the page shows them. The street address and the store's pasted pin still
  wait for a confirmed booking, where the link reads **Get directions** as it
  did. Signed out there is no link at all - check the page source at that tier,
  not only the screen.
- **The applicant's own Applied Shifts names the branch and how to reach it.**
  Open a booked row's **Employer/Shift Details**: Store Name, Store No. and
  Store Address now come from the store the shift is at, not from the employer's
  login row - for a multi-store owner that was their head office, the same
  mistake the booking e-mail was fixed for - and a **Get directions on Google
  Maps** link sits under the address. Check it against a multi-store owner
  specifically; a single-store employer looks the same either way.
- **The support number sits under the side menu, on all three kinds of account.**
  Sign in as an owner, as one of their managers and as a pharmacist: under
  Logout each must show *Need help? +1 (905) 304-7303*, and clicking it must
  open WhatsApp with a country code in front of the digits
  (`web.whatsapp.com/send?phone=1905...`) - without it the chat opens on nobody.
  Check it on a phone too: it sits outside the collapsing menu, so it is there
  with the menu shut. The number is `supportPhone` in
  `app/Config/AppSettings.php` - one edit changes both sides.

**Existing phone numbers are not rewritten by deploying.** Nothing runs over the
`users` or `store` tables, so a number already stored as `+1 905-304-7303` stays
exactly as it is and every screen keeps showing it. It is cleaned to ten digits
the next time somebody opens that record and saves it — the form loads the value
already trimmed, so what is on screen is what will be stored. Worth knowing
before it is reported as "the system changed my number": it changes on save, by
the person saving, and never behind their back. The WhatsApp links are unaffected
either way — they add the country code from `phoneCountryCode` themselves.

**A store that will not save.** `s_city` is a required dropdown filled by ajax
from the chosen province. If a store's saved province and city do not match, the
city list comes up empty and the browser silently refuses to submit — no error,
the Update button just does nothing. Fix it by reselecting the province, then
the city. Worth knowing before it is reported as "stores are broken".

If a page 500s, the cause is in `writable/logs/`. With `CI_ENVIRONMENT =
production` the browser shows a generic error page and never a stack trace,
which is correct — read the log.

---

## Rolling back

Keep the previous bundle. A roll-back is: upload the old bundle over the new
one, leaving `uploads/` and `writable/` alone. Migrations are the exception —
they are not reversed by re-uploading files. `php spark migrate:rollback` steps
back one batch if you have SSH.
