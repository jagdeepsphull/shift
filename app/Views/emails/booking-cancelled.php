<?php

/**
 * Sent to an applicant whose booking has been taken back off them, from the
 * shift form in the back office: swapped for somebody else, cleared, or the
 * shift itself made inactive. The agency is copied, as it is on the booking.
 *
 * It carries no message from the administrator on purpose. The box on that
 * form is addressed to whoever is being booked, and forwarding it to the
 * person losing the shift would be telling them something meant for someone
 * else - so this says only what happened and who to talk to about it.
 *
 * The shift is named the same way as in the booking e-mail, store and all, so
 * somebody with several bookings can tell which one has gone.
 *
 * @var string      $name
 * @var array       $shift    row from `post_job`
 * @var array|null  $employer row from `users`
 * @var object|null $store    the shift's store, from `shiftStore()`
 * @var array       $settings
 */
$store    = $store ?? null;
$employer = $employer ?? null;
?>
<?= $this->extend('emails/layout') ?>

<?= $this->section('content') ?>
    <h2 style="margin: 0 0 14px; font-size: 18px; color: #222;">Hello, <?= esc($name) ?>!</h2>

    <p style="line-height: 1.6;">We are writing to let you know that your booking for
    <strong><?= esc($shift['p_job_title']) ?></strong><?= $employer ? ' at <strong>' . esc($employer['u_comp_name']) . '</strong>' : '' ?>
    has been cancelled. <strong>Please do not attend this shift.</strong></p>

    <p style="line-height: 1.6;">The booking was for:</p>

    <ul style="line-height: 1.7; padding-left: 20px;">
        <?php if ($store) { ?>
        <li>Store: <?= esc($store->s_name) ?><?= $store->s_number !== '' ? ' (no. ' . esc($store->s_number) . ')' : '' ?></li>
        <?php } ?>
        <li>Shift date: <?= esc(dateFormat($shift['p_dates'])) ?></li>
        <li>Shift time: <?= esc($shift['p_shift_time']) ?></li>
        <li>Role: <?= esc(getShiftForName($shift['p_shift_for'])) ?></li>
    </ul>

    <p style="line-height: 1.6;">You are free to apply for other shifts on the site as usual, and we will be in
    touch as more come up. If you think this is a mistake, or you would like to know more, please reply to this
    e-mail<?= ! empty($settings[0]->s_contactno) ? ' or call us on ' . esc($settings[0]->s_contactno) : '' ?>.</p>

    <p style="line-height: 1.6;">We are sorry for the inconvenience.</p>
<?= $this->endSection() ?>
