<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Offer Letter — {{ $candidate->full_name }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; box-sizing: border-box; }
        body { margin: 0; padding: 40px; color: #0f172a; font-size: 12px; line-height: 1.7; }
        .header { border-bottom: 2px solid #1e293b; padding-bottom: 12px; margin-bottom: 24px; }
        .header h1 { margin: 0; font-size: 22px; }
        .header .meta { color: #64748b; font-size: 11px; margin-top: 4px; }
        h2 { font-size: 15px; margin: 24px 0 10px; }
        .ref { display: table; width: 100%; margin-bottom: 18px; }
        .ref .l { display: table-cell; }
        .ref .r { display: table-cell; text-align: right; color: #64748b; }
        table.terms { width: 100%; border-collapse: collapse; margin: 14px 0; }
        table.terms td { padding: 7px 10px; border-bottom: 1px solid #e2e8f0; }
        table.terms td.k { color: #64748b; width: 40%; }
        .sign { margin-top: 48px; display: table; width: 100%; }
        .sign .col { display: table-cell; width: 50%; vertical-align: bottom; }
        .sign .col.r { text-align: right; }
        .footer { text-align: center; color: #94a3b8; font-size: 9px; margin-top: 40px; border-top: 1px solid #e2e8f0; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $business->name ?? config('app.name') }}</h1>
        <div class="meta">
            {{ $business->address ?? '' }}{{ $business->city ? ', '.$business->city : '' }}{{ $business->state ? ', '.$business->state : '' }}
            @if($business->phone) · {{ $business->phone }}@endif
            @if($business->email) · {{ $business->email }}@endif
        </div>
    </div>

    <div class="ref">
        <div class="l"><strong>Ref:</strong> OFR/{{ $candidate->candidate_code }}</div>
        <div class="r">Date: {{ optional($candidate->offer_date)->format('d M Y') ?? now()->format('d M Y') }}</div>
    </div>

    <p>
        Dear {{ $candidate->full_name }},
    </p>

    <h2>Offer of Employment</h2>

    <p>
        We are pleased to offer you the position of
        <strong>{{ $candidate->offer_designation ?? $candidate->designation?->name ?? 'the role discussed' }}</strong>
        at {{ $business->name ?? config('app.name') }}. We were impressed with your background and are confident
        you will be a valuable addition to our team.
    </p>

    <table class="terms">
        <tr><td class="k">Position / Designation</td><td>{{ $candidate->offer_designation ?? $candidate->designation?->name ?? '—' }}</td></tr>
        @if($candidate->department)<tr><td class="k">Department</td><td>{{ $candidate->department->name }}</td></tr>@endif
        <tr><td class="k">Annual CTC</td><td>{{ $candidate->offer_ctc ? ($business->currency_symbol ?? '₹').' '.number_format($candidate->offer_ctc, 2) : 'As discussed' }}</td></tr>
        <tr><td class="k">Proposed Date of Joining</td><td>{{ optional($candidate->proposed_joining_date)->format('d M Y') ?? 'To be confirmed' }}</td></tr>
    </table>

    <p>
        This offer is contingent upon successful completion of background verification and submission of the
        required documents. Detailed terms of employment, including leave, benefits and notice period, will be
        shared in your appointment letter on joining.
    </p>

    <p>
        We request you to confirm your acceptance by signing and returning a copy of this letter. We look forward
        to welcoming you on board.
    </p>

    <div class="sign">
        <div class="col">
            <p>For {{ $business->name ?? config('app.name') }}</p>
            <br><br>
            ___________________________<br>
            Authorised Signatory
        </div>
        <div class="col r">
            <p>Accepted by Candidate</p>
            <br><br>
            ___________________________<br>
            {{ $candidate->full_name }}
        </div>
    </div>

    <div class="footer">
        This is a system-generated offer letter from {{ $business->name ?? config('app.name') }}.
    </div>
</body>
</html>
