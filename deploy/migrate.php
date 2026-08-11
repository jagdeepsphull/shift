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
 * This does the same three migrations over mysqli, reading credentials from the
 * .env beside it, and writes the same rows into `migrations` that spark would -
 * so a later `spark migrate` sees them as done rather than re-running them into
 * a "duplicate column" error.
 *
 * Every step checks the schema before touching it, so running this twice, or
 * running it after a partial spark run, is safe.
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

// ------------------------------------------------------ migrations table ----
// Same shape CodeIgniter's MigrationRunner creates, so spark reads it happily.
// `group` is a reserved word - it has to stay quoted.

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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

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
