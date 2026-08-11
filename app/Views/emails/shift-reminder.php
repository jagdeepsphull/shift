<?php

/**
 * Sent to a booked applicant the day before their shift.
 *
 * @var string $name
 * @var array  $shift     row from `post_job`
 * @var array  $employer  row from `users`
 * @var array  $settings
 */
?>
<?= $this->extend('emails/layout') ?>

<?= $this->section('content') ?>
    <h2 style="margin: 0 0 14px; font-size: 18px; color: #222;">Hello, <?= esc($name) ?>!</h2>

    <p style="line-height: 1.6;">A reminder that you are booked for a shift <strong>tomorrow</strong>,
    <?= esc(dateFormat($shift['p_dates'], 'l j F Y')) ?>.</p>

    <ul style="line-height: 1.7; padding-left: 20px;">
        <li>Store: <?= esc($employer['u_comp_name']) ?> (no. <?= esc($employer['u_licence_no']) ?>)</li>
        <li>Address: <?= esc($employer['u_address1']) ?>, <?= esc(getCityName($employer['u_city'])) ?>, <?= esc(getProvinceName($employer['u_provice'])) ?>, <?= esc($employer['u_pincode']) ?></li>
        <li>Shift time: <?= esc($shift['p_shift_time']) ?></li>
        <li>Rate: CAD$ <?= esc($shift['p_ac_hourly_rate']) ?>/hour</li>
        <li>Role: <?= esc(getShiftForName($shift['p_shift_for'])) ?></li>
        <li>Software: <?= esc(getSoftwareSkills($shift['p_skills'])) ?></li>
    </ul>

    <p style="line-height: 1.6;">If you can no longer attend, tell us as soon as you can so the pharmacy can be
    covered.</p>
<?= $this->endSection() ?>
