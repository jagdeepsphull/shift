-- ---------------------------------------------------------------------------
-- PickAShift - database update for the 2026-08-19 release
--
-- FOUR migrations, everything since `release-2026-08-14.sql`:
--
--   2026-08-15-090000  users.u_email_blocked      column
--   2026-08-15-120000  manager store snapshot     data backfill, no schema change
--   2026-08-17-090000  testimonial                new table
--   2026-08-18-100000  post_job.p_email_to        column
--
-- This SUPERSEDES `release-2026-08-19.sql`'s predecessor, release-2026-08-18.sql:
-- that file carried the last two of the four and not the first two, so a
-- database updated with it alone is missing `users.u_email_blocked` and the
-- Manage Email screen has nowhere to store an opt-out. Run this one instead.
-- Running it after that one is fine - see "safe to run twice" below.
--
-- The 2026-08-19 release itself - the pharmacist's screens on the site theme,
-- the hourly rate hidden from managers, the read-only shift panel - is code
-- only. It adds no table, column, index or default. The four above are the
-- backlog this file exists to clear.
--
-- Prefer `php spark migrate` (with SSH) or `deploy/migrate.php` (without). Use
-- this file only when neither is available: both of those write the bookkeeping
-- rows for you and check the schema first, which is most of what is below.
--
-- SAFE TO RUN TWICE. Every step is guarded - the CREATE by IF NOT EXISTS, each
-- ADD COLUMN by a look in information_schema, each bookkeeping INSERT by a NOT
-- EXISTS test. The one backfill that would not be safe on its own, the
-- `p_email_to` default, is gated on whether this run is the one that added the
-- column: a second run must not turn a shift somebody deliberately saved as
-- "tell neither side" back into "tell the owner".
--
-- Run against the SITE database (the one named in .env), not information_schema.
-- ---------------------------------------------------------------------------


-- 0 ------------------------------------------------------- where are you? --
--
-- Run this first on its own if you are not sure the database is at the
-- 2026-08-14 release. Anything at or past 2026-08-18-100000 has had part of
-- this file already, which is fine; anything below 2026-08-14-160000 is behind
-- release-2026-08-14.sql and should have that one first.

SELECT MAX(`version`) AS highest_migration_applied FROM `migrations`;


-- 1 ------------------------------------------------- which e-mails to skip --
--
-- The Manage Email screen, per user. Holds what is BLOCKED rather than what is
-- allowed, which is why the default is blank: every account that exists on the
-- day this runs keeps receiving everything, a new registration starts the same
-- way with no code writing a default, and an e-mail type added to the config
-- next year is on for everybody at once instead of silently off.
--
-- MySQL has no ADD COLUMN IF NOT EXISTS before 8.0.29, so this is a prepared
-- statement that becomes a no-op when the column is already there.

SET @add_blocked := (
  SELECT IF(COUNT(*) > 0,
            'SELECT ''u_email_blocked already there'' AS note',
            'ALTER TABLE `users`
               ADD COLUMN `u_email_blocked` VARCHAR(100) NOT NULL DEFAULT ''''
               COMMENT ''Comma separated AppSettings::$emailTypes codes this user must NOT receive. Blank = everything.''
               AFTER `u_email`')
    FROM `information_schema`.`COLUMNS`
   WHERE `TABLE_SCHEMA` = DATABASE()
     AND `TABLE_NAME`   = 'users'
     AND `COLUMN_NAME`  = 'u_email_blocked'
);

PREPARE stmt FROM @add_blocked;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- 2 -------------------------------------------------------- the bookkeeping --
--
-- CodeIgniter records what it has applied in `migrations`. Changing the schema
-- by hand and leaving this out is the trap: the next `php spark migrate` sees
-- the migration as outstanding, runs it, and stops on "column already exists" -
-- taking every later migration with it.
--
-- `batch` is one past the highest already there, which is what spark does for a
-- run of its own. `group` and `namespace` are quoted because both are reserved
-- words. The sub-select is why this is safe to run twice.

INSERT INTO `migrations` (`version`, `class`, `group`, `namespace`, `time`, `batch`)
SELECT
  '2026-08-15-090000',
  'App\\Database\\Migrations\\AddUserEmailBlocked',
  'default',
  'App',
  UNIX_TIMESTAMP(),
  COALESCE((SELECT MAX(m.`batch`) FROM `migrations` m), 0) + 1
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations` m2 WHERE m2.`version` = '2026-08-15-090000'
);


-- 3 ------------------------------------------- managers without a store name --
--
-- No schema change - this repairs rows.
--
-- A manager runs one of their corporate group's stores, and the store's name,
-- number and address are copied onto their own `users` row: a dozen screens,
-- exports and e-mails read those columns straight off the login rather than
-- joining the store. Public registration always did the copy. The back-office
-- employer form did not, so a manager added there was saved with no company
-- name and no address, and read as an empty row everywhere an employer is
-- named.
--
-- Only blank columns are filled, and only from the store the account is already
-- attached to, so a manager whose details were corrected by hand keeps them and
-- one with no store is left alone. That is also what makes a second run
-- harmless.

UPDATE `users` u
  JOIN `store` s
    ON s.`s_id` = u.`u_store_id`
   SET u.`u_comp_name`  = CASE WHEN COALESCE(u.`u_comp_name`, '')  = '' THEN s.`s_name`     ELSE u.`u_comp_name`  END,
       u.`u_licence_no` = CASE WHEN COALESCE(u.`u_licence_no`, '') = '' THEN s.`s_number`   ELSE u.`u_licence_no` END,
       u.`u_address1`   = CASE WHEN COALESCE(u.`u_address1`, '')   = '' THEN s.`s_address`  ELSE u.`u_address1`   END,
       u.`u_pincode`    = CASE WHEN COALESCE(u.`u_pincode`, '')    = '' THEN s.`s_pincode`  ELSE u.`u_pincode`    END,
       u.`u_l_provice`  = CASE WHEN COALESCE(u.`u_l_provice`, 0)   = 0  THEN s.`s_province` ELSE u.`u_l_provice`  END,
       u.`u_provice`    = CASE WHEN COALESCE(u.`u_provice`, 0)     = 0  THEN s.`s_province` ELSE u.`u_provice`    END,
       u.`u_city`       = CASE WHEN COALESCE(u.`u_city`, 0)        = 0  THEN s.`s_city`     ELSE u.`u_city`       END
 WHERE u.`u_usertype` = 1
   AND u.`u_emp_role` = 2
   AND COALESCE(u.`u_store_id`, 0) > 0;


-- 4 ---------------------------------------- the bookkeeping, for that one --

INSERT INTO `migrations` (`version`, `class`, `group`, `namespace`, `time`, `batch`)
SELECT
  '2026-08-15-120000',
  'App\\Database\\Migrations\\BackfillManagerStoreSnapshot',
  'default',
  'App',
  UNIX_TIMESTAMP(),
  COALESCE((SELECT MAX(m.`batch`) FROM `migrations` m), 0) + 1
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations` m2 WHERE m2.`version` = '2026-08-15-120000'
);


-- 5 ------------------------------------------------------------- the table --
--
-- The Testimonials master, maintained in the back office and drawn by the home
-- page carousel. Two text columns because a testimonial is a heading plus the
-- quote itself.
--
-- `t_status` MUST default to 1. The add form posts the two text fields and
-- nothing else, so without the default every new testimonial saves Deactive and
-- never appears on the home page.

CREATE TABLE IF NOT EXISTS `testimonial` (
  `t_id`          INT          NOT NULL AUTO_INCREMENT,
  `t_title`       VARCHAR(150) NULL DEFAULT NULL,
  `t_description` TEXT         NULL DEFAULT NULL,
  `t_status`      INT          NOT NULL DEFAULT 1,
  PRIMARY KEY (`t_id`)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- 6 ---------------------------------------- the bookkeeping, for that one --

INSERT INTO `migrations` (`version`, `class`, `group`, `namespace`, `time`, `batch`)
SELECT
  '2026-08-17-090000',
  'App\\Database\\Migrations\\AddTestimonialTable',
  'default',
  'App',
  UNIX_TIMESTAMP(),
  COALESCE((SELECT MAX(m.`batch`) FROM `migrations` m), 0) + 1
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations` m2 WHERE m2.`version` = '2026-08-17-090000'
);


-- 7 ------------------------------------------------- who to e-mail a shift --
--
-- Which side of the store is told a shift has gone live: the words `owner`,
-- `manager`, both, or neither, ticked on the shift forms.
--
-- Empty is a real answer and means "send it to the fallback address in
-- Config\AppSettings::$shiftEmailFallback", not "send nothing". So the backfill
-- below matters as much as the column: every shift that already exists was
-- never asked the question, and setting those to `owner` keeps them mailing
-- exactly who they mailed before this release.
--
-- @had_email_to is read BEFORE the column is added, and the backfill only runs
-- when it was 0. That is the difference between this file and its predecessor:
-- an unconditional backfill would, on a second run, rewrite every shift saved
-- as "tell neither side" into "tell the owner" - silently, and against what
-- somebody had chosen on the form.

SET @had_email_to := (
  SELECT COUNT(*)
    FROM `information_schema`.`COLUMNS`
   WHERE `TABLE_SCHEMA` = DATABASE()
     AND `TABLE_NAME`   = 'post_job'
     AND `COLUMN_NAME`  = 'p_email_to'
);

SET @add_email_to := IF(@had_email_to > 0,
  'SELECT ''p_email_to already there'' AS note',
  'ALTER TABLE `post_job`
     ADD COLUMN `p_email_to` VARCHAR(32) NOT NULL DEFAULT ''''
     COMMENT ''Comma separated: owner, manager. Empty = the fallback address.''
     AFTER `p_approved`');

PREPARE stmt FROM @add_email_to;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @backfill_email_to := IF(@had_email_to > 0,
  'SELECT ''existing choices left alone'' AS note',
  'UPDATE `post_job` SET `p_email_to` = ''owner'' WHERE `p_email_to` = ''''');

PREPARE stmt FROM @backfill_email_to;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- 8 ---------------------------------------- the bookkeeping, for that one --

INSERT INTO `migrations` (`version`, `class`, `group`, `namespace`, `time`, `batch`)
SELECT
  '2026-08-18-100000',
  'App\\Database\\Migrations\\AddShiftEmailRecipients',
  'default',
  'App',
  UNIX_TIMESTAMP(),
  COALESCE((SELECT MAX(m.`batch`) FROM `migrations` m), 0) + 1
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations` m2 WHERE m2.`version` = '2026-08-18-100000'
);


-- 9 ------------------------------------------------------------- check it --
--
-- Every number below should be 1, and the last query should list all four
-- versions. If a bookkeeping row is missing the site still works, but the next
-- `spark migrate` will fail as described in section 2.

SELECT COUNT(*) AS u_email_blocked_present
  FROM `information_schema`.`COLUMNS`
 WHERE `TABLE_SCHEMA` = DATABASE()
   AND `TABLE_NAME`   = 'users'
   AND `COLUMN_NAME`  = 'u_email_blocked';

SELECT COUNT(*) AS testimonial_table_present
  FROM `information_schema`.`TABLES`
 WHERE `TABLE_SCHEMA` = DATABASE()
   AND `TABLE_NAME`   = 'testimonial';

SELECT COUNT(*) AS p_email_to_present
  FROM `information_schema`.`COLUMNS`
 WHERE `TABLE_SCHEMA` = DATABASE()
   AND `TABLE_NAME`   = 'post_job'
   AND `COLUMN_NAME`  = 'p_email_to';

SELECT `version`, `class`
  FROM `migrations`
 WHERE `version` IN ('2026-08-15-090000', '2026-08-15-120000',
                     '2026-08-17-090000', '2026-08-18-100000')
 ORDER BY `version`;


-- ---------------------------------------------------------------------------
-- Nothing else to run.
--
-- What is in the 2026-08-19 release and needs no database change at all:
--
--   The pharmacist's screens on the site theme -> views only. Applied Shifts,
--                                 Edit Profile, Change Password and the side
--                                 menu now load the same partials the owner's
--                                 screens do.
--   Hourly Rate hidden from a manager -> reads users.u_emp_role, which has been
--                                 live since 2026-08-10. A shift a manager
--                                 posts leaves post_job.p_hourly_rate NULL for
--                                 the agency to fill in; the column already
--                                 allows NULL and always has.
--   The read-only shift panel   -> markup only.
--   DataTables stylesheets on the signed-in pages -> assets only.
--
-- The `testimonial` table arrives EMPTY, so the home page carousel has nothing
-- to show until testimonials are added in the back office.
-- ---------------------------------------------------------------------------
