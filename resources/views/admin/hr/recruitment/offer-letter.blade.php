<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Offer Letter — {{ $candidate->full_name }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; box-sizing: border-box; }
        body { margin: 0; padding: 40px 46px; color: #1a1a1a; font-size: 12px; line-height: 1.65; }

        .header { text-align: center; border-bottom: 2px solid #1e293b; padding-bottom: 14px; margin-bottom: 22px; }
        .header img { max-height: 64px; max-width: 220px; margin-bottom: 8px; }
        .header .name { font-size: 19px; font-weight: bold; letter-spacing: .3px; }
        .header .addr { color: #475569; font-size: 10.5px; margin-top: 4px; }

        .refrow { display: table; width: 100%; margin-bottom: 16px; }
        .refrow .l { display: table-cell; font-weight: bold; }
        .refrow .r { display: table-cell; text-align: right; color: #334155; }

        .title { text-align: center; font-size: 15px; font-weight: bold; letter-spacing: 1px;
                 margin: 18px 0 18px; text-decoration: underline; }

        p { margin: 0 0 12px; text-align: justify; }

        table.terms { width: 100%; border-collapse: collapse; margin: 14px 0 18px; }
        table.terms td { padding: 8px 12px; border: 1px solid #e2e8f0; vertical-align: top; }
        table.terms td.k { color: #475569; width: 38%; font-weight: bold; background: #f8fafc; }

        .sign { margin-top: 40px; }
        .sign .line { display: inline-block; width: 240px; border-top: 1px solid #475569; margin-top: 40px; }

        .accept { margin-top: 36px; border-top: 1px dashed #cbd5e1; padding-top: 18px; }
        .accept .title2 { font-weight: bold; letter-spacing: .5px; margin-bottom: 10px; }

        .footer { text-align: center; color: #94a3b8; font-size: 9px; margin-top: 34px;
                  border-top: 1px solid #e2e8f0; padding-top: 8px; }
    </style>
</head>
<body>
    @php
        if (! function_exists('offerRupeesInWords')) {
            function offerRupeesInWords($num) {
                $num = (int) round((float) $num);
                if ($num === 0) return 'Zero';
                $ones = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
                $tens = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
                $two = function ($n) use ($ones, $tens) {
                    if ($n < 20) return $ones[$n];
                    return trim($tens[intdiv($n, 10)].' '.$ones[$n % 10]);
                };
                $three = function ($n) use ($ones, $two) {
                    $s = '';
                    if ($n >= 100) { $s .= $ones[intdiv($n, 100)].' Hundred '; $n %= 100; }
                    if ($n > 0) $s .= $two($n);
                    return trim($s);
                };
                $w = '';
                $crore = intdiv($num, 10000000); $num %= 10000000;
                $lakh = intdiv($num, 100000); $num %= 100000;
                $thousand = intdiv($num, 1000); $num %= 1000;
                if ($crore) $w .= $three($crore).' Crore ';
                if ($lakh) $w .= $two($lakh).' Lakh ';
                if ($thousand) $w .= $two($thousand).' Thousand ';
                if ($num) $w .= $three($num);
                return trim($w);
            }
        }

        $company   = $business->name ?? config('app.name');
        $symbol    = $business->currency_symbol ?? '₹';
        $offerDate = optional($candidate->offer_date)->format('d F Y') ?? now()->format('d F Y');
        $acceptBy  = ($candidate->offer_date ? \Carbon\Carbon::parse($candidate->offer_date) : now())
                        ->copy()->addDays(5)->format('d F Y');
        $designation = $candidate->offer_designation ?? $candidate->designation?->name ?? 'the role discussed';

        // Work location: prefer the business city/state, else the candidate's location.
        $workLocation = trim(implode(', ', array_filter([$business->city ?? null, $business->state ?? null])))
                        ?: ($candidate->current_location ?: '—');

        // Embed the business logo (DomPDF needs a local file path).
        $logoPath = null;
        if ($business?->logo) {
            $p = storage_path('app/public/'.$business->logo);
            if (file_exists($p)) $logoPath = $p;
        }
    @endphp

    <div class="header">
        @if($logoPath)
            <img src="{{ $logoPath }}" alt="{{ $company }}" /><br>
        @endif
        <div class="name">{{ $company }}</div>
        <div class="addr">
            {{ trim(implode(', ', array_filter([
                $business->address ?? null, $business->city ?? null, $business->state ?? null,
                ($business->pincode ?? null) ? ('– '.$business->pincode) : null,
            ]))) }}
        </div>
        <div class="addr">
            @if($business->phone)Phone: {{ $business->phone }}@endif
            @if($business->phone && $business->email) | @endif
            @if($business->email)Email: {{ $business->email }}@endif
        </div>
    </div>

    <div class="refrow">
        <div class="l">Ref No.: OFR/{{ $candidate->candidate_code }}</div>
        <div class="r">Date: {{ $offerDate }}</div>
    </div>

    <div class="title">OFFER OF EMPLOYMENT</div>

    <p>Dear {{ $candidate->full_name }},</p>

    <p>
        We are pleased to offer you the position of <strong>{{ $designation }}</strong> with {{ $company }}.
        Based on your qualifications, experience, and interactions during the selection process, we believe that
        you will be a valuable addition to our organization.
    </p>

    <table class="terms">
        <tr>
            <td class="k">Position / Designation</td>
            <td>{{ $designation }}</td>
        </tr>
        <tr>
            <td class="k">Annual CTC</td>
            <td>
                @if($candidate->offer_ctc)
                    {{ $symbol }}{{ number_format($candidate->offer_ctc, 0) }}/-
                    (Rupees {{ offerRupeesInWords($candidate->offer_ctc) }} Only)
                @else
                    As discussed
                @endif
            </td>
        </tr>
        <tr>
            <td class="k">Proposed Date of Joining</td>
            <td>{{ optional($candidate->proposed_joining_date)->format('d F Y') ?? 'To Be Confirmed' }}</td>
        </tr>
        <tr>
            <td class="k">Work Location</td>
            <td>{{ $workLocation }}</td>
        </tr>
    </table>

    <p>
        This offer is subject to successful verification of your credentials, educational qualifications, previous
        employment records, and submission of all required documents.
    </p>

    <p>
        Detailed terms and conditions of employment, including compensation structure, leave policy, benefits,
        working hours, confidentiality obligations, and notice period, will be provided in your Appointment Letter
        upon joining.
    </p>

    <p>
        Kindly confirm your acceptance of this offer by signing and returning a copy of this letter on or before
        <strong>{{ $acceptBy }}</strong>.
    </p>

    <p>
        We look forward to welcoming you to the {{ $company }} family and wish you a successful association with us.
    </p>

    <div class="sign">
        <p style="margin-bottom:0;">Sincerely,</p>
        <p style="margin-top:2px;">For {{ $company }}</p>
        <div class="line"></div><br>
        Authorised Signatory<br>
        Name: ______________________<br>
        Designation: ________________
    </div>

    <div class="accept">
        <div class="title2">ACCEPTANCE BY CANDIDATE</div>
        <p>
            I, <strong>{{ $candidate->full_name }}</strong>, hereby accept the above offer and agree to join the
            organization as per the terms mentioned above.
        </p>
        <div class="line"></div><br>
        {{ $candidate->full_name }}<br>
        Date: ______________
    </div>

    <div class="footer">
        This is a system-generated Offer Letter and does not require a physical signature.
    </div>
</body>
</html>
