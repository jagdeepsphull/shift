<?php

/**
 * Sent when an admin flips a user's status from pending to active. Shared by
 * both the employer and the applicant screens — the copy was identical in each.
 *
 * Worded as the agency's e-mail spec has it (#2). The subject is set by the
 * sender, Sadmin::sendAccountApprovedEmail.
 *
 * @var string $name
 * @var array  $settings
 */
$site = $settings[0]->s_sitename ?? 'Pick-A-Shift';

$this->setVar('title', 'Your account is now Active');
?>
<?= $this->extend('emails/layout') ?>

<?= $this->section('content') ?>
    <p style="line-height: 1.6;">Hello, <?= esc($name) ?>!</p>

    <p style="line-height: 1.6;">Your account with <?= esc($site) ?> is now active. You can sign in using the
    credentials you provided during registration.</p>

    <div class="cta" style="text-align: center; margin: 22px 0 6px;">
        <a href="<?= base_url('front/login') ?>" style="display: inline-block; padding: 12px 22px; background: #7c3aed; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold;">Sign in</a>
    </div>
<?= $this->endSection() ?>
