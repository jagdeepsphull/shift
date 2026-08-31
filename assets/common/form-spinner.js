/*
 * A spinner in the submit button, from the moment a form is sent until the next
 * page arrives.
 *
 * On a slow connection the site otherwise looks like it ignored the click:
 * nothing moves, so the button gets pressed again, and a second booking or a
 * second registration is created. This says "working" and stops the second
 * press.
 *
 * Injected into every page by App\Filters\FormSubmitSpinner, for the same
 * reason the CSRF field is - a form added next month would otherwise have to
 * remember to opt in, and would not.
 *
 * A form that must not have one can opt out with `data-no-spinner` on the tag.
 *
 * ---------------------------------------------------------------------------
 * Two things about this application shape the whole file, and both are easy to
 * get wrong in a way that is invisible until it is live.
 *
 * 1. The submit button's name is part of the form data. Fifty-six of them carry
 *    one - `savedata`, `updatedata`, `updateprofile` - and thirty-two branches
 *    across Sadmin, Employer and Applicant do nothing unless they see it in the
 *    POST. A disabled control is not submitted, so disabling the button to stop
 *    a second press silently deletes the field that makes the first press save
 *    anything. `carryOver()` below is the answer.
 *
 * 2. jQuery Validate answers `return false` from its own submit handler, and in
 *    jQuery that means preventDefault *and* stopPropagation. The event never
 *    reaches a listener on the document in the bubble phase, so the ordinary way
 *    of doing this would miss sign-in, registration, the password screens and
 *    every other validated form - which is most of the ones that matter. The
 *    capture-phase listener below runs before jQuery Validate can stop
 *    anything, and asks the validator the same question it is about to ask
 *    itself.
 * ---------------------------------------------------------------------------
 */

(function () {
    'use strict';

    /** Marks the form that is currently in flight. Also the CSS hook. */
    var BUSY = 'data-pas-busy';

    /** Opt-out, mirroring `data-no-csrf` on the CSRF injector. */
    var OPT_OUT = 'data-no-spinner';

    /** Every form started this page-load, so `restore()` can find them again. */
    var started = [];

    /**
     * The last submit control pressed.
     *
     * `event.submitter` gives this directly, but only since Safari 15.4, and the
     * back office is used on whatever is installed. Tracked in the capture phase
     * so it is already recorded by the time the submit event arrives.
     */
    var lastPressed = null;

    document.addEventListener('mousedown', notePress, true);
    document.addEventListener('keydown', notePress, true);
    document.addEventListener('click', notePress, true);

    function notePress(e) {
        var el = e.target;

        while (el && el !== document) {
            if (isSubmitControl(el)) {
                lastPressed = el;

                return;
            }

            el = el.parentNode;
        }
    }

    function isSubmitControl(el) {
        if (!el || el.nodeType !== 1) {
            return false;
        }

        var tag = (el.tagName || '').toUpperCase();

        if (tag === 'BUTTON') {
            // A button with no type submits: that is the HTML default, and a
            // good half of the forms here rely on it.
            var type = (el.getAttribute('type') || 'submit').toLowerCase();

            return type === 'submit';
        }

        if (tag === 'INPUT') {
            var it = (el.getAttribute('type') || '').toLowerCase();

            return it === 'submit' || it === 'image';
        }

        return false;
    }

    /** The control that sent this form, however the browser reports it. */
    function submitterFor(form, event) {
        if (event && event.submitter) {
            return event.submitter;
        }

        if (lastPressed && form.contains(lastPressed)) {
            return lastPressed;
        }

        // Submitted from the keyboard with no button focused, or by script. Fall
        // back to the control the browser would have used - the first one.
        var controls = form.querySelectorAll('button, input[type="submit"], input[type="image"]');

        for (var i = 0; i < controls.length; i++) {
            if (isSubmitControl(controls[i])) {
                return controls[i];
            }
        }

        return null;
    }

    /**
     * The jQuery Validate validator on this form, if it has one.
     *
     * Asking it is how a validated form is decided before jQuery Validate gets
     * the chance to stop the event - see the note at the top.
     */
    function validatorFor(form) {
        var jq = window.jQuery;

        if (!jq || typeof jq.data !== 'function') {
            return null;
        }

        try {
            var v = jq.data(form, 'validator');

            return v && typeof v.checkForm === 'function' ? v : null;
        } catch (e) {
            return null;
        }
    }

    /**
     * Keep the pressed button's name in the POST.
     *
     * Belt and braces, and deliberately so. The button is disabled a tick later
     * than the submit rather than during it, and by then the browser has already
     * collected what it is sending - so the button's own field goes with it and
     * this hidden one is a duplicate, which PHP resolves to the same value.
     *
     * It is here for the day somebody moves that disable earlier, which is the
     * obvious tidy-up to make and looks entirely safe. It is not: it silently
     * empties the field that thirty-two save and update branches switch on, and
     * the only symptom is a page that reloads having saved nothing. See point 1
     * at the top.
     */
    function carryOver(form, btn) {
        if (!btn || !btn.name) {
            return;
        }

        var keep = document.createElement('input');

        keep.type = 'hidden';
        keep.name = btn.name;
        keep.value = btn.value === undefined || btn.value === null ? '' : btn.value;
        keep.setAttribute('data-pas-keep', '');

        form.appendChild(keep);
    }

    /** Put the spinner on the control and take it out of service. */
    function decorate(form, btn) {
        if (!btn) {
            return;
        }

        var tag = (btn.tagName || '').toUpperCase();

        btn.setAttribute('data-pas-control', '');

        btn._pas = {
            html: null,
            width: btn.style.width,
            wrap: null,
            disabled: btn.disabled,
        };

        carryOver(form, btn);

        if (tag === 'BUTTON') {
            // Pinned at the width it already had, so prepending the spinner does
            // not make the button grow and shove the row about.
            var w = btn.offsetWidth;

            if (w) {
                btn.style.width = w + 'px';
            }

            btn._pas.html = btn.innerHTML;

            var spin = document.createElement('span');

            spin.className = 'pas-spin pas-spin--inside';
            spin.setAttribute('aria-hidden', 'true');

            btn.insertBefore(spin, btn.firstChild);
        } else if (btn.parentNode) {
            // An <input> has no inside to put anything in, and a plain sibling
            // drops onto the next line the moment the button is full width -
            // which the sign-in button is. So it is wrapped, and the ring sits
            // over its right-hand end.
            //
            // The value is left alone throughout: on an <input type="submit">
            // that string is both the label and submitted data.
            var wrap = document.createElement('span');

            wrap.className = 'pas-wrap';

            // Held at the width it already had, so wrapping cannot change the
            // layout it was sitting in.
            var iw = btn.offsetWidth;

            if (iw) {
                wrap.style.width = iw + 'px';
            }

            btn.parentNode.insertBefore(wrap, btn);
            wrap.appendChild(btn);

            var over = document.createElement('span');

            over.className = 'pas-spin pas-spin--over';
            over.setAttribute('aria-hidden', 'true');

            // The ring is drawn in `currentColor`, and this one is beside the
            // input rather than inside it - so it would inherit the colour of
            // whatever the input sits in, not the button's own label colour.
            // On the sign-in button that is black on black, which is a spinner
            // that is running and cannot be seen. Take the colour off the
            // control itself.
            try {
                var cs = window.getComputedStyle(btn);

                over.style.color = cs.color;

                // The stylesheet clears the z-index: 1 both themes put on .btn.
                // A theme that stacks a control higher than that would hide the
                // ring underneath it again, and it would look like the spinner
                // had simply stopped working, so match it instead of guessing.
                var z = parseInt(cs.zIndex, 10);

                if (!isNaN(z) && z >= 2) {
                    over.style.zIndex = String(z + 1);
                }
            } catch (e) {
                // Leave it to inherit; a visible-but-wrong colour beats none.
            }

            wrap.appendChild(over);

            btn._pas.wrap = wrap;
        }

        // Disabled on the next tick rather than now. The submission is already
        // under way by then, and a control disabled from inside its own submit
        // event has historically been enough to make some browsers abandon it.
        // A second press in that sliver of time is caught by the BUSY guard.
        window.setTimeout(function () {
            btn.disabled = true;
        }, 0);
    }

    /**
     * Mark the form as sending and dress the button.
     *
     * @returns {boolean} false if this form is not one to touch, or is already
     *                    on its way.
     */
    function begin(form, btn) {
        if (!form || form.nodeType !== 1 || (form.tagName || '').toUpperCase() !== 'FORM') {
            return false;
        }

        if (form.hasAttribute(OPT_OUT) || form.hasAttribute(BUSY)) {
            return false;
        }

        // A form aimed at another window or frame leaves this page exactly where
        // it is, so nothing would ever come back to clear the spinner.
        var target = form.getAttribute('target');

        if (target && target !== '_self') {
            return false;
        }

        form.setAttribute(BUSY, '');
        form.setAttribute('aria-busy', 'true');

        decorate(form, btn);
        started.push(form);

        return true;
    }

    /**
     * Undo everything `begin()` did.
     *
     * Wanted on the way back: a browser restoring this page from its cache
     * restores the DOM as it was when it left, which is mid-submit, with a
     * disabled button and a ring still turning. Nothing else on the page is
     * stale, so the form looks broken rather than cached.
     */
    function restore() {
        for (var i = 0; i < started.length; i++) {
            var form = started[i];

            form.removeAttribute(BUSY);
            form.removeAttribute('aria-busy');

            var keeps = form.querySelectorAll('[data-pas-keep]');

            for (var k = 0; k < keeps.length; k++) {
                if (keeps[k].parentNode) {
                    keeps[k].parentNode.removeChild(keeps[k]);
                }
            }

            var controls = form.querySelectorAll('[data-pas-control]');

            for (var c = 0; c < controls.length; c++) {
                var btn = controls[c];
                var s = btn._pas;

                btn.removeAttribute('data-pas-control');

                if (!s) {
                    continue;
                }

                if (s.html !== null) {
                    btn.innerHTML = s.html;
                }

                // Put the input back where it was and take the wrapper away.
                if (s.wrap && s.wrap.parentNode) {
                    s.wrap.parentNode.insertBefore(btn, s.wrap);
                    s.wrap.parentNode.removeChild(s.wrap);
                }

                btn.style.width = s.width;
                btn.disabled = s.disabled;
                btn._pas = null;
            }
        }

        started = [];
    }

    // ---------------------------------------------------------------- events --

    /*
     * Capture, on the document: this runs before any handler on the form itself,
     * which is the only place a validated form can be caught at all.
     */
    document.addEventListener('submit', function (e) {
        var form = e.target;

        if (!form || (form.tagName || '').toUpperCase() !== 'FORM') {
            return;
        }

        // The second press, while the first is still going. This is the thing
        // the whole file exists to stop.
        if (form.hasAttribute(BUSY)) {
            e.preventDefault();

            return;
        }

        var v = validatorFor(form);

        if (!v) {
            // Nothing to ask. Leave it to the bubble listener below, which knows
            // by then whether anything cancelled the submit.
            return;
        }

        // `checkForm()` runs the rules and returns the answer without drawing
        // the error messages - jQuery Validate is about to do that itself a
        // moment later, and doing it twice would be visible.
        var ok = false;

        try {
            ok = v.checkForm();
        } catch (err) {
            // A validator that throws is not a reason to block the form.
            return;
        }

        if (ok) {
            begin(form, submitterFor(form, e));
        }
    }, true);

    /*
     * Bubble, on the document: only reached when nothing cancelled the submit,
     * so `onsubmit="return false"` on the search bar and a declined confirm()
     * on the resubscribe button both correctly get no spinner.
     */
    document.addEventListener('submit', function (e) {
        if (e.defaultPrevented) {
            return;
        }

        begin(e.target, submitterFor(e.target, e));
    }, false);

    /*
     * Forms sent by script rather than by a press.
     *
     * `form.submit()` is the DOM method, not the event - it fires no submit
     * event at all, so neither listener above ever sees it. The back office's
     * dashboard uses it on a select, and jQuery Validate uses it to send a form
     * it has just approved (already started by then, and the BUSY guard keeps
     * this from doing it twice).
     */
    try {
        var nativeSubmit = HTMLFormElement.prototype.submit;

        HTMLFormElement.prototype.submit = function () {
            try {
                begin(this, submitterFor(this, null));
            } catch (err) {
                // Never let the spinner be the reason a form does not send.
            }

            return nativeSubmit.apply(this, arguments);
        };
    } catch (err) {
        // Frozen prototype, or an environment without one. Presses still work.
    }

    window.addEventListener('pageshow', function (e) {
        if (e.persisted) {
            restore();
        }
    });
}());
