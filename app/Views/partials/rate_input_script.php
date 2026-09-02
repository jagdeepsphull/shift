<?php
/**
 * Dollars-and-cents typing for every hourly-rate field on the site.
 *
 * `min`, `max`, `step` and `inputmode` are written on the inputs themselves, so
 * the range and the cent step hold with JavaScript off; the server re-checks
 * either way - see setRateRule() and RATE_PATTERN. This is what stops the
 * shapes a number box will otherwise let somebody type or paste:
 *
 *  - a second decimal point, and a third and a fourth ("3.4.3.4"). A number
 *    input holds that text while reporting its value as '', so the box looks
 *    filled in and posts nothing.
 *  - more cents than exist ("42.555"), which the column would round off to a
 *    rate nobody typed.
 *  - a bare ".334" with no dollars in front of it.
 *
 * Included from the admin and employer footers, so a rate field added to either
 * area picks the behaviour up without another copy of this code.
 */
?>
<script>
  (function () {
    var DECIMALS = <?= (int) RATE_DECIMALS ?>;
    // Marked-up fields, plus the two names actually used, so a rate box added
    // without the attribute still behaves.
    var SELECTOR = 'input[data-rate-input], input[name="p_hourly_rate"], input[name="p_ac_hourly_rate"]';

    /**
     * A typed rate reduced to the only shape a rate has: digits, at most one
     * point, at most DECIMALS after it. Everything else is dropped rather than
     * rejected, so a paste of "CAD$ 42.50/hr" leaves 42.50 behind.
     */
    function clean(value) {
      var text = String(value).replace(/[^0-9.]/g, '');
      var parts = text.split('.');

      if (parts.length === 1) {
        return parts[0];
      }

      // Every point after the first is dropped, and its digits keep their
      // place: "3.4.3.4" is somebody typing 3.434, not 3.4.
      var cents = parts.slice(1).join('').slice(0, DECIMALS);

      // A trailing point is left alone while it is being typed - "42." is on
      // the way to "42.5", and taking the point away as it lands makes the
      // field impossible to use. It cannot survive the blur below.
      return parts[0] + '.' + cents;
    }

    function prepare(input) {
      if (input.dataset.rateBound === '1') {
        return;
      }

      input.dataset.rateBound = '1';
      input.setAttribute('inputmode', 'decimal');

      // A number input reports '' for text it considers invalid, so the raw
      // characters have to be read off the key rather than off the value.
      input.addEventListener('keypress', function (event) {
        if (!event.key || event.key.length !== 1) {
          return;
        }

        if (!/[0-9.]/.test(event.key)) {
          event.preventDefault();

          return;
        }

        // A point is only ever the first one, and only where there are dollars
        // in front of it - ".334" is not an amount of money.
        if (event.key === '.' && (input.value === '' || input.value.indexOf('.') !== -1)) {
          event.preventDefault();
        }
      });

      // Pasting goes nowhere near keypress, and neither does the spinner.
      input.addEventListener('input', function () {
        var cleaned = clean(input.value);

        if (cleaned !== input.value) {
          input.value = cleaned;
        }
      });

      input.addEventListener('paste', function (event) {
        var text = (event.clipboardData || window.clipboardData);

        if (!text) {
          return;
        }

        event.preventDefault();
        input.value = clean(text.getData('text'));
        input.dispatchEvent(new Event('input', { bubbles: true }));
      });

      // Once the field is left, what is in it has to be a rate and not a rate
      // half typed: "42." becomes 42, and a box the browser is holding as
      // invalid text is emptied rather than left looking filled in.
      input.addEventListener('blur', function () {
        var cleaned = clean(input.value).replace(/\.$/, '');

        input.value = cleaned === '.' ? '' : cleaned;
      });
    }

    function scan(root) {
      Array.prototype.forEach.call((root || document).querySelectorAll(SELECTOR), prepare);
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', function () { scan(document); });
    } else {
      scan(document);
    }

    // The employer's shift form builds parts of itself after load, so anything
    // added later is picked up too.
    if (window.MutationObserver) {
      new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
          Array.prototype.forEach.call(mutation.addedNodes, function (node) {
            if (node.nodeType !== 1) {
              return;
            }

            if (node.matches && node.matches(SELECTOR)) {
              prepare(node);
            }

            scan(node);
          });
        });
      }).observe(document.documentElement, { childList: true, subtree: true });
    }
  })();
</script>
