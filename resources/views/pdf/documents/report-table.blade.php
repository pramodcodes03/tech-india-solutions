{{-- The shared register template.

     Fourteen of the forty-two documents are "a table with a summary strip" and
     differ only in the rows DocumentDataResolver builds for them. Giving them
     one template means a column added to a report is a resolver change, not a
     new Blade file. --}}
@extends('pdf.layout')

@section('title', $documentName)
@section('doc-title', $documentName)
@if(! empty($period))
    @section('doc-sub', $period)
@endif

@section('doc-meta')
    <div class="row"><span class="label">Generated</span> <span class="val">{{ $generatedAt }}</span></div>
    <div class="row"><span class="label">Rows</span> <span class="val">{{ count($rows) }}</span></div>
@endsection

@section('content')
    @if(! empty($summary))
        <table class="data" style="margin-bottom:10px">
            <tr>
                @foreach($summary as $label => $value)
                    <td style="background:#f0f3f8; border:1px solid #e6e9ee; padding:7px 9px;">
                        <div style="font-size:7.5px; text-transform:uppercase; letter-spacing:.5px; color:#6b7280;">{{ $label }}</div>
                        <div style="font-size:11px; font-weight:bold; color:#0f2b5b; margin-top:1px;">{{ $value }}</div>
                    </td>
                @endforeach
            </tr>
        </table>
    @endif

    <table class="data">
        <thead>
            <tr>@foreach($headings as $heading)<th>{{ $heading }}</th>@endforeach</tr>
        </thead>
        <tbody>
            @forelse($rows as $i => $row)
                <tr @class(['alt' => $i % 2 === 1])>
                    @foreach($row as $cell)
                        {{-- Right-align anything that reads as a number, so
                             money and counts line up down the column. --}}
                        <td @class(['tr' => is_numeric(str_replace([',', '%', '₹'], '', (string) $cell))])>
                            {{ ($cell === null || $cell === '') ? '—' : $cell }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ max(1, count($headings)) }}" class="empty">No records for this selection.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection

@section('signature')@endsection
