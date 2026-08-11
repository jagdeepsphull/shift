/* ==========================================================================
   PickAShift front-end behaviour.

   Plain ES5, no jQuery dependency of its own, and safe to load on any page -
   every block checks that its markup is present before binding.
   ========================================================================== */
(function () {
  'use strict';

  /* -------------------------------------------------- sticky navigation --- */

  var nav = document.getElementById('wz-nav');

  if (nav) {
    var setStuck = function () {
      nav.classList.toggle('wz-stuck', window.scrollY > 12);
    };

    setStuck();
    window.addEventListener('scroll', setStuck, { passive: true });
  }

  /* ------------------------------------------------------- back to top ---- */

  var toTop = document.querySelector('.back-to-top');

  if (toTop) {
    var setVisible = function () {
      toTop.style.display = window.scrollY > 400 ? 'block' : 'none';
    };

    setVisible();
    window.addEventListener('scroll', setVisible, { passive: true });

    toTop.addEventListener('click', function (event) {
      event.preventDefault();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  /* ------------------------------------------ Bootstrap 5 opt-in widgets -- */

  // Bootstrap 5 no longer auto-initialises these; they must be constructed.
  if (window.bootstrap) {
    Array.prototype.forEach.call(
      document.querySelectorAll('[data-bs-toggle="tooltip"]'),
      function (el) { new window.bootstrap.Tooltip(el); }
    );

    Array.prototype.forEach.call(
      document.querySelectorAll('[data-bs-toggle="popover"]'),
      function (el) { new window.bootstrap.Popover(el, { trigger: 'focus', container: 'body' }); }
    );
  }

  /* ------------------------------------------------------- the job list --- */

  var list = document.getElementById('wz-jobs');

  if (!list) {
    return;
  }

  var cards = Array.prototype.slice.call(list.querySelectorAll('.wz-job'));
  var search = document.getElementById('wz-job-search');
  var dates = document.getElementById('wz-job-dates');
  var datesClear = document.getElementById('wz-job-dates-clear');
  var typeSelect = document.getElementById('wz-job-type');
  var tabs = Array.prototype.slice.call(document.querySelectorAll('.wz-tab'));
  var moreBtn = document.getElementById('wz-load-more');
  var empty = document.getElementById('wz-jobs-empty');
  var countEl = document.getElementById('wz-job-count');

  var PAGE = 8;
  var shown = PAGE;
  var filter = 'all';

  // The chosen shift-date range, as Y-m-d, which is what data-date carries too:
  // both sides being ISO means a plain string compare is a date compare.
  var from = '';
  var to = '';

  var matches = function (card) {
    var term = (search && search.value || '').trim().toLowerCase();
    var type = (typeSelect && typeSelect.value) || '';

    if (term && card.getAttribute('data-search').indexOf(term) === -1) {
      return false;
    }

    if (from || to) {
      // Empty when the server could not read the shift date - such a card
      // cannot be said to fall inside the range, so it drops out.
      var day = card.getAttribute('data-date') || '';

      if (!day || (from && day < from) || (to && day > to)) {
        return false;
      }
    }

    if (type && card.getAttribute('data-type') !== type) {
      return false;
    }

    if (filter === 'recent') {
      return card.getAttribute('data-recent') === '1';
    }

    if (filter === 'upcoming') {
      return card.getAttribute('data-upcoming') === '1';
    }

    return true;
  };

  var render = function () {
    var visible = 0;

    cards.forEach(function (card) {
      if (!matches(card)) {
        card.hidden = true;
        return;
      }

      visible++;
      card.hidden = visible > shown;
    });

    var total = cards.filter(matches).length;

    if (empty) {
      empty.hidden = total !== 0;
    }

    if (countEl) {
      countEl.textContent = total === 1 ? '1 shift' : total + ' shifts';
    }

    if (moreBtn) {
      moreBtn.hidden = total <= shown;
    }
  };

  if (search) {
    search.addEventListener('input', function () {
      shown = PAGE;
      render();
    });
  }

  // The hero search sits above the list; keep Enter on the page.
  var searchForm = (search || dates) ? (search || dates).closest('form') : null;

  if (searchForm) {
    searchForm.addEventListener('submit', function (event) {
      event.preventDefault();
      shown = PAGE;
      render();
      var anchor = document.getElementById('browsejobs');
      if (anchor) { anchor.scrollIntoView({ behavior: 'smooth' }); }
    });
  }

  /* --------------------------------------------- the shift-date range --- */

  // daterangepicker (jQuery + moment) is loaded in front/footer.php. Without
  // it the field simply stays an empty text box and the list is unfiltered.
  if (dates && window.jQuery && window.jQuery.fn && window.jQuery.fn.daterangepicker) {
    var moment = window.moment;
    var $dates = window.jQuery(dates);

    // Picked, not typed: the value is written back by the picker below.
    dates.readOnly = true;

    // The clear cross only earns its place once a range has been picked.
    var showClear = function () {
      if (datesClear) {
        datesClear.hidden = from === '' && to === '';
      }
    };

    $dates.daterangepicker({
      autoApply: true,
      autoUpdateInput: false, // leave the placeholder showing until a range is picked
      alwaysShowCalendars: true,
      opens: 'center',
      drops: 'down',
      locale: {
        format: 'DD MMM YYYY',
        firstDay: 1
      },
      ranges: {
        'Today': [moment(), moment()],
        'Tomorrow': [moment().add(1, 'days'), moment().add(1, 'days')],
        'Next 7 Days': [moment(), moment().add(6, 'days')],
        'Next 30 Days': [moment(), moment().add(29, 'days')],
        'This Month': [moment().startOf('month'), moment().endOf('month')],
        'Next Month': [
          moment().add(1, 'month').startOf('month'),
          moment().add(1, 'month').endOf('month')
        ]
      }
    });

    $dates.on('apply.daterangepicker', function (event, picker) {
      var start = picker.startDate.format('DD MMM YYYY');
      var end = picker.endDate.format('DD MMM YYYY');

      from = picker.startDate.format('YYYY-MM-DD');
      to = picker.endDate.format('YYYY-MM-DD');

      dates.value = start === end ? start : start + ' - ' + end;

      showClear();
      shown = PAGE;
      render();
    });

    if (datesClear) {
      datesClear.addEventListener('click', function () {
        from = '';
        to = '';
        dates.value = '';

        showClear();
        shown = PAGE;
        render();
      });
    }

    showClear();
  }

  if (typeSelect) {
    typeSelect.addEventListener('change', function () {
      shown = PAGE;
      render();
    });
  }

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      tabs.forEach(function (t) { t.classList.remove('is-active'); });
      tab.classList.add('is-active');
      filter = tab.getAttribute('data-filter') || 'all';
      shown = PAGE;
      render();
    });
  });

  if (moreBtn) {
    moreBtn.addEventListener('click', function () {
      shown += PAGE;
      render();
    });
  }

  render();
})();
