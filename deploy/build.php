<?php

/**
 * Assembles an upload-ready copy of the site.
 *
 *   php deploy/build.php staging
 *   php deploy/build.php production
 *
 * Output lands in deploy/build/<target>/. Upload the *contents* of that folder
 * (not the folder itself) to the matching directory on the server.
 *
 * The build is deliberately a whitelist: anything not named in ALLOW below does
 * not ship. That is what keeps .git, the plan documents, the CI3 backup, the
 * test suite and the local .env off a public server, rather than a blacklist
 * that silently misses whatever gets added next.
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$target = $argv[1] ?? '';
$makeZip = in_array('--zip', $argv, true);

/**
 * Split the bundle into a public half and a private one.
 *
 * Without it everything sits in the document root and `app/`, `vendor/`,
 * `writable/` and `.env` are kept out of reach by the rules in `.htaccess`.
 * That works - it is checked by the test suite - but it is one file away from
 * not working: a host that stops honouring `.htaccess`, an `AllowOverride`
 * changed during a server move, a migration to nginx, and every one of those
 * folders is suddenly a URL.
 *
 * With it, they are not under any document root at all. There is no rule to
 * honour, because there is nothing to reach. On this account the addon domain's
 * root is /home/<user>/pickashift.ca, so the private half sits beside it at
 * /home/<user>/pickashift_app - a sibling of the document roots, under none of
 * them.
 */
$split = in_array('--split', $argv, true);

/** The private folder's name, which the front controller looks for. */
$privateName = 'pickashift_app';

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--private=')) {
        $privateName = trim(substr($arg, strlen('--private=')), '/\\ ');
    }
}

if (! in_array($target, ['staging', 'production'], true)) {
    fwrite(STDERR, "Usage: php deploy/build.php staging|production [--zip] [--split] [--private=NAME]\n");
    exit(1);
}

if ($split && $target === 'staging') {
    // Staging lives in a subfolder of a document root, so "outside the document
    // root" is a different question there and the answer is not this.
    fwrite(STDERR, "  --split is for production. Staging is a subfolder deploy.\n");
    exit(1);
}

if ($privateName === '' || ! preg_match('/^[A-Za-z0-9._-]+$/', $privateName)) {
    fwrite(STDERR, "  --private must be a plain folder name, e.g. --private=pickashift_app\n");
    exit(1);
}

/** Everything that ships. Nothing else does. */
const ALLOW = [
    'index.php',
    'preload.php',
    'spark',
    'composer.json',
    'composer.lock',
    'app',
    'assets',
];

/** Skipped inside the folders above. */
const SKIP_DIRS = ['.git', 'node_modules', '.vscode', '.idea'];

/**
 * File types that never run in a browser.
 *
 * `.map` alone is 45 MB of the asset tree - source maps are read by devtools
 * and by nothing else. `.psd` is design source. Dropping both cuts the upload
 * by roughly a third and changes nothing a visitor can see.
 */
const SKIP_EXT = ['map', 'psd', 'ai', 'sketch'];

$out   = $root . '/deploy/build/' . $target;
$isStg = $target === 'staging';

echo "Building {$target}\n";
echo str_repeat('-', 60) . "\n";

// ---------------------------------------------------------------- clean ----

if (is_dir($out)) {
    rrmdir($out);
}

mkdir($out, 0755, true);

// ----------------------------------------------------------- application ----

$copied = 0;

foreach (ALLOW as $entry) {
    $src = $root . '/' . $entry;

    if (! file_exists($src)) {
        fwrite(STDERR, "  MISSING: {$entry}\n");

        continue;
    }

    $copied += is_dir($src) ? copyTree($src, $out . '/' . $entry) : (int) copy($src, $out . '/' . $entry);
}

echo "  application files: {$copied}\n";

// ---------------------------------------------------------------- vendor ----
// Built fresh rather than copied: the local vendor/ carries PHPUnit, Faker and
// vfsStream, none of which belong on a public server.

echo "  vendor: composer install --no-dev ...\n";

$cmd = sprintf(
    'composer install --no-dev --optimize-autoloader --classmap-authoritative --no-interaction --no-progress --working-dir=%s 2>&1',
    escapeshellarg($out)
);

exec($cmd, $composerOut, $code);

if ($code !== 0) {
    fwrite(STDERR, "  composer failed:\n    " . implode("\n    ", $composerOut) . "\n");
    exit(1);
}

foreach (['phpunit', 'fakerphp', 'mikey179'] as $dev) {
    if (is_dir($out . '/vendor/' . $dev)) {
        fwrite(STDERR, "  WARNING: dev package {$dev} present in vendor/\n");
    }
}

echo "  vendor: ok\n";

// -------------------------------------------------------------- writable ----
// Shipped as empty folders. Cache, logs and sessions are per-server state; the
// local ones are noise at best and a disclosure at worst.

foreach (['cache', 'logs', 'session', 'debugbar', 'uploads'] as $dir) {
    mkdir($out . '/writable/' . $dir, 0775, true);
    file_put_contents($out . '/writable/' . $dir . '/index.html', '');
}

file_put_contents($out . '/writable/index.html', '');

// Sessions and logs live in here. The root .htaccess already refuses the whole
// folder; this is the same refusal from inside it, so a host that ignores the
// outer rule - or a folder moved by hand - is still covered.
file_put_contents(
    $out . '/writable/.htaccess',
    "# Sessions, logs and cache. Nothing in here is ever served.\n"
    . "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n"
    . "<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n"
);

// The live uploads folder holds real user files. It is created here only so a
// first deploy has somewhere to write; UPLOAD.md warns never to overwrite it.
mkdir($out . '/uploads', 0775, true);
file_put_contents($out . '/uploads/index.html', '');

// This one is not a skeleton - it is the rule that stops anything uploaded
// here from being executed, and the bundle is wrong without it.
if (! is_file($root . '/uploads/.htaccess')) {
    fwrite(STDERR, "  ERROR: uploads/.htaccess is missing from the repository\n");
    exit(1);
}

copy($root . '/uploads/.htaccess', $out . '/uploads/.htaccess');

echo "  writable/ + uploads/: skeleton created, both closed to the web\n";

// ------------------------------------------------------------------ .env ----

$env = file_get_contents($root . '/deploy/templates/env.' . $target);

// The cron key is generated rather than left blank. Blank means the cron URLs
// return 404, which is the safe way for them to fail - but it fails the shift
// reminders too, silently, and "fill this in" is exactly the line that gets
// skipped. A key that is already there only has to be copied into the cron
// entry, and it is a different one in every bundle.
$cronKey = bin2hex(random_bytes(16));
$env     = preg_replace('/^cron\.key\s*=.*$/m', 'cron.key = ' . $cronKey, $env, 1);

file_put_contents($out . '/.env', $env);

echo "  .env: from templates/env.{$target}\n";
echo "  .env: cron.key generated - {$cronKey}\n";
echo "        cron URL: /cron/remind_shifts?key={$cronKey}\n";

// -------------------------------------------------------------- htaccess ----
// Generated from the live .htaccess so the two cannot drift. Staging gets a
// RewriteBase: without it mod_rewrite resolves the front controller against the
// document root and every URL under /staging/ 404s.

$ht = file_get_contents($root . '/.htaccess');

if ($isStg) {
    // After the first `RewriteEngine On` - the front controller's block; the
    // second one further down is the canonical-https redirect, which issues
    // absolute redirects and has no use for a base.
    //
    // Matched with a pattern rather than a literal string. The literal that
    // used to be here carried \n line endings and named the comment that
    // followed it, so the day `.htaccess` was saved with CRLF - which it now
    // has - the replace silently did nothing and the build stopped here. The
    // captured line ending is reused, so the file keeps whichever it has.
    $ht = preg_replace(
        '/([ \t]*)RewriteEngine On[ \t]*(\r?\n)/',
        '$1RewriteEngine On$2$1RewriteBase /staging/$2',
        $ht,
        1
    );

    if ($ht === null || ! str_contains($ht, 'RewriteBase /staging/')) {
        fwrite(STDERR, "  ERROR: could not insert RewriteBase - no 'RewriteEngine On' line in .htaccess\n");
        exit(1);
    }

    // Staging lives on the same hostname, so the SSL redirect block would send
    // it to the canonical site. Harmless over https, kept for parity.
    echo "  .htaccess: RewriteBase /staging/ added\n";
} else {
    echo "  .htaccess: copied as-is\n";
}

file_put_contents($out . '/.htaccess', $ht);

// ------------------------------------------------------------ robots.txt ----

if ($isStg) {
    // A staging copy on a live domain will be crawled and indexed otherwise,
    // and duplicate content is the least of it - half-approved copy goes public.
    file_put_contents($out . '/robots.txt', "User-agent: *\nDisallow: /\n");
    echo "  robots.txt: staging is disallowed from indexing\n";
} else {
    copy($root . '/robots.txt', $out . '/robots.txt');
    echo "  robots.txt: copied\n";
}

// ----------------------------------------------------------------- split ----
// Public half and private half. Up to here the bundle has been assembled flat,
// which is also the shape it ships in without --split; this pulls it apart.
//
// What has to stay in the document root is exactly what a browser asks for by
// URL: the front controller, the rules that route to it, the stylesheets and
// scripts, and the files people have uploaded. Everything else is machinery.

/** Where the two halves end up. Both are `$out` when the bundle is flat. */
$pub  = $out;
$priv = $out;

if ($split) {
    $pub  = $out . '/site';
    $priv = $out . '/private/' . $privateName;

    mkdir($pub, 0755, true);
    mkdir($priv, 0755, true);

    // `uploads/` is public because the site serves those files by URL - a logo
    // in a page, a licence scan an administrator opens. It keeps its own
    // .htaccess, which is what stops anything in there being executed.
    $public = ['index.php', '.htaccess', 'robots.txt', 'assets', 'uploads'];

    // `preload.php` goes with the application: it is an opcache script that
    // names vendor paths, and it is never requested over the web.
    $private = ['app', 'vendor', 'writable', '.env', 'composer.json', 'composer.lock', 'spark', 'preload.php'];

    foreach ([[$public, $pub], [$private, $priv]] as [$entries, $dest]) {
        foreach ($entries as $entry) {
            if (! file_exists($out . '/' . $entry)) {
                continue;
            }

            if (! rename($out . '/' . $entry, $dest . '/' . $entry)) {
                fwrite(STDERR, "  ERROR: could not move {$entry} into " . basename($dest) . "\n");
                exit(1);
            }
        }
    }

    // Anything the two lists forgot would be left behind in the root, where it
    // would be neither uploaded nor missed. Better to stop than to ship it.
    $stranded = array_diff(scandir($out) ?: [], ['.', '..', 'site', 'private']);

    if ($stranded !== []) {
        fwrite(STDERR, '  ERROR: not sorted into a half: ' . implode(', ', $stranded) . "\n");
        exit(1);
    }

    // The front controller is the only file that has to know where the other
    // half went.
    $index = (string) file_get_contents($pub . '/index.php');
    $needle = "require FCPATH . 'app/Config/Paths.php';";

    if (! str_contains($index, $needle)) {
        fwrite(STDERR, "  ERROR: index.php does not have the line that loads Paths.php\n");
        exit(1);
    }

    $locate = <<<PHP
        // The application itself is not in this folder, and not under any
        // document root: app/, vendor/, writable/ and .env live one level up,
        // in {$privateName}/. They have no URL at all - not a blocked one,
        // none - so no rule has to hold for them to stay unreachable.
        //
        // Built by deploy/build.php --split. FCPATH stays the document root, so
        // uploads/ and assets/ still resolve here, where the browser expects.
        \$privateDir = realpath(FCPATH . '../{$privateName}');

        if (\$privateDir === false || ! is_file(\$privateDir . '/app/Config/Paths.php')) {
            header('HTTP/1.1 503 Service Unavailable.', true, 503);

            echo 'Application files not found. This document root expects them in '
                . '../{$privateName}/ - see deploy/UPLOAD.md.';

            exit(1);
        }

        define('PRIVATEPATH', \$privateDir . DIRECTORY_SEPARATOR);

        require PRIVATEPATH . 'app/Config/Paths.php';
        PHP;

    // The heredoc above is indented for readability here; the file gets it flush.
    $locate = preg_replace('/^        /m', '', $locate);

    file_put_contents($pub . '/index.php', str_replace($needle, $locate, $index));

    echo "  split: site/ (document root) + private/{$privateName}/ (above it)\n";
}

// ---------------------------------------------------------------- report ----

[$files, $bytes] = measure($out);

echo str_repeat('-', 60) . "\n";
printf("  %d files, %.1f MB\n", $files, $bytes / 1048576);
echo "  -> deploy/build/{$target}/\n\n";

foreach (['.git', 'plan', 'tests', 'phpunit.dist.xml', '_backup_ci3_20260804', 'env'] as $mustNot) {
    foreach (array_unique([$pub, $priv]) as $half) {
        if (file_exists($half . '/' . $mustNot)) {
            fwrite(STDERR, "  LEAK: {$mustNot} is in the bundle\n");
            exit(1);
        }
    }
}

// The bundle is useless without the framework, and a dropped vendor/ shows up
// as a blank "Whoops!" page rather than anything that names the cause. Fail the
// build here rather than let that reach a server.
$boot = $priv . '/vendor/codeigniter4/framework/system/Boot.php';

if (! is_file($boot)) {
    fwrite(STDERR, "  ERROR: vendor/codeigniter4/framework/system/Boot.php missing\n");
    exit(1);
}

echo "  Checked: no .git, plan/, tests/ or local env in the bundle.\n";
echo "  Checked: framework Boot.php present.\n";

// ------------------------------------------------------------ safety net ----
// Each of these has been wrong at least once, and every one of them fails
// silently: the site works perfectly and is simply less safe than it reads.

$mustContain = [
    [$pub,  'uploads/.htaccess',                 'Require all denied'],
    [$priv, 'writable/.htaccess',                'Require all denied'],
    [$pub,  '.htaccess',                         'X-Content-Type-Options'],
    [$priv, 'app/Config/Security.php',           "csrfProtection = 'session'"],
    [$priv, 'app/Filters/CsrfTokenInjector.php', 'injectIntoForms'],

    // The filter names these two by path. Left behind, they are a 404 on every
    // page and no spinner anywhere - which looks like a slow server rather
    // than a missing file, and nothing logs it.
    [$pub,  'assets/common/form-spinner.js',     'carryOver'],
    [$pub,  'assets/common/form-spinner.css',    'pas-spin'],

    // The front controller has to agree with the layout it was built for.
    // A split bundle whose index.php still looks for app/ beside itself is a
    // 503 on every page, and a flat one rewritten for a private folder is the
    // same. Neither is visible until it is on a server.
    [$pub,  'index.php',                         $split ? 'PRIVATEPATH' : "FCPATH . 'app/Config/Paths.php'"],
];

foreach ($mustContain as [$half, $file, $needle]) {
    $path = $half . '/' . $file;

    if (! is_file($path) || ! str_contains((string) file_get_contents($path), $needle)) {
        fwrite(STDERR, "  ERROR: {$file} is missing or does not contain \"{$needle}\"\n");
        exit(1);
    }
}

// The CSRF check and the filter that puts the token in the page are one
// feature. Shipping the first without the second locks every form on the site.
$filters = (string) file_get_contents($priv . '/app/Config/Filters.php');

if (! preg_match("/'before'\s*=>\s*\[[^\]]*'csrf'/s", $filters) || ! str_contains($filters, "'csrftoken'")) {
    fwrite(STDERR, "  ERROR: the csrf filter and its token injector are not both registered in Config/Filters.php\n");
    exit(1);
}

echo "  Checked: uploads/ and writable/ closed, security headers set, CSRF on.\n";

// ------------------------------------------------------------------- zip ----
// One file uploads reliably; 5,000 over FTP do not. A dropped or half-copied
// vendor/ is the usual result, and it is invisible until the site 500s.

if ($makeZip && ! $split) {
    zipUp($out, $root . '/deploy/build/pickashift-' . $target . '.zip', '');

    echo "  Upload that one file into the document root, then Extract it.\n";
}

if ($makeZip && $split) {
    // Two archives, because the halves are extracted in two different places.
    //
    // The private one carries its own folder name inside it, so extracting it
    // in the home directory creates `<name>/` rather than emptying the
    // application over whatever is already there. The public one does not -
    // its contents go straight into the document root, which already exists.
    zipUp($pub, $root . '/deploy/build/pickashift-site.zip', '');
    zipUp($priv, $root . '/deploy/build/pickashift-private.zip', $privateName . '/');

    echo "\n";
    echo "  Upload and extract these in two places:\n";
    echo "    pickashift-site.zip     -> the document root  (pickashift.ca/)\n";
    echo "    pickashift-private.zip  -> the home directory (creates {$privateName}/ beside it)\n";
}

if ($split) {
    echo "\n";
    echo "  Layout on the server:\n";
    echo "    /home/<user>/pickashift.ca/   index.php  .htaccess  robots.txt  assets/  uploads/\n";
    echo "    /home/<user>/{$privateName}/" . str_repeat(' ', max(1, 14 - strlen($privateName)))
        . "app/  vendor/  writable/  .env  spark\n";
    echo "\n";
    echo "  The two must stay siblings: index.php looks for ../{$privateName}/.\n";
}

echo "  Next: deploy/UPLOAD.md\n";

// --------------------------------------------------------------- helpers ----

/**
 * Zip a tree, optionally under a folder name inside the archive.
 *
 * @param string $prefix '' puts the contents at the archive root; 'name/' wraps
 *                       them in a folder of that name
 */
function zipUp(string $dir, string $zipPath, string $prefix): void
{
    if (file_exists($zipPath)) {
        unlink($zipPath);
    }

    $zip = new ZipArchive();

    if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
        fwrite(STDERR, "  ERROR: could not create {$zipPath}\n");
        exit(1);
    }

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );

    $n = 0;

    foreach ($it as $file) {
        if (! $file->isFile()) {
            continue;
        }

        $local = $prefix . str_replace('\\', '/', substr($file->getPathname(), strlen($dir) + 1));
        $zip->addFile($file->getPathname(), $local);
        $n++;
    }

    $zip->close();

    printf("  zip: %d entries, %.1f MB -> deploy/build/%s\n", $n, filesize($zipPath) / 1048576, basename($zipPath));
}

function copyTree(string $src, string $dst): int
{
    $n = 0;

    if (! is_dir($dst)) {
        mkdir($dst, 0755, true);
    }

    foreach (scandir($src) as $item) {
        if ($item === '.' || $item === '..' || in_array($item, SKIP_DIRS, true)) {
            continue;
        }

        $from = $src . '/' . $item;
        $to   = $dst . '/' . $item;

        if (is_dir($from)) {
            $n += copyTree($from, $to);

            continue;
        }

        if (in_array(strtolower(pathinfo($item, PATHINFO_EXTENSION)), SKIP_EXT, true)) {
            continue;
        }

        copy($from, $to);
        $n++;
    }

    return $n;
}

function rrmdir(string $dir): void
{
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $dir . '/' . $item;
        is_dir($path) ? rrmdir($path) : unlink($path);
    }

    rmdir($dir);
}

function measure(string $dir): array
{
    $files = 0;
    $bytes = 0;

    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));

    foreach ($it as $f) {
        if ($f->isFile()) {
            $files++;
            $bytes += $f->getSize();
        }
    }

    return [$files, $bytes];
}
