<?php

/**
 * Sent when an admin flips a user's status from pending to active. Shared by
 * both the employer and the applicant screens — the copy was identical in each.
 *
 * @var string $name
 * @var array  $settings
 */
$site = $settings[0]->s_sitename ?? 'PickAShift';
?>
<?= $this->extend('emails/layout') ?>

<?= $this->section('content') ?>
    <h2 style="margin: 0 0 14px; font-size: 18px; color: #222;">Hello, <?= esc($name) ?>!</h2>

    <p style="line-height: 1.6;">Great news — your account with <?= esc($site) ?> has been approved and is now
    active. You can sign in using the credentials you provided during registration.</p>

    <div class="cta" style="text-align: center; margin: 22px 0 6px;">
        <a href="<?= base_url('front/login') ?>" style="display: inline-block; padding: 12px 22px; background: #7c3aed; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold;">Sign in</a>
    </div>

    <p style="line-height: 1.6;">Enjoy exploring the platform, and welcome to the <?= esc($site) ?>
    community!</p>
<?= $this->endSection() ?>
