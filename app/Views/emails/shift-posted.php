<?php

/**
 * Sent to the employer when an admin approves their shift and it goes live.
 *
 * Worded as the agency's e-mail spec has it (#3). The subject is set by the
 * sender, Sadmin::sendShiftPostedEmail.
 *
 * The store is named as well as the shift: a chain's head office runs several,
 * and "your shift is live" alone does not say which branch it is at.
 *
 * The spec gives this message no heading of its own, so the banner carries
 * only the site's name.
 *
 * @var string $name
 * @var string $shift_title
 * @var string $shift_date   already formatted for display, '' when unknown
 * @var string $store_name   '' for a shift standing against no store
 * @var array  $settings
 */
$site      = $settings[0]->s_sitename ?? 'Pick-A-Shift';
$shiftDate = trim((string) ($shift_date ?? ''));
$storeName = trim((string) ($store_name ?? ''));

$this->setVar('title', $site);
?>
<?= $this->extend('emails/layout') ?>

<?= $this->section('content') ?>
    <p style="line-height: 1.6;">Hello, <?= esc($name) ?>!</p>

    <p style="line-height: 1.6;">Your shift <strong><?= esc($shift_title) ?></strong><?php
        if ($shiftDate !== '') { ?> for <strong><?= esc($shiftDate) ?></strong><?php }
        if ($storeName !== '') { ?> at <strong><?= esc($storeName) ?></strong><?php }
    ?> is now live on our platform.</p>

    <p style="line-height: 1.6;">Stay up to date: Login to your account to view your latest shift updates.</p>

    <p style="line-height: 1.6;">We truly appreciate your business and the opportunity to support your pharmacy
    and look forward to working with you! Thank you for choosing <?= esc($site) ?>.</p>
<?= $this->endSection() ?>
