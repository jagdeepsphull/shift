<?php

/**
 * The home page's colour theme, applied to the signed-in area.
 *
 * The public site moved to `theme.css` - a light lavender-grey ground, white
 * cards on soft shadows rather than hard borders, near-black pill buttons that
 * go purple on hover, and Plus Jakarta Sans. The signed-in screens never got
 * it: they still load `main.css`, which paints a white ground, `#abacae` body
 * text, a `#ff6c89` title bar on every screen and `#F63854` buttons. Signing in
 * looked like arriving at a different product.
 *
 * `theme.css` itself is not the answer here - it is written against the
 * Bootstrap 5 markup the public pages now use, while this area is still on 4.1,
 * so pointing it at these screens would break layout to fix colour. This is the
 * palette and the type, taken from the same values, applied to the markup that
 * is actually here.
 *
 * The variables are declared once on `:root` and consumed by
 * partials/portal_sidebar_styles.php and partials/shift_form_styles.php, so
 * there is one set of colours for the area rather than three.
 *
 * Included from employer/header_inner.php and applicant/header_inner.php, so it
 * is on every screen behind a login. It is named for the portal rather than for
 * either side of it because the pharmacist's screens are the same product as
 * the owner's, and were the odd ones out for as long as this was employer-only.
 */
if (defined('PORTAL_THEME')) {
    return;
}

define('PORTAL_THEME', true);
?>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<style>
:root {
    /* Read off the home page: --wz-bg, --wz-surface, --wz-ink, --wz-ink-soft,
       --wz-muted, --wz-line, --wz-purple, --wz-orange. */
    --ps-bg: #f3f3f7;
    --ps-surface: #ffffff;
    --ps-ink: #0d0d12;
    --ps-ink-soft: #43434f;
    --ps-muted: #8f8fa3;
    --ps-line: #e7e7ee;
    --ps-accent: #7c3aed;
    --ps-accent-soft: rgba(124, 58, 237, .12);
    --ps-warm: #f9600b;

    /* The hero's gradient, verbatim from --wz-grad. The home page uses it for
       the banner and for the little square icon tiles in Services, so it is
       what the eye already reads as "this site" - here it marks the screen you
       are on, the step numbers and the top of every panel. */
    --ps-grad: linear-gradient(115deg, #a855f7 0%, #7c3aed 22%, #c2410c 62%, #f97316 100%);

    /* The same run of colour at a wash, for a heading band that should carry a
       tint without competing with the words on it. */
    --ps-grad-soft: linear-gradient(115deg, rgba(168, 85, 247, .08) 0%, rgba(124, 58, 237, .07) 22%, rgba(194, 65, 12, .06) 62%, rgba(249, 115, 22, .08) 100%);
    --ps-radius: 20px;
    --ps-radius-sm: 12px;
    --ps-shadow: 0 1px 2px rgba(16, 16, 40, .04), 0 8px 24px rgba(16, 16, 40, .05);
    --ps-font: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

/* ----------------------------------------------------------------- base --- */

/* `main.css` sets Open Sans on a white ground with `#abacae` text, which is the
   grey the whole area reads as. The home page's ground and ink instead. */
body.ps-portal {
    background: var(--ps-bg);
    font-family: var(--ps-font);
    color: var(--ps-ink-soft);
    -webkit-font-smoothing: antialiased;
}

body.ps-portal h1,
body.ps-portal h2,
body.ps-portal h3,
body.ps-portal h4,
body.ps-portal h5,
body.ps-portal h6 {
    font-family: var(--ps-font);
    color: var(--ps-ink);
    letter-spacing: -.01em;
}

body.ps-portal a:hover { color: var(--ps-accent); }

/* --------------------------------------------------------------- panels --- */

/* The pink frame `main.css` draws around every panel, and the pink title bar
   inside it. The home page has neither: a white card on the ground, and a
   heading in ink. */
body.ps-portal .dashboard-body {
    background: transparent;
    padding: 0;
    border-radius: 0;
}

body.ps-portal .dashboard-caption {
    background: var(--ps-surface);
    border: 0;
    border-radius: var(--ps-radius);
    box-shadow: var(--ps-shadow);
    overflow: hidden;
}

/* A band of the hero's own tint behind the title, with the gradient itself as a
   hairline along the top of the panel. Enough colour that the screen is not a
   white rectangle; not so much that the heading has to fight it. */
body.ps-portal .dashboard-caption-header {
    position: relative;
    background-color: var(--ps-surface);
    background-image: var(--ps-grad-soft);
    border-bottom: 1px solid var(--ps-line);
    padding: 22px 26px;
}

body.ps-portal .dashboard-caption-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--ps-grad);
}

body.ps-portal .dashboard-caption-header h4 { color: var(--ps-ink); font-weight: 700; }
body.ps-portal .dashboard-caption-header h4 i { color: var(--ps-accent); }

/* -------------------------------------------------------------- buttons --- */

/* The home page's primary action: a near-black pill that goes purple on hover.
   `main.css` makes this uppercase pink, and on hover swaps it for a dotted
   outline - which reads as the button having been disabled. */
body.ps-portal .btn-common,
body.ps-portal .dashboard-caption-wrap .btn-common {
    background: var(--ps-ink);
    background-color: var(--ps-ink);
    color: #fff;
    border: 0;
    border-radius: 999px;
    padding: 13px 28px;
    font-size: 14px;
    font-weight: 700;
    text-transform: none;
    letter-spacing: 0;
    box-shadow: none;
    transition: background .2s ease, transform .15s ease;
}

/* Ink at rest, the hero's gradient under the cursor - the same move the home
   page's own gradient buttons make. */
body.ps-portal .btn-common:hover,
body.ps-portal .dashboard-caption-wrap .btn-common:hover {
    background: var(--ps-grad);
    color: #fff;
    border: 0;
    box-shadow: 0 6px 18px rgba(124, 58, 237, .25);
    transform: translateY(-1px);
}

/* The row actions on All Shifts. "View" is Bootstrap's cyan `btn-info`, which
   belongs to no palette on this site; the accent instead. Edit and Delete keep
   green and red - those two carry a meaning the home page does not overrule. */
body.ps-portal .btn-info {
    background-color: var(--ps-accent);
    border-color: var(--ps-accent);
    color: #fff;
}

body.ps-portal .btn-info:hover,
body.ps-portal .btn-info:focus {
    background-color: #6d28d9;
    border-color: #6d28d9;
    color: #fff;
}

/* --------------------------------------------------------------- navbar --- */

/* The bar reads pink here and ink on the home page, where the account link is a
   filled pill at the end of the row. Same shape, so the two bars match. */
body.ps-portal .navbar-expand-md .navbar-nav .nav-link,
body.ps-portal .top-nav-collapse .navbar-nav .nav-link {
    color: var(--ps-ink);
    font-weight: 600;
}

body.ps-portal .navbar-expand-md .navbar-nav .nav-link:hover,
body.ps-portal .top-nav-collapse .navbar-nav .nav-link:hover { color: var(--ps-accent); }

/* `main.css` flags the active link `color: #F63854 !important`, which left the
   label pink inside the dark pill - the one place an !important is answered
   with one. */
body.ps-portal .navbar-nav li.active > a.nav-link,
body.ps-portal .top-nav-collapse .navbar-nav li.active a.nav-link {
    background: var(--ps-ink);
    color: #fff !important;
    border-color: transparent;
    border-radius: 999px;
    padding: 9px 20px;
}

body.ps-portal .navbar-nav li.active > a.nav-link:hover,
body.ps-portal .top-nav-collapse .navbar-nav li.active a.nav-link:hover {
    background: var(--ps-accent);
    color: #fff !important;
}

body.ps-portal .navbar-nav .dropdown-menu {
    border: 0;
    border-radius: var(--ps-radius-sm);
    box-shadow: var(--ps-shadow);
    padding: 6px;
}

body.ps-portal .navbar-nav .dropdown-item {
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    color: var(--ps-ink-soft);
    padding: 9px 12px;
}

body.ps-portal .navbar-nav .dropdown-item:hover {
    background: var(--ps-bg);
    color: var(--ps-ink);
}

/* --------------------------------------------------------------- inputs --- */

body.ps-portal .form-control:focus {
    border-color: var(--ps-accent);
    box-shadow: 0 0 0 3px var(--ps-accent-soft);
}

/* ---------------------------------------------------------------- table --- */

/* The lists on All Shifts and My Stores. Headings in ink, hairlines in the
   theme's line colour rather than the default grey. */
/* The tint goes on the row, not the cells: painted per `th` the gradient
   restarts in every column and the header reads as stripes. */
body.ps-portal .table thead tr { background-image: var(--ps-grad-soft); }

body.ps-portal .table thead th {
    color: var(--ps-ink);
    font-weight: 700;
    font-size: 13px;
    background-color: transparent;
    border-bottom: 1px solid var(--ps-line);
    border-top: 0;
}

/* The row a cursor is on, tinted from the same run of colour. */
body.ps-portal .table tbody tr:hover > td { background-color: rgba(124, 58, 237, .04); }

/* A step below the 13px heading above it: the headings name the columns and
   are read once, the rows are read down. */
body.ps-portal .table td {
    color: var(--ps-ink-soft);
    font-size: 12px;
    border-top: 1px solid var(--ps-line);
    vertical-align: middle;
}

/* --------------------------------------------------------------- status --- */

/* Where a row carries a state - Pending, Booked, Rejected - it used to be bold
   coloured words sitting in a line of plain ones, which reads as emphasis
   rather than as a value. A pill says the same thing in the space of a word,
   and the eye finds the column without reading it. */
body.ps-portal .ps-status {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    line-height: 1.3;
    white-space: nowrap;
}

/* Waiting on somebody else, so it is stated rather than flagged. */
body.ps-portal .ps-status-wait {
    background: rgba(124, 58, 237, .1);
    color: #6d28d9;
}

/* The one outcome worth spotting from across the table. */
body.ps-portal .ps-status-ok {
    background: rgba(22, 163, 74, .12);
    color: #15803d;
}

/* The warm end of the hero's gradient. Bootstrap's `text-danger` red read as an
   error on screen - this is an answer, not a fault. */
body.ps-portal .ps-status-no {
    background: rgba(249, 96, 11, .12);
    color: #c2410c;
}

/* ---------------------------------------------------------------- chips --- */

/* The small buttons that open a message or a detail panel from inside a row.
   As `btn-info` and `btn-warning` they were a cyan and a yellow that belong to
   Bootstrap rather than to this site, and two of them in one cell shouted over
   the row they sit in. */
body.ps-portal .ps-chip {
    display: inline-block;
    padding: 5px 13px;
    border: 1px solid var(--ps-line);
    border-radius: 999px;
    background: var(--ps-surface);
    font-size: 12px;
    font-weight: 600;
    line-height: 1.4;
    color: var(--ps-ink-soft);
    transition: background .15s ease, border-color .15s ease, color .15s ease;
}

body.ps-portal .ps-chip:hover,
body.ps-portal .ps-chip:focus {
    background: var(--ps-accent-soft);
    border-color: transparent;
    color: var(--ps-accent);
}

/* The reply is the half of a conversation you did not write, so it carries the
   warm end of the gradient to tell the two apart at a glance. */
body.ps-portal .ps-chip-them:hover,
body.ps-portal .ps-chip-them:focus {
    background: rgba(249, 96, 11, .1);
    color: var(--ps-warm);
}

/* --------------------------------------------------------------- modals --- */

/* Every detail panel in this area opens over a `bg-info` bar, which is the same
   Bootstrap cyan the row buttons used to be. The card matches the ones on the
   page behind it instead, under a band of the hero's own gradient. */
body.ps-portal .modal-content {
    border: 0;
    border-radius: var(--ps-radius);
    box-shadow: 0 20px 60px rgba(16, 16, 40, .18);
    overflow: hidden;
}

body.ps-portal .modal-header.bg-info {
    background: var(--ps-grad) !important;
    border-bottom: 0;
    padding: 18px 24px;
}

body.ps-portal .modal-header .modal-title { color: #fff; font-weight: 700; font-size: 17px; }
body.ps-portal .modal-header .close { color: #fff; opacity: .85; text-shadow: none; }
body.ps-portal .modal-header .close:hover { opacity: 1; }

body.ps-portal .modal-body { padding: 22px 24px; }
body.ps-portal .modal-body label { font-size: 12.5px; font-weight: 700; color: var(--ps-muted); margin-bottom: 4px; }
body.ps-portal .modal-footer { border-top: 1px solid var(--ps-line); padding: 14px 24px; }

/* A panel that only reports is marked readonly, and a readonly box that looks
   like an empty one invites a click and a wasted keystroke. */
body.ps-portal .modal-body .form-control[readonly] {
    background: var(--ps-bg);
    border-color: var(--ps-line);
    color: var(--ps-ink);
    font-weight: 600;
    box-shadow: none;
}

/* ------------------------------------------------------------ map link --- */

/* Under the store address on a booking, in the applicant's list. */
body.ps-portal .ps-map-link {
    display: inline-block;
    margin-top: 8px;
    font-size: 13px;
    font-weight: 700;
    color: var(--ps-accent);
    text-decoration: none;
}

body.ps-portal .ps-map-link:hover { text-decoration: underline; }

body.ps-portal .ps-map-link i { margin-right: 4px; }

/* ----------------------------------------------------------- datatables --- */

/* The furniture DataTables draws around every list in this area - a search box,
   a count and a pager. None of it is themed by the plugin's Bootstrap 4 skin
   beyond the defaults, so the pager stayed Bootstrap blue in the middle of a
   purple page and the search box kept square corners nothing else here has. */
body.ps-portal .dataTables_wrapper .dataTables_filter { margin-bottom: 6px; }

body.ps-portal .dataTables_wrapper .dataTables_filter label,
body.ps-portal .dataTables_wrapper .dataTables_length label,
body.ps-portal .dataTables_wrapper .dataTables_info {
    color: var(--ps-muted);
    font-size: 13px;
    font-weight: 600;
}

body.ps-portal .dataTables_wrapper .dataTables_filter input {
    border: 1px solid var(--ps-line);
    border-radius: 999px;
    padding: 8px 16px;
    margin-left: 10px;
    font-size: 14px;
    color: var(--ps-ink);
    background: var(--ps-surface);
}

body.ps-portal .dataTables_wrapper .dataTables_filter input:focus {
    outline: 0;
    border-color: var(--ps-accent);
    box-shadow: 0 0 0 3px var(--ps-accent-soft);
}

body.ps-portal .dataTables_wrapper .pagination { gap: 4px; }

body.ps-portal .dataTables_wrapper .page-link {
    border: 0;
    border-radius: 10px;
    color: var(--ps-ink-soft);
    font-size: 13px;
    font-weight: 600;
    background: transparent;
}

body.ps-portal .dataTables_wrapper .page-link:hover {
    background: var(--ps-accent-soft);
    color: var(--ps-accent);
}

body.ps-portal .dataTables_wrapper .page-item.active .page-link {
    background: var(--ps-ink);
    color: #fff;
}

body.ps-portal .dataTables_wrapper .page-item.disabled .page-link {
    color: var(--ps-muted);
    background: transparent;
}

/* On a phone the responsive extension folds the narrow columns away behind a
   round "+" on each row. It ships Bootstrap blue, and red once open; the
   accent and ink instead, which is what every other control here does. */
body.ps-portal table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control:before,
body.ps-portal table.dataTable.dtr-inline.collapsed > tbody > tr > th.dtr-control:before {
    background-color: var(--ps-accent);
    border: 0;
    box-shadow: none;
}

body.ps-portal table.dataTable.dtr-inline.collapsed > tbody > tr.parent > td.dtr-control:before,
body.ps-portal table.dataTable.dtr-inline.collapsed > tbody > tr.parent > th.dtr-control:before {
    background-color: var(--ps-ink);
}

/* The panel it opens: a list of label/value pairs, which arrive unstyled. */
body.ps-portal table.dataTable > tbody > tr > td.child ul.dtr-details { width: 100%; }
/* The gap is the plugin's min-width on the label, so a label longer than it -
   "Store Number" - sat against its own value. */
body.ps-portal table.dataTable > tbody > tr > td.child span.dtr-title {
    color: var(--ps-muted);
    font-weight: 700;
    margin-right: 10px;
}

/* ------------------------------------------------------------- phones --- */

@media (max-width: 767.98px) {
    /* The search box shared a line with its label and with the entry count,
       which on a phone put its right edge past the screen. Its own line,
       the width of the card. */
    body.ps-portal .dataTables_wrapper .dataTables_filter {
        float: none;
        text-align: left;
    }

    body.ps-portal .dataTables_wrapper .dataTables_filter label {
        display: block;
        width: 100%;
    }

    body.ps-portal .dataTables_wrapper .dataTables_filter input {
        display: block;
        width: 100%;
        margin: 6px 0 0;
    }

    /* DataTables measures the columns and writes the total onto the table, so
       a list wider than the phone was simply cut off at the right - the fold
       control on each row had nothing to fold, because as far as the plugin
       was concerned every column already fitted. Held to the width of the
       card, the responsive extension folds what does not fit into the panel
       under the row, which is where the rest of the shift is read. */
    body.ps-portal table.dataTable {
        width: 100% !important;
    }

    /* A store name written as one word - INDIANPHARMACYSTORE001 - has nowhere
       to wrap, and pushed the row past the side of the screen however narrow
       the columns around it were made. */
    body.ps-portal .table td,
    body.ps-portal .table thead th {
        padding-left: 10px !important;
        padding-right: 10px !important;
        overflow-wrap: anywhere;
    }

    /* Except the cell carrying the fold control, which needs the room the
       plugin positions the round +/- into - tightened with the rest, the
       control sat on top of the shift id. */
    body.ps-portal table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control {
        padding-left: 30px !important;
    }
}
</style>
