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

## Production → site root

Once staging is approved:

1. `php deploy/build.php production`
2. Fill in `deploy/build/production/.env`.
3. **Back up first** — database export *and* a copy of the current files.
4. Upload the contents to the site root.
5. Run the migrations.
6. Check the site, then check `writable/logs/` for anything new.

Do **not** copy the `/staging/` folder to the root. Its `.env` and `.htaccess`
are built for a subfolder and would break the live site. Build the production
bundle instead — that is what the second command is for.

### Two folders to leave alone

`uploads/` and `writable/` ship as empty skeletons so a first deploy has
somewhere to write. On an existing site they already hold real data:

- **`uploads/`** holds user-uploaded files — CVs, logos. Overwriting or deleting
  it destroys them, and nothing else has a copy.
- **`writable/`** holds live sessions. Replacing it signs everyone out.

Upload *around* them, or exclude both from the transfer after the first deploy.

---

## What this release changes in the database

Everything below is applied by the migrations — there is nothing to type into
phpMyAdmin. It is listed so you know what to expect, and what to check
afterwards.

**One new table**

| Table | Columns | Holds |
|---|---|---|
| `additional_details` | `ad_id`, `ad_name`, `ad_status` | The Additional Details master, maintained at Main Master → Additional Details. `ad_status` defaults to 1, so a newly added entry is Active. |

**Four new columns**, all `VARCHAR(255) NULL`, all holding a comma-separated
list of ids in the same shape as the existing `p_skills` / `p_services`

| Column | Points at | Meaning |
|---|---|---|
| `post_job.p_additional_details` | `additional_details.ad_id` | Which additional details this shift offers |
| `store.s_skills` | `software_skills.ss_id` | The store's default software, copied onto a new shift |
| `store.s_services` | `store_service.st_id` | The store's default details |
| `store.s_additional_details` | `additional_details.ad_id` | The store's default additional details |

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
| Each store's shift defaults | Manage Employers → Stores → Edit → Shift defaults | Blank on every store, so a new shift starts empty exactly as it does today. Set them per store as you go. |

---

## Migrations

**Fifteen** migrations exist. They are safe to run more than once — CodeIgniter
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

It applies all fifteen over mysqli and writes the same rows into `migrations` that
spark would, so a later `php spark migrate` sees them as done instead of
re-running them into a "duplicate column" error. Every step checks the schema
first, so running it twice is safe — the second run prints `[SKIP]` for each and
changes nothing. It prints what it did, including counts: how many stores it
built, how many shifts it pointed at one, and anything it had to leave alone.

This is now clearly the right route without SSH. Six of the fifteen are no longer
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
the exact SQL for all fifteen is in `deploy/migrate.php` itself — read the
`$migrations` array, which is ordered and commented.

---

## Scheduled jobs

Two jobs need a daily cron. Both are safe to run more than once a day.

| Job | With SSH | Without SSH |
|---|---|---|
| Mark passed shifts Inactive | `php spark jobs:expire` | `GET /cron/expire_jobs` |
| Remind applicants booked tomorrow | `php spark shifts:remind` | `GET /cron/remind_shifts` |

Run the reminder in the **morning** — it e-mails people about tomorrow, so a
09:00 run reads sensibly and a 23:50 run does not.

The URL versions exist for hosts without command-line cron. They are reachable
by anyone who knows the address; neither exposes data or is destructive, but if
that matters, block them by IP in `.htaccess` and use the SSH form.

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

```
php spark email:test you@gmail.com      # SMTP works and DKIM signs
```

Then, in a browser:

- Front page lists shifts, soonest first, and the date-range box narrows them.
  Clicking anywhere on a shift card opens it, not only the title.
- Sign in as admin → dashboard shows the four "new" tabs.
- Admin → Reports loads and the CSV export downloads.
- A shift whose date has passed reads **Inactive (Expired)** and the employer
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
- Open a shift on the front page: the address carries a **Get directions** link.
  Check the same link reaches the booking e-mail with `email:preview` rather than
  by booking a real shift.

New in this release:

- Main Master → **Additional Details**: add an entry, rename it, change its
  status, delete it. A new entry must land **Active**, not Deactive.
- Admin → Stores → Edit: the **Shift defaults** block shows Software, Details
  and Additional Details. Tick some and press Update Store, then reopen — they
  must come back ticked. *If Update appears to do nothing, see the note below on
  province and city.*
- Post a shift: **User Type** sits before Choose Employer and narrows it. Pick an
  employer with exactly one store — the store selects itself and all three
  tick-box groups fill from it. Pick one with several stores — the groups clear
  and wait until you choose a store, which is deliberate: with two stores there
  is no single right answer.
- Untick something on that shift and save. The **store's** defaults must be
  unchanged — the shift keeps its own copy from the moment it is saved.
- Post a shift with **Book an Applicant** filled in: pick somebody from the
  dropdown, type a message, save. The shift must land in the list reading
  **Closed**, Admin → Applications → Booked must show it, and two e-mails must
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
