<?php
/**
 * Digits-only, ten-at-most behaviour for every mobile number field on the site.
 *
 * `maxlength` and `inputmode` are written on the inputs themselves, so the cap
 * and the numeric keypad hold even with JavaScript off; this script is what
 * stops letters, spaces and bracketed pastes ("(905) 304-7303") from ever
 * reaching the field. The server still re-checks - see PHONE_PATTERN.
 *
 * Included from each of the four footers, so a field added to any area picks
 * the behaviour up without another copy of this code.
 */
?>
<script>
  (function () {
    var LENGTH   = <?= (int) PHONE_LENGTH ?>;
    // Fields marked up as phone inputs, plus the names actually used, so a
    // field that gets added without the attribute still behaves.
    var SELECTOR = 'input[data-phone-input], input[name="u_phone"], input[name="s_phone"], input[name="mobile"], input[name="u_a_cp_mobile"]';

    function digitsOnly(value) {
      return String(value).replace(/\D+/g, '').slice(0, LENGTH);
    }

    function prepare(input) {
      if (input.dataset.phoneBound === '1') {
        return;
      }

      input.dataset.phoneBound = '1';
      input.setAttribute('maxlength', LENGTH);
      input.setAttribute('inputmode', 'numeric');
      input.setAttribute('autocomplete', 'tel-national');

      // A value already in the box (an edit form, or a redisplayed form after a
      // failed save) is cleaned once on load, so what is shown is what will be
      // accepted.
      if (input.value !== '') {
        input.value = digitsOnly(input.value);
      }

      input.addEventListener('input', function () {
        var cleaned = digitsOnly(input.value);

        if (cleaned !== input.value) {
          // Keep the caret where the typing was, minus whatever was dropped.
          var caret  = input.selectionStart;
          var removed = input.value.length - cleaned.length;

          input.value = cleaned;

          if (caret !== null) {
            try {
              input.setSelectionRange(caret - removed, caret - removed);
            } catch (e) { /* input types that disallow selection */ }
          }
        }
      });

      // Typing is covered by 'input'; this catches the keys that would insert a
      // character the field cannot hold at all.
      input.addEventListener('keypress', function (event) {
        if (event.key && event.key.length === 1 && !/[0-9]/.test(event.key)) {
          event.preventDefault();
        }
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

    // Registration panels and modals are built after load on some pages, so
    // anything added later is picked up too.
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
