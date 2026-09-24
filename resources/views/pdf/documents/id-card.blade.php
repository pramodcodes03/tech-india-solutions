@php
    $photoPath = $employee?->profile_photo
        ? storage_path('app/public/'.ltrim($employee->profile_photo, '/'))
        : null;
    $hasPhoto = $photoPath && is_file($photoPath);
    $logoPath = $business?->logo ? public_path(ltrim($business->logo, '/')) : public_path('assets/images/logo.png');
    $hasLogo = is_file($logoPath);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee ID Card — {{ $employee?->employee_code }}</title>
    <style>
        {{-- The ID card is the one document that does NOT use the shared
             letterhead: it is a 54 × 86 mm card, not a page. Both faces are
             printed on one A4 sheet for cutting. --}}
        @page { margin: 14mm; size: A4 portrait; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #24292f; }
        .sheet-note { font-size: 8px; color: #9099a8; margin-bottom: 10px; }
        .cards { width: 100%; border-collapse: collapse; }
        .cards td { padding: 0 8px 0 0; vertical-align: top; }

        .card { width: 204px; height: 325px; border: 1px solid #d9dee5; }
        .card-head { background: #0f2b5b; color: #fff; padding: 9px 10px; text-align: center; }
        .card-head img { max-height: 26px; max-width: 110px; }
        .card-head .co { font-size: 9px; font-weight: bold; letter-spacing: .3px; margin-top: 3px; }
        .photo { text-align: center; padding: 12px 0 6px; }
        .photo .frame { width: 86px; height: 100px; border: 2px solid #0f2b5b; margin: 0 auto; overflow: hidden; }
        .photo img { width: 82px; height: 96px; }
        .photo .ph { width: 82px; height: 96px; background: #eef1f5; color: #9099a8; font-size: 8px; padding-top: 42px; text-align: center; }
        .card-name { text-align: center; font-size: 12px; font-weight: bold; color: #0f2b5b; padding: 0 8px; }
        .card-role { text-align: center; font-size: 8.5px; color: #6b7280; padding: 1px 8px 8px; }
        .card-rows { padding: 0 10px; }
        .card-rows table { width: 100%; border-collapse: collapse; }
        .card-rows td { font-size: 8px; padding: 2.5px 0; vertical-align: top; }
        .card-rows td.k { color: #8b95a5; width: 62px; }
        .card-rows td.v { font-weight: bold; }
        .card-foot { position: absolute; }
        .strip { background: #0f2b5b; color: #fff; font-size: 7.5px; text-align: center; padding: 4px; margin-top: 8px; }

        .back-body { padding: 12px 12px; font-size: 8px; line-height: 1.6; }
        .back-title { font-size: 9px; font-weight: bold; color: #0f2b5b; text-transform: uppercase;
                      letter-spacing: .4px; margin-bottom: 5px; }
        .back-body ol { padding-left: 13px; margin-bottom: 9px; }
        .back-body li { margin-bottom: 3px; }
        .sig-strip { border-top: 1px solid #24292f; margin-top: 18px; padding-top: 3px; font-size: 7.5px; text-align: center; }
    </style>
</head>
<body>
    <div class="sheet-note">Employee ID card — front and back. Print at 100% and cut along the card border (54 × 86 mm).</div>

    <table class="cards">
        <tr>
            {{-- ── Front ── --}}
            <td>
                <div class="card">
                    <div class="card-head">
                        @if($hasLogo)<img src="{{ $logoPath }}" alt="" />@endif
                        <div class="co">{{ $business?->name ?: 'Company' }}</div>
                    </div>

                    <div class="photo">
                        <div class="frame">
                            @if($hasPhoto)
                                <img src="{{ $photoPath }}" alt="" />
                            @else
                                <div class="ph">PHOTO</div>
                            @endif
                        </div>
                    </div>

                    <div class="card-name">{{ $employee?->full_name ?? '—' }}</div>
                    <div class="card-role">{{ $employee?->designation?->name ?? '—' }}</div>

                    <div class="card-rows">
                        <table>
                            <tr><td class="k">Emp ID</td><td class="v">{{ $employee?->employee_code ?? '—' }}</td></tr>
                            <tr><td class="k">Department</td><td class="v">{{ $employee?->department?->name ?? '—' }}</td></tr>
                            <tr><td class="k">Blood group</td><td class="v">{{ $employee?->blood_group ?: '—' }}</td></tr>
                            <tr><td class="k">Joined</td><td class="v">{{ optional($employee?->joining_date)->format('d M Y') ?? '—' }}</td></tr>
                        </table>
                    </div>

                    <div class="strip">{{ $business?->phone ?: '' }}</div>
                </div>
            </td>

            {{-- ── Back ── --}}
            <td>
                <div class="card">
                    <div class="card-head">
                        <div class="co">{{ $business?->legal_name ?: ($business?->name ?: 'Company') }}</div>
                    </div>

                    <div class="back-body">
                        <div class="back-title">If found, please return to</div>
                        <div style="margin-bottom:9px;">
                            {{ collect([$business?->address, $business?->city, $business?->state, $business?->pincode])->filter()->implode(', ') ?: '—' }}
                            @if($business?->phone)<br>{{ $business->phone }}@endif
                            @if($business?->email)<br>{{ $business->email }}@endif
                        </div>

                        <div class="back-title">Emergency contact</div>
                        <div style="margin-bottom:9px;">
                            {{ $employee?->emergency_contact_name ?: '—' }}
                            @if($employee?->emergency_contact_phone)<br>{{ $employee->emergency_contact_phone }}@endif
                            @if($employee?->emergency_contact_relation)<br>({{ $employee->emergency_contact_relation }})@endif
                        </div>

                        <div class="back-title">Conditions</div>
                        <ol>
                            <li>This card is the property of the Company.</li>
                            <li>It must be worn at all times on the premises.</li>
                            <li>It is not transferable.</li>
                            <li>Loss must be reported to HR immediately.</li>
                            <li>Return it on separation from service.</li>
                        </ol>

                        <div class="sig-strip">Authorised Signatory</div>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
