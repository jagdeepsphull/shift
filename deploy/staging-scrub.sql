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

-- Safety catch: refuses to run unless you are on a database whose name ends
-- in "n" (pickashiftn). Delete these two lines if you rename staging.
SET @db := DATABASE();
SET @ok := IF(@db LIKE '%n', 1, (SELECT 1 FROM `REFUSING TO SCRUB — THIS IS NOT THE STAGING DATABASE`));

-- Every user's outbound address becomes unroutable. `.invalid` is reserved by
-- RFC 2606 and can never resolve, so nothing can leak even by accident.
UPDATE users
   SET u_email = CONCAT('staging+user', u_id, '@example.invalid')
 WHERE u_email IS NOT NULL;

-- Phone numbers, for the same reason.
UPDATE users
   SET u_phone = CONCAT('000000', u_id)
 WHERE u_phone IS NOT NULL AND u_phone <> '';

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
SELECT DATABASE()                                     AS scrubbed_database,
       (SELECT COUNT(*) FROM users)                   AS users,
       (SELECT COUNT(*) FROM users
         WHERE u_email NOT LIKE '%@example.invalid')  AS addresses_still_real;
