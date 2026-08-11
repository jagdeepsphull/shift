<?php

/**
 * Sent to the employer when an admin approves their shift and it goes live.
 *
 * @var string $name
 * @var string $shift_title
 * @var array  $settings
 */
$site = $settings[0]->s_sitename ?? 'PickAShift';
?>
<?= $this->extend('emails/layout') ?>

<?= $this->section('content') ?>
    <h2 style="margin: 0 0 14px; font-size: 18px; color: #222;">Hello, <?= esc($name) ?>!</h2>

    <p style="line-height: 1.6;">Great news — your shift <strong><?= esc($shift_title) ?></strong> has been
    posted and is now live on our platform.</p>

    <p style="line-height: 1.6;">Applicants can view and apply for it straight away. We will let you know each
    time a candidate submits an application.</p>

    <p style="line-height: 1.6;">Thank you for choosing <?= esc($site) ?> to fill your shift.</p>
<?= $this->endSection() ?>
