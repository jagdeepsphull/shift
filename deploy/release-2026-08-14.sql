-- ---------------------------------------------------------------------------
-- PickAShift - database changes for the 14 Aug 2026 release
--
-- Run this ONLY if you cannot run `php spark migrate` or `deploy/migrate.php`.
-- Those are the preferred routes: they check the schema before each step, so
-- they are safe to run twice, and they print what they did. This file is the
-- same four changes written out for phpMyAdmin.
--
-- HOW TO RUN
--   phpMyAdmin -> pick the database -> SQL tab -> paste the whole file -> Go.
--   Run it ONCE. There is no "add column if not exists" in MySQL, so a second
--   run stops with "Duplicate column name" - which is harmless, but means you
--   should check what already applied rather than run it again blindly.
--
-- WHY THE LAST STATEMENT MATTERS
--   Section 5 writes the four rows CodeIgniter would have written itself. Skip
--   it and a later `php spark migrate` sees these migrations as outstanding and
--   re-runs them, failing on "Duplicate column name" and stopping the deploy.
--
-- NO STORAGE ENGINE IS NAMED ANYWHERE ON PURPOSE
--   CodeIgniter's Forge does not name one either, so a table it creates takes
--   the server default - MyISAM on the current host, InnoDB on many others.
--   Pinning one here would build a table spark would never have made.
-- ---------------------------------------------------------------------------


-- 1 -------------------------------------------------- AddAdditionalDetailsTable
-- The Additional Details master, maintained at Main Master -> Additional
-- Details. Same shape as store_service and shift_for. ad_status defaults to 1
-- because the add form posts a name and nothing else - without the default a
-- new entry would land Deactive and need a second click.

CREATE TABLE IF NOT EXISTS `additional_details` (
  `ad_id` INT NOT NULL AUTO_INCREMENT,
  `ad_name` VARCHAR(100) NULL DEFAULT NULL,
  `ad_status` INT NOT NULL DEFAULT 1,
  PRIMARY KEY (`ad_id`)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- 2 ---------------------------------------------------- AddJobAdditionalDetails
-- Which additional details a shift offers. Same comma separated shape as
-- p_services beside it, but wider: p_services is VARCHAR(100) and silently
-- truncates its tail once the list grows.

ALTER TABLE `post_job`
  ADD COLUMN `p_additional_details` VARCHAR(255) NULL DEFAULT NULL
  COMMENT 'Comma separated additional_details.ad_id list, same shape as p_services'
  AFTER `p_services`;


-- 3 ----------------------------------------------------- AddStoreShiftDefaults
-- What a store normally offers, so a shift posted against it starts from those
-- lists instead of from nothing. Only a starting point: the shift keeps its own
-- copy from the moment it is saved, so editing one never reaches back here.
-- Blank on every existing store, which means those shifts start empty as today.

ALTER TABLE `store`
  ADD COLUMN `s_skills` VARCHAR(255) NULL DEFAULT NULL
    COMMENT 'Default software_skills.ss_id list for shifts here, same shape as post_job.p_skills'
    AFTER `s_website`,
  ADD COLUMN `s_services` VARCHAR(255) NULL DEFAULT NULL
    COMMENT 'Default store_service.st_id list for shifts here, same shape as post_job.p_services'
    AFTER `s_skills`,
  ADD COLUMN `s_additional_details` VARCHAR(255) NULL DEFAULT NULL
    COMMENT 'Default additional_details.ad_id list for shifts here, same shape as post_job.p_additional_details'
    AFTER `s_services`;


-- 4 --------------------------------------------------- MakeEmployersMultiStore
-- Every employer becomes an Owner (Multi Store). Most live accounts still carry
-- u_emp_role 0 - they registered before the three kinds existed - which left
-- them in no kind at all: absent from every User Type filter, and with no basis
-- for deciding whether they may hold a second store.
--
-- Only the role moves. u_parent_id is left alone: where a manager does exist,
-- the group they answer to is real information, and employerKindSlug() reads
-- role 1 first so the row still reads as an owner.
--
-- THIS DOES NOT REVERSE. The column does not record what a row held before, so
-- there is no query that puts it back. If that matters, take a backup of the
-- column first:
--   CREATE TABLE `users_emp_role_backup_20260814` AS
--     SELECT `u_id`, `u_emp_role` FROM `users` WHERE `u_usertype` = 1;

UPDATE `users` SET `u_emp_role` = 1 WHERE `u_usertype` = 1 AND `u_emp_role` <> 1;


-- 5 ------------------------------------------------ record them as applied ---
-- The rows CodeIgniter's migration runner would have written. Without these a
-- later `php spark migrate` re-runs all four and fails on a duplicate column.
--
-- `group` is a reserved word and has to stay quoted. The batch number is the
-- next one after whatever is already there, which is what spark would use.

INSERT INTO `migrations` (`version`, `class`, `group`, `namespace`, `time`, `batch`)
SELECT * FROM (
  SELECT '2026-08-14-090000' AS `version`, 'App\\Database\\Migrations\\AddAdditionalDetailsTable' AS `class`, 'default' AS `group`, 'App' AS `namespace`, UNIX_TIMESTAMP() AS `time`, (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` m) AS `batch`
  UNION ALL SELECT '2026-08-14-110000', 'App\\Database\\Migrations\\AddJobAdditionalDetails',  'default', 'App', UNIX_TIMESTAMP(), (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` m)
  UNION ALL SELECT '2026-08-14-140000', 'App\\Database\\Migrations\\AddStoreShiftDefaults',    'default', 'App', UNIX_TIMESTAMP(), (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` m)
  UNION ALL SELECT '2026-08-14-160000', 'App\\Database\\Migrations\\MakeEmployersMultiStore',  'default', 'App', UNIX_TIMESTAMP(), (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` m)
) AS `rows`
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations` m2 WHERE m2.`version` = `rows`.`version`
);


-- ------------------------------------------------------------------ check ---
-- Run these afterwards. Expected: 1, 1, 3, 0, 4.

-- SELECT COUNT(*) FROM information_schema.TABLES
--  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'additional_details';
-- SELECT COUNT(*) FROM information_schema.COLUMNS
--  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'post_job' AND COLUMN_NAME = 'p_additional_details';
-- SELECT COUNT(*) FROM information_schema.COLUMNS
--  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'store'
--    AND COLUMN_NAME IN ('s_skills','s_services','s_additional_details');
-- SELECT COUNT(*) FROM `users` WHERE `u_usertype` = 1 AND `u_emp_role` <> 1;
-- SELECT COUNT(*) FROM `migrations` WHERE `version` LIKE '2026-08-14-%';
