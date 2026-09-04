<?php

/**
 * The support number, under the side menu behind a login.
 *
 * One block for all three kinds of account - owner, manager and pharmacist -
 * because the number they ring is the same one and a sidebar that offered help
 * on one side and not the other would only send people back to the Contact
 * page to look it up.
 *
 * It sits outside the collapsing menu panel on purpose: on a phone that panel
 * starts shut, and help you have to open a menu to find is help nobody finds.
 *
 * Clicking it opens web.whatsapp.com, as the back office's WhatsApp links do -
 * the chat opens in the browser the user is already signed in to, and the
 * mobile site hands off to the app.
 *
 * Presentation is in partials/portal_sidebar_styles.php, with the rest of the
 * column.
 */
$supportPhone  = trim((string) (config('AppSettings')->supportPhone ?? ''));
$supportNumber = $supportPhone !== '' ? whatsappNumber($supportPhone) : '';

if ($supportPhone === '') {
    return;
}
?>
<div class="ps-support">
    <?php if ($supportNumber !== '') { ?>
        <a class="ps-support-link" href="https://web.whatsapp.com/send?phone=<?php echo esc($supportNumber, 'attr'); ?>"
           target="_blank" rel="noopener noreferrer"
           title="Message PickAShift support on WhatsApp">
    <?php } else { ?>
        <?php /* A number that cannot be messaged is still worth dialling. */ ?>
        <a class="ps-support-link" href="tel:<?php echo esc($supportPhone, 'attr'); ?>">
    <?php } ?>
        <?php /* line-icons has no WhatsApp mark and Font Awesome is not
                 loaded in this area, so the glyph is drawn - out of the helper,
                 which is where the contact lists in this area get theirs. */ ?>
        <span class="ps-support-mark" aria-hidden="true">
            <?php echo whatsappMarkSvg(); ?>
        </span>
        <span class="ps-support-text">
            <strong>Need help?</strong>
            <span><?php echo esc($supportPhone); ?></span>
        </span>
    </a>
</div>
