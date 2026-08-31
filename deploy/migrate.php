<?php

/**
 * One-shot migration runner for hosts without SSH.
 *
 * Upload next to index.php (or /staging/index.php), open it, read the result,
 * then DELETE IT.
 *
 *   https://reliefshifts.com/staging/migrate.php?key=b7f4c1e93a2d6058
 *
 * `php spark migrate` is the right way to do this and needs a command line.
 * This does the same eighteen migrations over mysqli, reading credentials from the
 * .env beside it, and writes the same rows into `migrations` that spark would -
 * so a later `spark migrate` sees them as done rather than re-running them into
 * a "duplicate column" error.
 *
 * Every step checks the schema before touching it, so running this twice, or
 * running it after a partial spark run, is safe.
 *
 * Two rules this file lives by, both learned the hard way:
 *
 *  - No statement names a storage ENGINE. CodeIgniter's Forge does not either,
 *    so a table it creates takes the server's default - MyISAM on the current
 *    host, InnoDB on many others. Pinning one here would produce a table spark
 *    would never have made, on whichever server disagreed.
 *
 *  - An apostrophe inside a COMMENT is doubled ('') and never backslashed (\').
 *    A server running with sql_mode=NO_BACKSLASH_ESCAPES rejects the backslash
 *    form outright, and the failure lands mid-migration.
 */

declare(strict_types=1);

const KEY = 'b7f4c1e93a2d6058';

if (($_GET['key'] ?? '') !== KEY) {
    header('HTTP/1.1 404 Not Found');
    exit('Not found');
}

header('Content-Type: text/plain; charset=utf-8');

$here = __DIR__;

echo "PickAShift migration runner\n";
echo str_repeat('=', 72) . "\n";
echo 'Path : ' . $here . "\n";
echo str_repeat('=', 72) . "\n\n";

// ------------------------------------------------------------------ .env ----

if (! is_file("{$here}/.env")) {
    exit("FAIL: no .env beside this script. Upload it to the site root, not deploy/.\n");
}

$env = [];

foreach (file("{$here}/.env", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);

    if ($line === '' || $line[0] === '#' || ! str_contains($line, '=')) {
        continue;
    }

    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim(trim(trim($v), "'\""));
}

$db = @new mysqli(
    $env['database.default.hostname'] ?? 'localhost',
    $env['database.default.username'] ?? '',
    $env['database.default.password'] ?? '',
    $env['database.default.database'] ?? ''
);

if ($db->connect_errno) {
    // Message only - never the credentials themselves.
    exit('FAIL: db connect error ' . $db->connect_errno . ': ' . $db->connect_error . "\n");
}

$dbName = $env['database.default.database'] ?? '';
echo "Connected to {$dbName}\n\n";

// --------------------------------------------------------------- helpers ----

function tableExists(mysqli $db, string $table): bool
{
    $res = $db->query("SHOW TABLES LIKE '" . $db->real_escape_string($table) . "'");

    return $res !== false && $res->num_rows > 0;
}

function columnExists(mysqli $db, string $table, string $column): bool
{
    if (! tableExists($db, $table)) {
        return false;
    }

    $res = $db->query(
        'SHOW COLUMNS FROM `' . $db->real_escape_string($table) . "` LIKE '"
        . $db->real_escape_string($column) . "'"
    );

    return $res !== false && $res->num_rows > 0;
}

function indexExists(mysqli $db, string $table, string $index): bool
{
    if (! tableExists($db, $table)) {
        return false;
    }

    $res = $db->query(
        'SHOW INDEX FROM `' . $db->real_escape_string($table) . "` WHERE Key_name = '"
        . $db->real_escape_string($index) . "'"
    );

    return $res !== false && $res->num_rows > 0;
}

function run(mysqli $db, string $sql): void
{
    if ($db->query($sql) === false) {
        throw new RuntimeException($db->error . ' -- while running: ' . preg_replace('/\s+/', ' ', $sql));
    }
}

/**
 * A string as a SQL literal, apostrophes doubled.
 *
 * Deliberately not `real_escape_string()`, which backslashes them: a server
 * running with sql_mode=NO_BACKSLASH_ESCAPES rejects that form outright. Most of
 * the strings here are COMMENT text, and several contain an apostrophe.
 */
function quoted(string $value): string
{
    return "'" . str_replace("'", "''", $value) . "'";
}

// ------------------------------------------------------ migrations table ----
// Same shape CodeIgniter's MigrationRunner creates, so spark reads it happily.
// `group` is a reserved word - it has to stay quoted. No ENGINE, for the reason
// at the top of this file: spark's own table takes the server default too.

if (! tableExists($db, 'migrations')) {
    run($db, 'CREATE TABLE `migrations` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `version` VARCHAR(255) NOT NULL,
        `class` VARCHAR(255) NOT NULL,
        `group` VARCHAR(255) NOT NULL,
        `namespace` VARCHAR(255) NOT NULL,
        `time` INT NOT NULL,
        `batch` INT UNSIGNED NOT NULL,
        PRIMARY KEY (`id`)
    ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');

    echo "  created  migrations table\n";
} else {
    echo "  present  migrations table\n";
}

$done = [];
$res  = $db->query('SELECT version FROM `migrations`');

while ($r = $res->fetch_assoc()) {
    $done[$r['version']] = true;
}

// The batch number spark would use next.
$res   = $db->query('SELECT COALESCE(MAX(`batch`), 0) + 1 AS b FROM `migrations`');
$batch = (int) $res->fetch_assoc()['b'];

echo "  batch    {$batch}\n\n";

// ------------------------------------------------------------ migrations ----

$migrations = [
    [
        'version' => '2026-08-04-120000',
        'class'   => 'App\Database\Migrations\AddShiftDateColumn',
        'label'   => 'AddShiftDateColumn',
        'apply'   => static function (mysqli $db): array {
            $notes = [];

            if (! columnExists($db, 'post_job', 'p_date_start')) {
                run($db, "ALTER TABLE `post_job`
                    ADD COLUMN `p_date_start` DATE NULL
                    COMMENT 'Parsed from p_dates (dd-mm-yyyy). Sortable shift date.'
                    AFTER `p_dates`");
                $notes[] = 'added post_job.p_date_start';
            } else {
                $notes[] = 'post_job.p_date_start already there';
            }

            // Backfill. Rows whose text does not parse stay NULL rather than
            // being guessed at.
            run($db, "UPDATE `post_job`
                    SET `p_date_start` = STR_TO_DATE(`p_dates`, '%d-%m-%Y')
                  WHERE `p_date_start` IS NULL
                    AND `p_dates` IS NOT NULL
                    AND `p_dates` <> ''
                    AND STR_TO_DATE(`p_dates`, '%d-%m-%Y') IS NOT NULL");

            $notes[] = 'backfilled ' . $db->affected_rows . ' row(s)';

            if (! indexExists($db, 'post_job', 'idx_post_job_date_start')) {
                run($db, 'CREATE INDEX `idx_post_job_date_start` ON `post_job` (`p_date_start`)');
                $notes[] = 'created index';
            } else {
                $notes[] = 'index already there';
            }

            $res       = $db->query('SELECT COUNT(*) AS n FROM `post_job`
                                     WHERE `p_date_start` IS NULL
                                       AND `p_dates` IS NOT NULL AND `p_dates` <> \'\'');
            $unparsed = (int) $res->fetch_assoc()['n'];

            if ($unparsed > 0) {
                $notes[] = "WARNING: {$unparsed} row(s) have p_dates text that would not parse - "
                         . 'they sort last and read as undated';
            }

            return $notes;
        },
    ],
    [
        'version' => '2026-08-05-100000',
        'class'   => 'App\Database\Migrations\AddAgencyCopyEmailSetting',
        'label'   => 'AddAgencyCopyEmailSetting',
        'apply'   => static function (mysqli $db): array {
            $notes = [];

            if (! columnExists($db, 'settings', 's_agency_copy_email')) {
                run($db, "ALTER TABLE `settings`
                    ADD COLUMN `s_agency_copy_email` VARCHAR(150) NULL
                    COMMENT 'Copied on booking e-mails. Blank switches the copy off.'
                    AFTER `s_email`");
                $notes[] = 'added settings.s_agency_copy_email';

                run($db, "UPDATE `settings` SET `s_agency_copy_email` = 'info@reliefshifts.com' WHERE `s_id` = 1");
                $notes[] = 'defaulted to info@reliefshifts.com';
            } else {
                $notes[] = 'settings.s_agency_copy_email already there';
            }

            return $notes;
        },
    ],
    [
        'version' => '2026-08-06-090000',
        'class'   => 'App\Database\Migrations\AddShiftReminderSentAt',
        'label'   => 'AddShiftReminderSentAt',
        'apply'   => static function (mysqli $db): array {
            $notes = [];

            if (! columnExists($db, 'stu_saved_applied_jobs', 'sj_reminder_sent_at')) {
                run($db, "ALTER TABLE `stu_saved_applied_jobs`
                    ADD COLUMN `sj_reminder_sent_at` DATETIME NULL
                    COMMENT 'When the day-before shift reminder was e-mailed. NULL = not sent.'
                    AFTER `sj_accept_date`");
                $notes[] = 'added stu_saved_applied_jobs.sj_reminder_sent_at';
            } else {
                $notes[] = 'stu_saved_applied_jobs.sj_reminder_sent_at already there';
            }

            return $notes;
        },
    ],
    [
        'version' => '2026-08-10-090000',
        'class'   => 'App\Database\Migrations\AddStoreTable',
        'label'   => 'AddStoreTable',
        'apply'   => static function (mysqli $db): array {
            $notes = [];

            // The largest of the eight, and the one whose absence is loudest:
            // the shift form offers stores and nothing else, so until this runs
            // no shift can be posted at all and three screens 500.
            //
            // Forge puts NOT NULL on every column of a CREATE TABLE unless the
            // field says 'null' => true, and none of these do. The u_id key
            // rides inside the CREATE TABLE, named after the column it covers.
            // No ENGINE - see the note at the top of this file.
            if (! tableExists($db, 'store')) {
                run($db, "CREATE TABLE `store` (
                    `s_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `u_id` INT NOT NULL COMMENT 'users.u_id of the owning employer login',
                    `s_name` VARCHAR(200) NOT NULL,
                    `s_number` VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Store number - what u_licence_no held for an employer',
                    `s_province` INT NOT NULL DEFAULT 0,
                    `s_city` INT NOT NULL DEFAULT 0,
                    `s_address` VARCHAR(255) NOT NULL DEFAULT '',
                    `s_pincode` VARCHAR(10) NOT NULL DEFAULT '',
                    `s_phone` VARCHAR(25) NOT NULL DEFAULT '',
                    `s_status` TINYINT NOT NULL DEFAULT 1,
                    `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `modified` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`s_id`),
                    KEY `u_id` (`u_id`)
                ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
                $notes[] = 'created store table';
            } else {
                $notes[] = 'store table already there';

                // A spark run that died part-way through can leave the table
                // behind without its key.
                if (! indexExists($db, 'store', 'u_id')) {
                    run($db, 'ALTER TABLE `store` ADD KEY `u_id` (`u_id`)');
                    $notes[] = 'added the missing store.u_id key';
                }
            }

            // The next two columns are left nullable on purpose. addColumn
            // writes no null clause of its own - only createTable does - so
            // spark leaves both NULL-able with a default of 0, and matching
            // spark is the whole point of this file.
            if (! columnExists($db, 'post_job', 'p_store_id')) {
                run($db, 'ALTER TABLE `post_job`
                    ADD COLUMN `p_store_id` INT DEFAULT 0
                    COMMENT ' . quoted('store.s_id the shift is at. 0 = from before B4; falls back to the owner\'s login columns.') . '
                    AFTER `u_id`');
                $notes[] = 'added post_job.p_store_id';
            } else {
                $notes[] = 'post_job.p_store_id already there';
            }

            if (! columnExists($db, 'users', 'u_emp_role')) {
                run($db, 'ALTER TABLE `users`
                    ADD COLUMN `u_emp_role` TINYINT DEFAULT 0
                    COMMENT ' . quoted('Employer role: 1 manager, 2 store. 0 = registered before B4, treated as manager.') . '
                    AFTER `u_usersubtype`');
                $notes[] = 'added users.u_emp_role';
            } else {
                $notes[] = 'users.u_emp_role already there';
            }

            // Every existing employer becomes exactly one store, built from the
            // columns on their login, so nothing they see changes on day one.
            // The LEFT JOIN is the guard the CI4 migration does not need and
            // this file does: an employer who already has a store is skipped,
            // so a second run cannot double anybody up. `u_provice` really is
            // spelt that way in `users`.
            run($db, "INSERT INTO `store`
                          (`u_id`, `s_name`, `s_number`, `s_province`, `s_city`, `s_address`, `s_pincode`, `s_phone`, `s_status`)
                   SELECT `u`.`u_id`,
                          COALESCE(NULLIF(`u`.`u_comp_name`, ''), CONCAT(`u`.`u_fname`, ' ', `u`.`u_lname`)),
                          COALESCE(`u`.`u_licence_no`, ''),
                          `u`.`u_provice`, `u`.`u_city`,
                          COALESCE(`u`.`u_address1`, ''), COALESCE(`u`.`u_pincode`, ''), COALESCE(`u`.`u_phone`, ''),
                          1
                     FROM `users` `u`
                     LEFT JOIN `store` `s` ON `s`.`u_id` = `u`.`u_id`
                    WHERE `u`.`u_usertype` = 1
                      AND `s`.`s_id` IS NULL");

            $notes[] = 'built ' . $db->affected_rows . ' store(s) from employer logins';

            // And every existing shift points at its owner's only store, so past
            // bookings keep showing the address they always showed. Skip this
            // and every historic shift silently loses its address.
            run($db, 'UPDATE `post_job` `pj`
                        JOIN `store` `s` ON `s`.`u_id` = `pj`.`u_id`
                         SET `pj`.`p_store_id` = `s`.`s_id`
                       WHERE `pj`.`p_store_id` = 0');

            $notes[] = 'pointed ' . $db->affected_rows . ' shift(s) at their store';

            // Shifts whose u_id has no employer login behind it - deleted
            // accounts, mostly - keep 0 and read their address off `users` as
            // they always did. Say so, rather than let the count look wrong.
            $res     = $db->query('SELECT COUNT(*) AS n FROM `post_job` WHERE `p_store_id` = 0');
            $orphans = (int) $res->fetch_assoc()['n'];

            if ($orphans > 0) {
                $notes[] = "note: {$orphans} shift(s) still have p_store_id 0 - no employer login "
                         . 'matches their u_id, so they fall back to the login columns';
            }

            return $notes;
        },
    ],
    [
        'version' => '2026-08-10-140000',
        'class'   => 'App\Database\Migrations\AddUserParentId',
        'label'   => 'AddUserParentId',
        'apply'   => static function (mysqli $db): array {
            $notes = [];

            // Which pharmacy group a single-store login answers to. 0 means it
            // answers to nobody, which is what every account before this was.
            // Nullable with a default, like every other addColumn here.
            if (! columnExists($db, 'users', 'u_parent_id')) {
                run($db, 'ALTER TABLE `users`
                    ADD COLUMN `u_parent_id` INT DEFAULT 0
                    COMMENT ' . quoted('users.u_id of the multi-store owner this single-store login belongs to. 0 = independent.') . '
                    AFTER `u_emp_role`');
                $notes[] = 'added users.u_parent_id';
            } else {
                $notes[] = 'users.u_parent_id already there';
            }

            return $notes;
        },
    ],
    [
        'version' => '2026-08-10-160000',
        'class'   => 'App\Database\Migrations\ClarifyEmpRoleComment',
        'label'   => 'ClarifyEmpRoleComment',
        'apply'   => static function (mysqli $db): array {
            $notes = [];

            // The new wording, character for character, in one variable so the
            // guard below compares against exactly what the ALTER writes.
            $comment = 'Employer kind: 1 owns many stores, 2 owns one (a manager when u_parent_id is set, '
                     . 'an individual owner when it is 0). 0 = registered before B4.';

            // The column comes from AddStoreTable. Stop rather than let the
            // runner record this as done: with the column absent, a later spark
            // migrate would add it with the old wording and then skip this
            // migration, leaving the wrong comment in place for good.
            if (! columnExists($db, 'users', 'u_emp_role')) {
                throw new RuntimeException('users.u_emp_role is missing - AddStoreTable has not run');
            }

            // Nothing about the column changes except its comment, so
            // columnExists() reads the same before and after and cannot say
            // whether this ran. The stored comment is the only evidence there
            // is, so read it back.
            $res = $db->query("SELECT `COLUMN_COMMENT`
                                 FROM `information_schema`.`COLUMNS`
                                WHERE `TABLE_SCHEMA` = DATABASE()
                                  AND `TABLE_NAME`   = 'users'
                                  AND `COLUMN_NAME`  = 'u_emp_role'");

            $col = $res === false ? null : $res->fetch_assoc();

            if ($col !== null && $col['COLUMN_COMMENT'] === $comment) {
                $notes[] = 'users.u_emp_role comment already reworded';

                return $notes;
            }

            // The type and default have to be repeated or MODIFY would drop
            // them. No NOT NULL: spark leaves this column nullable, so this
            // must too. No AFTER: it stays where AddStoreTable put it.
            run($db, 'ALTER TABLE `users`
                MODIFY `u_emp_role` TINYINT DEFAULT 0
                COMMENT ' . quoted($comment));

            $notes[] = 'reworded users.u_emp_role comment';

            return $notes;
        },
    ],
    [
        'version' => '2026-08-12-090000',
        'class'   => 'App\Database\Migrations\BackfillBlankUserLoginId',
        'label'   => 'BackfillBlankUserLoginId',
        'apply'   => static function (mysqli $db): array {
            $notes = [];

            // No schema change - this repairs data. `u_userid` is the column the
            // login screen looks up, and neither back-office "add" form ever
            // wrote it, so accounts an administrator created could never sign
            // in: the password was right and the lookup found nothing.
            //
            // The LEFT JOIN is the safety catch. A row whose e-mail is already
            // some other account's login id is left alone - handing one person's
            // login id to another is worse than the account staying broken.
            run($db, "UPDATE `users` `u`
                      LEFT JOIN `users` `other`
                             ON `other`.`u_userid` = `u`.`u_email`
                            AND `other`.`u_id` <> `u`.`u_id`
                        SET `u`.`u_userid` = `u`.`u_email`
                      WHERE (`u`.`u_userid` IS NULL OR `u`.`u_userid` = '')
                        AND `u`.`u_email` IS NOT NULL
                        AND `u`.`u_email` <> ''
                        AND `other`.`u_id` IS NULL");

            $notes[] = 'gave ' . $db->affected_rows . ' account(s) their login id back';

            // Whatever is still blank could not be repaired safely. Name the
            // count: those people cannot sign in, and only a human can decide
            // what their login id should be.
            $res  = $db->query("SELECT COUNT(*) AS n FROM `users`
                                 WHERE `u_userid` IS NULL OR `u_userid` = ''");
            $left = (int) $res->fetch_assoc()['n'];

            if ($left > 0) {
                $notes[] = "WARNING: {$left} account(s) still have no login id - their e-mail is "
                         . 'blank, or already belongs to another account. They cannot sign in until '
                         . 'someone sets one by hand.';
            }

            return $notes;
        },
    ],
    [
        'version' => '2026-08-12-100000',
        'class'   => 'App\Database\Migrations\AddLocationAndWebsiteFields',
        'label'   => 'AddLocationAndWebsiteFields',
        'apply'   => static function (mysqli $db): array {
            $notes = [];

            // Columns only - `store` itself is AddStoreTable's. If that has not
            // run there is nothing to alter, and recording this as done would
            // hide the gap from whoever looks next.
            if (! tableExists($db, 'store')) {
                throw new RuntimeException('`store` is missing - AddStoreTable has not run');
            }

            // All four land NULL-able with a default of '', because Forge writes
            // no null clause on an addColumn. Do not tidy a NOT NULL in here or
            // this stops matching what spark produces.
            if (! columnExists($db, 'store', 's_location_label')) {
                run($db, 'ALTER TABLE `store`
                    ADD COLUMN `s_location_label` VARCHAR(255) DEFAULT ""
                    COMMENT ' . quoted('What to call the spot, when the street address alone will not find it') . '
                    AFTER `s_address`');
                $notes[] = 'added store.s_location_label';
            } else {
                $notes[] = 'store.s_location_label already there';
            }

            // Has to follow the label: its AFTER names the column added above.
            // 500 characters on purpose - a Google share link with a CID and a
            // plus-code runs past 255 on its own, and a shorter column would
            // truncate the pin into a dead link.
            if (! columnExists($db, 'store', 's_map_url')) {
                run($db, 'ALTER TABLE `store`
                    ADD COLUMN `s_map_url` VARCHAR(500) DEFAULT ""
                    COMMENT ' . quoted('Google Maps link for this location, pasted from Share > Copy link') . '
                    AFTER `s_location_label`');
                $notes[] = 'added store.s_map_url';
            } else {
                $notes[] = 'store.s_map_url already there';
            }

            if (! columnExists($db, 'store', 's_website')) {
                run($db, 'ALTER TABLE `store`
                    ADD COLUMN `s_website` VARCHAR(255) DEFAULT ""
                    COMMENT ' . quoted('This location\'s own page. Blank falls back to the owner\'s u_website.') . '
                    AFTER `s_phone`');
                $notes[] = 'added store.s_website';
            } else {
                $notes[] = 'store.s_website already there';
            }

            if (! columnExists($db, 'users', 'u_website')) {
                run($db, 'ALTER TABLE `users`
                    ADD COLUMN `u_website` VARCHAR(255) DEFAULT ""
                    COMMENT ' . quoted('The employer\'s web address - the group\'s site for a multi-store owner') . '
                    AFTER `u_email`');
                $notes[] = 'added users.u_website';
            } else {
                $notes[] = 'users.u_website already there';
            }

            return $notes;
        },
    ],
    [
        'version' => '2026-08-13-090000',
        'class'   => 'App\Database\Migrations\AddUserStoreId',
        'label'   => 'AddUserStoreId',
        'apply'   => static function (mysqli $db): array {
            $notes = [];

            // Which of the group's stores a manager runs. They used to type an
            // address and get a `store` row of their own; they now pick one the
            // group already added, so the row stays the group's and this says
            // which. 0 = the login owns its stores outright, which is every
            // account before this one.
            if (! columnExists($db, 'users', 'u_parent_id')) {
                throw new RuntimeException('users.u_parent_id is missing - AddUserParentId has not run');
            }

            if (! columnExists($db, 'users', 'u_store_id')) {
                run($db, 'ALTER TABLE `users`
                    ADD COLUMN `u_store_id` INT DEFAULT 0
                    COMMENT ' . quoted('store.s_id this manager runs, owned by their u_parent_id group. 0 = the login owns its own stores.') . '
                    AFTER `u_parent_id`');
                $notes[] = 'added users.u_store_id';
            } else {
                $notes[] = 'users.u_store_id already there';
            }

            // Managers registered before this change own the store they typed
            // in. Pointing the column at it keeps them resolving through the
            // same path as everyone else instead of needing a special case.
            $res = $db->query('SELECT COUNT(*) AS n
                                 FROM `users` u
                                 JOIN `store` s ON s.`u_id` = u.`u_id`
                                WHERE u.`u_emp_role` = 2
                                  AND u.`u_parent_id` > 0
                                  AND COALESCE(u.`u_store_id`, 0) = 0');

            $pending = $res === false ? 0 : (int) $res->fetch_assoc()['n'];

            if ($pending > 0) {
                run($db, 'UPDATE `users` u
                            JOIN `store` s ON s.`u_id` = u.`u_id`
                             SET u.`u_store_id` = s.`s_id`
                           WHERE u.`u_emp_role` = 2
                             AND u.`u_parent_id` > 0
                             AND COALESCE(u.`u_store_id`, 0) = 0');
                $notes[] = "pointed {$pending} existing manager(s) at the store they already owned";
            } else {
                $notes[] = 'no existing managers needed a store id';
            }

            return $notes;
        },
    ],
    [
        'version' => '2026-08-14-090000',
        'class'   => 'App\Database\Migrations\AddAdditionalDetailsTable',
        'label'   => 'AddAdditionalDetailsTable',
        'apply'   => static function (mysqli $db): array {
            $notes = [];

            // The Additional Details master - the same shape as store_service
            // and shift_for. ad_status defaults to 1 so the add form, which
            // posts a name and nothing else, does not leave the row Deactive.
            if (! tableExists($db, 'additional_details')) {
                run($db, 'CREATE TABLE `additional_details` (
                    `ad_id` INT NOT NULL AUTO_INCREMENT,
                    `ad_name` VARCHAR(100) NULL DEFAULT NULL,
                    `ad_status` INT NOT NULL DEFAULT 1,
                    PRIMARY KEY (`ad_id`)
                ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
                $notes[] = 'created additional_details';
            } else {
                $notes[] = 'additional_details already there';
            }

            return $notes;
        },
    ],
    [
        'version' => '2026-08-14-110000',
        'class'   => 'App\Database\Migrations\AddJobAdditionalDetails',
        'label'   => 'AddJobAdditionalDetails',
        'apply'   => static function (mysqli $db): array {
            $notes = [];

            // Which Additional Details a shift offers - the same comma
            // separated shape as p_services beside it, but wider: that column
            // is VARCHAR(100) and truncates its tail once the list grows.
            if (! tableExists($db, 'additional_details')) {
                throw new RuntimeException('additional_details is missing - AddAdditionalDetailsTable has not run');
            }

            if (! columnExists($db, 'post_job', 'p_additional_details')) {
                run($db, 'ALTER TABLE `post_job`
                    ADD COLUMN `p_additional_details` VARCHAR(255) NULL DEFAULT NULL
                    COMMENT ' . quoted('Comma separated additional_details.ad_id list, same shape as p_services') . '
                    AFTER `p_services`');
                $notes[] = 'added post_job.p_additional_details';
            } else {
                $notes[] = 'post_job.p_additional_details already there';
            }

            return $notes;
        },
    ],
    [
        'version' => '2026-08-14-140000',
        'class'   => 'App\Database\Migrations\AddStoreShiftDefaults',
        'label'   => 'AddStoreShiftDefaults',
        'apply'   => static function (mysqli $db): array {
            $notes = [];

            // What a store normally offers, so a shift posted against it starts
            // from those lists rather than from nothing. Same three shapes the
            // shift itself carries; blank on every existing store, which just
            // means those shifts start empty as they do today.
            $columns = [
                's_skills'             => ['s_website', 'Default software_skills.ss_id list for shifts here, same shape as post_job.p_skills'],
                's_services'           => ['s_skills', 'Default store_service.st_id list for shifts here, same shape as post_job.p_services'],
                's_additional_details' => ['s_services', 'Default additional_details.ad_id list for shifts here, same shape as post_job.p_additional_details'],
            ];

            foreach ($columns as $column => [$after, $comment]) {
                if (! columnExists($db, 'store', $column)) {
                    run($db, 'ALTER TABLE `store`
                        ADD COLUMN `' . $column . '` VARCHAR(255) NULL DEFAULT NULL
                        COMMENT ' . quoted($comment) . '
                        AFTER `' . $after . '`');
                    $notes[] = 'added store.' . $column;
                } else {
                    $notes[] = 'store.' . $column . ' already there';
                }
            }

            return $notes;
        },
    ],
    [
        'version' => '2026-08-14-160000',
        'class'   => 'App\Database\Migrations\MakeEmployersMultiStore',
        'label'   => 'MakeEmployersMultiStore',
        'apply'   => static function (mysqli $db): array {
            $notes = [];

            // Every employer becomes an Owner (Multi Store). The pre-B4 rows
            // carry role 0 and so belong to no kind, which left them out of
            // every kind filter and gave the "may this account hold a second
            // store" rule nothing to go on. Only the role moves; u_parent_id
            // is left alone, and role 1 is what employerKindSlug() reads first.
            $res     = $db->query('SELECT COUNT(*) AS n FROM `users` WHERE `u_usertype` = 1 AND `u_emp_role` <> 1');
            $pending = $res === false ? 0 : (int) $res->fetch_assoc()['n'];

            if ($pending > 0) {
                run($db, 'UPDATE `users` SET `u_emp_role` = 1 WHERE `u_usertype` = 1 AND `u_emp_role` <> 1');
                $notes[] = "made {$pending} employer(s) multi-store";
            } else {
                $notes[] = 'every employer is already multi-store';
            }

            return $notes;
        },
    ],
    [
        'version' => '2026-08-15-090000',
        'class'   => 'App\Database\Migrations\AddUserEmailBlocked',
        'label'   => 'AddUserEmailBlocked',
        'apply'   => static function (mysqli $db): array {
            $notes = [];

            // Which e-mails a user is opted out of, for Manage Email. Holds
            // what is BLOCKED so a blank row - every existing account, and
            // every new registration - receives everything, and a new e-mail
            // type added to the config later is on for everybody at once.
            if (! columnExists($db, 'users', 'u_email_blocked')) {
                run($db, 'ALTER TABLE `users`
                    ADD COLUMN `u_email_blocked` VARCHAR(100) NOT NULL DEFAULT \'\'
                    COMMENT ' . quoted('Comma separated AppSettings::$emailTypes codes this user must NOT receive. Blank = everything.') . '
                    AFTER `u_email`');
                $notes[] = 'added users.u_email_blocked';
            } else {
                $notes[] = 'users.u_email_blocked already there';
            }

            return $notes;
        },
    ],
    [
        'version' => '2026-08-15-120000',
        'class'   => 'App\Database\Migrations\BackfillManagerStoreSnapshot',
        'label'   => 'BackfillManagerStoreSnapshot',
        'apply'   => static function (mysqli $db): array {
            $notes = [];

            // A manager runs one of their group's stores, and the store's name
            // and address are copied onto their own users row - every screen
            // that names an employer reads those columns rather than joining
            // the store. Registration always did it; the back-office employer
            // form did not, so a manager added there was a nameless row on the
            // employer list and in the employer dropdown on both shift forms.
            //
            // Both forms do it now. This repairs the accounts made before they
            // did: blank columns only, and only from the store the account is
            // already attached to, so anything corrected by hand is kept.
            $res     = $db->query("SELECT COUNT(*) AS n
                                     FROM `users` u
                                     JOIN `store` s ON s.`s_id` = u.`u_store_id`
                                    WHERE u.`u_usertype` = 1 AND u.`u_emp_role` = 2
                                      AND COALESCE(u.`u_comp_name`, '') = ''");
            $pending = $res === false ? 0 : (int) $res->fetch_assoc()['n'];

            run($db, "UPDATE `users` u
                        JOIN `store` s ON s.`s_id` = u.`u_store_id`
                         SET u.`u_comp_name`  = CASE WHEN COALESCE(u.`u_comp_name`, '')  = '' THEN s.`s_name`     ELSE u.`u_comp_name` END,
                             u.`u_licence_no` = CASE WHEN COALESCE(u.`u_licence_no`, '') = '' THEN s.`s_number`   ELSE u.`u_licence_no` END,
                             u.`u_address1`   = CASE WHEN COALESCE(u.`u_address1`, '')   = '' THEN s.`s_address`  ELSE u.`u_address1` END,
                             u.`u_pincode`    = CASE WHEN COALESCE(u.`u_pincode`, '')    = '' THEN s.`s_pincode`  ELSE u.`u_pincode` END,
                             u.`u_l_provice`  = CASE WHEN COALESCE(u.`u_l_provice`, 0)   = 0  THEN s.`s_province` ELSE u.`u_l_provice` END,
                             u.`u_provice`    = CASE WHEN COALESCE(u.`u_provice`, 0)     = 0  THEN s.`s_province` ELSE u.`u_provice` END,
                             u.`u_city`       = CASE WHEN COALESCE(u.`u_city`, 0)        = 0  THEN s.`s_city`     ELSE u.`u_city` END
                       WHERE u.`u_usertype` = 1
                         AND u.`u_emp_role` = 2
                         AND COALESCE(u.`u_store_id`, 0) > 0");

            $notes[] = $pending > 0
                ? "filled in {$pending} nameless manager(s) from their store"
                : 'every manager already carries their store details';

            return $notes;
        },
    ],
    [
        'version' => '2026-08-17-090000',
        'class'   => 'App\Database\Migrations\AddTestimonialTable',
        'label'   => 'AddTestimonialTable',
        'apply'   => static function (mysqli $db): array {
            $notes = [];

            // The Testimonials master - the same shape as additional_details
            // beside it, but with two text columns rather than one: a
            // testimonial is a heading plus the quote, and the home page
            // carousel draws both. t_status defaults to 1 so the add form,
            // which posts the two text fields and nothing else, does not leave
            // a new testimonial Deactive and invisible.
            if (! tableExists($db, 'testimonial')) {
                run($db, 'CREATE TABLE `testimonial` (
                    `t_id` INT NOT NULL AUTO_INCREMENT,
                    `t_title` VARCHAR(150) NULL DEFAULT NULL,
                    `t_description` TEXT NULL DEFAULT NULL,
                    `t_status` INT NOT NULL DEFAULT 1,
                    PRIMARY KEY (`t_id`)
                ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
                $notes[] = 'created testimonial';
            } else {
                $notes[] = 'testimonial already there';
            }

            return $notes;
        },
    ],
    [
        'version' => '2026-08-18-100000',
        'class'   => 'App\Database\Migrations\AddShiftEmailRecipients',
        'label'   => 'AddShiftEmailRecipients',
        'apply'   => static function (mysqli $db): array {
            $notes = [];

            // Who "your shift is live" goes to, per shift: a comma separated
            // list of the words owner and manager, ticked on the shift form.
            if (! columnExists($db, 'post_job', 'p_email_to')) {
                run($db, "ALTER TABLE `post_job`
                          ADD COLUMN `p_email_to` VARCHAR(32) NOT NULL DEFAULT ''
                          COMMENT 'Comma separated: owner, manager. Empty = the fallback address.'
                          AFTER `p_approved`");
                $notes[] = 'added post_job.p_email_to';
            } else {
                $notes[] = 'post_job.p_email_to already there';
            }

            // Empty means "send to the fallback address", and every shift that
            // already exists was never asked - so they are set to the owner,
            // which is exactly who they mailed before the column existed.
            $res     = $db->query("SELECT COUNT(*) AS n FROM `post_job` WHERE `p_email_to` = ''");
            $pending = $res === false ? 0 : (int) $res->fetch_assoc()['n'];

            run($db, "UPDATE `post_job` SET `p_email_to` = 'owner' WHERE `p_email_to` = ''");

            $notes[] = $pending > 0
                ? "pointed {$pending} existing shift(s) at the store's owner, as before"
                : 'every shift already names who to e-mail';

            return $notes;
        },
    ],
    [
        'version' => '2026-08-20-090000',
        'class'   => 'App\Database\Migrations\AddUserAgreementDone',
        'label'   => 'AddUserAgreementDone',
        'apply'   => static function (mysqli $db): array {
            $notes = [];

            // Whether the signed agreement is on file, ticked on the applicant
            // and employer forms in the back office. Nobody is asked at
            // registration, so 0 is the right answer for every row that exists
            // - no backfill goes with this one.
            if (! columnExists($db, 'users', 'u_agreement_done')) {
                run($db, "ALTER TABLE `users`
                          ADD COLUMN `u_agreement_done` TINYINT(1) NOT NULL DEFAULT 0
                          COMMENT '1 when the signed agreement is on file. Ticked in the back office only.'
                          AFTER `u_status`");
                $notes[] = 'added users.u_agreement_done';
            } else {
                $notes[] = 'users.u_agreement_done already there';
            }

            return $notes;
        },
    ],
    [
        'version' => '2026-08-24-090000',
        'class'   => 'App\Database\Migrations\AddUserUnsubscribed',
        'label'   => 'AddUserUnsubscribed',
        'apply'   => static function (mysqli $db): array {
            $notes = [];

            // NULL, not 0: this records when somebody opted out as well as
            // that they did, and NULL means every account carries on receiving
            // exactly what it received before this ran.
            if (! columnExists($db, 'users', 'u_unsubscribed_at')) {
                run($db, "ALTER TABLE `users`
                          ADD COLUMN `u_unsubscribed_at` DATETIME NULL DEFAULT NULL
                          COMMENT 'When this user opted out of all optional e-mail. NULL = still subscribed.'
                          AFTER `u_email_blocked`");
                $notes[] = 'added users.u_unsubscribed_at';
            } else {
                $notes[] = 'users.u_unsubscribed_at already there';
            }

            if (! columnExists($db, 'users', 'u_unsub_token')) {
                run($db, "ALTER TABLE `users`
                          ADD COLUMN `u_unsub_token` VARCHAR(64) NOT NULL DEFAULT ''
                          COMMENT 'Secret in this user\'s unsubscribe link. Blank until first needed.'
                          AFTER `u_unsubscribed_at`");
                $notes[] = 'added users.u_unsub_token';
            } else {
                $notes[] = 'users.u_unsub_token already there';
            }

            // Not unique - rows sit at '' until their first e-mail, and a
            // unique key would refuse the second one.
            $idx = $db->query("SHOW INDEX FROM `users` WHERE Key_name = 'idx_users_unsub_token'");

            if ($idx && $idx->num_rows === 0) {
                run($db, 'CREATE INDEX `idx_users_unsub_token` ON `users` (`u_unsub_token`)');
                $notes[] = 'indexed users.u_unsub_token';
            } else {
                $notes[] = 'users.u_unsub_token already indexed';
            }

            // Give every existing account its token now, so the first e-mail
            // after this deploy is a plain read rather than a write. Done in
            // SQL rather than a PHP loop because this runs over a web request
            // on shared hosting, where a hundred round trips is a timeout.
            $filled = 0;

            if ($res = $db->query("SELECT `u_id` FROM `users` WHERE `u_unsub_token` = ''")) {
                while ($row = $res->fetch_assoc()) {
                    $token = bin2hex(random_bytes(16));
                    run($db, "UPDATE `users` SET `u_unsub_token` = '{$token}' WHERE `u_id` = " . (int) $row['u_id']);
                    $filled++;
                }
            }

            $notes[] = $filled > 0
                ? "gave {$filled} existing account(s) an unsubscribe token"
                : 'every account already has an unsubscribe token';

            return $notes;
        },
    ],
];

$applied = 0;
$failed  = 0;

foreach ($migrations as $m) {
    if (isset($done[$m['version']])) {
        printf("  [SKIP] %-28s already in migrations table\n", $m['label']);

        continue;
    }

    printf("  [RUN ] %s\n", $m['label']);

    try {
        foreach ($m['apply']($db) as $note) {
            echo "           - {$note}\n";
        }
    } catch (Throwable $e) {
        echo '           ! FAILED: ' . $e->getMessage() . "\n";
        echo "           ! Stopping here - later migrations assume this one ran.\n";
        $failed++;

        break;
    }

    $stmt = $db->prepare(
        'INSERT INTO `migrations` (`version`, `class`, `group`, `namespace`, `time`, `batch`)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $group = 'default';
    $ns    = 'App';
    $time  = time();
    $stmt->bind_param('ssssii', $m['version'], $m['class'], $group, $ns, $time, $batch);
    $stmt->execute();
    $stmt->close();

    echo "           - recorded in migrations table\n";
    $applied++;
}

$db->close();

echo "\n" . str_repeat('-', 72) . "\n";
printf("  %d applied, %d failed\n", $applied, $failed);
echo str_repeat('-', 72) . "\n";

echo "\nNow reload https://reliefshifts.com/staging/ - the front page should list shifts.\n";
echo "Then re-run diagnose.php to confirm, and delete both files.\n";
echo "\n*** DELETE THIS FILE NOW: migrate.php ***\n";
