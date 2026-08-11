<?php

/**
 * Sent by `php spark email:test <address>`. Renders through the same layout as
 * every real message, so a delivery test also proves the templates render.
 *
 * @var string $sent_at
 * @var string $host
 * @var array  $settings
 */
?>
<?= $this->extend('emails/layout') ?>

<?= $this->section('content') ?>
    <h2 style="margin: 0 0 14px; font-size: 18px; color: #222;">Delivery test</h2>

    <p style="line-height: 1.6;">This message was sent at <strong><?= esc($sent_at) ?></strong> through
    <strong><?= esc($host) ?></strong>.</p>

    <p style="line-height: 1.6;">If it reached you, SMTP delivery from this server is working. If it landed in
    the junk folder, the sending domain still needs SPF and DKIM records.</p>
<?= $this->endSection() ?>
