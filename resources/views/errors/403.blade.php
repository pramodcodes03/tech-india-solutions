@php
    // Resolve a sensible "go back" landing page depending on who's logged in.
    // Self-contained — doesn't import the admin/employee layout because those
    // layouts assume permissions/routes the 403'd user may not have.
    $admin    = auth('admin')->user();
    $employee = auth('employee')->user();

    $homeUrl = match (true) {
        (bool) $admin    => route('admin.dashboard'),
        (bool) $employee => route('employee.dashboard'),
        default          => route('admin.login'),
    };

    // Best-effort "who do I contact" address. Prefer the active business's
    // support/admin email, then the global settings table, then a generic
    // placeholder. The mailto link below pre-fills a sensible subject line.
    $business    = app(\App\Support\Tenancy\CurrentBusiness::class)->get();
    $settings    = \App\Models\Setting::pluck('value', 'key')->toArray() ?? [];
    $contactMail = $business?->email
        ?? ($settings['company_email'] ?? null);

    $mailtoSubject = rawurlencode('Access request — '.($_SERVER['REQUEST_URI'] ?? '/admin'));
    $mailtoBody    = rawurlencode(
        "Hi,\n\nI was trying to open ".($_SERVER['REQUEST_URI'] ?? '')." but got a 'page not accessible' message.\n\nPlease grant me access if appropriate.\n\nThanks,\n"
        .($admin->name ?? $employee?->full_name ?? 'A user')
    );
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access denied</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <style>
        :root {
            --ink: #122e6d;
            --ink-soft: #4361ee;
            --bg: #f6f8fb;
            --card: #ffffff;
            --muted: #6b7280;
            --line: #e5e7eb;
            --warning-bg: #fef3c7;
            --warning-ink: #92400e;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --ink: #e0e6ff;
                --ink-soft: #93c5fd;
                --bg: #0e1726;
                --card: #1b2e4b;
                --muted: #9ca3af;
                --line: #1f3553;
                --warning-bg: #422b06;
                --warning-ink: #fbbf24;
            }
        }
        * { box-sizing: border-box; }
        html, body {
            margin: 0; padding: 0; height: 100%;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: var(--bg);
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
        }
        .wrap {
            min-height: 100%;
            display: flex; align-items: center; justify-content: center;
            padding: 24px;
        }
        .card {
            width: 100%; max-width: 560px;
            background: var(--card);
            border-radius: 20px;
            box-shadow: 0 20px 50px -20px rgba(18, 46, 109, 0.15), 0 1px 0 var(--line);
            overflow: hidden;
        }
        .top {
            padding: 36px 36px 16px;
            text-align: center;
        }
        .lock {
            display: inline-flex; align-items: center; justify-content: center;
            width: 84px; height: 84px;
            border-radius: 50%;
            background: linear-gradient(135deg, #fde68a 0%, #fbbf24 100%);
            color: #78350f;
            margin-bottom: 18px;
            box-shadow: 0 12px 28px -10px rgba(251, 191, 36, 0.55);
        }
        .lock svg { width: 38px; height: 38px; }
        .code {
            font-size: 11px;
            letter-spacing: 0.18em;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        h1 {
            font-size: 28px; font-weight: 800;
            margin: 0 0 10px;
            letter-spacing: -0.01em;
            color: var(--ink);
        }
        .lede {
            font-size: 15px; line-height: 1.55;
            color: var(--muted);
            max-width: 420px; margin: 0 auto;
        }
        .contact-strip {
            margin: 24px 36px 0;
            padding: 14px 16px;
            background: var(--warning-bg);
            color: var(--warning-ink);
            border-radius: 12px;
            font-size: 13px; line-height: 1.5;
            display: flex; align-items: flex-start; gap: 10px;
        }
        .contact-strip svg { width: 18px; height: 18px; flex-shrink: 0; margin-top: 1px; }
        .contact-strip a {
            color: inherit; font-weight: 700; text-decoration: underline;
        }
        .actions {
            padding: 28px 36px 36px;
            display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;
        }
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 11px 20px;
            border-radius: 10px;
            font-size: 14px; font-weight: 600;
            text-decoration: none;
            transition: transform 0.05s ease, box-shadow 0.15s ease;
            cursor: pointer;
            border: 1px solid transparent;
        }
        .btn:active { transform: translateY(1px); }
        .btn-primary {
            background: var(--ink);
            color: #fff;
            box-shadow: 0 6px 18px -8px rgba(18, 46, 109, 0.5);
        }
        .btn-primary:hover { background: #0e2456; }
        .btn-ghost {
            background: transparent;
            color: var(--ink);
            border-color: var(--line);
        }
        .btn-ghost:hover { background: var(--bg); }
        .footer-note {
            text-align: center;
            font-size: 12px;
            color: var(--muted);
            padding: 0 36px 28px;
        }
        .footer-note code {
            background: var(--bg);
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 11px;
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="top">
            <div class="lock" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" />
                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                </svg>
            </div>
            <div class="code">403 · Access denied</div>
            <h1>You don't have access to this page</h1>
            <p class="lede">
                Your account doesn't have permission to view this section. If you believe you should have access, please contact your administrator and they can grant it from <em>Roles &amp; Permissions</em>.
            </p>
        </div>

        @if($contactMail)
            <div class="contact-strip">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                    <polyline points="22,6 12,13 2,6" />
                </svg>
                <div>
                    Need access? Email
                    <a href="mailto:{{ $contactMail }}?subject={{ $mailtoSubject }}&body={{ $mailtoBody }}">{{ $contactMail }}</a>
                    and ask for the permission to be turned on for your role.
                </div>
            </div>
        @else
            <div class="contact-strip">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="12" y1="8" x2="12" y2="12" />
                    <line x1="12" y1="16" x2="12.01" y2="16" />
                </svg>
                <div>
                    Need access? Ask your administrator to enable this permission for your role under <strong>Settings → Roles &amp; Permissions</strong>.
                </div>
            </div>
        @endif

        <div class="actions">
            <a href="{{ $homeUrl }}" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 12l9-9 9 9" />
                    <path d="M5 10v10h14V10" />
                </svg>
                Go to dashboard
            </a>
            <a href="javascript:history.back()" class="btn btn-ghost">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12" />
                    <polyline points="12 19 5 12 12 5" />
                </svg>
                Go back
            </a>
        </div>

        @if(! empty($exception?->getMessage()))
            <div class="footer-note">
                <code>{{ $exception->getMessage() }}</code>
            </div>
        @endif
    </div>
</div>
</body>
</html>
