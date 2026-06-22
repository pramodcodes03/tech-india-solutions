{{--
    Shared date-range picker for the analytics dashboards.

    Renders a daterangepicker (jQuery + Moment.js, loaded scoped to dashboards
    only) bound to a read-only input. On apply it reloads the current page with
    ?from=YYYY-MM-DD&to=YYYY-MM-DD so the controller can window its period-based
    metrics. Point-in-time metrics (stock, receivables, totals) ignore it.

    Expects $rangeFrom and $rangeTo (Carbon|string Y-m-d) from the controller.
--}}
@php
    $from = $rangeFrom instanceof \Carbon\Carbon ? $rangeFrom->format('Y-m-d') : (string) $rangeFrom;
    $to   = $rangeTo   instanceof \Carbon\Carbon ? $rangeTo->format('Y-m-d')   : (string) $rangeTo;
@endphp

<div class="inline-flex items-center gap-2 bg-white dark:bg-[#0e1726] border border-gray-200 dark:border-[#1b2e4b] rounded-lg px-3 py-2 shadow-sm cursor-pointer">
    <svg class="w-4 h-4 text-primary shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
    <input type="text" id="dashboardDateRange" readonly autocomplete="off"
           class="bg-transparent border-0 p-0 text-sm font-semibold focus:ring-0 outline-none cursor-pointer w-[180px] dark:text-gray-200"
           data-from="{{ $from }}" data-to="{{ $to }}" />
    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
</div>

@once
@push('scripts')
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<style>
    /* ───────────────────────────────────────────────────────────────────
       daterangepicker layout hardening.
       The app's global table styles (.table-striped, td/th padding, widths,
       border-collapse) bleed into the picker's <table class="calendar-table">
       and collapse the two month grids together. These rules reset the
       picker's tables to their intended fixed layout so the two calendars
       render correctly side-by-side.
       ─────────────────────────────────────────────────────────────────── */
    .daterangepicker { font-family: inherit; z-index: 9999; }

    /* Two months side-by-side on wide screens (library default). */
    .daterangepicker.show-calendar .drp-calendar.left,
    .daterangepicker.show-calendar .drp-calendar.right { display: block; float: left; }
    .daterangepicker .drp-calendar { max-width: 270px; }
    .daterangepicker .drp-calendar.left  { padding: 8px 0 8px 8px; }
    .daterangepicker .drp-calendar.right { padding: 8px; }

    /* Reset the calendar table so global td/th styles don't distort it. */
    .daterangepicker .calendar-table table { width: 100%; margin: 0; border-collapse: separate; border-spacing: 0; }
    .daterangepicker .calendar-table th,
    .daterangepicker .calendar-table td {
        width: 32px; height: 30px; min-width: 32px;
        padding: 0; text-align: center; vertical-align: middle;
        font-size: 12px; line-height: 30px; white-space: nowrap;
        border: 1px solid transparent; border-radius: 4px; cursor: pointer;
        background: transparent;
    }
    .daterangepicker .calendar-table .next span,
    .daterangepicker .calendar-table .prev span { border-color: #6b7280; }

    /* Selection + hover states. */
    .daterangepicker td.available:hover,
    .daterangepicker th.available:hover { background: #eef1ff; }
    .daterangepicker td.in-range { background: #e6ebff; border-radius: 0; }
    .daterangepicker td.active,
    .daterangepicker td.active:hover { background: #4361ee !important; color:#fff; border-radius: 4px; }
    .daterangepicker .ranges li.active { background: #4361ee; color:#fff; }
    .daterangepicker td.off { color:#cbd5e1; }

    /* Dark-mode polish. */
    .dark .daterangepicker { background:#0e1726; border-color:#1b2e4b; color:#cbd5e1; }
    .dark .daterangepicker:after { border-bottom-color:#0e1726; }
    .dark .daterangepicker:before { border-bottom-color:#1b2e4b; }
    .dark .daterangepicker .calendar-table { background:#0e1726; border:none; }
    .dark .daterangepicker td.available:hover,
    .dark .daterangepicker th.available:hover { background:#1b2e4b; }
    .dark .daterangepicker td.in-range { background:#1e2a4a; }
    .dark .daterangepicker td.off { background:#0e1726; color:#475569; }
    .dark .daterangepicker .ranges li:hover { background:#1b2e4b; }
    .dark .daterangepicker .drp-buttons { border-top-color:#1b2e4b; }
    .dark .daterangepicker select { background:#1b2e4b; color:#cbd5e1; border-color:#334155; }
</style>
<script src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script>
    (function () {
        function initDashboardRange() {
            if (typeof jQuery === 'undefined' || !jQuery.fn.daterangepicker) { return; }
            var $el = jQuery('#dashboardDateRange');
            if (!$el.length) return;

            var start = moment($el.data('from'), 'YYYY-MM-DD');
            var end   = moment($el.data('to'),   'YYYY-MM-DD');

            $el.daterangepicker({
                startDate: start,
                endDate: end,
                locale: { format: 'DD MMM YYYY' },
                ranges: {
                    'Today':        [moment(), moment()],
                    'Yesterday':    [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days':  [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month':   [moment().startOf('month'), moment().endOf('month')],
                    'Last Month':   [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    'This Year':    [moment().startOf('year'), moment()],
                }
            }, function (s, e) {
                // Reload the page with the chosen window, preserving other params.
                var url = new URL(window.location.href);
                url.searchParams.set('from', s.format('YYYY-MM-DD'));
                url.searchParams.set('to',   e.format('YYYY-MM-DD'));
                window.location.href = url.toString();
            });
        }
        if (document.readyState !== 'loading') initDashboardRange();
        else document.addEventListener('DOMContentLoaded', initDashboardRange);
    })();
</script>
@endpush
@endonce
