<?php

/**
 * One-shot deployment check. Upload to the site (or /staging/), open it, read
 * the result, then DELETE IT.
 *
 *   https://pickashift.ca/staging/diagnose.php?key=aa985b3d36a381d2
 *
 * It answers the question a 500 page will not: which of the half-dozen things a
 * fresh deploy needs is actually missing. It runs standalone - no framework, so
 * it still reports even when CodeIgniter cannot boot at all.
 *
 * It never prints a password, and the key stops a passer-by reading it.
 */

declare(strict_types=1);

const KEY = 'aa985b3d36a381d2';

if (($_GET['key'] ?? '') !== KEY) {
    header('HTTP/1.1 404 Not Found');
    exit('Not found');
}

header('Content-Type: text/plain; charset=utf-8');

$here = __DIR__;
$rows = [];

function row(string $label, bool $ok, string $detail = ''): void
{
    global $rows;
    $rows[] = [$ok ? 'OK  ' : 'FAIL', $label, $detail];
}

// ------------------------------------------------------------------- php ----

row('PHP >= 8.2', version_compare(PHP_VERSION, '8.2', '>='), PHP_VERSION);

foreach (['mysqli', 'intl', 'mbstring', 'json', 'curl'] as $ext) {
    row("ext {$ext}", extension_loaded($ext));
}

// ---------------------------------------------------------------- files -----

row('index.php', is_file("{$here}/index.php"));
row('vendor/ framework Boot.php', is_file("{$here}/vendor/codeigniter4/framework/system/Boot.php"));
row('app/Config/App.php', is_file("{$here}/app/Config/App.php"));
row('.env', is_file("{$here}/.env"), is_file("{$here}/.env") ? 'present' : 'MISSING - CI falls back to app/Config defaults');

// A repo upload rather than the built bundle. Harmless in itself, but it means
// .htaccess has no RewriteBase and writable/ is probably incomplete.
foreach (['.git', 'plan', 'tests'] as $shouldNotBeHere) {
    if (file_exists("{$here}/{$shouldNotBeHere}")) {
        row("{$shouldNotBeHere}/ present", false, 'this is the repo, not deploy/build/<target>/');
    }
}

// ------------------------------------------------------------- writable -----
// The usual cause of a blank 500. These four are git-ignored, so any git-based
// upload omits them and CodeIgniter cannot boot or even log why.

foreach (['writable', 'writable/cache', 'writable/logs', 'writable/session', 'writable/uploads'] as $dir) {
    $path = "{$here}/{$dir}";

    if (! is_dir($path)) {
        row($dir, false, 'MISSING - create it, 775');

        continue;
    }

    row($dir, is_writable($path), is_writable($path) ? 'writable' : 'NOT WRITABLE - chmod 775');
}

// ----------------------------------------------------------- .htaccess ------

$ht = is_file("{$here}/.htaccess") ? (string) file_get_contents("{$here}/.htaccess") : '';

// dirname() returns a backslash on Windows, which would read as a subfolder.
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$inSub     = $scriptDir !== '';

if ($inSub) {
    row('RewriteBase for subfolder', str_contains($ht, 'RewriteBase'),
        str_contains($ht, 'RewriteBase') ? 'present' : 'MISSING - subfolder URLs will 404');
}

// ----------------------------------------------------------------- .env -----

$env = [];

if (is_file("{$here}/.env")) {
    foreach (file("{$here}/.env", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);

        if ($line === '' || $line[0] === '#' || ! str_contains($line, '=')) {
            continue;
        }

        [$k, $v] = explode('=', $line, 2);
        $env[trim($k)] = trim(trim(trim($v), "'\""));
    }
}

row('CI_ENVIRONMENT', ($env['CI_ENVIRONMENT'] ?? '') !== '', $env['CI_ENVIRONMENT'] ?? 'not set');
row('app.baseURL', ($env['app.baseURL'] ?? '') !== '', $env['app.baseURL'] ?? 'not set');

$expected = ($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? '')
          . $scriptDir . '/';

if (isset($env['app.baseURL'])) {
    row('baseURL matches this URL', rtrim($env['app.baseURL'], '/') === rtrim($expected, '/'),
        'should be ' . $expected);
}

// ------------------------------------------------------------- database -----

$host = $env['database.default.hostname'] ?? 'localhost';
$name = $env['database.default.database'] ?? '';
$user = $env['database.default.username'] ?? '';
$pass = $env['database.default.password'] ?? '';

row('db name configured', $name !== '', $name !== '' ? $name : 'BLANK');

if ($name !== '' && extension_loaded('mysqli')) {
    $conn = @new mysqli($host, $user, $pass, $name);

    if ($conn->connect_errno) {
        // Message only - never the credentials themselves.
        row('db connect', false, 'error ' . $conn->connect_errno . ': ' . $conn->connect_error);
    } else {
        row('db connect', true, "connected to {$name}");

        $tables = [];
        $res    = $conn->query('SHOW TABLES');

        while ($r = $res->fetch_array()) {
            $tables[] = $r[0];
        }

        row('tables present', $tables !== [], count($tables) . ' tables'
            . ($tables === [] ? ' - EMPTY DATABASE, import the data first' : ''));

        // `store` is in this list because the shift form offers stores and
        // nothing else: without the table, posting a shift and three admin
        // screens all 500, and the front page is the only thing still working.
        // `additional_details` for the same reason - the shift form and both
        // store forms read it to draw their tick-box groups.
        foreach (['settings', 'users', 'post_job', 'store', 'additional_details', 'migrations'] as $need) {
            row("table {$need}", in_array($need, $tables, true));
        }

        // Which migrations are outstanding, read from the files rather than a
        // hand-written list. The version is the leading timestamp of the file
        // name, which is exactly what CodeIgniter records in `migrations`, so
        // the two compare directly - and a migration added next month is
        // covered here without anybody remembering to update this script.
        $files = glob("{$here}/app/Database/Migrations/*.php") ?: [];
        $want  = [];

        foreach ($files as $file) {
            if (preg_match('/^(\d{4}-\d{2}-\d{2}-\d{6})_(.+)\.php$/', basename($file), $m)) {
                $want[$m[1]] = $m[2];
            }
        }

        if ($want === []) {
            row('migration files', false, 'none found in app/Database/Migrations - is this the built bundle?');
        } elseif (! in_array('migrations', $tables, true)) {
            row('migrations applied', false, count($want) . ' to run - no migrations table yet, run them');
        } else {
            $applied = [];
            $res     = $conn->query('SELECT version FROM migrations');

            while ($r = $res->fetch_assoc()) {
                $applied[$r['version']] = true;
            }

            ksort($want);
            $missing = array_diff_key($want, $applied);

            row('migrations applied', $missing === [],
                $missing === []
                    ? count($want) . ' of ' . count($want)
                    : count($missing) . ' NOT applied: ' . implode(', ', $missing) . ' - run them');
        }

        $conn->close();
    }
}

// ----------------------------------------------------------------- logs -----

$logDir = "{$here}/writable/logs";
$latest = '';

if (is_dir($logDir)) {
    $files = glob($logDir . '/log-*.log') ?: [];
    rsort($files);
    $latest = $files[0] ?? '';
}

// ---------------------------------------------------------------- print -----

$fails = 0;

echo "PickAShift deployment check\n";
echo str_repeat('=', 72) . "\n";
echo 'Path : ' . $here . "\n";
echo 'URL  : ' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['SCRIPT_NAME'] ?? '') . "\n";
echo str_repeat('=', 72) . "\n\n";

foreach ($rows as [$status, $label, $detail]) {
    if ($status === 'FAIL') {
        $fails++;
    }

    printf("  [%s] %-38s %s\n", $status, $label, $detail);
}

echo "\n" . str_repeat('-', 72) . "\n";
echo $fails === 0 ? "  Everything checked passed.\n" : "  {$fails} problem(s) above.\n";
echo str_repeat('-', 72) . "\n";

if ($latest !== '') {
    echo "\nLast 40 lines of " . basename($latest) . ":\n" . str_repeat('-', 72) . "\n";
    $lines = file($latest) ?: [];
    echo implode('', array_slice($lines, -40));
} else {
    echo "\nNo CodeIgniter log found in writable/logs/.\n";
    echo "If the site is 500ing, the reason is in the host's PHP error_log instead\n";
    echo "(cPanel File Manager: error_log, in this folder or the one above it).\n";
}

echo "\n\n*** DELETE THIS FILE NOW: diagnose.php ***\n";
