<?php

/**
 * Presentation for the side menu behind a login.
 *
 * Scoped under `.ps-side`, the class both sidebars carry, so it restyles that
 * column and nothing else. Layout only: the colours come from
 * partials/portal_theme.php, which holds the home page's palette for the whole
 * area.
 *
 * The menu itself lives in employer/header_inner.php and
 * applicant/header_inner.php, so it appears on every screen either of them
 * serves. That is deliberate: a menu that looked one way on Post New Shift and
 * another way on My Stores would be worse than the one it replaces - and the
 * same goes for the owner's screens against the pharmacist's, which is why one
 * file dresses both.
 */
if (defined('PORTAL_SIDEBAR_STYLES')) {
    return;
}

define('PORTAL_SIDEBAR_STYLES', true);
?>
<style>
/* Colours come from partials/portal_theme.php, which declares them once on
   :root for the whole area. Nothing is redeclared here. */

/* `main.css` paints this `#ffebee` with a 10px radius, which frames the column
   in pink. The card below carries its own edge - a shadow rather than a border,
   which is how the home page draws a card. */
.ps-side.side-dashboard {
    background: var(--ps-surface);
    border: 0;
    border-radius: var(--ps-radius);
    padding: 0;
    box-shadow: var(--ps-shadow);
    overflow: hidden;
    /* The legacy rule sets `min-height: 100vh`, which drew the card a whole
       screen tall with the last two thirds empty under Logout. */
    min-height: 0;
}

/* Six links are far shorter than most of these pages, so the menu follows you
   down rather than scrolling away. Only where there is a column to follow. */
@media (min-width: 768px) {
    .ps-side.side-dashboard { position: sticky; top: 100px; }
}

/* ------------------------------------------------------------ identity --- */

.ps-side .ps-who {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 18px 18px 14px;
}

/* No photo is uploaded on most accounts, so the initials stand in rather than
   leaving a grey square. Filled with the hero's gradient, the same as the home
   page's round icon tiles. */
.ps-side .ps-avatar {
    flex: 0 0 46px;
    width: 46px;
    height: 46px;
    border-radius: 50%;
    background: var(--ps-grad);
    color: #fff;
    font-size: 15px;
    font-weight: 700;
    letter-spacing: .02em;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-transform: uppercase;
}

/* A photo, where one has been uploaded, fills the same circle rather than
   sitting in it as a square. The pharmacist's profile takes one; the owner's
   does not, so this is dead weight on that side and a broken square without it
   on this one. */
.ps-side .ps-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    display: block;
}

.ps-side .ps-who-text { min-width: 0; }

.ps-side .ps-name {
    font-size: 15px;
    font-weight: 700;
    color: var(--ps-ink);
    margin: 0 0 2px;
    line-height: 1.3;
    overflow-wrap: anywhere;
}

/* Company names run long - "Samy's Drug Mart, Main St Pharmacy (SMDM-310377)" -
   and a licence number in brackets has no break point of its own. */
.ps-side .ps-company {
    display: block;
    font-size: 12.5px;
    font-weight: 500;
    color: var(--ps-muted);
    line-height: 1.4;
    overflow-wrap: anywhere;
}

/* ------------------------------------------------------------- contact --- */

.ps-side .ps-contact {
    border-top: 1px solid var(--ps-line);
    padding: 12px 18px;
}

.ps-side .ps-contact a {
    display: flex;
    align-items: center;
    gap: 9px;
    font-size: 13px;
    font-weight: 500;
    color: var(--ps-ink-soft);
    padding: 5px 0;
    overflow-wrap: anywhere;
}

.ps-side .ps-contact a:hover { color: var(--ps-accent); }
.ps-side .ps-contact i { color: var(--ps-muted); font-size: 15px; flex: 0 0 16px; }

/* ---------------------------------------------------------------- menu --- */

.ps-side .dashboard-menu { padding: 10px; border-top: 1px solid var(--ps-line); }
.ps-side .dashboard-menu ul { list-style: none; margin: 0; padding: 0; }
.ps-side .dashboard-menu li { margin: 0 0 2px; }

.ps-side .dashboard-menu li a {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 11px 12px;
    border: 0;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    color: var(--ps-ink-soft);
    background: transparent;
    transition: background .15s ease, color .15s ease;
}

.ps-side .dashboard-menu li a i {
    font-size: 16px;
    flex: 0 0 18px;
    color: var(--ps-muted);
    transition: color .15s ease;
}

/* Hover is a flat wash of the gradient's purple end, not the gradient itself.
   Tried it with the full wash first: the label sits on the warm half and the
   two colours fight. This keeps one colour under the text, and leaves the
   gradient to mean one thing only - the screen you are on. */
.ps-side .dashboard-menu li a:hover {
    background: rgba(124, 58, 237, .07);
    color: var(--ps-accent);
}

.ps-side .dashboard-menu li a:hover i { color: var(--ps-accent); }

/* The screen you are on, filled with the gradient itself - the old amber wash
   was easy to miss, and ink alone said nothing about which site this is. */
.ps-side .dashboard-menu li.active > a {
    background: var(--ps-grad);
    color: #fff;
    box-shadow: 0 6px 16px rgba(124, 58, 237, .22);
}

.ps-side .dashboard-menu li.active > a i { color: #fff; }
.ps-side .dashboard-menu li.active > a:hover { background: var(--ps-grad); color: #fff; }
.ps-side .dashboard-menu li.active > a:hover i { color: #fff; }

/* Leaving is not navigation; it sits under a rule, away from the rest. */
.ps-side .ps-menu-out {
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px solid var(--ps-line);
}

/* Leaving borrows the warm end of the gradient rather than the purple one, so
   it does not look like one more place to navigate to. */
.ps-side .ps-menu-out a { color: var(--ps-muted); }
.ps-side .ps-menu-out a:hover { background: rgba(249, 96, 11, .09); color: var(--ps-warm); }
.ps-side .ps-menu-out a:hover i { color: var(--ps-warm); }

/* ------------------------------------------------------------- support --- */

/* The card used to end on Logout, leaving the corner under it empty. The site's
   own number sits there instead - outside the menu panel, so a phone with the
   menu shut still shows it. Markup is partials/portal_support.php. */
.ps-side .ps-support {
    border-top: 1px solid var(--ps-line);
    background: var(--ps-grad-soft);
    padding: 12px;
}

.ps-side .ps-support-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border-radius: var(--ps-radius-sm);
    color: var(--ps-ink);
    transition: background .18s ease;
}

.ps-side .ps-support-link:hover,
.ps-side .ps-support-link:focus {
    background: rgba(255, 255, 255, .75);
    color: var(--ps-ink);
    text-decoration: none;
}

/* WhatsApp's own green, because this is WhatsApp and not one more menu row. */
.ps-side .ps-support-mark {
    flex: 0 0 34px;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #25d366;
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.ps-side .ps-support-mark svg { width: 20px; height: 20px; display: block; }

.ps-side .ps-support-text { min-width: 0; line-height: 1.35; }

.ps-side .ps-support-text strong {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: var(--ps-muted);
    text-transform: uppercase;
    letter-spacing: .04em;
}

.ps-side .ps-support-text span {
    display: block;
    font-size: 14px;
    font-weight: 700;
    color: var(--ps-ink);
    overflow-wrap: anywhere;
}

/* -------------------------------------------------------- mobile toggle --- */

/* On a phone this column stacks above the content, so the whole menu sat
   between the page heading and the form - every screen began with a scroll
   past it. Collapsed behind one button instead, open on tablet and up. */
.ps-side .ps-menu-toggle {
    display: none;
    width: 100%;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 13px 18px;
    border: 0;
    border-top: 1px solid var(--ps-line);
    background: #fff;
    font-size: 14px;
    font-weight: 700;
    color: var(--ps-ink);
}

.ps-side .ps-menu-toggle:focus { outline: 0; box-shadow: none; }
.ps-side .ps-menu-toggle .lni-chevron-down { transition: transform .2s ease; font-size: 14px; color: var(--ps-muted); }
.ps-side .ps-menu-toggle[aria-expanded="true"] .lni-chevron-down { transform: rotate(180deg); }

@media (max-width: 767.98px) {
    .ps-side.side-dashboard { margin-bottom: 18px; }
    .ps-side .ps-menu-toggle { display: flex; }

    /* Bootstrap's collapse owns the panel below this breakpoint; above it the
       rule below keeps the menu open whatever state the class is left in. */
    .ps-side .dashboard-menu { border-top: 0; }
}

@media (min-width: 768px) {
    .ps-side .ps-menu-panel { display: block !important; height: auto !important; }
}
</style>
