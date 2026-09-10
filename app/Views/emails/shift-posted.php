<?php

/**
 * Sent to the employer when an admin approves their shift and it goes live.
 *
 * The store is named as well as the shift: a chain's head office runs several,
 * and "your shift is live" alone does not say which branch it is at.
 *
 * @var string $name
 * @var string $shift_title
 * @var string $shift_date   already formatted for display, '' when unknown
 * @var string $store_name   '' for a shift standing against no store
 * @var array  $settings
 */
$site       = $settings[0]->s_sitename ?? 'PickAShift';
$shiftDate  = trim((string) ($shift_date ?? ''));
$storeName  = trim((string) ($store_name ?? ''));
?>
<?= $this->extend('emails/layout') ?>

<?= $this->section('content') ?>
    <h2 style="margin: 0 0 14px; font-size: 18px; color: #222;">Hello, <?= esc($name) ?>!</h2>

    <p style="line-height: 1.6;">Your shift <strong><?= esc($shift_title) ?></strong><?php
        if ($shiftDate !== '') { ?> for <strong><?= esc($shiftDate) ?></strong><?php }
        if ($storeName !== '') { ?> at <strong><?= esc($storeName) ?></strong><?php }
    ?> is now live on our platform.</p>

    <p style="line-height: 1.6;">Stay up to date &mdash; log in to your account to view your latest shift
    updates.</p>

    <p style="line-height: 1.6;">We truly appreciate your business and the opportunity to support your
    pharmacy, and look forward to working with you! Thank you for choosing <strong><?= esc($site) ?></strong>.</p>
<?= $this->endSection() ?>
