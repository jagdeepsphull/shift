-- ---------------------------------------------------------------------------
-- PickAShift - database update for the 2026-09-09 release
--
-- TWO migrations:
--
--   2026-09-09-090000  settings social links      three columns + a seed
--   2026-09-09-100000  testimonial person fields  five columns
--
-- These are what the home page testimonial cards and the footer/contact social
-- icons need. Without the first, /sadmin/settings fails on an unknown column
-- the moment somebody presses Save, and both icon rows stay on the hard-coded
-- addresses. Without the second, every testimonial screen - the three in the
-- back office and the carousel on the home page - fails the same way.
--
-- This does NOT supersede `release-2026-08-20.sql`; it carries only the two
-- migrations above. Three others landed between them and are in neither file:
--
--   2026-09-02-090000  ShiftRatesTakeCents
--   2026-09-04-090000  AddShiftForOrder
--   2026-09-08-090000  AddListOrderColumns
--
-- `deploy/migrate.php` carries all three (and the second of the two below).
-- Run that first if section 0 says the database is behind 2026-09-08-090000 -
-- this file will apply cleanly either way, but those three still have to happen
-- before the release is complete.
--
-- Prefer `php spark migrate` (with SSH) or `deploy/migrate.php` (without). Use
-- this file only when neither is available: both of those write the bookkeeping
-- rows for you and check the schema first, which is most of what is below.
--
-- SAFE TO RUN TWICE. Each ADD COLUMN is guarded by a look in information_schema
-- and each bookkeeping INSERT by a NOT EXISTS test. The one seed that would not
-- be safe on its own - the social addresses - only fills columns that have
-- never been set, so a second run cannot undo a link the agency has since
-- edited or deliberately cleared.
--
-- Run against the SITE database (the one named in .env), not information_schema.
-- ---------------------------------------------------------------------------


-- 0 ------------------------------------------------------- where are you? --
--
-- Run this first on its own. Anything at or past 2026-09-09-100000 has had this
-- whole file already; anything below 2026-09-08-090000 is behind the three
-- migrations named at the top and wants `deploy/migrate.php` as well.

SELECT MAX(`version`) AS highest_migration_applied FROM `migrations`;


-- 1 ------------------------------------------------- the three social links --
--
-- Facebook, X and Instagram, editable at /sadmin/settings. They were written
-- into the footer and the contact page by hand and had drifted on to different
-- accounts; one row in `settings` gives both pages the same answer.
--
-- Nullable, because blank is meaningful: the views hide an icon whose link is
-- empty, which is better than an icon that goes nowhere.
--
-- `settings` is a latin1 table, so no CHARACTER SET is named here - the column
-- takes the table's, which is what CodeIgniter's Forge would also have done.
--
-- MySQL has no ADD COLUMN IF NOT EXISTS before 8.0.29, so this is a prepared
-- statement that becomes a no-op when the columns are already there. All three
-- go in one ALTER: they arrived in one migration, and a half-applied set is the
-- state this file exists to avoid.

SET @add_social := (
  SELECT IF(COUNT(*) > 0,
            'SELECT ''social link columns already there'' AS note',
            'ALTER TABLE `settings`
               ADD COLUMN `s_facebook_url` VARCHAR(255) NULL
                 COMMENT ''Footer and contact-page Facebook link. Blank hides the icon.''
                 AFTER `s_agency_copy_email`,
               ADD COLUMN `s_twitter_url` VARCHAR(255) NULL
                 COMMENT ''Footer and contact-page X/Twitter link. Blank hides the icon.''
                 AFTER `s_facebook_url`,
               ADD COLUMN `s_instagram_url` VARCHAR(255) NULL
                 COMMENT ''Footer and contact-page Instagram link. Blank hides the icon.''
                 AFTER `s_twitter_url`')
    FROM `information_schema`.`COLUMNS`
   WHERE `TABLE_SCHEMA` = DATABASE()
     AND `TABLE_NAME`   = 'settings'
     AND `COLUMN_NAME`  = 's_facebook_url'
);

PREPARE stmt FROM @add_social;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- 2 ------------------------------------------------ what those links start as --
--
-- The footer's set, because that is the one every page carried. Facebook starts
-- blank on purpose: the footer's was "#", which goes nowhere, and an empty box
-- takes the icon off both pages until somebody fills it in.
--
-- Only NULL columns are filled - that is, only ones section 1 has just added.
-- A second run of this file finds them set (to a real address, or to the blank
-- Facebook deliberately left alone) and changes nothing, so an address the
-- agency has since edited survives.

UPDATE `settings`
   SET `s_facebook_url`  = COALESCE(`s_facebook_url`,  ''),
       `s_twitter_url`   = COALESCE(`s_twitter_url`,   'https://x.com/reliefshifts'),
       `s_instagram_url` = COALESCE(`s_instagram_url`, 'https://www.instagram.com/reliefshifts')
 WHERE `s_id` = 1;


-- 3 -------------------------------------------------------- the bookkeeping --
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
  '2026-09-09-090000',
  'App\\Database\\Migrations\\AddSocialLinkSettings',
  'default',
  'App',
  UNIX_TIMESTAMP(),
  COALESCE((SELECT MAX(m.`batch`) FROM `migrations` m), 0) + 1
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations` m2 WHERE m2.`version` = '2026-09-09-090000'
);


-- 4 ------------------------------------------------ the person behind the quote --
--
-- `testimonial` started as a heading plus a body. A quote with nobody's name on
-- it reads as copy the agency wrote about itself, so the card now shows who
-- said it - their name, what they are, where they are, a star rating and a
-- photo.
--
-- Four of the five are nullable; blank hides that line on the card. `t_rating`
-- is NOT NULL DEFAULT 5, because it is drawn as stars on every card and NULL
-- would render as an empty strip beside four cards showing five.
--
-- `t_image` holds a bare filename, not a path - the convention `users.u_photo`
-- set. Files live in uploads/testimonial/, and a row with none falls back to
-- assets/front/assets/img/testimonial/thumb.svg, so a photoless testimonial
-- still fills its circle rather than showing a broken image.
--
-- They go after `t_description` so the columns read in the order the card does.
-- `t_status` and `t_order` are bookkeeping rather than content and stay at the
-- end, where they were.

SET @add_person := (
  SELECT IF(COUNT(*) > 0,
            'SELECT ''testimonial person columns already there'' AS note',
            'ALTER TABLE `testimonial`
               ADD COLUMN `t_name` VARCHAR(100) NULL
                 COMMENT ''Who said it, as shown on the card - "Sarah M.".''
                 AFTER `t_description`,
               ADD COLUMN `t_role` VARCHAR(100) NULL
                 COMMENT ''Their standing - "Job Seeker", "HR Manager". Blank hides the line.''
                 AFTER `t_name`,
               ADD COLUMN `t_location` VARCHAR(120) NULL
                 COMMENT ''City and province. Blank hides the pin.''
                 AFTER `t_role`,
               ADD COLUMN `t_image` VARCHAR(255) NULL
                 COMMENT ''Filename under uploads/testimonial/. Blank falls back to the placeholder thumb.''
                 AFTER `t_location`,
               ADD COLUMN `t_rating` TINYINT(1) NOT NULL DEFAULT 5
                 COMMENT ''Stars out of five, 1-5.''
                 AFTER `t_image`')
    FROM `information_schema`.`COLUMNS`
   WHERE `TABLE_SCHEMA` = DATABASE()
     AND `TABLE_NAME`   = 'testimonial'
     AND `COLUMN_NAME`  = 't_name'
);

PREPARE stmt FROM @add_person;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- 5 ---------------------------------------- the bookkeeping, for that one --

INSERT INTO `migrations` (`version`, `class`, `group`, `namespace`, `time`, `batch`)
SELECT
  '2026-09-09-100000',
  'App\\Database\\Migrations\\AddTestimonialPersonFields',
  'default',
  'App',
  UNIX_TIMESTAMP(),
  COALESCE((SELECT MAX(m.`batch`) FROM `migrations` m), 0) + 1
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations` m2 WHERE m2.`version` = '2026-09-09-100000'
);


-- 6 ------------------------------------------------------------- did it work --
--
-- Eight columns and two rows. Anything short of that and the release is not on
-- this database yet.

SELECT 'settings social links' AS what,
       COUNT(*)                AS columns_found,
       3                       AS columns_expected
  FROM `information_schema`.`COLUMNS`
 WHERE `TABLE_SCHEMA` = DATABASE()
   AND `TABLE_NAME`   = 'settings'
   AND `COLUMN_NAME` IN ('s_facebook_url', 's_twitter_url', 's_instagram_url')
UNION ALL
SELECT 'testimonial person fields',
       COUNT(*),
       5
  FROM `information_schema`.`COLUMNS`
 WHERE `TABLE_SCHEMA` = DATABASE()
   AND `TABLE_NAME`   = 'testimonial'
   AND `COLUMN_NAME` IN ('t_name', 't_role', 't_location', 't_image', 't_rating')
UNION ALL
SELECT 'migrations recorded',
       COUNT(*),
       2
  FROM `migrations`
 WHERE `version` IN ('2026-09-09-090000', '2026-09-09-100000');
