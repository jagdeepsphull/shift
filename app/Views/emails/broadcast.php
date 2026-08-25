<?= $this->extend('emails/layout') ?>

<?= $this->section('content') ?>
<?php
/**
 * The body an administrator typed on the "Send Email" screen.
 *
 * It used to go out as a bare HTML string straight from the controller, which
 * meant the one e-mail on the site that is unmistakably bulk was also the only
 * one with no footer, no styling and - once the Unsubscribe link existed -
 * nowhere for a recipient to get off the list. Running it through the layout
 * fixes all three at once, and puts this message in the same place as every
 * other body the site sends.
 *
 * `$body` is printed unescaped on purpose: the screen is an HTML composer, the
 * markup is the point, and the only people who can reach it are signed in to
 * the admin panel.
 *
 * @var string $body
 */
?>
<?= $body ?>
<?= $this->endSection() ?>
