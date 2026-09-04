<?php

/**
 * Presentation for the employer's shift form.
 *
 * Layout for this one screen, scoped under `.ps-shift-page`. The colours are
 * not here: partials/portal_theme.php carries the home page's palette for the
 * whole employer area, and this consumes its variables. So a change of theme
 * happens in one file, and this one only decides where things sit.
 *
 * Written as a partial rather than inline so the edit form can wear the same
 * clothes by including it, and guarded so two includes on one page emit one
 * copy.
 */
if (defined('SHIFT_FORM_STYLES')) {
    return;
}

define('SHIFT_FORM_STYLES', true);
?>
<style>
/* Colours come from partials/portal_theme.php, which declares them once on
   :root with the home page's values. Nothing is redeclared here. */

/* `main.css` paints this wrapper `#ffebee` and pads it, which reads as a pink
   frame drawn around the card. The card below carries its own edge. */
.ps-shift-page .dashboard-body {
    background: transparent;
    padding: 0;
    border-radius: 0;
}

/* The card the whole screen sits in. The legacy rule gives it a square 1px
   border and a 2px radius; softened here, with the flat pink title bar traded
   for a white header and a hairline. */
.ps-shift-page .dashboard-caption {
    border: 0;
    border-radius: var(--ps-radius);
    box-shadow: var(--ps-shadow);
    overflow: hidden;
}

.ps-shift-page .dashboard-caption-header {
    padding: 24px 28px;
    align-items: flex-start;
}

.ps-shift-page .dashboard-caption-header h4 {
    color: var(--ps-ink);
    font-size: 21px;
    font-weight: 700;
    letter-spacing: -.01em;
    margin-bottom: 2px;
}

.ps-shift-page .dashboard-caption-header h4 i {
    color: var(--ps-accent);
    top: 2px;
}

/* `min-height: 500px` on the legacy wrapper leaves a tall empty gap under a
   short form; the sections below set the height instead. */
.ps-shift-page .dashboard-caption-wrap {
    padding: 26px 28px 28px;
    min-height: 0;
    background: var(--ps-bg);
}

/* ------------------------------------------------------------- sections --- */

.ps-shift-page .ps-card {
    background: #fff;
    border: 1px solid var(--ps-line);
    border-radius: var(--ps-radius-sm);
    box-shadow: none;
    margin-bottom: 20px;
}

/* The <h5> band. A wash of the hero's gradient carries the colour; the heading
   itself stays ink, because a section title has to be read before it is
   admired. */
.ps-shift-page .ps-card-header {
    display: flex;
    align-items: center;
    gap: 14px;
    background-image: var(--ps-grad-soft);
    border-bottom: 1px solid var(--ps-line);
    border-radius: var(--ps-radius-sm) var(--ps-radius-sm) 0 0;
    padding: 16px 20px;
}

.ps-shift-page .ps-card-header h5 {
    font-size: 15px;
    font-weight: 700;
    color: var(--ps-ink);
    margin: 0 0 1px;
}

/* The step number. Numbered because the form reads top to bottom and the
   sections are in the order somebody thinks about a shift. */
.ps-shift-page .ps-step {
    flex: 0 0 30px;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: var(--ps-grad);
    box-shadow: 0 4px 12px rgba(124, 58, 237, .25);
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.ps-shift-page .ps-card-body { padding: 20px; }

/* --------------------------------------------------------------- fields --- */

.ps-shift-page label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--ps-ink-soft);
    margin-bottom: 6px;
}

.ps-shift-page .ps-req { color: var(--ps-accent); margin-left: 2px; }

.ps-shift-page .ps-hint {
    display: block;
    font-size: 12px;
    color: var(--ps-muted);
    margin-top: 6px;
}

/* The legacy rule hangs 20px under every control, which double-spaces a grid
   of form groups. The group's own margin is the one that should show. */
.ps-shift-page .form-control {
    margin-bottom: 0;
    height: auto;
    min-height: 46px;
    padding: 11px 14px;
    font-size: 14px;
    color: var(--ps-ink);
    border: 1px solid var(--ps-line);
    border-radius: var(--ps-radius-sm);
    background-color: #fff;
}

.ps-shift-page .form-control::placeholder { color: #98a2b3; }

.ps-shift-page .form-control:focus {
    border-color: var(--ps-accent);
    box-shadow: 0 0 0 3px var(--ps-accent-soft);
}

.ps-shift-page .form-group { margin-bottom: 18px; }
.ps-shift-page .ps-card-body .row > [class*="col-"] > .form-group:last-child { margin-bottom: 0; }

/* select2 draws its own box; these line it up with the inputs beside it. */
.ps-shift-page .select2-container--bootstrap4 .select2-selection {
    min-height: 46px;
    border: 1px solid var(--ps-line);
    border-radius: var(--ps-radius-sm);
}

.ps-shift-page .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
    line-height: 44px;
    padding-left: 14px;
    color: var(--ps-ink);
    font-size: 14px;
}

/* The theme pins the arrow with `position: absolute; top: 50%`, which needs a
   positioned parent and a pull-back of its own height - without both it lands
   under the box rather than inside it. */
.ps-shift-page .select2-container--bootstrap4 .select2-selection--single { position: relative; }

.ps-shift-page .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
    top: 50%;
    right: 10px;
    height: auto;
    transform: translateY(-50%);
}

.ps-shift-page .select2-container--bootstrap4.select2-container--focus .select2-selection,
.ps-shift-page .select2-container--bootstrap4.select2-container--open .select2-selection {
    border-color: var(--ps-accent);
    box-shadow: 0 0 0 3px var(--ps-accent-soft);
}

/* Input add-ons: the currency and the two picker icons.

   `main.css` sets `.form-control { width: 100% }` and loads after Bootstrap, so
   it beats `.input-group > .form-control { width: 1% }` on equal specificity -
   the control fills the row and the add-on wraps onto the next line. This puts
   the flex sizing back. */
.ps-shift-page .input-group { flex-wrap: nowrap; }

.ps-shift-page .input-group > .form-control {
    flex: 1 1 auto;
    width: 1%;
    min-width: 0;
}

.ps-shift-page .input-group-text {
    background: var(--ps-bg);
    border: 1px solid var(--ps-line);
    color: var(--ps-muted);
    font-size: 14px;
    padding: 0 14px;
    border-radius: var(--ps-radius-sm);
}

.ps-shift-page .input-group > .input-group-prepend > .input-group-text { border-right: 0; border-radius: var(--ps-radius-sm) 0 0 var(--ps-radius-sm); }
.ps-shift-page .input-group > .input-group-append > .input-group-text { border-left: 0; border-radius: 0 var(--ps-radius-sm) var(--ps-radius-sm) 0; }
.ps-shift-page .input-group > .form-control:not(:first-child) { border-top-left-radius: 0; border-bottom-left-radius: 0; }
.ps-shift-page .input-group > .form-control:not(:last-child) { border-top-right-radius: 0; border-bottom-right-radius: 0; }

/* The date and time boxes open a picker on click, so the whole control is a
   pointer rather than a text caret. */
.ps-shift-page input.date,
.ps-shift-page input.timePicker { cursor: pointer; background-color: #fff; }

/* ------------------------------------------------------- tick-box groups --- */

/* The shared grid puts two per row. Inside a third-width column that leaves
   about 150px for a label, which clipped the longer software names against a
   horizontal scrollbar. One per row here, and long names wrap instead. */
.ps-shift-page .checkbox-grid {
    max-height: 244px !important;
    border-color: var(--ps-line) !important;
    border-radius: var(--ps-radius-sm) !important;
    overflow-x: hidden;
    padding: 10px 12px !important;
    background: #fff;
}

.ps-shift-page .checkbox-grid .row > [class*="col-"] {
    flex: 0 0 100%;
    max-width: 100%;
}

.ps-shift-page .checkbox-grid .custom-control-label {
    font-size: 13.5px;
    font-weight: 500;
    color: var(--ps-ink-soft);
    overflow-wrap: anywhere;
    cursor: pointer;
}

.ps-shift-page .checkbox-grid .custom-control-input:checked ~ .custom-control-label { color: var(--ps-ink); }

.ps-shift-page .checkbox-grid.border-danger { border-color: var(--ps-accent) !important; }

/* The group label is rendered by the shared partial, so it is matched here
   rather than given a class of its own. */
.ps-shift-page .checkbox-grid + *,
.ps-shift-page .form-group > label.d-block { margin-bottom: 8px; }

/* ------------------------------------------------------------ summernote --- */

.ps-shift-page .note-editor.note-frame {
    border: 1px solid var(--ps-line);
    border-radius: var(--ps-radius-sm);
    overflow: hidden;
    margin-bottom: 0;
}

.ps-shift-page .note-editor.note-frame .note-toolbar {
    background: var(--ps-bg);
    border-bottom: 1px solid var(--ps-line);
    padding: 6px 8px;
}

.ps-shift-page .note-editor.note-frame .note-statusbar { background: var(--ps-bg); }

/* ---------------------------------------------------------- action bar --- */

.ps-shift-page .ps-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    flex-wrap: wrap;
    padding-top: 4px;
}

.ps-shift-page .ps-actions .btn-common { margin: 0; }

.ps-shift-page .ps-cancel {
    font-size: 14px;
    font-weight: 600;
    color: var(--ps-muted);
    padding: 12px 8px;
}

.ps-shift-page .ps-cancel:hover { color: var(--ps-ink); }

/* ---------------------------------------------------------- responsive --- */

@media (max-width: 767.98px) {
    .ps-shift-page .dashboard-caption { border-radius: var(--ps-radius-sm); }
    .ps-shift-page .dashboard-caption-header { padding: 18px 16px; }
    .ps-shift-page .dashboard-caption-header h4 { font-size: 19px; }
    .ps-shift-page .dashboard-caption-wrap { padding: 16px 14px 20px; }
    .ps-shift-page .ps-card-header { padding: 14px 14px; gap: 11px; }
    .ps-shift-page .ps-card-body { padding: 16px 14px; }
    .ps-shift-page .ps-card { margin-bottom: 14px; }

    /* One thumb, one column: the buttons go full width and the primary one
       sits on top, where the thumb already is. */
    .ps-shift-page .ps-actions { flex-direction: column-reverse; align-items: stretch; gap: 6px; }
    .ps-shift-page .ps-actions .btn-common { width: 100%; }
    .ps-shift-page .ps-cancel { text-align: center; }

    /* Summernote's toolbar is a row of button groups that would otherwise
       scroll sideways on a phone. */
    .ps-shift-page .note-editor.note-frame .note-toolbar { display: flex; flex-wrap: wrap; }
}

@media (max-width: 575.98px) {
    .ps-shift-page .checkbox-grid { max-height: 200px !important; }
}
</style>
