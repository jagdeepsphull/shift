<?php

/**
 * Sent to the employer when an admin approves an applicant for their shift. The
 * agency is copied on this one (see `getAgencyCopyEmail()`).
 *
 * No rate is shown. Both halves of a booking used to carry one - what the
 * employer is billed here, what the applicant is paid on their copy - and the
 * spec took them off both: the figure is on the portal, and an e-mail is
 * forwarded far more casually than a login is shared.
 *
 * @var string      $name           the employer's contact name
 * @var string      $applicant_name
 * @var object      $applicant      row from `users`
 * @var array       $shift          row from `post_job`
 * @var object|null $store          the shift's store, from `shiftStore()`
 * @var array       $settings
 */
$store = $store ?? null;
?>
<?= $this->extend('emails/layout') ?>

<?= $this->section('content') ?>
    <h2 style="margin: 0 0 14px; font-size: 18px; color: #222;">Hello, <?= esc($name) ?>!</h2>

    <p style="line-height: 1.6;">An applicant has been approved for your shift
    <strong><?= esc($shift['p_job_title']) ?></strong>.</p>

    <p style="line-height: 1.6;">Here are their details:</p>

    <ul style="line-height: 1.7; padding-left: 20px;">
        <?php if ($store) { ?>
        <?php /* Which branch, for a chain: the head office reading this may run
           a dozen, and "your shift" alone does not say which one. */ ?>
        <li>Store: <?= esc($store->s_name) ?><?= $store->s_number !== '' ? ' (no. ' . esc($store->s_number) . ')' : '' ?></li>
        <?php } ?>
        <li>Applicant name: <?= esc($applicant_name) ?></li>
        <li>Applicant type: <?= esc(getShiftForName($applicant->u_usersubtype)) ?></li>
        <li>Licence no.: <?= esc($applicant->u_licence_no) ?></li>
        <li>Licence province: <?= esc(getProvinceName($applicant->u_l_provice)) ?></li>
        <li>Shift requested for: <?= esc(getShiftForName($shift['p_shift_for'])) ?></li>
        <li>Shift date: <?= esc(dateFormat($shift['p_dates'])) ?></li>
        <li>Shift time: <?= esc($shift['p_shift_time']) ?></li>
        <li>Software: <?= esc(getSoftwareSkills($shift['p_skills'])) ?></li>
        <li>Services: <?= esc(getStoreServices($shift['p_services'])) ?></li>
    </ul>

    <p style="line-height: 1.6;">Thank you for choosing <?= esc($settings[0]->s_sitename ?? 'PickAShift') ?>.
    We appreciate the opportunity to support your pharmacy and look forward to assisting you with your future
    staffing needs.</p>
<?= $this->endSection() ?>
