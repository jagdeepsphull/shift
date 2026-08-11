<?php

/**
 * Sent to the applicant when an admin approves them for a shift. The agency is
 * copied on this one (see `getAgencyCopyEmail()`).
 *
 * Note the rate shown here is `p_ac_hourly_rate` — what the applicant is paid —
 * which is deliberately not the rate on the employer's copy of this booking.
 *
 * @var string $name
 * @var array  $shift            row from `post_job`
 * @var array  $employer         row from `users`
 * @var string $approval_comment
 * @var array  $settings
 */
?>
<?= $this->extend('emails/layout') ?>

<?= $this->section('content') ?>
    <h2 style="margin: 0 0 14px; font-size: 18px; color: #222;">Hello, <?= esc($name) ?>!</h2>

    <p style="line-height: 1.6;">We are delighted to tell you that you have been approved for
    <strong><?= esc($shift['p_job_title']) ?></strong> at
    <strong><?= esc($employer['u_comp_name']) ?></strong>.</p>

    <p style="line-height: 1.6;">Here are the details:</p>

    <ul style="line-height: 1.7; padding-left: 20px;">
        <li>Store no.: <?= esc($employer['u_licence_no']) ?></li>
        <li>Store address: <?= esc($employer['u_address1']) ?>, <?= esc(getCityName($employer['u_city'])) ?>, <?= esc(getProvinceName($employer['u_provice'])) ?>, <?= esc($employer['u_pincode']) ?></li>
        <li>Shift requested for: <?= esc(getShiftForName($shift['p_shift_for'])) ?></li>
        <li>Shift date: <?= esc(dateFormat($shift['p_dates'])) ?></li>
        <li>Shift time: <?= esc($shift['p_shift_time']) ?></li>
        <li>Rate: CAD$ <?= esc($shift['p_ac_hourly_rate']) ?>/hour</li>
        <li>Software: <?= esc(getSoftwareSkills($shift['p_skills'])) ?></li>
        <li>Services: <?= esc(getStoreServices($shift['p_services'])) ?></li>
        <?php if (trim((string) $approval_comment) !== '') { ?>
            <li>Message from the agency: <?= esc($approval_comment) ?></li>
        <?php } ?>
    </ul>

    <p style="line-height: 1.6;">We wish you all the best for the shift.</p>
<?= $this->endSection() ?>
