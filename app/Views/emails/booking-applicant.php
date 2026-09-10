<?php

/**
 * Sent to the applicant when an admin approves them for a shift. The agency is
 * copied on this one (see `getAgencyCopyEmail()`).
 *
 * Worded as the agency's e-mail spec has it (#4), which greets the applicant by
 * first name only and gives the message no heading, so the banner carries only
 * the site's name. The subject is set by the sender, Sadmin::sendBookingEmails.
 *
 * The pharmacy is named by its store, not by `u_comp_name`: the employer's
 * company name is the group an owner trades under, and a person told they are
 * booked at "Independent Pharmacy" has not been told which shop that is.
 *
 * No rate is shown. Both halves of a booking used to carry one - what the
 * applicant is paid here, what the employer is billed on their copy - and the
 * spec took them off both: the figure is on the portal, and an e-mail is
 * forwarded far more casually than a login is shared.
 *
 * The address is the shift's own store, not the employer's login columns: for a
 * multi-store owner those are the head office, and this message is the one
 * telling somebody which building to walk into.
 *
 * @var string      $name             the applicant's full name
 * @var string      $first_name       what the greeting uses; $name when absent
 * @var array       $shift            row from `post_job`
 * @var array       $employer         row from `users`
 * @var object|null $store            the shift's store, from `shiftStore()`
 * @var string      $approval_comment
 * @var array       $settings
 */
$store     = $store ?? null;
$mapLink   = $store ? storeMapLink($store) : '';
$pharmacy  = $store ? $store->s_name : $employer['u_comp_name'];
$firstName = trim((string) ($first_name ?? '')) !== '' ? trim($first_name) : $name;

$this->setVar('title', $settings[0]->s_sitename ?? 'Pick-A-Shift');
?>
<?= $this->extend('emails/layout') ?>

<?= $this->section('content') ?>
    <p style="line-height: 1.6;">Hello <?= esc($firstName) ?>,</p>

    <p style="line-height: 1.6;">We are pleased to inform you that the shift
    <strong><?= esc($shift['p_job_title']) ?></strong> has been booked for you at
    <strong><?= esc($pharmacy) ?></strong>.</p>

    <p style="line-height: 1.6;">Here are the details:</p>

    <ul style="line-height: 1.7; padding-left: 20px;">
        <?php if ($store) { ?>
        <li>Store: <?= esc($store->s_name) ?><?= $store->s_number !== '' ? ' (no. ' . esc($store->s_number) . ')' : '' ?></li>
        <li>Store Address: <?= esc($store->s_address) ?>, <?= esc(getCityName($store->s_city)) ?>, <?= esc(getProvinceName($store->s_province)) ?>, <?= esc($store->s_pincode) ?></li>
        <?php if (trim((string) ($store->s_phone ?? '')) !== '') { ?>
        <li>Store Phone: <?= esc($store->s_phone) ?></li>
        <?php } ?>
        <?php } else { ?>
        <li>Store: <?= esc($employer['u_comp_name']) ?><?= trim((string) $employer['u_licence_no']) !== '' ? ' (no. ' . esc($employer['u_licence_no']) . ')' : '' ?></li>
        <li>Store Address: <?= esc($employer['u_address1']) ?>, <?= esc(getCityName($employer['u_city'])) ?>, <?= esc(getProvinceName($employer['u_provice'])) ?>, <?= esc($employer['u_pincode']) ?></li>
        <?php } ?>
        <li>Shift requested for: <?= esc(getShiftForName($shift['p_shift_for'])) ?></li>
        <li>Shift Date: <?= esc(dateFormat($shift['p_dates'])) ?></li>
        <li>Shift time: <?= esc($shift['p_shift_time']) ?></li>
        <li>Software: <?= esc(getSoftwareSkills($shift['p_skills'])) ?></li>
        <li>Services: <?= esc(getStoreServices($shift['p_services'])) ?></li>
        <?php if (trim((string) $approval_comment) !== '') { ?>
            <li>Message from the agency: <?= esc($approval_comment) ?></li>
        <?php } ?>
    </ul>

    <p style="line-height: 1.6;">You may Login to your account on our portal for full details.</p>

    <?php if ($mapLink !== '') { ?>
    <?php /* The pasted pin where the store has one, otherwise a search for the
       address above - a street address alone does not find a pharmacy inside a
       supermarket, and this is the message somebody reads on the way there. */ ?>
    <p style="line-height: 1.6;">
        <a href="<?= esc($mapLink) ?>" style="color: #1a73e8;">Get directions to this store</a>
    </p>
    <?php } ?>

    <p style="line-height: 1.6;">Note:</p>

    <ul style="line-height: 1.7; padding-left: 20px;">
        <li>Please ensure you arrive on time for your shift.</li>
        <li>Review the location and plan your route in advance.</li>
        <li>Check in with the on-site supervisor or assistant upon arrival (if available).</li>
    </ul>

    <p style="line-height: 1.6;">We wish you all the best for the shift and look forward to working and
    supporting you with your upcoming shifts.</p>
<?= $this->endSection() ?>
