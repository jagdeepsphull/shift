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

## Migrations

Three migrations exist. They are safe to run more than once — CodeIgniter tracks
which have been applied.

**With SSH:**

```
cd /path/to/site && php spark migrate
```

**Without SSH**, upload `deploy/migrate.php` next to `index.php`, open it, then
delete it:

```
https://reliefshifts.com/staging/migrate.php?key=b7f4c1e93a2d6058
```

It applies the same three migrations over mysqli and writes the same rows into
`migrations` that spark would, so a later `php spark migrate` sees them as done
instead of re-running them into a "duplicate column" error. Every step checks the
schema first, so running it twice is safe.

**Or by hand** in phpMyAdmin. Check first whether they have already been applied
— if the `migrations` table already lists them, stop. Note that doing it this way
leaves the `migrations` table untouched, so a later `spark migrate` *will* try to
re-run them.

```sql
-- 2026-08-05-100000 AddAgencyCopyEmailSetting
ALTER TABLE settings
  ADD COLUMN s_agency_copy_email VARCHAR(150) NULL
  COMMENT 'Copied on booking e-mails. Blank switches the copy off.' AFTER s_email;
UPDATE settings SET s_agency_copy_email = 'info@reliefshifts.com' WHERE s_id = 1;

-- 2026-08-06-090000 AddShiftReminderSentAt
ALTER TABLE stu_saved_applied_jobs
  ADD COLUMN sj_reminder_sent_at DATETIME NULL
  COMMENT 'When the day-before shift reminder was e-mailed. NULL = not sent.'
  AFTER sj_accept_date;
```

`2026-08-04-120000 AddShiftDateColumn` is the one that is easiest to miss, and
missing it is the loudest: the front page orders by `p_date_start`, so every page
500s with `Unknown column 'p_date_start' in 'ORDER BY'` until it runs. Its SQL is
longer than the other two (add column, backfill from the `p_dates` text, index),
which is why `migrate.php` above is the better route than hand-typing it.

```sql
-- 2026-08-04-120000 AddShiftDateColumn
ALTER TABLE post_job
  ADD COLUMN p_date_start DATE NULL
  COMMENT 'Parsed from p_dates (dd-mm-yyyy). Sortable shift date.' AFTER p_dates;

UPDATE post_job
   SET p_date_start = STR_TO_DATE(p_dates, '%d-%m-%Y')
 WHERE p_dates IS NOT NULL AND p_dates <> ''
   AND STR_TO_DATE(p_dates, '%d-%m-%Y') IS NOT NULL;

CREATE INDEX idx_post_job_date_start ON post_job (p_date_start);
```

A staging database imported from live will **not** have any of the three: live is
still on the CI3 code, so nothing has ever applied them there either.

Skipping the first one breaks the admin Settings page — the form posts a column
that would not exist yet.

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

## After deploying, verify

```
php spark email:test you@gmail.com      # SMTP works and DKIM signs
```

Then, in a browser:

- Front page lists shifts, soonest first.
- Sign in as admin → dashboard shows the four "new" tabs.
- Admin → Reports loads and the CSV export downloads.
- A shift whose date has passed reads **Inactive (Expired)** and the employer
  has no Edit or Delete button on it.
- Post a shift: Software and Details are tick boxes, not Ctrl-click lists.

If a page 500s, the cause is in `writable/logs/`. With `CI_ENVIRONMENT =
production` the browser shows a generic error page and never a stack trace,
which is correct — read the log.

---

## Rolling back

Keep the previous bundle. A roll-back is: upload the old bundle over the new
one, leaving `uploads/` and `writable/` alone. Migrations are the exception —
they are not reversed by re-uploading files. `php spark migrate:rollback` steps
back one batch if you have SSH.
