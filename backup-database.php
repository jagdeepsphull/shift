#!/usr/bin/env php
<?php

/**
 * A full backup of the database, zipped and e-mailed.
 *
 * Written to be pointed at from cPanel's Cron Jobs screen, once a day:
 *
 *   /usr/local/bin/php /home/<user>/pickashift_app/backup-database.php
 *
 * On a bundle built without --split the application sits in the document root
 * instead, so the path is /home/<user>/pickashift.ca/backup-database.php. The
 * file always sits beside `app/` and `vendor/`, wherever those went.
 *
 * It stands on its own: no route reaches it, no controller calls it, and
 * nothing else in the application knows it exists. It loads the framework only
 * to read `.env` and to send the message - the database credentials and the
 * SMTP settings are the ones the site already uses, so there is no second copy
 * of either to keep in step.
 *
 * WHAT IT DOES
 *   1. Dumps every table, structure and data, with mysqldump where the host
 *      allows it and in PHP where it does not.
 *   2. Zips the dump. A dump of this database is text and compresses to about a
 *      tenth, which is the difference between a mail Gmail accepts and one it
 *      refuses.
 *   3. E-mails the zip to everybody in `backup.to`.
 *   4. Deletes its own old zips, so the account does not fill up unattended.
 *
 * OPTIONS
 *   --to=a@b.com,c@d.com   send to these instead of `backup.to` in .env
 *   --keep=14              days of backups to keep on the server (default 14)
 *   --no-mail              write the zip and send nothing
 *   --php-dump             skip mysqldump and use the PHP dump, to prove that
 *                          the fallback works on this host before a day comes
 *                          when it is the only one that does
 *   --quiet                print only failures. cron mails you whatever a job
 *                          prints, so this is the difference between an e-mail
 *                          every morning and one only when it matters
 *
 * A failure is e-mailed too. A backup that stops running is the thing nobody
 * notices until the morning it is needed, so silence here has to mean the
 * backup ran - not that the cron entry was deleted six weeks ago.
 */

declare(strict_types=1);

// ------------------------------------------------------------------ guards --

/*
 * Never over the web. The build puts this file where no URL reaches it - the
 * private half of a split deploy, or behind the .htaccess rule that refuses it
 * in a flat one - but a backup a stranger can trigger, which e-mails the whole
 * database when they do, is worth refusing twice. This check depends on no
 * server honouring anything.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);

    exit("Not found\n");
}

if (str_starts_with(PHP_SAPI, 'cgi')) {
    exit("This script needs php-cli, not php-cgi.\n");
}

if (version_compare(PHP_VERSION, '8.2', '<')) {
    exit('PHP 8.2 or higher is needed. This is ' . PHP_VERSION . ".\n");
}

error_reporting(E_ALL);
ini_set('display_errors', '1');

// The PHP dump holds one chunk of one table at a time, not the whole database,
// but the framework and the zip still want room. This only lifts a limit low
// enough to be a problem; cPanel's usual CLI default is already above it.
if (in_array((string) ini_get('memory_limit'), ['16M', '32M', '64M'], true)) {
    ini_set('memory_limit', '256M');
}

// Nothing here is quick, and no browser is waiting on it.
set_time_limit(0);

// --------------------------------------------------------------- bootstrap --

define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
chdir(FCPATH);

if (! is_file(FCPATH . 'app/Config/Paths.php')) {
    exit("Cannot find app/Config/Paths.php beside this file.\nThis script has to sit in the same folder as app/ and vendor/.\n");
}

require FCPATH . 'app/Config/Paths.php';

$paths = new Config\Paths();

require $paths->systemDirectory . '/Boot.php';

/*
 * bootConsole leaves ENVIRONMENT to whoever calls it, and the framework's own
 * caller for it defaults to 'development' - which on a live server would turn
 * on the debug toolbar and full error pages. A cron job has to run in the
 * environment the site runs in, so `.env` is read here first and the constant
 * is worked out the way bootWeb works it out. Boot reads `.env` again on its
 * way through; DotEnv leaves a variable that is already set alone.
 */
require_once $paths->systemDirectory . '/Config/DotEnv.php';

(new CodeIgniter\Config\DotEnv($paths->envDirectory ?? FCPATH))->load();

define('ENVIRONMENT', $_ENV['CI_ENVIRONMENT'] ?? $_SERVER['CI_ENVIRONMENT'] ?? getenv('CI_ENVIRONMENT') ?: 'production');

// bootConsole, not bootSpark: this is not a spark command and has no command
// name to route. It gives the autoloader, the config classes and the services,
// which is all of the framework this needs.
CodeIgniter\Boot::bootConsole($paths);

helper('common');

// ----------------------------------------------------------------- options --

/** `--flag` answers '', `--flag=value` answers the value, absent answers null. */
$option = static function (string $name) use ($argv): ?string {
    foreach ($argv as $arg) {
        if ($arg === '--' . $name) {
            return '';
        }

        if (str_starts_with($arg, '--' . $name . '=')) {
            return substr($arg, strlen($name) + 3);
        }
    }

    return null;
};

$quiet   = $option('quiet') !== null;
$noMail  = $option('no-mail') !== null;
$phpDump = $option('php-dump') !== null;
$keep    = max(1, (int) ($option('keep') ?? env('backup.keep', 14)));
$toRaw   = (string) ($option('to') ?? env('backup.to', ''));

// Gmail refuses anything over 25 MB and counts the base64 encoding, which adds
// about a third. Twenty leaves room for that and for the message around it.
$maxBytes = (int) round((float) env('backup.maxAttachMB', 20) * 1024 * 1024);

$addresses  = array_filter(array_map('trim', explode(',', $toRaw)), 'strlen');
$recipients = array_values(array_filter(
    $addresses,
    static fn ($address) => filter_var($address, FILTER_VALIDATE_EMAIL) !== false
));

$lines   = [];
$started = microtime(true);

/** Collected rather than echoed, so --quiet can throw the lot away. */
$say = static function (string $line) use (&$lines): void {
    $lines[] = $line;
};

$say('PickAShift database backup - ' . date('Y-m-d H:i:s'));
$say(str_repeat('-', 62));

foreach (array_diff($addresses, $recipients) as $bad) {
    $say("  WARNING: '{$bad}' is not an e-mail address and was skipped");
}

// ------------------------------------------------------------------- paths --

$dir = rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'backups';

if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
    backupFailed($lines, $recipients, $noMail, "Cannot create {$dir}");
}

$db      = config('Database')->default;
$dbName  = (string) ($db['database'] ?? '');
$base    = 'pickashift-' . ($dbName !== '' ? $dbName : 'db') . '-' . date('Y-m-d-His');
$sqlFile = $dir . DIRECTORY_SEPARATOR . $base . '.sql';
$zipFile = $dir . DIRECTORY_SEPARATOR . $base . '.zip';

// -------------------------------------------------------------------- dump --

$tool = 'mysqldump';

try {
    $mysqldump = $phpDump ? null : findMysqldump();

    if ($mysqldump === null) {
        $tool = 'PHP';
        $say($phpDump
            ? '  mysqldump: skipped (--php-dump), dumping in PHP instead'
            : '  mysqldump: not available on this host, dumping in PHP instead');
        dumpWithPhp($sqlFile, $say);
    } else {
        dumpWithMysqldump($mysqldump, $db, $sqlFile, $say);
    }
} catch (Throwable $e) {
    // A half-written dump is worse than none. It restores without complaint and
    // stops in the middle of a table.
    @unlink($sqlFile);

    backupFailed($lines, $recipients, $noMail, 'Dump failed: ' . $e->getMessage());
}

$sqlSize = (int) filesize($sqlFile);

$say(sprintf('  dump: %s, via %s', human($sqlSize), $tool));

// --------------------------------------------------------------------- zip --

try {
    zipUp($sqlFile, $zipFile, basename($sqlFile));
} catch (Throwable $e) {
    @unlink($sqlFile);
    @unlink($zipFile);

    backupFailed($lines, $recipients, $noMail, 'Zip failed: ' . $e->getMessage());
}

// The .sql was only ever the raw material for the zip.
@unlink($sqlFile);

$zipSize = (int) filesize($zipFile);

$say(sprintf(
    '  zip:  %s, %s (%d%% of the dump)',
    basename($zipFile),
    human($zipSize),
    $sqlSize > 0 ? (int) round($zipSize / $sqlSize * 100) : 100
));

// ------------------------------------------------------------------- prune --

if (($removed = prune($dir, $keep)) > 0) {
    $say(sprintf('  kept: %d days, %d older backup(s) deleted', $keep, $removed));
}

// -------------------------------------------------------------------- mail --

if ($noMail) {
    $say('  mail: skipped (--no-mail)');
} elseif ($recipients === []) {
    $say('  mail: nobody to send to. Set backup.to in .env, or pass --to=');
} else {
    $tooBig = $zipSize > $maxBytes;

    $body = email_body('backup', [
        'title'    => 'Database backup',
        'failure'  => '',
        'database' => $dbName,
        'file'     => basename($zipFile),
        'size'     => human($zipSize),
        'rawSize'  => human($sqlSize),
        'tool'     => $tool,
        'taken_at' => date('l j F Y, g:ia'),
        'keep'     => $keep,
        'path'     => $dir,
        'attached' => ! $tooBig,
        'maxSize'  => human($maxBytes),
    ]);

    if ($tooBig) {
        $say(sprintf('  mail: the zip is over %s, sending a notice instead of the file', human($maxBytes)));
    }

    if (! sendBackupMail($recipients, siteName() . ' database backup - ' . date('j M Y'), $body, $tooBig ? null : $zipFile)) {
        // The zip is on disk either way, which is why this is not a dump
        // failure: the backup was taken, only the delivery of it failed.
        $say('  mail: FAILED. What SMTP said is in writable/logs');
        echo implode("\n", $lines) . "\n";

        exit(1);
    }

    $say('  mail: sent to ' . implode(', ', $recipients));
}

$say(str_repeat('-', 62));
$say(sprintf('Done in %.1fs.', microtime(true) - $started));

if (! $quiet) {
    echo implode("\n", $lines) . "\n";
}

exit(0);

// ============================================================================
// The work
// ============================================================================

/**
 * Stop, say why, and tell somebody.
 *
 * Always printed, whatever --quiet says: this is the run cron should mail on.
 *
 * @param string[] $lines
 * @param string[] $recipients
 */
function backupFailed(array $lines, array $recipients, bool $noMail, string $message): void
{
    $lines[] = '  ERROR: ' . $message;

    if (! $noMail && $recipients !== []) {
        $body = email_body('backup', [
            'title'    => 'Database backup FAILED',
            'failure'  => $message,
            'taken_at' => date('l j F Y, g:ia'),
            'attached' => false,
        ]);

        sendBackupMail($recipients, siteName() . ' database backup FAILED - ' . date('j M Y'), $body, null);
    }

    echo implode("\n", $lines) . "\n";

    exit(1);
}

/** The site's own name, for the subject line. */
function siteName(): string
{
    $settings = model('App\Models\CustomModel')->getSettings();

    return (string) ($settings[0]->s_sitename ?? 'PickAShift');
}

/**
 * Send one message, with the zip on it where there is one.
 *
 * `send_email()` in the common helper is not used: it attaches nothing, and it
 * appends the unsubscribe machinery that belongs on mail to applicants and
 * employers. Nobody unsubscribes from their own backups.
 *
 * @param string[] $recipients
 */
function sendBackupMail(array $recipients, string $subject, string $body, ?string $attachment): bool
{
    $settings = config('AppSettings');
    $email    = service('email');

    // Protocol, host and credentials come from app/Config/Email.php, which
    // reads them from .env - the same SMTP the rest of the site sends over.
    $email->initialize([
        'mailType' => 'html',
        'charset'  => 'utf-8',
        'newline'  => "\r\n",
        'CRLF'     => "\r\n",
    ]);

    $email->setFrom($settings->mailFromEmail, $settings->mailFromName);

    // The first to To:, the rest bcc. These are administrators, and a To: line
    // naming all of them puts each one's address in front of the others every
    // morning for as long as the job runs.
    $email->setTo($recipients[0]);

    if (count($recipients) > 1) {
        $email->setBCC(array_slice($recipients, 1));
    }

    $email->setSubject($subject);

    // The layout carries an unsubscribe block that the sending helper finishes.
    // Nothing finishes it here, so it is stripped.
    $email->setMessage(apply_unsubscribe_link($body, ''));
    $email->setAltMessage(trim(html_entity_decode(strip_tags($body), ENT_QUOTES, 'UTF-8')));

    if ($attachment !== null) {
        $email->attach($attachment);
    }

    if ($email->send(false)) {
        return true;
    }

    log_message('error', 'Backup e-mail failed: ' . $email->printDebugger(['headers']));

    return false;
}

/**
 * Where mysqldump is, or null on a host that has no shell.
 *
 * Shared hosting varies: some accounts have it on PATH, some only at a full
 * path, and some disable exec() altogether. All three are ordinary, and the
 * last is what the PHP dump below exists for.
 */
function findMysqldump(): ?string
{
    $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

    if (! function_exists('exec') || in_array('exec', $disabled, true)) {
        return null;
    }

    $candidates = ['mysqldump'];

    foreach (['/usr/bin', '/usr/local/bin', '/usr/local/mysql/bin', '/opt/cpanel/mysql/bin'] as $path) {
        $candidates[] = $path . '/mysqldump';
    }

    foreach ($candidates as $candidate) {
        $out  = [];
        $code = 1;

        @exec(escapeshellarg($candidate) . ' --version 2>/dev/null', $out, $code);

        if ($code === 0) {
            return $candidate;
        }
    }

    return null;
}

/**
 * Dump with mysqldump, which is the one that gets everything.
 *
 * The password goes in a defaults file rather than on the command line:
 * arguments are visible in `ps` to every other account on a shared server, for
 * as long as the dump runs.
 */
function dumpWithMysqldump(string $binary, array $db, string $file, callable $say): void
{
    $defaults = tempnam(sys_get_temp_dir(), 'pas');

    if ($defaults === false) {
        throw new RuntimeException('cannot write a temporary defaults file');
    }

    // Before the password goes in, not after: tempnam creates it readable by
    // more than this account on some hosts.
    @chmod($defaults, 0600);

    $quote = static fn (string $value): string => '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';

    file_put_contents($defaults, implode("\n", [
        '[client]',
        'user=' . $quote((string) ($db['username'] ?? '')),
        'password=' . $quote((string) ($db['password'] ?? '')),
        'host=' . $quote((string) ($db['hostname'] ?? 'localhost')),
        'port=' . (int) ($db['port'] ?? 3306),
        '',
    ]));

    /*
     * --single-transaction  a consistent picture of InnoDB without locking the
     *                       site out of its own tables while the dump runs
     * --no-tablespaces      asks for nothing needing the PROCESS privilege,
     *                       which a cPanel database user does not have. Without
     *                       it, MySQL 8 refuses the whole dump
     * --routines --triggers --events   everything that is not a table
     */
    $attempts = [
        '--single-transaction --quick --no-tablespaces --add-drop-table --default-character-set=utf8mb4 --routines --triggers --events',
        // Some hosts refuse --events to an account without the EVENT privilege
        // and fail the whole dump over it. This database has no events, so the
        // second run is a complete backup rather than a partial one - and the
        // log says which run produced the file either way.
        '--single-transaction --quick --no-tablespaces --add-drop-table --default-character-set=utf8mb4',
    ];

    $error = '';

    foreach ($attempts as $i => $flags) {
        $cmd = sprintf(
            '%s --defaults-extra-file=%s %s %s > %s 2>&1',
            escapeshellarg($binary),
            escapeshellarg($defaults),
            $flags,
            escapeshellarg((string) ($db['database'] ?? '')),
            escapeshellarg($file)
        );

        $out  = [];
        $code = 1;

        @exec($cmd, $out, $code);

        if ($code === 0 && dumpLooksComplete($file)) {
            if ($i > 0) {
                $say('  mysqldump: retried without --routines/--triggers/--events, which this account may not read');
            }

            @unlink($defaults);

            return;
        }

        // stderr was redirected into the file, so that is where the reason is.
        $error = trim((string) @file_get_contents($file));
    }

    @unlink($defaults);
    @unlink($file);

    throw new RuntimeException('mysqldump: ' . ($error !== '' ? substr($error, 0, 400) : 'exited non-zero'));
}

/**
 * Did mysqldump finish, or stop in the middle?
 *
 * It writes "Dump completed" as its last line. One cut off by a timeout or a
 * full disk has no such line, and is a file that restores halfway and looks
 * like it worked.
 */
function dumpLooksComplete(string $file): bool
{
    if (! is_file($file) || filesize($file) < 100) {
        return false;
    }

    $handle = fopen($file, 'rb');

    if ($handle === false) {
        return false;
    }

    fseek($handle, -200, SEEK_END);
    $tail = (string) fread($handle, 200);
    fclose($handle);

    return str_contains($tail, 'Dump completed');
}

/**
 * Dump in PHP, for a host with no shell at all.
 *
 * Structure and rows for every table, written a chunk at a time so a large
 * table does not have to fit in memory. This database is tables only - no
 * views, triggers or stored routines - so this is a complete backup of it. It
 * says so out loud if any of those ever appear, because it cannot carry them,
 * and a backup that quietly leaves something behind is not a backup.
 */
function dumpWithPhp(string $file, callable $say): void
{
    $db     = db_connect();
    $handle = fopen($file, 'wb');

    if ($handle === false) {
        throw new RuntimeException("cannot write {$file}");
    }

    $write = static function (string $sql) use ($handle): void {
        if (fwrite($handle, $sql) === false) {
            throw new RuntimeException('write failed - is the disk full?');
        }
    };

    $schema = $db->getDatabase();

    $write("-- PickAShift database backup\n");
    $write('-- Database: ' . $schema . "\n");
    $write('-- Taken:    ' . date('Y-m-d H:i:s') . " (PHP dump - no mysqldump on this host)\n\n");
    $write("SET NAMES utf8mb4;\n");
    $write("SET FOREIGN_KEY_CHECKS = 0;\n");
    $write("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n");

    foreach (['TRIGGERS' => 'TRIGGER_SCHEMA', 'ROUTINES' => 'ROUTINE_SCHEMA'] as $what => $column) {
        $found = (int) $db->query(
            "SELECT COUNT(*) AS c FROM information_schema.{$what} WHERE {$column} = ?",
            [$schema]
        )->getRow()->c;

        if ($found > 0) {
            $say("  WARNING: {$found} {$what} are NOT in this dump - the PHP dump cannot carry them");
            $write("\n-- WARNING: {$found} {$what} were not dumped.\n");
        }
    }

    foreach ($db->listTables() as $table) {
        $create = $db->query('SHOW CREATE TABLE ' . bq($table))->getRowArray();

        $write("\n--\n-- {$table}\n--\n\n");

        if (isset($create['Create View'])) {
            $write('DROP VIEW IF EXISTS ' . bq($table) . ";\n");
            $write($create['Create View'] . ";\n");

            continue;
        }

        $write('DROP TABLE IF EXISTS ' . bq($table) . ";\n");
        $write(($create['Create Table'] ?? '') . ";\n\n");

        // The columns, and which of them hold bytes rather than text.
        // `hourly_rate.hr_name` is varbinary, and escaping bytes as if they
        // were a string is how a dump comes back holding something other than
        // what went in. Read now, before the rows start streaming: an
        // unbuffered result has the connection to itself until it is finished
        // with, and a second query while one is open fails.
        $columnNames = [];
        $binary      = [];

        foreach ($db->query(
            'SELECT COLUMN_NAME, DATA_TYPE FROM information_schema.COLUMNS'
            . ' WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
            [$schema, $table]
        )->getResultArray() as $column) {
            $columnNames[] = $column['COLUMN_NAME'];

            if (in_array(strtolower($column['DATA_TYPE']), ['blob', 'tinyblob', 'mediumblob', 'longblob', 'binary', 'varbinary'], true)) {
                $binary[$column['COLUMN_NAME']] = true;
            }
        }

        $columns = '(' . implode(', ', array_map('bq', $columnNames)) . ')';
        $rows    = 0;
        $values  = [];

        /*
         * Streamed, not paged. `LIMIT 500 OFFSET n` makes the server count past
         * every row it has already sent, so the cost of a table grows with the
         * square of its size - fine for the small tables here, an hour for one
         * with a million rows in it. An unbuffered result reads once, in order,
         * and holds one row at a time rather than the table.
         */
        $streaming = property_exists($db, 'resultMode') && defined('MYSQLI_USE_RESULT');

        if ($streaming) {
            $db->resultMode = MYSQLI_USE_RESULT;
        }

        try {
            $result = $db->query('SELECT * FROM ' . bq($table));

            while (($row = $result->getUnbufferedRow('array')) !== null) {
                $cells = [];

                foreach ($row as $name => $value) {
                    if ($value === null) {
                        $cells[] = 'NULL';
                    } elseif (isset($binary[$name])) {
                        // 0x is not valid SQL on its own, so an empty one stays
                        // an empty string.
                        $cells[] = $value === '' ? "''" : '0x' . bin2hex((string) $value);
                    } else {
                        $cells[] = $db->escape($value);
                    }
                }

                $values[] = '(' . implode(', ', $cells) . ')';
                $rows++;

                // Written away in batches: one INSERT per row triples the size
                // of the dump and the time it takes to restore, and holding
                // them all is the memory this loop exists to avoid.
                if (count($values) >= 500) {
                    $write('INSERT INTO ' . bq($table) . ' ' . $columns . " VALUES\n" . implode(",\n", $values) . ";\n");
                    $values = [];
                }
            }

            if ($values !== []) {
                $write('INSERT INTO ' . bq($table) . ' ' . $columns . " VALUES\n" . implode(",\n", $values) . ";\n");
            }

            $result->freeResult();
        } finally {
            // Whatever happened, the connection goes back to buffered results -
            // everything after this, including the next table's columns, is an
            // ordinary query.
            if ($streaming) {
                $db->resultMode = MYSQLI_STORE_RESULT;
            }
        }

        $write("\n-- {$rows} row(s) in {$table}\n");
    }

    $write("\nSET FOREIGN_KEY_CHECKS = 1;\n");
    $write('-- Dump completed on ' . date('Y-m-d H:i:s') . "\n");

    fclose($handle);
}

/** A table or column name, quoted for the dump. */
function bq(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

/** Put the dump in a zip of its own. */
function zipUp(string $source, string $target, string $entryName): void
{
    if (! class_exists('ZipArchive')) {
        throw new RuntimeException('the zip extension is not installed on this host');
    }

    $zip = new ZipArchive();

    if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException("cannot create {$target}");
    }

    if (! $zip->addFile($source, $entryName)) {
        $zip->close();

        throw new RuntimeException('cannot add the dump to the zip');
    }

    if (! $zip->close()) {
        throw new RuntimeException('the zip would not close - out of disk space?');
    }
}

/**
 * Delete this script's own old backups.
 *
 * Matched by the name it writes, not by age alone: a backups folder is
 * somewhere people also put things by hand, and a nightly job that deletes on
 * age alone will eventually delete one of those.
 */
function prune(string $dir, int $keepDays): int
{
    $cutoff  = time() - $keepDays * 86400;
    $removed = 0;

    foreach ((array) glob($dir . DIRECTORY_SEPARATOR . 'pickashift-*-[0-9]*.zip') as $file) {
        if (is_file($file) && filemtime($file) < $cutoff && @unlink($file)) {
            $removed++;
        }
    }

    return $removed;
}

/** Bytes, in the units a person reads. */
function human(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }

    if ($bytes < 1024 * 1024) {
        return round($bytes / 1024, 1) . ' KB';
    }

    return round($bytes / 1024 / 1024, 1) . ' MB';
}
