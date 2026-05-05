<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? ($business->name ?? config('app.name')) }}</title>
    <style>
        /* Inlined for email-client compatibility */
        body { margin: 0; padding: 0; background: #f5f7fb; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; color: #1a1a2e; line-height: 1.5; }
        .wrapper { width: 100%; background: #f5f7fb; padding: 24px 12px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .header { background: #122e6d; padding: 20px 24px; color: #fff; }
        .header table { width: 100%; }
        .header .logo img { max-height: 38px; max-width: 160px; }
        .header .name { font-size: 16px; font-weight: bold; vertical-align: middle; }
        .header .tag { font-size: 11px; opacity: 0.85; margin-top: 2px; }
        .body { padding: 28px 24px; }
        .body h1 { font-size: 20px; margin: 0 0 14px; color: #122e6d; }
        .body h2 { font-size: 15px; margin: 18px 0 10px; color: #1a1a2e; }
        .body p { margin: 0 0 12px; font-size: 14px; }
        .body a { color: #122e6d; }
        .meta-table { width: 100%; border-collapse: collapse; margin: 12px 0; }
        .meta-table td { padding: 7px 9px; font-size: 13px; border-bottom: 1px solid #eef0f5; }
        .meta-table td.label { color: #666; width: 40%; }
        .meta-table td.val { color: #1a1a2e; font-weight: 600; }
        .btn { display: inline-block; padding: 11px 22px; background: #122e6d; color: #fff !important; text-decoration: none; border-radius: 4px; font-weight: 600; font-size: 14px; margin: 12px 0; }
        .btn-success { background: #16a34a; }
        .btn-warning { background: #d97706; }
        .btn-danger  { background: #dc2626; }
        .alert { padding: 12px 14px; border-radius: 4px; margin: 12px 0; font-size: 13.5px; }
        .alert-info    { background: #eff6ff; border-left: 4px solid #3b82f6; color: #1e40af; }
        .alert-warning { background: #fffbeb; border-left: 4px solid #d97706; color: #92400e; }
        .alert-danger  { background: #fef2f2; border-left: 4px solid #dc2626; color: #991b1b; }
        .alert-success { background: #f0fdf4; border-left: 4px solid #16a34a; color: #166534; }
        .amount { font-size: 22px; font-weight: bold; color: #122e6d; }
        .footer { padding: 18px 24px; text-align: center; background: #f9fafb; color: #6b7280; font-size: 11.5px; line-height: 1.6; border-top: 1px solid #eef0f5; }
        .footer .biz { color: #1a1a2e; font-weight: 600; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <table>
                    <tr>
                        @php
                            $logoSrc = null;
                            if ($business->logo) {
                                $diskPath = storage_path('app/public/'.$business->logo);
                                if (file_exists($diskPath)) {
                                    // Embed via data URI so the email is self-contained
                                    $mime = mime_content_type($diskPath) ?: 'image/png';
                                    $logoSrc = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($diskPath));
                                }
                            }
                        @endphp
                        @if($logoSrc)
                            <td class="logo" style="width: 60%;">
                                <img src="{{ $logoSrc }}" alt="{{ $business->name }}" />
                            </td>
                            <td style="text-align: right; vertical-align: middle;">
                                <div class="name">{{ $business->name }}</div>
                                <div class="tag">{{ $business->city ? $business->city.', '.($business->state ?? '') : '' }}</div>
                            </td>
                        @else
                            <td>
                                <div class="name">{{ strtoupper($business->name) }}</div>
                                <div class="tag">{{ collect([$business->city, $business->state])->filter()->implode(', ') }}</div>
                            </td>
                        @endif
                    </tr>
                </table>
            </div>

            <div class="body">
                @yield('body')
            </div>

            <div class="footer">
                <div class="biz">{{ $business->name }}</div>
                @if($business->address){{ $business->address }} · @endif
                {{ collect([$business->city, $business->state, $business->pincode])->filter()->implode(', ') }}
                <br>
                @if($business->phone)Phone: {{ $business->phone }} · @endif
                @if($business->email)Email: {{ $business->email }}@endif
                @if($business->gst)<br>GSTIN: {{ $business->gst }}@endif
                <br><br>
                <span style="color: #9ca3af; font-size: 10.5px;">This is a system-generated transactional email from {{ $business->name }} ERP.</span>
            </div>
        </div>
    </div>
</body>
</html>
