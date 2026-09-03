<?php

/**
 * Presentation for the shift list's date filter and its Upcoming Shifts button.
 *
 * Scoped under `.ps-jobs-page`, the class the employer's All Shifts view puts
 * on its own wrapper, so nothing here reaches the other listings in the portal
 * that share the `#joblist` id and the theme's table styling.
 *
 * The date box is dressed as the home page's hero search field is - a rounded
 * pill, a calendar in front, a muted placeholder reading "Any shift date" -
 * because it is the same control doing the same job, and somebody who has used
 * one should recognise the other. Colours come from partials/portal_theme.php,
 * which declares them once for the whole portal; nothing is redeclared here.
 *
 * Guarded so two includes on one page emit one copy.
 */
if (defined('SHIFT_LIST_FILTER_STYLES')) {
    return;
}

define('SHIFT_LIST_FILTER_STYLES', true);
?>
<style>
/* The card's title bar carries the heading and, now, a button. Laid out as a
   row that wraps rather than one that squeezes: on a phone the button drops
   under the heading instead of shrinking it to two words a line. */
.ps-jobs-page .ps-jobs-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.ps-jobs-page .ps-jobs-header h4 {
    margin: 0;
}

/* ---------------------------------------------- Upcoming Shifts button --- */

/* Off by default and outlined, because the screen is called All Shifts and
   arriving to a filtered list would hide a past shift without saying so. Lit
   with the portal's accent once it is on, so the state is readable at a
   glance rather than only from the emptier table. */
.ps-jobs-page .ps-upcoming-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 7px 15px;
    border: 1px solid var(--ps-line);
    border-radius: 999px;
    background: var(--ps-surface);
    color: var(--ps-ink-soft);
    font-size: 13px;
    font-weight: 600;
    line-height: 1.2;
    white-space: nowrap;
    transition: background .15s ease, border-color .15s ease, color .15s ease;
}

.ps-jobs-page .ps-upcoming-btn:hover,
.ps-jobs-page .ps-upcoming-btn:focus {
    border-color: var(--ps-accent);
    color: var(--ps-accent);
    outline: none;
}

.ps-jobs-page .ps-upcoming-btn.is-on {
    background: var(--ps-accent);
    border-color: var(--ps-accent);
    color: #fff;
}

.ps-jobs-page .ps-upcoming-btn i {
    font-size: 14px;
}

/* --------------------------------------------------- the date-range box --- */

/* Dropped by the script into the empty left half of the table's own toolbar,
   opposite the search box. `lengthChange` is off on this table, so that half
   was standing empty and the filter reads as one row of controls with the
   search rather than as a band above it. */
.ps-jobs-page .ps-date-filter {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 8px;
}

.ps-jobs-page .ps-date-filter__box {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 14px;
    border: 1px solid var(--ps-line);
    border-radius: 999px;
    background: var(--ps-surface);
}

.ps-jobs-page .ps-date-filter__box:focus-within {
    border-color: var(--ps-accent);
}

.ps-jobs-page .ps-date-filter__box i {
    color: var(--ps-muted);
    font-size: 14px;
}

/* Readonly - the value is written by the picker, never typed - so the box is
   given no border or background of its own and sits inside the pill. */
.ps-jobs-page .ps-date-filter__input {
    width: 168px;
    max-width: 46vw;
    border: 0;
    padding: 0;
    background: transparent;
    color: var(--ps-ink);
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.ps-jobs-page .ps-date-filter__input:focus {
    outline: none;
}

.ps-jobs-page .ps-date-filter__input::placeholder {
    color: var(--ps-muted);
    font-weight: 500;
}

/* Only worth its place once a range is on, so the script keeps it hidden
   until then. */
.ps-jobs-page .ps-date-filter__clear {
    border: 0;
    background: transparent;
    color: var(--ps-muted);
    font-size: 20px;
    line-height: 1;
    padding: 0 4px;
}

.ps-jobs-page .ps-date-filter__clear:hover {
    color: var(--ps-warm);
}

/* The picker is appended to <body>, so it is outside the scope above and
   named on its own. `.shift-date-picker` is the class the template below adds;
   this only rounds the plugin's default corners to match the portal. */
.daterangepicker.ps-shift-picker {
    border-radius: var(--ps-radius-sm, 12px);
    box-shadow: var(--ps-shadow, 0 8px 24px rgba(16, 16, 40, .12));
    border-color: var(--ps-line, #e7e7ee);
    font-family: var(--ps-font, inherit);
}

.daterangepicker.ps-shift-picker td.active,
.daterangepicker.ps-shift-picker td.active:hover {
    background-color: var(--ps-accent, #7c3aed);
}

.daterangepicker.ps-shift-picker .ranges li.active {
    background-color: var(--ps-accent, #7c3aed);
}
</style>
