<?php

/**
 * Presentation for the site's two legal pages - Terms and Conditions, and the
 * Privacy Policy.
 *
 * These two are the only pages on the site that are a document rather than a
 * screen: a long run of headings, clauses and definitions that somebody has to
 * be able to read to the end of. Left to the theme's defaults they arrive as
 * unspaced browser bullets running the full width of a 1360px container, which
 * is about 160 characters a line - readable in the sense that the words are
 * there.
 *
 * Scoped under `.wz-legal-doc`, so nothing here reaches any other page.
 *
 * Written as a partial rather than into `theme.css` for the same reason
 * shift_form_styles.php is: it belongs to two views, not to the site, and it
 * ships with the markup it styles rather than behind a stylesheet the browser
 * may still be holding an old copy of. Colours are the home page's, declared
 * on :root in theme.css - nothing is redeclared here.
 *
 * Guarded so two includes on one page emit one copy.
 */
if (defined('LEGAL_DOC_STYLES')) {
    return;
}

define('LEGAL_DOC_STYLES', true);
?>
<style>
/* The sheet the document sits on. A measure of ~78 characters: long enough not
   to break the longer clauses into ribbons, short enough that the eye finds the
   start of the next line without hunting. */
.wz-legal-doc {
    max-width: 860px;
    margin: 0 auto;
    background: var(--wz-surface, #fff);
    border-radius: var(--wz-radius, 20px);
    box-shadow: var(--wz-shadow, 0 1px 2px rgba(16, 16, 40, .04), 0 8px 24px rgba(16, 16, 40, .05));
    padding: 56px 60px 60px;
    color: var(--wz-ink-soft, #43434f);
    font-size: 16px;
    line-height: 1.7;
}

/* ------------------------------------------------------------- title --- */

/* The lead-in block, where a document has one - Terms opens with a welcome and
   a list of what its defined terms mean. Privacy goes straight into its first
   numbered section, so it carries a bare title and no block at all. */
.wz-legal-doc .wz-legal-head {
    margin-bottom: 34px;
    padding-bottom: 26px;
    border-bottom: 1px solid var(--wz-line, #e7e7ee);
}

.wz-legal-doc h2 {
    font-size: 34px;
    line-height: 1.2;
    margin: 0 0 34px;
    color: var(--wz-ink, #0d0d12);
}

.wz-legal-doc .wz-legal-head h2 { margin-bottom: 14px; }

/* The gradient the home page uses for its banner, as a short rule under the
   title - it marks the page as this site's without colouring the words of a
   document that should stay black on white. */
.wz-legal-doc h2::after {
    content: '';
    display: block;
    width: 64px;
    height: 4px;
    margin-top: 16px;
    border-radius: 999px;
    background: var(--wz-grad, linear-gradient(115deg, #a855f7 0%, #7c3aed 22%, #c2410c 62%, #f97316 100%));
}

.wz-legal-doc .wz-legal-head p { margin: 0 0 10px; }

.wz-legal-doc .wz-legal-head p:last-child { margin-bottom: 0; }

/* ---------------------------------------------------------- sections --- */

/* A hairline above every section but the first: eight numbered clauses in one
   column need a visible edge between them, and space alone was not enough. */
.wz-legal-doc h3 {
    font-size: 20px;
    line-height: 1.35;
    color: var(--wz-ink, #0d0d12);
    margin: 40px 0 16px;
    padding-top: 34px;
    border-top: 1px solid var(--wz-line, #e7e7ee);
}

/* The first section needs no rule above it - the title block is already an
   edge. Both openings are covered: a lead-in block, or a bare title. */
.wz-legal-doc .wz-legal-head + h3,
.wz-legal-doc h2 + h3 {
    margin-top: 0;
    padding-top: 0;
    border-top: 0;
}

/* Sub-clauses - "2.1 Personal Information". Inside a section, so no rule and
   no number of their own: a step down in size is the whole of the signal. */
.wz-legal-doc h4 {
    font-size: 16.5px;
    font-weight: 700;
    color: var(--wz-ink, #0d0d12);
    margin: 26px 0 10px;
}

.wz-legal-doc h3 + h4 { margin-top: 0; }

.wz-legal-doc p { margin: 0 0 14px; }

.wz-legal-doc em { font-style: italic; }

/* A bold lead-in - "Account Creation:" - is the clause's name, so it reads as
   ink rather than as emphasis inside the sentence. */
.wz-legal-doc strong { color: var(--wz-ink, #0d0d12); font-weight: 700; }

/* ------------------------------------------------------------- lists --- */

/* The browser's disc sits on the baseline and crowds the text. A square in the
   accent colour, held at the cap height of the first line, reads as a clause
   marker rather than as a shopping list. */
.wz-legal-doc ul {
    list-style: none;
    margin: 0 0 18px;
    padding: 0;
}

.wz-legal-doc ul li {
    position: relative;
    padding-left: 26px;
    margin-bottom: 14px;
}

.wz-legal-doc ul li::before {
    content: '';
    position: absolute;
    left: 2px;
    top: .62em;
    width: 7px;
    height: 7px;
    border-radius: 2px;
    background: var(--wz-purple, #7c3aed);
}

/* The nested lists - the prohibited activities, the CRA assessments. A hollow
   marker one step in, so a sub-clause is visibly subordinate to the clause it
   belongs to rather than a ninth item in the same list. */
.wz-legal-doc ul ul {
    margin: 12px 0 4px;
    padding-left: 4px;
}

.wz-legal-doc ul ul li {
    margin-bottom: 8px;
    padding-left: 22px;
}

.wz-legal-doc ul ul li::before {
    top: .68em;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: transparent;
    border: 1.5px solid var(--wz-muted, #8f8fa3);
}

.wz-legal-doc ul li:last-child { margin-bottom: 0; }

/* ------------------------------------------------------------ footer --- */

/* Who to write to, set apart from the clauses above it - it is the one part of
   the page the reader is meant to act on. */
.wz-legal-doc .wz-legal-contact {
    margin-top: 14px;
    padding: 22px 24px;
    background: var(--wz-bg, #f3f3f7);
    border-radius: var(--wz-radius-sm, 12px);
}

.wz-legal-doc .wz-legal-contact p:last-child { margin-bottom: 0; }

.wz-legal-doc .wz-legal-contact a { color: var(--wz-purple, #7c3aed); font-weight: 600; }

.wz-legal-doc .wz-legal-contact a:hover { text-decoration: underline; }

/* ------------------------------------------------------------ phones --- */

@media (max-width: 767px) {
    .wz-legal-doc {
        padding: 32px 22px 36px;
        border-radius: var(--wz-radius-sm, 12px);
        font-size: 15.5px;
    }

    .wz-legal-doc h2 { font-size: 26px; }

    .wz-legal-doc h3 { font-size: 18px; margin-top: 30px; padding-top: 26px; }

    .wz-legal-doc .wz-legal-contact { padding: 18px; }
}
</style>
