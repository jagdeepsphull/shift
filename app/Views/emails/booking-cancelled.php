<?php

/**
 * Sent to an applicant whose booking has been taken back off them, from the
 * shift form in the back office: swapped for somebody else, cleared, or the
 * shift itself made inactive. The agency is copied, as it is on the booking.
 *
 * Worded as the agency's e-mail spec has it (#6). The subject is set by the
 * sender, Sadmin::sendCancellationEmail. The spec names the support address
 * inside its own paragraph, so the layout's usual line is switched off rather
 * than saying it twice.
 *
 * It carries no message from the administrator on purpose. The box on that
 * form is addressed to whoever is being booked, and forwarding it to the
 * person losing the shift would be telling them something meant for someone
 * else - so this says only what happened and who to talk to about it.
 *
 * The shift is named the same way as in the booking e-mail, store and all, so
 * somebody with several bookings can tell which one has gone - by the store's
 * own name rather than the group the owner trades under, for the same reason.
 *
 * @var string      $name
 * @var array       $shift    row from `post_job`
 * @var array|null  $employer row from `users`
 * @var object|null $store    the shift's store, from `shiftStore()`
 * @var array       $settings
 */
$store     = $store ?? null;
$employer  = $employer ?? null;
$pharmacy  = $store ? $store->s_name : ($employer['u_comp_name'] ?? '');
$shiftDate = dateFormat($shift['p_dates'] ?? null);
$site      = $settings[0]->s_sitename ?? 'Pick-A-Shift';
$supportTo = config('AppSettings')->supportEmail;

$this->setVar('title', 'Your upcoming shift has been cancelled');
$this->setVar('support_line', false);
?>
<?= $this->extend('emails/layout') ?>

<?= $this->section('content') ?>
    <p style="line-height: 1.6;">Hello, <?= esc($name) ?>!</p>

    <?php /* "at [Store Name] on [SHIFT DATE]" is bold as one run in the spec;
       either half is left out when the shift has nothing to fill it. */ ?>
    <p style="line-height: 1.6;">Your upcoming shift <strong><?= esc($shift['p_job_title']) ?></strong><?php
        if ($pharmacy !== '') { ?> at <strong><?= esc($pharmacy) ?><?= $shiftDate !== '' ? ' on ' . esc($shiftDate) : '' ?></strong><?php }
        elseif ($shiftDate !== '') { ?> on <strong><?= esc($shiftDate) ?></strong><?php }
    ?> has been cancelled. <strong>Please do not attend this shift.</strong></p>

    <p style="line-height: 1.6;">Details :</p>

    <ul style="line-height: 1.7; padding-left: 20px;">
        <?php if ($store) { ?>
        <li>Store: <?= esc($store->s_name) ?><?= $store->s_number !== '' ? ' (no. ' . esc($store->s_number) . ')' : '' ?></li>
        <?php } ?>
        <li>Shift Date: <?= esc($shiftDate) ?></li>
        <li>Shift Time: <?= esc($shift['p_shift_time']) ?></li>
        <li>Role: <?= esc(getShiftForName($shift['p_shift_for'])) ?></li>
    </ul>

    <p style="line-height: 1.6;">We appreciate your understanding and welcome you to apply for other upcoming
    shifts available through <?= esc($site) ?> as we update them regularly. If you have any questions or believe
    this cancellation was made in error, please contact our support team at
    <a href="mailto:<?= esc($supportTo) ?>" style="color: #7c3aed;"><?= esc($supportTo) ?></a>.</p>

    <p style="line-height: 1.6;">We apologize for any inconvenience this may cause.</p>
<?= $this->endSection() ?>
