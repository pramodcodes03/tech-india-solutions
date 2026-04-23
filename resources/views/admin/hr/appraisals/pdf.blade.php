<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Appraisal Letter — {{ $appraisal->appraisal_code }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11pt; color: #1f2937; margin: 0; padding: 32px 40px; }
        .header { border-bottom: 3px solid #3b82f6; padding-bottom: 16px; margin-bottom: 24px; }
        .brand { font-size: 20pt; font-weight: 800; color: #1e40af; letter-spacing: -0.02em; }
        .sub { font-size: 9pt; color: #6b7280; letter-spacing: 0.1em; text-transform: uppercase; }
        .title-row { display: table; width: 100%; margin-bottom: 16px; }
        .title-row .left { display: table-cell; vertical-align: top; width: 70%; }
        .title-row .right { display: table-cell; vertical-align: top; text-align: right; }
        h1 { font-size: 18pt; margin: 0 0 4px; color: #111827; letter-spacing: -0.02em; }
        h2 { font-size: 13pt; margin: 0 0 8px; color: #374151; }
        h3 { font-size: 10pt; margin: 14px 0 6px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.1em; }
        .meta { font-size: 9pt; color: #6b7280; }
        .code-pill { display: inline-block; background: #eff6ff; color: #1e40af; padding: 4px 10px; border-radius: 12px; font-weight: 700; font-size: 9pt; }
        .employee-card { background: #f9fafb; border-left: 4px solid #3b82f6; padding: 14px 16px; margin-bottom: 20px; border-radius: 4px; }
        .employee-card .name { font-size: 14pt; font-weight: 800; color: #111827; }
        .employee-card .role { color: #6b7280; font-size: 10pt; }
        table { width: 100%; border-collapse: collapse; margin: 8px 0 16px; font-size: 10pt; }
        th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #e5e7eb; }
        th { background: #f3f4f6; color: #374151; font-weight: 700; text-transform: uppercase; font-size: 8pt; letter-spacing: 0.05em; }
        .score-row { display: table; width: 100%; margin: 12px 0; }
        .score-cell { display: table-cell; width: 16.66%; text-align: center; padding: 10px 6px; border: 1px solid #e5e7eb; background: #f9fafb; }
        .score-label { font-size: 8pt; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; }
        .score-value { font-size: 15pt; font-weight: 800; color: #111827; margin-top: 4px; }
        .overall-box { background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); color: #fff; padding: 24px; border-radius: 8px; text-align: center; margin: 20px 0; }
        .overall-box .label { font-size: 9pt; text-transform: uppercase; letter-spacing: 0.1em; opacity: 0.8; }
        .overall-box .value { font-size: 44pt; font-weight: 900; line-height: 1; margin: 8px 0; }
        .overall-box .rating { font-size: 14pt; font-weight: 700; }
        .hike-box { background: #ecfdf5; border: 1px solid #10b981; border-radius: 8px; padding: 16px; margin: 14px 0; }
        .hike-box .label { font-size: 9pt; color: #047857; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700; }
        .hike-box .value { font-size: 22pt; font-weight: 800; color: #047857; }
        .hike-box .sub { font-size: 9pt; color: #065f46; margin-top: 4px; }
        .section { margin: 16px 0; padding: 12px 14px; border-radius: 6px; }
        .section.strength { background: #ecfdf5; border-left: 3px solid #10b981; }
        .section.improve { background: #fffbeb; border-left: 3px solid #f59e0b; }
        .section.comment { background: #eff6ff; border-left: 3px solid #3b82f6; }
        .section .h { font-size: 9pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #374151; margin-bottom: 6px; }
        .section p { margin: 0; white-space: pre-wrap; }
        .foot { margin-top: 40px; padding-top: 16px; border-top: 1px solid #e5e7eb; font-size: 9pt; color: #6b7280; }
        .sign-row { display: table; width: 100%; margin-top: 50px; }
        .sign-cell { display: table-cell; width: 50%; padding-right: 20px; }
        .sign-line { border-top: 1px solid #9ca3af; margin-top: 40px; padding-top: 6px; font-size: 9pt; color: #6b7280; }
        .muted { color: #6b7280; }
        .goals-table td { font-size: 9.5pt; }
        .goals-table .score-cell-small { text-align: center; font-weight: 700; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title-row">
            <div class="left">
                <div class="brand">{{ config('app.name', 'Tech India Solutions') }}</div>
                <div class="sub">Performance Appraisal Letter</div>
            </div>
            <div class="right">
                <div class="code-pill">{{ $appraisal->appraisal_code }}</div>
                <div class="meta" style="margin-top:6px">Generated {{ now()->format('d M Y') }}</div>
            </div>
        </div>
    </div>

    <div class="employee-card">
        <div class="name">{{ $appraisal->employee->full_name }} <span class="muted" style="font-size:10pt">({{ $appraisal->employee->employee_code }})</span></div>
        <div class="role">{{ $appraisal->employee->designation?->name ?? '—' }} · {{ $appraisal->employee->department?->name ?? '—' }}</div>
        <div class="role" style="margin-top:4px">Joined: {{ $appraisal->employee->joining_date?->format('d M Y') ?? '—' }}</div>
    </div>

    <div class="title-row">
        <div class="left">
            <h2>Review Period</h2>
            <div>{{ $appraisal->period_start->format('d F Y') }} – {{ $appraisal->period_end->format('d F Y') }}</div>
        </div>
        <div class="right">
            <h2>Rating</h2>
            <div style="font-size:14pt; font-weight:800; color:#1e40af">{{ $appraisal->rating ?? '—' }}</div>
        </div>
    </div>

    <div class="overall-box">
        <div class="label">Overall Performance Score</div>
        <div class="value">{{ number_format($appraisal->overall_score, 1) }}</div>
        <div class="rating">{{ $appraisal->rating ?? '—' }}</div>
    </div>

    <h3>Score Breakdown</h3>
    <div class="score-row">
        <div class="score-cell" style="width: 20%">
            <div class="score-label">Performance</div>
            <div class="score-value">{{ number_format($appraisal->performance_score, 1) }}</div>
        </div>
        <div class="score-cell" style="width: 20%">
            <div class="score-label">Attendance</div>
            <div class="score-value">{{ number_format($appraisal->attendance_score, 1) }}</div>
        </div>
        <div class="score-cell" style="width: 20%">
            <div class="score-label">Leave</div>
            <div class="score-value">{{ number_format($appraisal->leave_score, 1) }}</div>
        </div>
        <div class="score-cell" style="width: 20%">
            <div class="score-label">Penalty</div>
            <div class="score-value">{{ number_format($appraisal->penalty_score, 1) }}</div>
        </div>
        <div class="score-cell" style="width: 20%">
            <div class="score-label">Warning</div>
            <div class="score-value">{{ number_format($appraisal->warning_score, 1) }}</div>
        </div>
    </div>

    @if($appraisal->strengths)
        <div class="section strength">
            <div class="h">Strengths</div>
            <p>{{ $appraisal->strengths }}</p>
        </div>
    @endif

    @if($appraisal->improvement_areas)
        <div class="section improve">
            <div class="h">Areas for Improvement</div>
            <p>{{ $appraisal->improvement_areas }}</p>
        </div>
    @endif

    @if($appraisal->manager_comments)
        <div class="section comment">
            <div class="h">Manager's Comments</div>
            <p>{{ $appraisal->manager_comments }}</p>
        </div>
    @endif

    @if($appraisal->recommended_hike_percent || $appraisal->new_ctc_annual)
        <div class="hike-box">
            <div class="label">Compensation Revision</div>
            <div class="value">{{ number_format($appraisal->recommended_hike_percent ?? 0, 1) }}%</div>
            @if($appraisal->new_ctc_annual)
                <div class="sub">
                    New Annual CTC: <strong>₹{{ number_format($appraisal->new_ctc_annual, 2) }}</strong>
                    @if($appraisal->current_ctc)
                        (from ₹{{ number_format($appraisal->current_ctc, 2) }})
                    @endif
                </div>
                @if($appraisal->effective_from)
                    <div class="sub">Effective from: <strong>{{ $appraisal->effective_from->format('d F Y') }}</strong></div>
                @endif
            @endif
        </div>
    @endif

    <div class="sign-row">
        <div class="sign-cell">
            <div class="sign-line">Employee Signature<br><strong style="color:#111827">{{ $appraisal->employee->full_name }}</strong></div>
        </div>
        <div class="sign-cell">
            <div class="sign-line">HR / Authorised Signatory</div>
        </div>
    </div>

    <div class="foot">
        This is a system-generated appraisal letter. Please contact HR for any clarifications.
    </div>
</body>
</html>
