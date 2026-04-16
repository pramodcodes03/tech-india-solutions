<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Quotation - {{ $quotation->quotation_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; line-height: 1.5; }
        .container { padding: 30px; }
        .header { display: table; width: 100%; margin-bottom: 30px; }
        .header-left { display: table-cell; width: 60%; vertical-align: top; }
        .header-right { display: table-cell; width: 40%; vertical-align: top; text-align: right; }
        .company-name { font-size: 22px; font-weight: bold; color: #1a1a2e; margin-bottom: 5px; }
        .company-details { font-size: 11px; color: #666; line-height: 1.6; }
        .doc-title { font-size: 24px; font-weight: bold; color: #1a1a2e; margin-bottom: 10px; }
        .doc-meta { font-size: 11px; color: #555; }
        .doc-meta table { margin-left: auto; }
        .doc-meta td { padding: 2px 0; }
        .doc-meta td:first-child { font-weight: bold; padding-right: 15px; text-align: right; }
        .divider { border-top: 2px solid #1a1a2e; margin: 20px 0; }
        .client-section { display: table; width: 100%; margin-bottom: 25px; }
        .client-box { display: table-cell; width: 50%; vertical-align: top; }
        .section-label { font-size: 10px; font-weight: bold; text-transform: uppercase; color: #999; letter-spacing: 1px; margin-bottom: 5px; }
        .client-name { font-weight: bold; font-size: 14px; margin-bottom: 3px; }
        .client-details { font-size: 11px; color: #555; line-height: 1.6; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        .items-table th { background-color: #1a1a2e; color: #fff; padding: 10px 8px; text-align: left; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .items-table th.text-right, .items-table td.text-right { text-align: right; }
        .items-table th.text-center, .items-table td.text-center { text-align: center; }
        .items-table td { padding: 10px 8px; border-bottom: 1px solid #e0e0e0; font-size: 11px; }
        .items-table tbody tr:nth-child(even) { background-color: #f9f9f9; }
        .totals-section { display: table; width: 100%; margin-bottom: 25px; }
        .totals-spacer { display: table-cell; width: 55%; }
        .totals-box { display: table-cell; width: 45%; }
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 6px 10px; font-size: 11px; }
        .totals-table td:first-child { text-align: right; color: #666; }
        .totals-table td:last-child { text-align: right; font-weight: bold; }
        .totals-table .grand-total td { border-top: 2px solid #1a1a2e; font-size: 14px; color: #1a1a2e; padding-top: 10px; }
        .terms-section { margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0; }
        .terms-title { font-size: 12px; font-weight: bold; color: #1a1a2e; margin-bottom: 8px; }
        .terms-text { font-size: 10px; color: #666; line-height: 1.6; white-space: pre-line; }
        .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #e0e0e0; padding-top: 15px; }
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 3px; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-draft { background-color: #e0e0e0; color: #333; }
        .status-sent { background-color: #d1ecf1; color: #0c5460; }
        .status-accepted { background-color: #d4edda; color: #155724; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }
        .status-expired { background-color: #fff3cd; color: #856404; }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <div class="header-left">
                <div class="company-name">{{ $settings['company_name'] ?? 'Leather Technics' }}</div>
                <div class="company-details">
                    @if(!empty($settings['company_address'])){{ $settings['company_address'] }}<br>@endif
                    @if(!empty($settings['company_phone']))Phone: {{ $settings['company_phone'] }}<br>@endif
                    @if(!empty($settings['company_email']))Email: {{ $settings['company_email'] }}<br>@endif
                    @if(!empty($settings['company_gstin']))GSTIN: {{ $settings['company_gstin'] }}@endif
                </div>
            </div>
            <div class="header-right">
                <div class="doc-title">QUOTATION</div>
                <div class="doc-meta">
                    <table>
                        <tr>
                            <td>Quotation #</td>
                            <td>{{ $quotation->quotation_number }}</td>
                        </tr>
                        <tr>
                            <td>Date</td>
                            <td>{{ $quotation->quotation_date }}</td>
                        </tr>
                        <tr>
                            <td>Valid Until</td>
                            <td>{{ $quotation->valid_until ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td>
                                <span class="status-badge status-{{ $quotation->status }}">{{ ucfirst($quotation->status) }}</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="divider"></div>

        {{-- Client Info --}}
        <div class="client-section">
            <div class="client-box">
                <div class="section-label">Bill To</div>
                <div class="client-name">{{ $quotation->customer->name ?? '-' }}</div>
                <div class="client-details">
                    @if($quotation->customer->address ?? false){{ $quotation->customer->address }}<br>@endif
                    @if($quotation->customer->city ?? false){{ $quotation->customer->city->name ?? '' }}@endif
                    @if($quotation->customer->state ?? false), {{ $quotation->customer->state }}@endif
                    @if($quotation->customer->pincode ?? false) - {{ $quotation->customer->pincode }}@endif
                    @if($quotation->customer->phone ?? false)<br>Phone: {{ $quotation->customer->phone }}@endif
                    @if($quotation->customer->email ?? false)<br>Email: {{ $quotation->customer->email }}@endif
                    @if($quotation->customer->gstin ?? false)<br>GSTIN: {{ $quotation->customer->gstin }}@endif
                </div>
            </div>
        </div>

        {{-- Items Table --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 30px;" class="text-center">#</th>
                    <th>Description</th>
                    <th style="width: 70px;">HSN</th>
                    <th style="width: 50px;" class="text-right">Qty</th>
                    <th style="width: 40px;" class="text-center">Unit</th>
                    <th style="width: 80px;" class="text-right">Rate</th>
                    <th style="width: 50px;" class="text-right">Disc%</th>
                    <th style="width: 50px;" class="text-right">Tax%</th>
                    <th style="width: 90px;" class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quotation->items as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $item->description }}</td>
                        <td>{{ $item->hsn_code ?? '-' }}</td>
                        <td class="text-right">{{ $item->quantity }}</td>
                        <td class="text-center">{{ ucfirst($item->unit) }}</td>
                        <td class="text-right">{{ number_format($item->rate, 2) }}</td>
                        <td class="text-right">{{ $item->discount_percent ?? 0 }}%</td>
                        <td class="text-right">{{ $item->tax_percent ?? 0 }}%</td>
                        <td class="text-right">{{ number_format($item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals --}}
        <div class="totals-section">
            <div class="totals-spacer"></div>
            <div class="totals-box">
                <table class="totals-table">
                    <tr>
                        <td>Subtotal</td>
                        <td>{{ number_format($quotation->subtotal, 2) }}</td>
                    </tr>
                    @if($quotation->discount_value > 0)
                        <tr>
                            <td>
                                Discount
                                @if($quotation->discount_type === 'percent')({{ $quotation->discount_value }}%)@endif
                            </td>
                            <td>- {{ number_format($quotation->discount_type === 'percent' ? ($quotation->subtotal * $quotation->discount_value / 100) : $quotation->discount_value, 2) }}</td>
                        </tr>
                    @endif
                    @if($quotation->tax_amount > 0)
                        <tr>
                            <td>Tax ({{ $quotation->tax_percent ?? 0 }}%)</td>
                            <td>+ {{ number_format($quotation->tax_amount, 2) }}</td>
                        </tr>
                    @endif
                    <tr class="grand-total">
                        <td>Grand Total</td>
                        <td>{{ number_format($quotation->grand_total, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Terms & Conditions --}}
        @if($quotation->terms)
            <div class="terms-section">
                <div class="terms-title">Terms & Conditions</div>
                <div class="terms-text">{{ $quotation->terms }}</div>
            </div>
        @endif

        {{-- Footer --}}
        <div class="footer">
            This is a computer-generated quotation and does not require a physical signature.
        </div>
    </div>
</body>
</html>
