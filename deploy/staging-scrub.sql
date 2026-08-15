-- Run this on the STAGING database ONLY, straight after importing a copy of
-- the live data. Never run it on `pickashift`.
--
-- A staging copy holds every pharmacist's and pharmacy's real e-mail address.
-- The moment SMTP credentials are filled in, an approval test e-mails real
-- people about shifts that do not exist. This redirects every outbound address
-- to an unroutable one so that cannot happen.
--
-- Sign-in still works: the login field is `u_userid`, which is left untouched.
-- Only `u_email` - the address the mailer actually sends to - is changed.

-- Safety catch: stops the script unless the database name ends in "n"
-- (pickashiftn) or contains "staging". Neither can match the live `pickashift`.
--
-- Two things were wrong with the version this replaces, and together they meant
-- it had never once run:
--
--   1. It only accepted a name ending in "n", but deploy/templates/env.staging
--      ships database.default.database = pickashift_staging, which ends in "g".
--
--   2. Far worse, it aborted on EVERY database, including pickashiftn. It put a
--      missing table name in the false branch of IF() as the error message, but
--      MySQL resolves table and column names when it prepares a statement,
--      before any branch is chosen - so the error fired whatever the answer was.
--      The scrub therefore stopped at this line every time it was run, and any
--      staging database restored from live still holds real e-mail addresses.
--
-- The guard below aborts at run time instead of prepare time: a subquery over no
-- table is evaluated only if the branch is taken, and one returning two rows is
-- an error (ERROR 1242, "Subquery returns more than 1 row"). The SELECT above it
-- prints the reason in plain words first, because 1242 on its own explains
-- nothing to whoever is reading.
SET @db   := DATABASE();
SET @safe := (@db LIKE '%n' OR @db LIKE '%staging%');

SELECT IF(@safe,
          CONCAT('Scrubbing ', @db),
          CONCAT('REFUSING TO SCRUB: ', @db, ' is not a staging database. Nothing has been changed.')
       ) AS guard;

SET @stop := IF(@safe, 1, (SELECT 1 UNION SELECT 2));

-- Every user's outbound address becomes unroutable. `.invalid` is reserved by
-- RFC 2606 and can never resolve, so nothing can leak even by accident.
UPDATE users
   SET u_email = CONCAT('staging+user', u_id, '@example.invalid')
 WHERE u_email IS NOT NULL;

-- Phone numbers, for the same reason.
UPDATE users
   SET u_phone = CONCAT('000000', u_id)
 WHERE u_phone IS NOT NULL AND u_phone <> '';

-- The `store` table did not exist when this was written, and every row in it is
-- a real pharmacy. `s_phone` is the number the booking and the day-before
-- reminder e-mails print as "Store phone", and the one the shift page shows to
-- a booked applicant - so a staging booking test hands a tester a live pharmacy
-- line to ring. Scrubbing users.u_phone above does not cover it: that only
-- reaches the fallback store shiftStore() builds for a shift with no store row.
--
-- Skipped rather than fatal when `store` is absent, which means the migrations
-- have not run yet. A plain UPDATE would abort the script here - after the
-- e-mail addresses above but BEFORE the settings rows below, leaving the agency
-- copy address real, which is the single most dangerous row in the file to miss.
SET @has_store := (SELECT COUNT(*) FROM information_schema.tables
                    WHERE table_schema = DATABASE() AND table_name = 'store');

SET @sql := IF(@has_store,
               'UPDATE store SET s_phone = CONCAT(''000000'', s_id) WHERE s_phone <> ''''',
               'DO 0');

PREPARE scrub_store FROM @sql;
EXECUTE scrub_store;
DEALLOCATE PREPARE scrub_store;

SELECT IF(@has_store, 'store phones scrubbed',
          'NOTE: no `store` table yet - run the migrations, then run this again') AS store_sweep;

-- How many store numbers are still real, read into a variable rather than left
-- as a subquery in the confirmation below. Naming `store` there directly would
-- abort the whole script when the table is absent, for the same prepare-time
-- reason the old safety catch never worked.
SET @sql := IF(@has_store,
               'SELECT COUNT(*) INTO @store_left FROM store WHERE s_phone <> '''' AND s_phone NOT LIKE ''000000%''',
               'SET @store_left = NULL');

PREPARE count_store FROM @sql;
EXECUTE count_store;
DEALLOCATE PREPARE count_store;

-- Deliberately NOT scrubbed: store.s_website, store.s_map_url, users.u_website.
-- None is a way to reach a person - they are a pharmacy's public home page and a
-- Google Maps pin, already published to the world. Blanking them would cost
-- staging the ability to check the "Get directions" link and the store-site ->
-- owner-site fallback, and would remove no risk at all.

-- Site + agency-copy addresses. Put YOUR OWN address here if you want to
-- receive staging mail and actually see what the templates look like.
UPDATE settings
   SET s_email             = 'staging@example.invalid',
       s_agency_copy_email = 'staging@example.invalid'
 WHERE s_id = 1;

-- Clear any reminder stamps so the day-before reminder can be tested from a
-- clean slate on staging.
UPDATE stu_saved_applied_jobs SET sj_reminder_sent_at = NULL;

-- Confirmation.
-- Both "still real" columns must read 0. A non-zero store figure is the one to
-- watch: it means the store sweep above was added after this database was last
-- scrubbed, and real pharmacy numbers are still sitting in it.
SELECT DATABASE()                                     AS scrubbed_database,
       (SELECT COUNT(*) FROM users)                   AS users,
       (SELECT COUNT(*) FROM users
         WHERE u_email NOT LIKE '%@example.invalid')  AS addresses_still_real,
       COALESCE(@store_left, 'no store table yet')    AS store_phones_still_real;
