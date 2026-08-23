<?php

/**
 * "Send shift e-mail to" - the two sides of the store that can be told a shift
 * is live. On both admin shift forms, so the two cannot drift apart.
 *
 * The e-mail is `shift-posted`, and it is sent at the moment the shift becomes
 * Live: when a new shift is saved as Live, or when an existing one is approved.
 * Editing a shift that is already live sends nothing, so changing these boxes
 * afterwards changes who the *next* announcement reaches, not who has already
 * been written to.
 *
 * Neither box ticked is a real answer, not a mistake - some shifts are arranged
 * by phone and the pharmacy does not want the mail - and the shift still gets
 * announced, because the address in `AppSettings::$shiftEmailFallback` is on
 * every one of these e-mails. It is shown here as a third recipient, fixed and
 * disabled: it is not a choice an administrator makes per shift, but leaving it
 * off the card would make an empty pair of boxes read as "no e-mail at all".
 *
 * The applicant side is not on this card and never will be. Booking e-mails go
 * out whenever a booking is made, to the person it was made with, and are not
 * something an administrator chooses per shift.
 *
 * @var mixed  $selected the stored or posted `p_email_to`
 * @var string $manager  who runs this shift's store, if the caller knows
 */
$chosen   = shiftEmailChoice($selected ?? '');
$fallback = trim((string) config('AppSettings')->shiftEmailFallback);

$sides = [
    'owner'   => ['label' => 'Owner', 'hint' => 'The account the store belongs to.'],
    'manager' => ['label' => 'Manager', 'hint' => 'Whoever runs this branch, if the store has one.'],
];
?>
<div class="form-group">
    <label class="d-block">Send shift e-mail to</label>

    <div class="border rounded p-2">
        <?php foreach ($sides as $side => $meta) { ?>
            <div class="custom-control custom-checkbox mb-1">
                <input type="checkbox" class="custom-control-input"
                       id="p_email_to_<?= esc($side, 'attr') ?>"
                       name="p_email_to[]"
                       value="<?= esc($side, 'attr') ?>"
                       <?= in_array($side, $chosen, true) ? 'checked' : '' ?>>
                <label class="custom-control-label" for="p_email_to_<?= esc($side, 'attr') ?>">
                    <?= esc($meta['label']) ?>
                    <small class="text-muted d-block"><?= esc($meta['hint']) ?></small>
                </label>
            </div>
        <?php } ?>

        <?php if ($fallback !== '') { ?>
            <?php /* A radio rather than a checkbox, and disabled: it is on
               every shift e-mail and there is nothing here to decide. Disabled
               inputs are not posted, so this cannot reach `p_email_to`. */ ?>
            <div class="custom-control custom-radio">
                <input type="radio" class="custom-control-input" id="p_email_to_site" checked disabled>
                <label class="custom-control-label" for="p_email_to_site">
                    <?= esc($fallback) ?>
                    <small class="text-muted d-block">Always sent, whoever else is.</small>
                </label>
            </div>
        <?php } ?>
    </div>

</div>
