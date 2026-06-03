<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light only">
    <meta name="supported-color-schemes" content="light only">
    <title>{{ $subject ?? ($business->name ?? config('app.name')) }}</title>
    <style>
        /* Reset */
        body, table, td, p, a, h1, h2, h3 { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table { border-collapse: collapse; }
        img { border: 0; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; display: block; }
        a { text-decoration: none; }

        /* System font stack — renders crisply in Gmail/Outlook/Apple/Yahoo without web fonts */
        body {
            margin: 0; padding: 0;
            background: #eef1f6;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI Variable", "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #0f172a;
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
        }

        .preheader { display: none !important; visibility: hidden; opacity: 0; color: transparent; height: 0; width: 0; overflow: hidden; }

        .wrapper { width: 100%; background: #eef1f6; padding: 32px 12px; }
        .container { max-width: 620px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06); }

        /* Header */
        .header { background: linear-gradient(135deg, #0f1e4a 0%, #1e3a8a 100%); padding: 28px 32px; color: #ffffff; }
        .header .name { font-size: 18px; font-weight: 700; letter-spacing: 0.3px; line-height: 1.2; }
        .header .tag  { font-size: 12px; opacity: 0.78; margin-top: 4px; letter-spacing: 0.3px; }
        .header .logo img { max-height: 44px; max-width: 180px; }

        /* Body */
        .body { padding: 36px 32px 28px; }
        .body h1 { font-size: 22px; font-weight: 700; margin: 0 0 6px; color: #0f1e4a; line-height: 1.3; letter-spacing: -0.2px; }
        .body .lede { font-size: 14px; color: #64748b; margin: 0 0 24px; }
        .body h2 { font-size: 16px; font-weight: 600; margin: 24px 0 10px; color: #0f172a; }
        .body p  { margin: 0 0 14px; font-size: 14.5px; color: #1e293b; }
        .body a  { color: #2563eb; }
        .body strong { color: #0f172a; font-weight: 600; }

        /* Meta table */
        .meta-table { width: 100%; border-collapse: separate; border-spacing: 0; margin: 18px 0; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
        .meta-table td { padding: 11px 16px; font-size: 13.5px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .meta-table tr:last-child td { border-bottom: 0; }
        .meta-table td.label { color: #64748b; width: 40%; font-weight: 500; }
        .meta-table td.val   { color: #0f172a; font-weight: 600; }

        /* Buttons */
        .btn { display: inline-block; padding: 12px 22px; background: #1e3a8a; color: #ffffff !important; border-radius: 8px; font-weight: 600; font-size: 14px; margin: 16px 0 6px; box-shadow: 0 2px 6px rgba(30,58,138,0.25); }
        .btn-success { background: #16a34a; box-shadow: 0 2px 6px rgba(22,163,74,0.25); }
        .btn-warning { background: #d97706; box-shadow: 0 2px 6px rgba(217,119,6,0.25); }
        .btn-danger  { background: #dc2626; box-shadow: 0 2px 6px rgba(220,38,38,0.25); }

        /* Alerts */
        .alert { padding: 13px 16px; border-radius: 8px; margin: 16px 0; font-size: 13.5px; line-height: 1.5; }
        .alert-info    { background: #eff6ff; border-left: 3px solid #3b82f6; color: #1e3a8a; }
        .alert-warning { background: #fffbeb; border-left: 3px solid #d97706; color: #78350f; }
        .alert-danger  { background: #fef2f2; border-left: 3px solid #dc2626; color: #7f1d1d; }
        .alert-success { background: #f0fdf4; border-left: 3px solid #16a34a; color: #14532d; }

        /* Status badges */
        .badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 11.5px; font-weight: 600; letter-spacing: 0.3px; text-transform: uppercase; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-approved, .badge-active { background: #d1fae5; color: #065f46; }
        .badge-rejected, .badge-cancelled { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }

        /* Amount */
        .amount { font-size: 24px; font-weight: 700; color: #0f1e4a; letter-spacing: -0.3px; }

        /* Divider */
        .divider { border: 0; border-top: 1px solid #e2e8f0; margin: 24px 0; }

        /* Footer */
        .footer { padding: 22px 32px 26px; background: #f8fafc; color: #64748b; font-size: 12px; line-height: 1.65; border-top: 1px solid #e2e8f0; text-align: center; }
        .footer .biz { color: #0f172a; font-weight: 700; font-size: 13px; letter-spacing: 0.2px; }
        .footer .tag-line { color: #94a3b8; font-size: 11px; margin-top: 12px; }

        @media only screen and (max-width: 480px) {
            .wrapper { padding: 16px 8px; }
            .container { border-radius: 8px; }
            .header, .body, .footer { padding-left: 20px !important; padding-right: 20px !important; }
            .body h1 { font-size: 19px; }
            .meta-table td { padding: 9px 12px; font-size: 13px; }
        }
    </style>
</head>
<body>
    @isset($preheader)
        <div class="preheader">{{ $preheader }}</div>
    @endisset

    <div class="wrapper">
        <div class="container">
            <div class="header">
                <table style="width:100%;">
                    <tr>
                        @php
                            $logoSrc = null;
                            if ($business->logo) {
                                $diskPath = storage_path('app/public/'.$business->logo);
                                if (file_exists($diskPath)) {
                                    $mime = mime_content_type($diskPath) ?: 'image/png';
                                    $logoSrc = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($diskPath));
                                }
                            }
                        @endphp
                        @if($logoSrc)
                            <td class="logo" style="width: 55%;">
                                <img src="{{ $logoSrc }}" alt="{{ $business->name }}" />
                            </td>
                            <td style="text-align: right; vertical-align: middle;">
                                <div class="name">{{ $business->name }}</div>
                                <div class="tag">{{ collect([$business->city, $business->state])->filter()->implode(', ') }}</div>
                            </td>
                        @else
                            <td>
                                <div class="name">{{ $business->name }}</div>
                                <div class="tag">{{ collect([$business->city, $business->state])->filter()->implode(', ') ?: 'Notification' }}</div>
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
                @if($business->address || $business->city)
                    <div>
                        @if($business->address){{ $business->address }}@if($business->city), @endif @endif{{ collect([$business->city, $business->state, $business->pincode])->filter()->implode(', ') }}
                    </div>
                @endif
                @if($business->phone || $business->email)
                    <div>
                        @if($business->phone){{ $business->phone }}@endif
                        @if($business->phone && $business->email) · @endif
                        @if($business->email){{ $business->email }}@endif
                    </div>
                @endif
                @if($business->gst)<div>GSTIN: {{ $business->gst }}</div>@endif
                <div class="tag-line">This is a system-generated email from {{ $business->name }} ERP. Please do not reply.</div>
            </div>
        </div>
    </div>
</body>
</html>
