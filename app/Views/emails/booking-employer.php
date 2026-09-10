<?php

/**
 * Sent to the employer when an admin approves an applicant for their shift. The
 * agency is copied on this one (see `getAgencyCopyEmail()`).
 *
 * Worded as the agency's e-mail spec has it (#5), which greets the store by
 * first name only and lets the banner say what happened. The subject is set by
 * the sender, Sadmin::sendBookingEmails.
 *
 * No rate is shown. Both halves of a booking used to carry one - what the
 * employer is billed here, what the applicant is paid on their copy - and the
 * spec took them off both: the figure is on the portal, and an e-mail is
 * forwarded far more casually than a login is shared.
 *
 * @var string      $name           the employer's contact name
 * @var string      $first_name     what the greeting uses; $name when absent
 * @var string      $applicant_name
 * @var object      $applicant      row from `users`
 * @var array       $shift          row from `post_job`
 * @var object|null $store          the shift's store, from `shiftStore()`
 * @var array       $settings
 */
$store     = $store ?? null;
$site      = $settings[0]->s_sitename ?? 'Pick-A-Shift';
$firstName = trim((string) ($first_name ?? '')) !== '' ? trim($first_name) : $name;

$this->setVar('title', 'An applicant has been approved for your shift');
?>
<?= $this->extend('emails/layout') ?>

<?= $this->section('content') ?>
    <p style="line-height: 1.6;">Hello, <?= esc($firstName) ?></p>

    <p style="line-height: 1.6;">Here are the details:</p>

    <ul style="line-height: 1.7; padding-left: 20px;">
        <?php if ($store) { ?>
        <?php /* Which branch, for a chain: the head office reading this may run
           a dozen, and "your shift" alone does not say which one. */ ?>
        <li>Store: <?= esc($store->s_name) ?><?= $store->s_number !== '' ? ' (no. ' . esc($store->s_number) . ')' : '' ?></li>
        <?php } ?>
        <li>Applicant name: <?= esc($applicant_name) ?></li>
        <li>Applicant Type: <?= esc(getShiftForName($applicant->u_usersubtype)) ?></li>
        <li>Licence No.: <?= esc($applicant->u_licence_no) ?></li>
        <li>Licence Province: <?= esc(getProvinceName($applicant->u_l_provice)) ?></li>
        <li>Shift requested for: <?= esc(getShiftForName($shift['p_shift_for'])) ?></li>
        <li>Shift Date: <?= esc(dateFormat($shift['p_dates'])) ?></li>
        <li>Shift Time: <?= esc($shift['p_shift_time']) ?></li>
        <li>Software: <?= esc(getSoftwareSkills($shift['p_skills'])) ?></li>
        <li>Services: <?= esc(getStoreServices($shift['p_services'])) ?></li>
    </ul>

    <p style="line-height: 1.6;">Thank you for choosing <?= esc($site) ?>. We appreciate the opportunity to
    support your pharmacy and look forward to assisting you with your future staffing needs.</p>
<?= $this->endSection() ?>
