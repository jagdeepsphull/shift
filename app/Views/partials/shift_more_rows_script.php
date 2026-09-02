<?php
/**
 * "Add More" on the admin's Add Shift form: one more date-and-hours row per
 * click, and an (X) on each of those rows that takes it off again.
 *
 * The first row is the shift as before; each row added here is one more shift
 * on the same terms, and the controller writes one post_job row per row on the
 * page. Nothing is numbered or capped - five clicks is five rows, and every
 * row added can be removed. The first row cannot: it is the form's own date
 * and hours, and a shift with no date is not a shift.
 *
 * A new row is copied from the <template> the form renders (the same partial
 * the server uses for a row that comes back from a failed save, so the markup
 * is in one place), given the hours of the row above it - a run of shifts
 * mostly keeps the same hours, and the date is the thing that changes - and
 * then dressed with the pickers through window.applyShiftPickers(), which is
 * how the first row got its own. The pickers each hang a dropdown off the
 * body, so a removed row takes its two down with it rather than leaving them
 * behind.
 *
 * Included from the admin footer and a no-op on every other page: it looks for
 * the button and does nothing without it. It binds nothing that needs jQuery
 * ready - the footer defines applyShiftPickers inside ready, and the first
 * click cannot come before that.
 */
?>
<script>
  (function () {
    var button = document.getElementById('shift_more_add');
    var rows = document.getElementById('shift_more_rows');
    var template = document.getElementById('shift_more_row_template');

    if (!button || !rows || !template || !window.jQuery) { return; }

    var $ = window.jQuery;

    button.addEventListener('click', function () {
      var $row = $(template.innerHTML.trim());

      // The hours of the row above - the first row's until one is added.
      // Set before the pickers go on, because the hours picker opens on
      // whatever the box holds when it is dressed.
      $row.find('.timePicker').val($(button.form).find('.timePicker').last().val());

      $(rows).append($row);

      if (window.applyShiftPickers) { window.applyShiftPickers($row); }
    });

    $(rows).on('click', '[data-shift-more-remove]', function () {
      var $row = $(this).closest('[data-shift-more-row]');

      if ($.fn.datepicker) { $row.find('.date').datepicker('remove'); }

      $row.find('.timePicker').each(function () {
        var picker = $(this).data('daterangepicker');

        if (picker) { picker.remove(); }
      });

      $row.remove();
    });
  })();
</script>
