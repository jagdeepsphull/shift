<?php

/**
 * Sent by `backup-database.php`, the nightly cron job.
 *
 * The one e-mail in here that is not about somebody's account. It goes to the
 * addresses in `backup.to`, carries the zip, and is the only sign the job is
 * still running - so it says what was taken and how big it was, and a failure
 * says what went wrong rather than simply not arriving.
 *
 * @var string $title    banner; says FAILED on a bad run
 * @var string $failure  why it failed, or '' on a good run
 * @var string $taken_at when the run started
 * @var bool   $attached is the zip on this message
 * @var string $file     the zip's name
 * @var string $size     the zip
 * @var string $rawSize  the dump inside it
 * @var string $database the database that was dumped
 * @var string $tool     mysqldump, or PHP where the host has no shell
 * @var int    $keep     days of backups kept on the server
 * @var string $path     the folder they are kept in
 * @var string $maxSize  the most this job will attach
 */
$failure = $failure ?? '';
?>
<?= $this->extend('emails/layout') ?>

<?= $this->section('content') ?>
<?php if ($failure !== '') { ?>
    <h2 style="margin: 0 0 14px; font-size: 18px; color: #b00020;">The backup did not run</h2>

    <p style="line-height: 1.6;">The nightly database backup was attempted at
    <strong><?= esc($taken_at) ?></strong> and did not finish. Nothing was saved and
    nothing is attached to this message.</p>

    <p style="line-height: 1.6; background: #fdf1f3; border-left: 3px solid #b00020; padding: 10px 12px;">
        <strong>What went wrong:</strong><br>
        <?= esc($failure) ?>
    </p>

    <p style="line-height: 1.6;">Until this is fixed there is no backup from today.
    Running the job by hand over SSH prints the same message with more around it:</p>

    <p style="line-height: 1.6; font-family: monospace; font-size: 13px; background: #f4f4f7; padding: 10px 12px;">
        php backup-database.php
    </p>
<?php } else { ?>
    <h2 style="margin: 0 0 14px; font-size: 18px; color: #222;">Database backup</h2>

    <p style="line-height: 1.6;">The backup of <strong><?= esc($database ?? '') ?></strong> ran at
    <strong><?= esc($taken_at) ?></strong> and finished.</p>

    <?php if ($attached) { ?>
    <p style="line-height: 1.6;">The zip is attached to this message:
    <strong><?= esc($file ?? '') ?></strong> (<?= esc($size ?? '') ?>). Unzip it for the
    <code>.sql</code> file, which restores through phpMyAdmin or <code>mysql &lt; file.sql</code>.</p>
    <?php } else { ?>
    <p style="line-height: 1.6; background: #fff8e6; border-left: 3px solid #d18b00; padding: 10px 12px;">
        <strong>The zip is not attached.</strong> At <?= esc($size ?? '') ?> it is over the
        <?= esc($maxSize ?? '') ?> this job will send, and a mail that size is refused rather
        than delivered. It is on the server at <code><?= esc(($path ?? '') . DIRECTORY_SEPARATOR . ($file ?? '')) ?></code>
        - download it from cPanel's File Manager.
    </p>
    <?php } ?>

    <ul style="line-height: 1.7; padding-left: 20px; margin: 0 0 14px;">
        <li>Zipped: <strong><?= esc($size ?? '') ?></strong>, from a <?= esc($rawSize ?? '') ?> dump</li>
        <li>Taken with: <strong><?= esc($tool ?? '') ?></strong></li>
        <li>Kept on the server for <strong><?= esc((string) ($keep ?? '')) ?> days</strong>, then deleted</li>
    </ul>

    <p style="line-height: 1.6;">Nothing needs doing. This message arrives once a day, and the
    day it stops arriving is the day to look at the cron job.</p>
<?php } ?>
<?= $this->endSection() ?>
