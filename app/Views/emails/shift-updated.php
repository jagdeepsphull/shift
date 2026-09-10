<?php

/**
 * Sent to the store when an admin saves a change to one of its shifts, to
 * whoever the shift's "Send shift e-mails to" boxes name, plus the fixed
 * address. The subject is set by the sender, Sadmin::sendShiftUpdatedEmail.
 *
 * Not one of the e-mails in the agency's PDF, so it borrows that document's
 * voice: the greeting and the "Stay up to date" and "Thank you" lines are
 * those of e-mail #3, the one a store gets when the shift first goes live.
 *
 * It lists the shift as it now stands rather than only what changed, so the
 * message is complete on its own; a line that changed says what it was. A
 * save that changed nothing the store is shown - the recipients, say, or the
 * job title - still sends the list, with no line marked.
 *
 * @var string                $name       the store owner's full name
 * @var string                $shift_title
 * @var array<string, string> $lines      shiftSummaryLines() after the save
 * @var array<string, string> $was        the same before it, for changed lines
 * @var array                 $settings
 */
$site = $settings[0]->s_sitename ?? 'Pick-A-Shift';
$was  = $was ?? [];

$this->setVar('title', 'Your shift has been updated');
?>
<?= $this->extend('emails/layout') ?>

<?= $this->section('content') ?>
    <p style="line-height: 1.6;">Hello, <?= esc($name) ?>!</p>

    <p style="line-height: 1.6;">Your shift <strong><?= esc($shift_title) ?></strong> has been updated. Here are
    the details:</p>

    <ul style="line-height: 1.7; padding-left: 20px;">
        <?php foreach ($lines as $label => $value) { ?>
            <?php $changed = $was !== [] && ($was[$label] ?? '') !== $value; ?>
            <li><?= esc($label) ?>: <?= $changed ? '<strong>' . esc($value !== '' ? $value : '-') . '</strong>' : esc($value) ?><?php
                if ($changed) { ?> <span style="color: #888;">(was <?= esc(($was[$label] ?? '') !== '' ? $was[$label] : '-') ?>)</span><?php }
            ?></li>
        <?php } ?>
        <?php foreach (array_diff_key($was, $lines) as $label => $value) { ?>
            <?php /* A line that has gone, which is only ever the booked
               applicant: the shift was taken back off them. */ ?>
            <li><?= esc($label) ?>: <strong>-</strong> <span style="color: #888;">(was <?= esc($value) ?>)</span></li>
        <?php } ?>
    </ul>

    <p style="line-height: 1.6;">Stay up to date: Login to your account to view your latest shift updates.</p>

    <p style="line-height: 1.6;">Thank you for choosing <?= esc($site) ?>.</p>
<?= $this->endSection() ?>
