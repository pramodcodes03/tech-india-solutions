@php
    use App\Support\AmountInWords;
    $letterTitle = 'Salary Certificate';
    $reference = 'SAL/'.($employee?->employee_code ?? '—');
    $subjectLine = null;
@endphp
@extends('pdf.documents._letter')

@section('letter-body')
    <p style="text-align:center; font-weight:bold; letter-spacing:1px; margin-bottom:14px;">TO WHOMSOEVER IT MAY CONCERN</p>

    <p>
        This is to certify that <strong>{{ $employee?->full_name ?? '—' }}</strong>
        (Employee ID <strong>{{ $employee?->employee_code ?? '—' }}</strong>) is employed with
        {{ $business?->legal_name ?: ($business?->name ?: 'the Company') }} as
        <strong>{{ $employee?->designation?->name ?? '—' }}</strong> in the
        <strong>{{ $employee?->department?->name ?? '—' }}</strong> department since
        <strong>{{ optional($employee?->joining_date)->format('d F Y') ?? '—' }}</strong>.
    </p>

    @if($salary)
        <p>
            {{ $employee?->gender === 'female' ? 'Her' : 'His' }} present annual cost to company is
            <strong>₹{{ number_format((float) $salary->ctc_annual, 2) }}</strong>
            — {{ AmountInWords::currency($salary->ctc_annual) }}
        </p>
    @endif

    <p>This certificate is issued at the request of the employee.</p>
@endsection

@section('signature')
    {{-- The CTC annexure sits between the letter and the signature: the
         certificate is only meaningful with the break-up attached. --}}
    @if($salary)
        <div style="margin-top:16px;">
            <div class="panel-title" style="margin-bottom:6px;">Annexure — Cost to Company break-up</div>
            <table class="data">
                <thead><tr><th>Component</th><th class="tr">Monthly (₹)</th><th class="tr">Annual (₹)</th></tr></thead>
                <tbody>
                    @php
                        $components = [
                            'Basic' => $salary->basic,
                            'House Rent Allowance' => $salary->hra,
                            'Conveyance Allowance' => $salary->conveyance,
                            'Medical Allowance' => $salary->medical,
                            'Special Allowance' => $salary->special,
                            'Other Allowance' => $salary->other_allowance ?? null,
                        ];
                        $rowIndex = 0;
                    @endphp
                    @foreach($components as $label => $value)
                        @continue($value === null)
                        <tr @class(['alt' => $rowIndex++ % 2 === 1])>
                            <td>{{ $label }}</td>
                            <td class="tr">{{ number_format((float) $value, 2) }}</td>
                            <td class="tr">{{ number_format((float) $value * 12, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td>Gross</td>
                        <td class="tr">{{ number_format((float) $salary->gross_monthly, 2) }}</td>
                        <td class="tr">{{ number_format((float) $salary->gross_monthly * 12, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Cost to Company</td>
                        <td class="tr">{{ number_format((float) $salary->ctc_annual / 12, 2) }}</td>
                        <td class="tr">{{ number_format((float) $salary->ctc_annual, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
            <div class="words mt">
                <span class="lbl">CTC in words:</span> <strong>{{ AmountInWords::currency($salary->ctc_annual) }}</strong>
            </div>
        </div>
    @else
        <div class="empty mt">No salary structure on record for this employee.</div>
    @endif

    @include('pdf._signature', [
        'business' => $business,
        'hasSignature' => $business?->signature_path && is_file(storage_path('app/public/'.$business->signature_path)),
        'signaturePath' => $business?->signature_path ? storage_path('app/public/'.$business->signature_path) : null,
        'hasSeal' => $business?->seal_path && is_file(storage_path('app/public/'.$business->seal_path)),
        'sealPath' => $business?->seal_path ? storage_path('app/public/'.$business->seal_path) : null,
    ])
@endsection
