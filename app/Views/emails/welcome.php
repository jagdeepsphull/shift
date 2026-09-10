<?php

/**
 * Sent on registration, before an admin has approved the account.
 *
 * Worded as the agency's e-mail spec has it (#1). The subject is set by the
 * sender, Front::register.
 *
 * @var string $name
 * @var array  $settings
 */
$site = $settings[0]->s_sitename ?? 'Pick-A-Shift';

$this->setVar('title', 'Welcome to ' . $site);
?>
<?= $this->extend('emails/layout') ?>

<?= $this->section('content') ?>
    <p style="line-height: 1.6;">Hello, <?= esc($name) ?>!</p>

    <p style="line-height: 1.6;">Thank you for registering with <?= esc($site) ?>. Your account is currently
    under review and will be activated once it is verified.</p>

    <p style="line-height: 1.6;">Once verified you will get a confirmation e-mail, and you will be able to sign
    in with the credentials you provided during registration.</p>

    <p style="line-height: 1.6;">We appreciate your patience and look forward to having you as part of our
    platform.</p>
<?= $this->endSection() ?>
