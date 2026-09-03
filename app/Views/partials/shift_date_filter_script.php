<?php
/**
 * Date-range filtering for an admin listing, on its shift-date column.
 *
 * The back-office shift list runs to every shift ever posted, and the job
 * applications list to every application ever made. The question asked of both
 * most - "what is on next week?" - had no answer but sorting by date and
 * scrolling to the right place. The search box is no help: typing
 * "Sep 2026" matches that month but not a range crossing one, and matches
 * nothing at all while the responsive plugin has the column collapsed.
 *
 * A table opts in with `data-daterange-col`, naming the column index to filter
 * on - see admin/postjobs/index.php and admin/application/index.php. Nothing is
 * added to a table without it, so the other listings sharing the `#example1`
 * id are untouched.
 *
 * Filtering is client-side, like the search, the sorting and the exports
 * already on these pages: the controller sends the whole table down in one go,
 * so there is nothing to fetch. It also means the Excel and PDF downloads carry
 * the filtered rows and only those, DataTables exporting what the table shows.
 *
 * Each row's date is read from the `data-order` attribute the view already puts
 * on the cell for sorting - a plain YYYY-MM-DD out of shiftDateSortValue() - so
 * the range test is a string comparison, and no date is parsed per row per
 * redraw. A shift whose typed date could not be read carries that helper's
 * 9999-12-31 sentinel and so falls outside any range an admin would pick; it is
 * still there with the filter off, which is where it can be found and fixed.
 *
 * Included from the admin footer, after the DataTable is built: this needs the
 * instance, not the markup.
 */
?>
<script>
  (function () {
    if (!window.jQuery || !window.moment || !jQuery.fn.dataTable) { return; }

    var $ = jQuery;

    $(function () {
      var $table = $('#example1');
      var col    = $table.data('daterange-col');

      if (!$table.length || col === undefined || col === '' || !$.fn.dataTable.isDataTable($table[0])) {
        return;
      }

      col = parseInt(col, 10);

      var table   = $table.DataTable();
      var tableId = $table.attr('id');
      var heading = ($table.find('thead th').eq(col).first().text() || 'date').trim().toLowerCase();

      // The chosen range, in the same YYYY-MM-DD the cells carry, or null while
      // the list is unfiltered. Read by the search callback at the bottom.
      var from = null;
      var to   = null;

      // ---- the control, in the table's own toolbar ------------------------
      //
      // Dropped in beside the Excel / PDF / Column visibility buttons rather
      // than above the card: it belongs with the other things that change what
      // the table is showing. `.dt-buttons` is inline-block and this is
      // inline-flex, so on a narrow window the two wrap rather than crowd the
      // search box opposite.
      var $filter = $(
        '<div class="shift-date-filter d-inline-flex align-items-center ml-2 mb-1">' +
          '<div class="input-group input-group-sm shift-date-filter__box">' +
            '<div class="input-group-prepend">' +
              '<span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>' +
            '</div>' +
            '<input type="text" class="form-control" readonly>' +
          '</div>' +
          '<button type="button" class="btn btn-sm btn-link text-muted shift-date-filter__clear" hidden>Clear</button>' +
        '</div>'
      );

      var $input = $filter.find('input');
      var $clear = $filter.find('.shift-date-filter__clear');

      $input.attr({
        placeholder: $table.data('daterange-label') || 'All dates',
        title: 'Filter this list by ' + heading,
        'aria-label': 'Filter this list by ' + heading
      });

      // Into the half of the toolbar the buttons live in. That container is
      // put there by the footer's DataTable setup, and this runs after it.
      var $toolbar = $('#' + tableId + '_wrapper').find('.dt-buttons').parent();

      if (!$toolbar.length) {
        $toolbar = $('#' + tableId + '_wrapper').find('.col-md-6').first();
      }

      $filter.appendTo($toolbar);

      // ---- the picker -----------------------------------------------------
      //
      // Forward-looking presets, because a shift list is read forwards: an
      // admin checks what is coming up, and reaches for Custom Range on the
      // rarer occasion they want to look back.
      var day = function () { return moment().startOf('day'); };

      var ranges = {
        'Today':        [day(), day()],
        'Tomorrow':     [day().add(1, 'days'), day().add(1, 'days')],
        'Next 7 Days':  [day(), day().add(6, 'days')],
        'Next 30 Days': [day(), day().add(29, 'days')],
        'This Month':   [moment().startOf('month'), moment().endOf('month')],
        'Next Month':   [moment().add(1, 'month').startOf('month'), moment().add(1, 'month').endOf('month')]
      };

      $input.daterangepicker({
        // The box starts empty, saying "All dates". Without this the plugin
        // writes today's date into it on load, which reads as a filter that is
        // on while the list is in fact showing everything.
        autoUpdateInput: false,
        // A second click on the calendar applies a custom range, so there is no
        // Apply button to hunt for; a preset applies on its one click either
        // way - see clickRange() in the plugin.
        autoApply: true,
        alwaysShowCalendars: true,
        opens: 'right',
        ranges: ranges,
        // The plugin's own template with one class added, so the admin header's
        // time-picker rules - which hide the calendar and the range list, for
        // the hours boxes on the shift form - leave this picker alone.
        template:
          '<div class="daterangepicker shift-date-picker">' +
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
        from = picker.startDate.format('YYYY-MM-DD');
        to   = picker.endDate.format('YYYY-MM-DD');

        // The preset's own words when one was clicked - "Next 7 Days" says more
        // at a glance than the two dates it stands for - and the dates
        // themselves for a range picked off the calendar.
        $input.val(picker.chosenLabel && picker.chosenLabel !== picker.locale.customRangeLabel
          ? picker.chosenLabel
          : picker.startDate.format('DD MMM YYYY') + ' - ' + picker.endDate.format('DD MMM YYYY'));

        $clear.prop('hidden', false);
        table.draw();
      });

      // Without this there is no way back to the whole list but reloading the
      // page, which also loses whatever was typed in the search box.
      $clear.on('click', function () {
        from = null;
        to   = null;

        $input.val('');
        $clear.prop('hidden', true);
        table.draw();
      });

      // ---- the filter itself ----------------------------------------------
      //
      // ext.search is one global list every table on the page runs, so this
      // checks which table it has been handed before deciding anything.
      $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        if (settings.nTable.id !== tableId || from === null) { return true; }

        var row  = settings.aoData[dataIndex];
        var cell = row && row.anCells ? row.anCells[col] : null;
        var date = cell ? cell.getAttribute('data-order') : null;

        // No `data-order` on the cell - a listing that opted in without one -
        // so read the date as it is printed instead.
        if (!/^\d{4}-\d{2}-\d{2}$/.test(date || '')) {
          var shown = moment($.trim(data[col] || ''), ['DD MMM YYYY', 'YYYY-MM-DD', 'DD-MM-YYYY'], true);

          if (!shown.isValid()) { return false; }

          date = shown.format('YYYY-MM-DD');
        }

        // Both ends are YYYY-MM-DD, where comparing the strings and comparing
        // the dates are the same thing.
        return date >= from && date <= to;
      });
    });
  })();
</script>
