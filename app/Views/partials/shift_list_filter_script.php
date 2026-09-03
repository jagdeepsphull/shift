<?php
/**
 * Date filtering for the employer's shift list, and the Upcoming Shifts button
 * that is one preset of it.
 *
 * All Shifts runs to every shift the account has ever raised. The question
 * asked of it most - "what is on next week?" - had no answer but sorting by
 * date and scrolling to the right place, and the search box is no help: typing
 * "Sep 2026" matches that month but not a range crossing one, and matches
 * nothing at all while the responsive plugin has the column collapsed.
 *
 * The button and the box are not two filters. Upcoming Shifts sets the same
 * range to "today, open ended" and says so in the box; picking a range off the
 * calendar turns the button off; clearing the box turns both off. One state,
 * two ways to set it, so the two can never contradict each other on screen.
 *
 * A table opts in with `data-daterange-col`, naming the column index to filter
 * on - see employer/all_jobs.php. The applicant's applied-shift list shares the
 * `#joblist` id and does not carry the attribute, so it is left alone.
 *
 * Filtering is client-side, like the search and the sorting already on this
 * page: the controller sends the whole table down in one go, so there is
 * nothing to fetch and no page to wait for.
 *
 * Each row's date is read from the `data-order` attribute the view already puts
 * on the cell for sorting - a plain YYYY-MM-DD out of shiftDateSortValue() - so
 * the range test is a string comparison, and no date is parsed per row per
 * redraw.
 *
 * Included from employer/footer.php, after the DataTable is built: this needs
 * the instance, not the markup.
 */
?>
<script>
  (function () {
    if (!window.jQuery || !window.moment || !jQuery.fn.dataTable) { return; }

    var $ = jQuery;

    $(function () {
      var $table = $('#joblist');
      var col    = $table.data('daterange-col');

      if (!$table.length || col === undefined || col === '' || !$.fn.dataTable.isDataTable($table[0])) {
        return;
      }

      col = parseInt(col, 10);

      var table   = $table.DataTable();
      var tableId = $table.attr('id');

      // shiftDateSortValue()'s stand-in for a shift date it could not read. It
      // is not a date, so such a row cannot be said to fall inside any window -
      // including the open-ended one Upcoming Shifts asks for. It is still
      // there with the filter off, which is where it can be found and fixed.
      var UNREADABLE = '9999-12-31';

      // The chosen window, in the same YYYY-MM-DD the cells carry. Either end
      // may be null: both while the list is unfiltered, and the far end alone
      // for Upcoming Shifts, which has no last day.
      var from = null;
      var to   = null;

      var $upcoming = $('#joblist-upcoming');

      // ---- the box, in the table's own toolbar ---------------------------
      //
      // `lengthChange` is off on this table, so DataTables leaves the left half
      // of its toolbar row empty; the box goes there, opposite the search. On a
      // narrow window the two halves stack, which is the layout the theme's
      // grid already gives them.
      var $filter = $(
        '<div class="ps-date-filter">' +
          '<span class="ps-date-filter__box">' +
            '<i class="lni lni-calendar" aria-hidden="true"></i>' +
            '<input type="text" class="ps-date-filter__input" readonly>' +
          '</span>' +
          '<button type="button" class="ps-date-filter__clear" aria-label="Clear the date filter" hidden>&times;</button>' +
        '</div>'
      );

      var $input = $filter.find('.ps-date-filter__input');
      var $clear = $filter.find('.ps-date-filter__clear');

      $input.attr({
        placeholder: $table.data('daterange-label') || 'Any shift date',
        title: 'Filter these shifts by date',
        'aria-label': 'Filter these shifts by date'
      });

      var $wrapper = $('#' + tableId + '_wrapper');
      var $toolbar = $wrapper.find('.dataTables_filter').closest('.row').children().first();

      if (!$toolbar.length) {
        $toolbar = $wrapper.find('.row').first().children().first();
      }

      $filter.appendTo($toolbar.length ? $toolbar : $wrapper);

      // ---- one filter state, however it was set ---------------------------

      /**
       * @param {?string} nextFrom  first day in the window, or null for none
       * @param {?string} nextTo    last day, or null for open ended
       * @param {string}  label     what the box reads while it is on
       * @param {boolean} viaButton whether Upcoming Shifts is what set it
       */
      var apply = function (nextFrom, nextTo, label, viaButton) {
        from = nextFrom;
        to   = nextTo;

        $input.val(label);
        $clear.prop('hidden', from === null && to === null);

        $upcoming
          .toggleClass('is-on', !!viaButton)
          .attr('aria-pressed', viaButton ? 'true' : 'false');

        table.draw();
      };

      // ---- the picker -----------------------------------------------------
      //
      // The same presets and the same wording as the home page's hero search,
      // which is where an employer will have met this control first. Forward
      // looking, because a shift list is read forwards: Custom Range is there
      // for the rarer occasion somebody wants to look back.
      var day = function () { return moment().startOf('day'); };

      $input.daterangepicker({
        // The box starts empty, saying "Any shift date". Without this the
        // plugin writes today's date into it on load, which reads as a filter
        // that is on while the list is in fact showing everything.
        autoUpdateInput: false,
        // A second click on the calendar applies a custom range, so there is no
        // Apply button to hunt for; a preset applies on its one click either
        // way - see clickRange() in the plugin.
        autoApply: true,
        alwaysShowCalendars: true,
        opens: 'right',
        drops: 'down',
        ranges: {
          'Today':        [day(), day()],
          'Tomorrow':     [day().add(1, 'days'), day().add(1, 'days')],
          'Next 7 Days':  [day(), day().add(6, 'days')],
          'Next 30 Days': [day(), day().add(29, 'days')],
          'This Month':   [moment().startOf('month'), moment().endOf('month')],
          'Next Month':   [moment().add(1, 'month').startOf('month'), moment().add(1, 'month').endOf('month')]
        },
        // The plugin's own template with one class added, so the portal's
        // time-picker rules - which hide the calendar and the range list, for
        // the hours boxes on the shift form - leave this picker alone.
        template:
          '<div class="daterangepicker ps-shift-picker">' +
            '<div class="ranges"></div>' +
            '<div class="drp-calendar left">' +
              '<div class="calendar-table"></div>' +
              '<div class="calendar-time"></div>' +
            '</div>' +
            '<div class="drp-calendar right">' +
              '<div class="calendar-table"></div>' +
              '<div class="calendar-time"></div>' +
            '</div>' +
            '<div class="drp-buttons">' +
              '<span class="drp-selected"></span>' +
              '<button class="cancelBtn" type="button"></button>' +
              '<button class="applyBtn" disabled="disabled" type="button"></button>' +
            '</div>' +
          '</div>',
        locale: {
          format: 'DD MMM YYYY',
          firstDay: 1,
          customRangeLabel: 'Custom Range'
        }
      });

      $input.on('apply.daterangepicker', function (ev, picker) {
        var start = picker.startDate.format('DD MMM YYYY');
        var end   = picker.endDate.format('DD MMM YYYY');

        // The preset's own words when one was clicked - "Next 7 Days" says more
        // at a glance than the two dates it stands for - and the dates
        // themselves for a range picked off the calendar.
        var label = (picker.chosenLabel && picker.chosenLabel !== picker.locale.customRangeLabel)
          ? picker.chosenLabel
          : (start === end ? start : start + ' - ' + end);

        apply(
          picker.startDate.format('YYYY-MM-DD'),
          picker.endDate.format('YYYY-MM-DD'),
          label,
          false
        );
      });

      // Without this there is no way back to the whole list but reloading the
      // page, which also loses whatever was typed in the search box.
      $clear.on('click', function () {
        apply(null, null, '', false);
      });

      // ---- Upcoming Shifts ------------------------------------------------
      //
      // Today counts as upcoming: a shift is still worked on the morning of its
      // own day, which is the line shiftDatePassed() draws for the public site.
      $upcoming.on('click', function () {
        if ($upcoming.hasClass('is-on')) {
          apply(null, null, '', false);

          return;
        }

        apply(day().format('YYYY-MM-DD'), null, 'Today onwards', true);
      });

      // ---- the filter itself ----------------------------------------------
      //
      // ext.search is one global list every table on the page runs, so this
      // checks which table it has been handed before deciding anything.
      $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        if (settings.nTable.id !== tableId || (from === null && to === null)) { return true; }

        var row  = settings.aoData[dataIndex];
        var cell = row && row.anCells ? row.anCells[col] : null;
        var date = cell ? cell.getAttribute('data-order') : null;

        if (!/^\d{4}-\d{2}-\d{2}$/.test(date || '') || date === UNREADABLE) { return false; }

        // Both ends are YYYY-MM-DD, where comparing the strings and comparing
        // the dates are the same thing.
        if (from !== null && date < from) { return false; }
        if (to !== null && date > to) { return false; }

        return true;
      });
    });
  })();
</script>
