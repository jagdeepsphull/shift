<?php

/**
 * Shell every e-mail template extends. Templates fill the `content` section and
 * may set `$title` (used for the <title> and the coloured banner).
 *
 * Styles are inline-ish and table-free-ish on purpose: Outlook and Gmail both
 * strip <style> blocks in some contexts, so anything that must survive is
 * repeated as a style attribute on the element itself.
 *
 * @var string      $title
 * @var array|null  $settings  rows from `settings`; [0] is the live one
 */
$site      = $settings[0]->s_sitename ?? 'PickAShift';
$supportTo = $settings[0]->s_email ?? 'info@reliefshifts.com';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? $site) ?></title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; margin: 0; padding: 0; background-color: #f4f4f7; color: #333; }
        .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,.1); }
        .header { background: #7c3aed; color: #ffffff; text-align: center; padding: 22px 20px; }
        .header h1 { margin: 0; font-size: 20px; line-height: 1.35; color: #ffffff; }
        .content { padding: 22px 24px; }
        .content h2 { margin: 0 0 14px; font-size: 18px; color: #222; }
        .content p { line-height: 1.6; margin: 0 0 12px; }
        .content ul { line-height: 1.7; padding-left: 20px; margin: 0 0 14px; }
        .footer { background: #f1f1f4; padding: 16px 20px; text-align: center; font-size: 13px; color: #666; }
        .footer a { color: #7c3aed; text-decoration: none; }
        .cta { text-align: center; margin: 22px 0 6px; }
        .cta a { display: inline-block; padding: 12px 22px; background: #7c3aed; color: #ffffff !important; text-decoration: none; border-radius: 6px; font-weight: bold; }
    </style>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; background-color: #f4f4f7; margin: 0; padding: 0;">
    <div class="container" style="max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 10px; overflow: hidden;">
        <div class="header" style="background: #7c3aed; color: #ffffff; text-align: center; padding: 22px 20px;">
            <h1 style="margin: 0; font-size: 20px; line-height: 1.35; color: #ffffff;"><?= esc($title ?? $site) ?></h1>
        </div>

        <div class="content" style="padding: 22px 24px;">
            <?= $this->renderSection('content') ?>

            <p style="line-height: 1.6;">If you have any questions, our support team is at
                <a href="mailto:<?= esc($supportTo) ?>" style="color: #7c3aed;"><?= esc($supportTo) ?></a>.</p>
        </div>

        <div class="footer" style="background: #f1f1f4; padding: 16px 20px; text-align: center; font-size: 13px; color: #666;">
            <p style="margin: 0 0 6px;">&copy; <?= date('Y') ?> <?= esc($site) ?>. All rights reserved.</p>
            <p style="margin: 0;"><a href="<?= base_url('terms') ?>" style="color: #7c3aed;">Terms &amp; Conditions</a></p>
        </div>
    </div>
</body>
</html>
