<?php

/**
 * Sent to the applicant when an admin approves them for a shift. The agency is
 * copied on this one (see `getAgencyCopyEmail()`).
 *
 * Note the rate shown here is `p_ac_hourly_rate` — what the applicant is paid —
 * which is deliberately not the rate on the employer's copy of this booking.
 *
 * The address is the shift's own store, not the employer's login columns: for a
 * multi-store owner those are the head office, and this message is the one
 * telling somebody which building to walk into.
 *
 * @var string      $name
 * @var array       $shift            row from `post_job`
 * @var array       $employer         row from `users`
 * @var object|null $store            the shift's store, from `shiftStore()`
 * @var string      $approval_comment
 * @var array       $settings
 */
$store   = $store ?? null;
$mapLink = $store ? storeMapLink($store) : '';
?>
<?= $this->extend('emails/layout') ?>

<?= $this->section('content') ?>
    <h2 style="margin: 0 0 14px; font-size: 18px; color: #222;">Hello, <?= esc($name) ?>!</h2>

    <p style="line-height: 1.6;">We are delighted to tell you that you have been approved for
    <strong><?= esc($shift['p_job_title']) ?></strong> at
    <strong><?= esc($employer['u_comp_name']) ?></strong>.</p>

    <p style="line-height: 1.6;">Here are the details:</p>

    <ul style="line-height: 1.7; padding-left: 20px;">
        <?php if ($store) { ?>
        <li>Store: <?= esc($store->s_name) ?><?= $store->s_number !== '' ? ' (no. ' . esc($store->s_number) . ')' : '' ?></li>
        <?php if (trim((string) ($store->s_location_label ?? '')) !== '') { ?>
        <li>Where to find it: <?= esc($store->s_location_label) ?></li>
        <?php } ?>
        <li>Store address: <?= esc($store->s_address) ?>, <?= esc(getCityName($store->s_city)) ?>, <?= esc(getProvinceName($store->s_province)) ?>, <?= esc($store->s_pincode) ?></li>
        <?php if (trim((string) ($store->s_phone ?? '')) !== '') { ?>
        <li>Store phone: <?= esc($store->s_phone) ?></li>
        <?php } ?>
        <?php } else { ?>
        <li>Store no.: <?= esc($employer['u_licence_no']) ?></li>
        <li>Store address: <?= esc($employer['u_address1']) ?>, <?= esc(getCityName($employer['u_city'])) ?>, <?= esc(getProvinceName($employer['u_provice'])) ?>, <?= esc($employer['u_pincode']) ?></li>
        <?php } ?>
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

    <?php if ($mapLink !== '') { ?>
    <?php /* The pasted pin where the store has one, otherwise a search for the
       address above - a street address alone does not find a pharmacy inside a
       supermarket, and this is the message somebody reads on the way there. */ ?>
    <p style="line-height: 1.6;">
        <a href="<?= esc($mapLink) ?>" style="color: #1a73e8;">Get directions to this store</a>
    </p>
    <?php } ?>

    <p style="line-height: 1.6;">We wish you all the best for the shift.</p>
<?= $this->endSection() ?>
