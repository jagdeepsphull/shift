-- ---------------------------------------------------------------------------
-- PickAShift - database update for the 2026-08-18 release
--
-- TWO new migrations: the `testimonial` master, and `post_job.p_email_to` -
-- who the "your shift is live" e-mail goes to. Everything else in this release
-- is code only - no other table, column, index or data change.
--
-- Prefer `php spark migrate` (with SSH) or `deploy/migrate.php` (without). Use
-- this file only when neither is available: both of those write the bookkeeping
-- row for you and check the schema first, which is most of what is below.
--
-- Safe to run twice. The CREATE is guarded by IF NOT EXISTS, the ADD COLUMN by
-- a look in information_schema, and each bookkeeping INSERT by a NOT EXISTS
-- test, so a second run changes nothing.
--
-- Run against the SITE database (the one in .env), not `information_schema`.
-- ---------------------------------------------------------------------------


-- 1 -------------------------------------------------------------- the table --
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


-- 2 -------------------------------------------------------- the bookkeeping --
--
-- CodeIgniter records what it has applied in `migrations`. Creating the table
-- by hand and leaving this out is the trap: the next `php spark migrate` sees
-- the migration as outstanding, runs it, and stops on "table already exists" -
-- taking every later migration with it.
--
-- `batch` is one past the highest already there, which is what spark does for a
-- run of its own. `group` and `namespace` are quoted because both are reserved
-- words. The sub-select is why this is safe to run twice.

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


-- 3 ------------------------------------------------- who to e-mail a shift --
--
-- Which side of the store is told a shift has gone live: the words `owner`,
-- `manager`, both, or neither, ticked on the two back-office shift forms.
--
-- Empty is a real answer and means "send it to the fallback address in
-- Config\AppSettings::$shiftEmailFallback", not "send nothing". So the UPDATE
-- below matters as much as the column: every shift that already exists was
-- never asked the question, and setting them to `owner` keeps them mailing
-- exactly who they mailed before this release.
--
-- MySQL has no ADD COLUMN IF NOT EXISTS before 8.0.29, so this is done with a
-- prepared statement that becomes a no-op when the column is already there.

SET @add_email_to := (
  SELECT IF(COUNT(*) > 0,
            'SELECT ''p_email_to already there'' AS note',
            'ALTER TABLE `post_job`
               ADD COLUMN `p_email_to` VARCHAR(32) NOT NULL DEFAULT ''''
               COMMENT ''Comma separated: owner, manager. Empty = the fallback address.''
               AFTER `p_approved`')
    FROM `information_schema`.`COLUMNS`
   WHERE `TABLE_SCHEMA` = DATABASE()
     AND `TABLE_NAME`   = 'post_job'
     AND `COLUMN_NAME`  = 'p_email_to'
);

PREPARE stmt FROM @add_email_to;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `post_job` SET `p_email_to` = 'owner' WHERE `p_email_to` = '';


-- 4 ------------------------------------------ the bookkeeping, for that one --

INSERT INTO `migrations` (`version`, `class`, `group`, `namespace`, `time`, `batch`)
SELECT
  '2026-08-18-100000',
  'App\Database\Migrations\AddShiftEmailRecipients',
  'default',
  'App',
  UNIX_TIMESTAMP(),
  COALESCE((SELECT MAX(m.`batch`) FROM `migrations` m), 0) + 1
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations` m2 WHERE m2.`version` = '2026-08-18-100000'
);


-- 5 ------------------------------------------------------------- check it ---
--
-- All four should come back 1. If either bookkeeping row is 0 the site still
-- works, but the next `spark migrate` will fail as described above.

SELECT COUNT(*) AS testimonial_table_present
  FROM `information_schema`.`TABLES`
 WHERE `TABLE_SCHEMA` = DATABASE()
   AND `TABLE_NAME`   = 'testimonial';

SELECT COUNT(*) AS migration_recorded
  FROM `migrations`
 WHERE `version` = '2026-08-17-090000';

SELECT COUNT(*) AS p_email_to_present
  FROM `information_schema`.`COLUMNS`
 WHERE `TABLE_SCHEMA` = DATABASE()
   AND `TABLE_NAME`   = 'post_job'
   AND `COLUMN_NAME`  = 'p_email_to';

SELECT COUNT(*) AS email_migration_recorded
  FROM `migrations`
 WHERE `version` = '2026-08-18-100000';


-- ---------------------------------------------------------------------------
-- Nothing else to run.
--
-- The rest of this release reuses columns that are already live:
--
--   Assigned To on All Shifts  -> stu_saved_applied_jobs.sj_is_approved,
--                                 sj_status, sj_admin_comment, sj_accept_date
--   Applicant/agency messages  -> stu_saved_applied_jobs.sj_applied_desc,
--                                 sj_admin_comment
--   Managers on the store list -> users.u_store_id, u_emp_role, u_fname/u_lname
--   Details no longer required -> a form flag; post_job.p_services unchanged
--   The shift page's three tiers, and the rate it shows once signed in ->
--                                 post_job.p_ac_hourly_rate, and the booking
--                                 row that already decides who is on a shift.
--                                 Code only.
--   Ten-digit mobile numbers   -> users.u_phone, store.s_phone unchanged.
--                                 Existing numbers are NOT rewritten; each is
--                                 trimmed to ten digits the next time somebody
--                                 opens that record and saves it.
--   Website fields, store shift defaults, the split store picker, the employer
--   theme -> code only.
--
-- The `testimonial` table arrives EMPTY, so the home page carousel has nothing
-- to show until testimonials are added in the back office.
-- ---------------------------------------------------------------------------
