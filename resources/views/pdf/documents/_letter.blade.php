{{-- Shared frame for the nine HR letters: reference block, addressee, subject
     line, then the body each letter fills in. --}}
@extends('pdf.layout')

@section('title', $documentName)
@section('doc-title', $letterTitle ?? $documentName)

@section('doc-meta')
    <div class="row"><span class="label">Ref</span> <span class="val">{{ $reference ?? '—' }}</span></div>
    <div class="row"><span class="label">Date</span> <span class="val">{{ ($letterDate ?? now())->format('d M Y') }}</span></div>
@endsection

@section('content')
    <div style="margin-bottom:14px; font-size:10px;">
        <div style="font-weight:bold; color:#24292f;">{{ $employee?->full_name ?? '—' }}</div>
        <div class="muted">{{ $employee?->designation?->name ?? '' }}{{ $employee?->department?->name ? ' · '.$employee->department->name : '' }}</div>
        <div class="muted">Employee ID: {{ $employee?->employee_code ?? '—' }}</div>
        @if($employee?->current_address)
            <div class="muted" style="margin-top:3px; max-width:300px;">{{ $employee->current_address }}</div>
        @endif
    </div>

    @if(! empty($subjectLine))
        <div style="margin-bottom:12px; font-size:10.5px;">
            <strong>Subject: {{ $subjectLine }}</strong>
        </div>
    @endif

    <div class="letter-body">
        @yield('letter-body')
    </div>
@endsection
