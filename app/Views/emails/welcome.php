<?php

/**
 * Sent on registration, before an admin has approved the account.
 *
 * @var string $name
 * @var array  $settings
 */
$site = $settings[0]->s_sitename ?? 'PickAShift';
?>
<?= $this->extend('emails/layout') ?>

<?= $this->section('content') ?>
    <h2 style="margin: 0 0 14px; font-size: 18px; color: #222;">Hello, <?= esc($name) ?>!</h2>

    <p style="line-height: 1.6;">Thank you for registering with <?= esc($site) ?>. Your account is currently
    under review and will be activated once it is approved.</p>

    <p style="line-height: 1.6;">As soon as that happens you will get a confirmation e-mail, and you will be
    able to sign in with the credentials you chose during registration.</p>

    <p style="line-height: 1.6;">We appreciate your patience and look forward to having you as part of our
    community.</p>
<?= $this->endSection() ?>
