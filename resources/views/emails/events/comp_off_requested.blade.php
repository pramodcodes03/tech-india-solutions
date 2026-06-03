@extends('emails.layouts.business')

@section('body')
    <h1>New comp-off request awaiting approval</h1>

    @if($recipientName)
        <p>Hi {{ $recipientName }},</p>
    @else
        <p>Hello,</p>
    @endif

    <p>A team member has submitted a compensatory-off (comp-off) request and is awaiting your approval.</p>

    <table class="meta-table">
        <tr><td class="label">Employee</td><td class="val">{{ $entity?->employee?->first_name ?? '—' }} {{ $entity?->employee?->last_name ?? '' }}</td></tr>
        <tr><td class="label">Worked on</td><td class="val">{{ $entity?->worked_on ? \Carbon\Carbon::parse($entity->worked_on)->format('d M Y') : '—' }}</td></tr>
        <tr><td class="label">Comp-off date</td><td class="val">{{ $entity?->comp_date ? \Carbon\Carbon::parse($entity->comp_date)->format('d M Y') : '—' }}</td></tr>
        <tr><td class="label">Reason</td><td class="val">{{ $entity?->reason ?: '—' }}</td></tr>
    </table>

    <div class="alert alert-info">
        <strong>Action:</strong> Log in to the HR module → Comp-off to approve or reject this request.
    </div>

    <p>Thanks,<br><strong>{{ $business->name }}</strong></p>
@endsection
