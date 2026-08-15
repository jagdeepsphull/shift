<?php

/**
 * Sent to a booked applicant the day before their shift.
 *
 * The address is the shift's own store: for a multi-store owner the employer's
 * login columns are a head office, and this is the message somebody reads the
 * night before deciding how to get there.
 *
 * @var string      $name
 * @var array       $shift     row from `post_job`
 * @var array       $employer  row from `users`
 * @var object|null $store     the shift's store, from `shiftStore()`
 * @var array       $settings
 */
$store   = $store ?? null;
$mapLink = $store ? storeMapLink($store) : '';
?>
<?= $this->extend('emails/layout') ?>

<?= $this->section('content') ?>
    <h2 style="margin: 0 0 14px; font-size: 18px; color: #222;">Hello, <?= esc($name) ?>!</h2>

    <p style="line-height: 1.6;">A reminder that you are booked for a shift <strong>tomorrow</strong>,
    <?= esc(dateFormat($shift['p_dates'], 'l j F Y')) ?>.</p>

    <ul style="line-height: 1.7; padding-left: 20px;">
        <?php if ($store) { ?>
        <li>Store: <?= esc($store->s_name) ?><?= $store->s_number !== '' ? ' (no. ' . esc($store->s_number) . ')' : '' ?></li>
        <?php if (trim((string) ($store->s_location_label ?? '')) !== '') { ?>
        <li>Where to find it: <?= esc($store->s_location_label) ?></li>
        <?php } ?>
        <li>Address: <?= esc($store->s_address) ?>, <?= esc(getCityName($store->s_city)) ?>, <?= esc(getProvinceName($store->s_province)) ?>, <?= esc($store->s_pincode) ?></li>
        <?php if (trim((string) ($store->s_phone ?? '')) !== '') { ?>
        <li>Store phone: <?= esc($store->s_phone) ?></li>
        <?php } ?>
        <?php } else { ?>
        <li>Store: <?= esc($employer['u_comp_name']) ?> (no. <?= esc($employer['u_licence_no']) ?>)</li>
        <li>Address: <?= esc($employer['u_address1']) ?>, <?= esc(getCityName($employer['u_city'])) ?>, <?= esc(getProvinceName($employer['u_provice'])) ?>, <?= esc($employer['u_pincode']) ?></li>
        <?php } ?>
        <li>Shift time: <?= esc($shift['p_shift_time']) ?></li>
        <li>Rate: CAD$ <?= esc($shift['p_ac_hourly_rate']) ?>/hour</li>
        <li>Role: <?= esc(getShiftForName($shift['p_shift_for'])) ?></li>
        <li>Software: <?= esc(getSoftwareSkills($shift['p_skills'])) ?></li>
    </ul>

    <?php if ($mapLink !== '') { ?>
    <p style="line-height: 1.6;">
        <a href="<?= esc($mapLink) ?>" style="color: #1a73e8;">Get directions to this store</a>
    </p>
    <?php } ?>

    <p style="line-height: 1.6;">If you can no longer attend, tell us as soon as you can so the pharmacy can be
    covered.</p>
<?= $this->endSection() ?>
