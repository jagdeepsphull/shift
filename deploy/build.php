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

if (! in_array($target, ['staging', 'production'], true)) {
    fwrite(STDERR, "Usage: php deploy/build.php staging|production [--zip]\n");
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

// The live uploads folder holds real user files. It is created here only so a
// first deploy has somewhere to write; UPLOAD.md warns never to overwrite it.
mkdir($out . '/uploads', 0775, true);
file_put_contents($out . '/uploads/index.html', '');

echo "  writable/ + uploads/: skeleton created\n";

// ------------------------------------------------------------------ .env ----

$env = file_get_contents($root . '/deploy/templates/env.' . $target);
file_put_contents($out . '/.env', $env);

echo "  .env: from templates/env.{$target}\n";

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

// ---------------------------------------------------------------- report ----

[$files, $bytes] = measure($out);

echo str_repeat('-', 60) . "\n";
printf("  %d files, %.1f MB\n", $files, $bytes / 1048576);
echo "  -> deploy/build/{$target}/\n\n";

foreach (['.git', 'plan', 'tests', 'phpunit.dist.xml', '_backup_ci3_20260804', 'env'] as $mustNot) {
    if (file_exists($out . '/' . $mustNot)) {
        fwrite(STDERR, "  LEAK: {$mustNot} is in the bundle\n");
        exit(1);
    }
}

// The bundle is useless without the framework, and a dropped vendor/ shows up
// as a blank "Whoops!" page rather than anything that names the cause. Fail the
// build here rather than let that reach a server.
$boot = $out . '/vendor/codeigniter4/framework/system/Boot.php';

if (! is_file($boot)) {
    fwrite(STDERR, "  ERROR: vendor/codeigniter4/framework/system/Boot.php missing\n");
    exit(1);
}

echo "  Checked: no .git, plan/, tests/ or local env in the bundle.\n";
echo "  Checked: framework Boot.php present.\n";

// ------------------------------------------------------------------- zip ----
// One file uploads reliably; 5,000 over FTP do not. A dropped or half-copied
// vendor/ is the usual result, and it is invisible until the site 500s.

if ($makeZip) {
    $zipPath = $root . '/deploy/build/pickashift-' . $target . '.zip';

    if (file_exists($zipPath)) {
        unlink($zipPath);
    }

    $zip = new ZipArchive();

    if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
        fwrite(STDERR, "  ERROR: could not create {$zipPath}\n");
        exit(1);
    }

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($out, FilesystemIterator::SKIP_DOTS)
    );

    $n = 0;

    foreach ($it as $file) {
        if (! $file->isFile()) {
            continue;
        }

        $local = str_replace('\\', '/', substr($file->getPathname(), strlen($out) + 1));
        $zip->addFile($file->getPathname(), $local);
        $n++;
    }

    $zip->close();

    printf("  zip: %d entries, %.1f MB -> deploy/build/%s\n", $n, filesize($zipPath) / 1048576, basename($zipPath));
    echo "  Upload that one file, then Extract it in cPanel File Manager.\n";
}

echo "  Next: deploy/UPLOAD.md\n";

// --------------------------------------------------------------- helpers ----

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
