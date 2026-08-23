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
        <?php /* line-icons has no WhatsApp mark and Font Awesome is not loaded
                 in this area, so the glyph is drawn here. */ ?>
        <span class="ps-support-mark" aria-hidden="true">
            <svg viewBox="0 0 32 32" role="img" focusable="false">
                <path fill="currentColor" d="M16.03 4A11.9 11.9 0 0 0 4.1 15.9c0 2.1.55 4.15 1.6 5.96L4 28l6.32-1.65a11.87 11.87 0 0 0 5.7 1.45h.01A11.9 11.9 0 0 0 27.95 15.9 11.9 11.9 0 0 0 16.03 4Zm0 21.79h-.01a9.9 9.9 0 0 1-5.03-1.38l-.36-.21-3.75.98 1-3.65-.24-.38a9.86 9.86 0 0 1-1.51-5.25 9.9 9.9 0 1 1 9.9 9.89Zm5.43-7.41c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.96-.94 1.16-.17.2-.35.22-.65.07-.3-.15-1.25-.46-2.39-1.47-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.61-.92-2.21-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.01-1.04 2.47s1.07 2.87 1.22 3.07c.15.2 2.1 3.2 5.08 4.49.71.3 1.26.49 1.69.63.71.22 1.36.19 1.87.12.57-.09 1.76-.72 2-1.41.25-.7.25-1.29.18-1.42-.07-.13-.27-.2-.57-.35Z"/>
            </svg>
        </span>
        <span class="ps-support-text">
            <strong>Need help?</strong>
            <span><?php echo esc($supportPhone); ?></span>
        </span>
    </a>
</div>
