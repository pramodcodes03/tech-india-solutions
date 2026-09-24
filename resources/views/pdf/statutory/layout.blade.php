{{--
    The statutory form layout.

    Deliberately NOT pdf/layout.blade.php. A labour register is a prescribed
    form: it carries the form number, the rule it is made under and the
    establishment block — not a company letterhead, logo or brand colour. An
    inspector reads it against the schedule in the rules, so the page is a plain
    black grid and nothing else.

    Sections a form fills:
      @section('form-no')     "Form D", "FORM B", "Form II - A"
      @section('form-rule')   "[Rule 21(4)]" — printed above the title when set
      @section('form-title')  "Register of wages of Employees"
      @section('form-act')    "(Rule 5 of the Punjab Shops … Rules, 1958)"
      @section('form-meta')   the rows under the establishment block (month, …)
      @section('content')     the grid

    Variables the renderer always passes: $establishment, $generatedAt.
    Optional: $repeatHeader — false when each page carries its own header
    (Form C prints one page per employee, so the block is not page-fixed).
--}}
@php
    $repeatHeader = $repeatHeader ?? true;
    // Every prescribed register here is wide enough to need landscape;
    // the flag still comes from the catalogue so the two never disagree.
    $orientation = ($landscape ?? true) ? 'landscape' : 'portrait';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('form-no', 'Statutory Register') — @yield('form-title', '')</title>
    <style>
        @page {
            margin: @yield('page-margin', $repeatHeader ? '116px 18px 34px 18px' : '20px 18px 34px 18px');
            size: A4 @yield('page-orientation', $orientation);
        }
        /* NOT `*` and NOT `body`: DOMPDF implements the @page margin as the
           body element's margin, so resetting either silently collapses the
           page margin to zero — which also drops any position:fixed header or
           footer off the top of the sheet. Reset the elements we actually use
           and leave body alone. */
        div, p, span, table, thead, tbody, tfoot, tr, th, td,
        h1, h2, h3, h4, h5, h6, ul, ol, li, img, figure { margin: 0; padding: 0; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 6.2px; color: #000; line-height: 1.35; }

        /* ── The prescribed heading ──────────────────────────────────── */
        .form-head { text-align: center; }
        .form-no { font-size: 10px; font-weight: bold; letter-spacing: .3px; }
        .form-rule { font-size: 7px; }
        .form-title { font-size: 8.5px; font-weight: bold; margin-top: 1px; }
        .form-act { font-size: 7px; margin-top: 1px; }

        /* ── Establishment block ─────────────────────────────────────── */
        .est { width: 100%; border-collapse: collapse; margin-top: 5px; font-size: 7px; }
        .est td { padding: 1px 2px; vertical-align: top; }
        .est td.k { width: 200px; font-weight: bold; }
        .est td.k:after { content: ''; }
        .est .val { font-weight: bold; }

        .meta { width: 100%; border-collapse: collapse; margin-top: 3px; font-size: 7px; }
        .meta td { padding: 1px 2px; vertical-align: top; }
        .meta td.k { width: 110px; font-weight: bold; }

        .page-head { position: fixed; top: -108px; left: 0; right: 0; }

        /* ── The grid ────────────────────────────────────────────────── */
        table.grid { width: 100%; border-collapse: collapse; margin-top: 5px;
                     table-layout: fixed; }
        table.grid th, table.grid td {
            border: 0.6px solid #000; padding: 1.5px 2px;
            vertical-align: middle; word-wrap: break-word; overflow-wrap: break-word;
        }
        table.grid th { font-weight: bold; text-align: center; font-size: 6px; }
        table.grid td { font-size: 6.2px; }
        table.grid td.c, table.grid th.c { text-align: center; }
        table.grid td.r, table.grid th.r { text-align: right; }
        /* The numbered column-index row every prescribed form carries. */
        table.grid tr.colno td { text-align: center; font-size: 5.6px; }
        table.grid tfoot td { font-weight: bold; }
        table.grid .nil { text-align: center; }

        /* ── Footer ──────────────────────────────────────────────────── */
        .sign-off { margin-top: 8px; text-align: right; font-size: 7px; font-weight: bold; }
        .page-foot { position: fixed; bottom: -26px; left: 0; right: 0; font-size: 5.6px; color: #444; }
        .page-foot table { width: 100%; border-collapse: collapse; }
        .page-foot td.right { text-align: right; }
        /* Only counter(page) is printed: DOMPDF resolves counter(pages) to 0,
           and a wrong total on a statutory register is worse than none.
           A real total needs the canvas page_text() API after render. */
        .pagenum:after { content: counter(page); }

        .break { page-break-after: always; }
        .nowrap { white-space: nowrap; }
    </style>
    @stack('styles')
</head>
<body>

@php
    $headBlock = view('pdf.statutory._head', [
        'establishment' => $establishment,
        'formNo' => trim($__env->yieldContent('form-no')),
        'formRule' => trim($__env->yieldContent('form-rule')),
        'formTitle' => trim($__env->yieldContent('form-title')),
        'formAct' => trim($__env->yieldContent('form-act')),
        'formMeta' => trim($__env->yieldContent('form-meta')),
    ])->render();
@endphp

@if($repeatHeader)
    <div class="page-head">{!! $headBlock !!}</div>
@endif

<div class="page-foot">
    <table>
        <tr>
            <td>{{ $establishment['name'] ?? '' }}@if(! empty($establishment['code'])) · {{ $establishment['code'] }}@endif</td>
            <td class="right">Page <span class="pagenum"></span>
                · generated {{ $generatedAt ?? now()->format('d M Y, h:i A') }}</td>
        </tr>
    </table>
</div>

@yield('content')

@hasSection('signature')
    @yield('signature')
@else
    <div class="sign-off">Signature of Employer / Manager / Contractor / Authorised Person</div>
@endif

</body>
</html>
