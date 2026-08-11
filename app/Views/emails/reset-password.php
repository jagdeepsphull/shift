<?php

/**
 * Password reset link. Previously this e-mail was the bare URL in a <p> with no
 * greeting, no branding and no mention of the expiry.
 *
 * @var string $reset_link
 * @var int    $expires_minutes
 * @var array  $settings
 */
?>
<?= $this->extend('emails/layout') ?>

<?= $this->section('content') ?>
    <h2 style="margin: 0 0 14px; font-size: 18px; color: #222;">Reset your password</h2>

    <p style="line-height: 1.6;">We received a request to reset the password on your account. Use the button
    below to choose a new one.</p>

    <div class="cta" style="text-align: center; margin: 22px 0 6px;">
        <a href="<?= esc($reset_link, 'attr') ?>" style="display: inline-block; padding: 12px 22px; background: #7c3aed; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold;">Choose a new password</a>
    </div>

    <p style="line-height: 1.6; font-size: 13px; color: #666;">The link is valid for
    <?= (int) $expires_minutes ?> minutes. If the button does not work, copy this address into your browser:<br>
    <span style="word-break: break-all;"><?= esc($reset_link) ?></span></p>

    <p style="line-height: 1.6;">If you did not ask for a password reset you can ignore this e-mail — your
    current password stays as it is.</p>
<?= $this->endSection() ?>
