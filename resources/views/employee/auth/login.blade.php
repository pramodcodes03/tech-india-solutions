<x-layout.auth title="Employee Portal - Sign In">
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

    * { box-sizing: border-box; margin: 0; padding: 0; }

    .emp-root {
        font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
        min-height: 100vh;
        background: #030712;
        position: relative;
        overflow: hidden;
        display: grid;
        place-items: center;
        padding: 1.25rem;
        color: #e5e7eb;
    }

    /* ── Aurora background layers ───────────────────────────────────────── */
    .aurora {
        position: fixed;
        inset: -20%;
        z-index: 0;
        background:
            radial-gradient(ellipse 60% 50% at 15% 20%, rgba(59,130,246,.35), transparent 60%),
            radial-gradient(ellipse 50% 45% at 85% 15%, rgba(168,85,247,.28), transparent 60%),
            radial-gradient(ellipse 55% 50% at 80% 85%, rgba(236,72,153,.20), transparent 60%),
            radial-gradient(ellipse 45% 40% at 20% 80%, rgba(16,185,129,.18), transparent 60%);
        filter: blur(40px) saturate(140%);
        animation: auroraShift 18s ease-in-out infinite alternate;
    }
    @keyframes auroraShift {
        0%   { transform: translate3d(0,0,0) scale(1)   rotate(0deg); }
        100% { transform: translate3d(-3%, 2%, 0) scale(1.08) rotate(3deg); }
    }

    /* Grid mesh */
    .mesh {
        position: fixed; inset: 0; z-index: 0; pointer-events: none;
        background-image:
            linear-gradient(rgba(99,102,241,.05) 1px, transparent 1px),
            linear-gradient(90deg, rgba(99,102,241,.05) 1px, transparent 1px);
        background-size: 48px 48px;
        mask-image: radial-gradient(ellipse 70% 70% at 50% 50%, black, transparent 85%);
    }

    /* Floating glow dots */
    .dots { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; }
    .dot {
        position: absolute;
        width: 4px; height: 4px; border-radius: 50%;
        background: rgba(147,197,253,.8);
        box-shadow: 0 0 12px rgba(59,130,246,.9);
        animation: dotRise linear infinite;
    }
    @keyframes dotRise {
        0%   { transform: translateY(110vh) scale(0.3); opacity: 0; }
        10%  { opacity: 1; }
        90%  { opacity: 1; }
        100% { transform: translateY(-10vh) scale(1.1); opacity: 0; }
    }

    /* ── Main stage ─────────────────────────────────────────────────────── */
    .stage {
        position: relative;
        z-index: 10;
        width: 100%;
        max-width: 1080px;
        display: grid;
        grid-template-columns: 1.1fr 1fr;
        gap: 0;
        border-radius: 28px;
        overflow: hidden;
        background: linear-gradient(135deg, rgba(15,23,42,.85), rgba(2,6,23,.9));
        backdrop-filter: blur(28px) saturate(160%);
        -webkit-backdrop-filter: blur(28px) saturate(160%);
        border: 1px solid rgba(255,255,255,.08);
        box-shadow:
            0 0 0 1px rgba(99,102,241,.1),
            0 30px 100px -20px rgba(0,0,0,.8),
            0 0 60px -10px rgba(99,102,241,.25);
        animation: stageIn .9s cubic-bezier(.16,1,.3,1) both;
    }
    @keyframes stageIn {
        from { opacity: 0; transform: translateY(40px) scale(.97); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    /* ── Left: brand panel ──────────────────────────────────────────────── */
    .brand-side {
        position: relative;
        padding: 56px 48px;
        display: flex;
        flex-direction: column;
        gap: 40px;
        justify-content: space-between;
        background:
            radial-gradient(circle at 100% 0%, rgba(99,102,241,.2), transparent 55%),
            radial-gradient(circle at 0% 100%, rgba(236,72,153,.15), transparent 55%),
            linear-gradient(180deg, rgba(17,24,39,.55), rgba(2,6,23,.75));
        overflow: hidden;
    }

    /* Rotating gradient ring */
    .ring {
        position: absolute;
        border-radius: 50%;
        border: 1px solid rgba(147,197,253,.15);
        animation: spin 24s linear infinite;
    }
    .ring::before, .ring::after {
        content: '';
        position: absolute;
        width: 8px; height: 8px; border-radius: 50%;
        background: linear-gradient(135deg, #60a5fa, #a78bfa);
        box-shadow: 0 0 20px rgba(96,165,250,.9);
    }
    .ring::before { top: -4px; left: 50%; transform: translateX(-50%); }
    .ring::after { bottom: -4px; left: 50%; transform: translateX(-50%); background: linear-gradient(135deg, #f472b6, #c084fc); box-shadow: 0 0 20px rgba(244,114,182,.8); }
    .ring-1 { width: 420px; height: 420px; top: -120px; right: -120px; }
    .ring-2 { width: 240px; height: 240px; bottom: -60px; left: -60px; animation-direction: reverse; animation-duration: 18s; }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* Brand top */
    .brand-top {
        position: relative; z-index: 2;
        display: flex; align-items: center; gap: 14px;
        animation: fadeDown .8s .2s both;
    }
    @keyframes fadeDown { from { opacity: 0; transform: translateY(-16px); } to { opacity: 1; transform: translateY(0); } }

    .brand-icon {
        width: 56px; height: 56px;
        display: grid; place-items: center;
        border-radius: 18px;
        background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 60%, #ec4899 100%);
        box-shadow: 0 10px 32px -8px rgba(139,92,246,.6), inset 0 1px 0 rgba(255,255,255,.2);
        position: relative;
    }
    .brand-icon::after {
        content: '';
        position: absolute; inset: 0;
        border-radius: inherit;
        background: conic-gradient(from 90deg, rgba(255,255,255,.3), transparent, rgba(255,255,255,.3));
        opacity: .4;
        animation: conic 4s linear infinite;
    }
    @keyframes conic { to { transform: rotate(360deg); } }

    .brand-name {
        font-weight: 800; font-size: 1.15rem; color: #f8fafc;
        letter-spacing: -.02em; line-height: 1.1;
    }
    .brand-sub {
        font-size: .7rem; color: #60a5fa; letter-spacing: .2em;
        text-transform: uppercase; font-weight: 700;
    }

    /* Hero */
    .hero {
        position: relative; z-index: 2;
        animation: fadeUp .8s .4s both;
    }
    @keyframes fadeUp { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: translateY(0); } }

    .eyebrow {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 6px 14px; border-radius: 100px;
        background: linear-gradient(135deg, rgba(59,130,246,.15), rgba(168,85,247,.15));
        border: 1px solid rgba(147,197,253,.25);
        font-size: .72rem; color: #93c5fd; font-weight: 700;
        letter-spacing: .12em; text-transform: uppercase;
        margin-bottom: 20px;
    }
    .eyebrow .pulse {
        width: 6px; height: 6px; border-radius: 50%;
        background: #60a5fa;
        box-shadow: 0 0 10px #60a5fa;
        animation: pulse 1.4s ease-in-out infinite;
    }
    @keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: .4; } }

    .hero h1 {
        font-size: 2.85rem;
        font-weight: 800;
        line-height: 1.05;
        letter-spacing: -.035em;
        color: #f8fafc;
        margin-bottom: 14px;
    }
    .hero h1 .grad {
        background: linear-gradient(135deg, #60a5fa 0%, #a78bfa 50%, #f472b6 100%);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        background-size: 200% 200%;
        animation: gradMove 6s ease-in-out infinite;
    }
    @keyframes gradMove { 0%,100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }

    .hero p {
        color: #94a3b8; font-size: .95rem; line-height: 1.65;
        max-width: 400px;
    }

    /* Feature chips */
    .chips {
        position: relative; z-index: 2;
        display: grid; gap: 10px;
        animation: fadeUp .8s .6s both;
    }
    .chip {
        display: flex; align-items: center; gap: 14px;
        padding: 14px 16px;
        border-radius: 14px;
        background: rgba(255,255,255,.025);
        border: 1px solid rgba(255,255,255,.06);
        transition: all .3s ease;
    }
    .chip:hover {
        background: rgba(99,102,241,.08);
        border-color: rgba(99,102,241,.25);
        transform: translateX(4px);
    }
    .chip-icon {
        width: 38px; height: 38px; border-radius: 11px;
        display: grid; place-items: center;
        flex-shrink: 0;
    }
    .chip-icon.blue   { background: linear-gradient(135deg, rgba(59,130,246,.2), rgba(59,130,246,.05)); color: #60a5fa; border: 1px solid rgba(59,130,246,.3); }
    .chip-icon.purple { background: linear-gradient(135deg, rgba(168,85,247,.2), rgba(168,85,247,.05)); color: #c084fc; border: 1px solid rgba(168,85,247,.3); }
    .chip-icon.pink   { background: linear-gradient(135deg, rgba(236,72,153,.2), rgba(236,72,153,.05)); color: #f472b6; border: 1px solid rgba(236,72,153,.3); }
    .chip-text strong { font-size: .88rem; color: #e2e8f0; font-weight: 700; display: block; }
    .chip-text span { font-size: .75rem; color: #64748b; }

    /* ── Right: form panel ──────────────────────────────────────────────── */
    .form-side {
        position: relative;
        padding: 56px 52px;
        display: flex; flex-direction: column;
        justify-content: center;
        background: rgba(2,6,23,.6);
    }
    .form-side::before {
        content: '';
        position: absolute; top: 0; left: 0; right: 0; height: 1px;
        background: linear-gradient(90deg, transparent, rgba(96,165,250,.7), transparent);
        animation: scanLine 3s ease-in-out infinite;
    }
    @keyframes scanLine { 0%,100% { opacity: .3; } 50% { opacity: 1; } }

    .form-head {
        margin-bottom: 32px;
        animation: fadeUp .8s .5s both;
    }
    .form-head h2 {
        font-size: 1.9rem; font-weight: 800; color: #f8fafc;
        letter-spacing: -.025em; margin-bottom: 6px;
    }
    .form-head p { color: #64748b; font-size: .88rem; }

    .alert {
        padding: 12px 14px; border-radius: 10px;
        background: linear-gradient(135deg, rgba(239,68,68,.08), rgba(239,68,68,.04));
        border: 1px solid rgba(239,68,68,.25);
        border-left: 3px solid #ef4444;
        color: #fca5a5; font-size: .85rem;
        margin-bottom: 20px;
        display: flex; align-items: flex-start; gap: 10px;
        animation: shake .4s ease;
    }
    @keyframes shake {
        0%,100% { transform: translateX(0); }
        20% { transform: translateX(-6px); }
        40% { transform: translateX(6px); }
        60% { transform: translateX(-4px); }
        80% { transform: translateX(4px); }
    }

    /* Inputs */
    .field { margin-bottom: 18px; animation: slideIn .5s both; }
    .field:nth-child(1) { animation-delay: .55s; }
    .field:nth-child(2) { animation-delay: .65s; }
    @keyframes slideIn { from { opacity: 0; transform: translateX(16px); } to { opacity: 1; transform: translateX(0); } }

    .field label {
        display: block;
        font-size: .7rem; font-weight: 700; color: #64748b;
        text-transform: uppercase; letter-spacing: .14em;
        margin-bottom: 9px;
    }
    .input-wrap {
        position: relative;
        transition: transform .2s ease;
    }
    .input-wrap:focus-within { transform: translateY(-1px); }
    .input-wrap svg.left {
        position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
        color: #475569; transition: color .2s;
        pointer-events: none;
    }
    .input-wrap:focus-within svg.left { color: #60a5fa; }

    .input {
        width: 100%;
        padding: 14px 16px 14px 46px;
        background: rgba(15,23,42,.6);
        border: 1px solid rgba(71,85,105,.3);
        border-radius: 12px;
        color: #f8fafc;
        font-size: .95rem; font-family: inherit;
        outline: none;
        transition: all .25s ease;
    }
    .input::placeholder { color: #475569; }
    .input:focus {
        border-color: rgba(96,165,250,.5);
        background: rgba(30,58,138,.08);
        box-shadow:
            0 0 0 4px rgba(96,165,250,.08),
            inset 0 0 20px rgba(96,165,250,.04);
    }
    .input.pwd { padding-right: 46px; }

    .eye-btn {
        position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
        background: none; border: 0; cursor: pointer;
        color: #475569; padding: 6px;
        display: grid; place-items: center; transition: color .2s;
    }
    .eye-btn:hover { color: #94a3b8; }

    /* Meta row */
    .meta {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 22px; font-size: .85rem;
        animation: fadeUp .5s .75s both;
    }
    .remember { display: inline-flex; align-items: center; gap: 9px; cursor: pointer; user-select: none; }
    .remember input { appearance: none; width: 18px; height: 18px; border-radius: 5px; border: 1.5px solid rgba(71,85,105,.6); background: rgba(15,23,42,.6); cursor: pointer; position: relative; transition: all .2s; flex-shrink: 0; }
    .remember input:checked { background: linear-gradient(135deg, #3b82f6, #8b5cf6); border-color: transparent; }
    .remember input:checked::after {
        content: ''; position: absolute; inset: 0;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='white'%3e%3cpath fill-rule='evenodd' d='M16.7 5.3a1 1 0 010 1.4l-7 7a1 1 0 01-1.4 0l-3-3a1 1 0 011.4-1.4L9 11.6l6.3-6.3a1 1 0 011.4 0z'/%3e%3c/svg%3e");
        background-repeat: no-repeat; background-position: center; background-size: 14px;
    }
    .remember span { color: #94a3b8; }

    /* Submit */
    .submit {
        width: 100%;
        padding: 16px 20px;
        background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 50%, #ec4899 100%);
        background-size: 200% 200%;
        background-position: 0% 50%;
        color: #fff; font-size: .95rem; font-weight: 700; letter-spacing: .015em;
        border: 0; border-radius: 12px;
        cursor: pointer;
        position: relative; overflow: hidden;
        display: flex; align-items: center; justify-content: center; gap: 10px;
        transition: background-position .6s ease, transform .15s ease, box-shadow .3s ease;
        font-family: inherit;
        animation: fadeUp .5s .85s both;
        box-shadow: 0 10px 30px -10px rgba(139,92,246,.5);
    }
    .submit:hover {
        background-position: 100% 50%;
        transform: translateY(-1px);
        box-shadow: 0 14px 40px -10px rgba(139,92,246,.7);
    }
    .submit:active { transform: translateY(0); }
    .submit::after {
        content: '';
        position: absolute; top: -50%; left: -60%;
        width: 40%; height: 200%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,.25), transparent);
        transform: skewX(-20deg);
        transition: left .6s ease;
    }
    .submit:hover::after { left: 130%; }

    /* Help row */
    .help {
        margin-top: 24px;
        padding: 14px 16px;
        border-radius: 12px;
        background: linear-gradient(135deg, rgba(99,102,241,.06), rgba(168,85,247,.04));
        border: 1px solid rgba(99,102,241,.15);
        font-size: .78rem; color: #94a3b8; line-height: 1.55;
        animation: fadeUp .5s .95s both;
    }
    .help strong { color: #c7d2fe; }
    .help code {
        background: rgba(99,102,241,.15);
        color: #c7d2fe;
        padding: 1px 6px; border-radius: 4px;
        font-size: .75rem;
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    }

    /* Footer links */
    .foot {
        display: flex; align-items: center; justify-content: space-between;
        margin-top: 22px;
        padding-top: 20px;
        border-top: 1px solid rgba(255,255,255,.05);
        animation: fadeUp .5s 1s both;
    }
    .foot a {
        font-size: .8rem; color: #64748b; text-decoration: none;
        display: inline-flex; align-items: center; gap: 6px;
        transition: color .2s;
    }
    .foot a:hover { color: #93c5fd; }
    .foot .shield {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 5px 10px; border-radius: 100px;
        background: rgba(16,185,129,.08);
        border: 1px solid rgba(16,185,129,.2);
        color: #6ee7b7; font-size: .72rem; font-weight: 600;
    }

    /* Responsive */
    @media (max-width: 920px) {
        .stage { grid-template-columns: 1fr; max-width: 480px; }
        .brand-side { display: none; }
        .form-side { padding: 44px 32px; }
    }
    @media (max-width: 520px) {
        .form-side { padding: 36px 22px; }
        .form-head h2 { font-size: 1.65rem; }
    }

    /* Loading spin */
    @keyframes spin360 { to { transform: rotate(360deg); } }
</style>

<div class="emp-root">
    <div class="aurora"></div>
    <div class="mesh"></div>
    <div class="dots" id="dots"></div>

    <div class="stage">

        {{-- ── LEFT: brand / hero ──────────────────────────────────────── --}}
        <aside class="brand-side">
            <div class="ring ring-1"></div>
            <div class="ring ring-2"></div>

            <div class="brand-top">
                <div class="brand-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <div>
                    <div class="brand-name">Tech India Solutions</div>
                    <div class="brand-sub">Employee Portal</div>
                </div>
            </div>

            <div class="hero">
                <span class="eyebrow"><span class="pulse"></span> Secure Workspace</span>
                <h1>Your work,<br><span class="grad">beautifully connected</span></h1>
                <p>Access attendance, leaves, payslips, and performance — all in one elegant workspace built for you.</p>
            </div>

            <div class="chips">
                <div class="chip">
                    <div class="chip-icon blue">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18M8 2v4M16 2v4"/></svg>
                    </div>
                    <div class="chip-text"><strong>Live Attendance</strong><span>Check in / out, monthly view, holidays</span></div>
                </div>
                <div class="chip">
                    <div class="chip-icon purple">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 2v4M16 2v4M3 10h18M12 14l2 2 4-4"/><rect x="3" y="4" width="18" height="18" rx="2"/></svg>
                    </div>
                    <div class="chip-text"><strong>Leave Requests</strong><span>Apply, track balances, see approvals</span></div>
                </div>
                <div class="chip">
                    <div class="chip-icon pink">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="13" rx="2"/><circle cx="12" cy="12.5" r="2.25"/></svg>
                    </div>
                    <div class="chip-text"><strong>Payslips & Performance</strong><span>Download PDFs, view appraisals</span></div>
                </div>
            </div>
        </aside>

        {{-- ── RIGHT: form ─────────────────────────────────────────────── --}}
        <section class="form-side" x-data="{ show: false, loading: false }">

            <div class="form-head">
                <h2>Welcome back 👋</h2>
                <p>Sign in with your employee code or email to continue.</p>
            </div>

            @if(session('error'))
                <div class="alert">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:2px"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif
            @if($errors->any())
                <div class="alert">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:2px"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('employee.signin') }}" @submit="loading = true">
                @csrf

                <div class="field">
                    <label for="login">Employee Code or Email</label>
                    <div class="input-wrap">
                        <svg class="left" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                        <input id="login" name="login" type="text" value="{{ old('login') }}" required autofocus autocomplete="username"
                               class="input" placeholder="EMP-0001 or you@company.com" />
                    </div>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <svg class="left" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 1 1 8 0v4"/></svg>
                        <input id="password" name="password" :type="show ? 'text' : 'password'" required autocomplete="current-password"
                               class="input pwd" placeholder="••••••••••" />
                        <button type="button" class="eye-btn" @click="show = !show" aria-label="Toggle password visibility">
                            <svg x-show="!show" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg x-show="show" x-cloak width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>
                </div>

                <div class="meta">
                    <label class="remember">
                        <input type="checkbox" name="remember" value="1" />
                        <span>Keep me signed in</span>
                    </label>
                </div>

                <button type="submit" class="submit" :disabled="loading">
                    <span x-show="!loading">Sign In to Portal</span>
                    <svg x-show="!loading" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span x-show="loading" x-cloak>Authenticating…</span>
                    <svg x-show="loading" x-cloak width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="animation: spin360 .8s linear infinite"><path d="M21 12a9 9 0 1 1-6.2-8.55" stroke-linecap="round"/></svg>
                </button>
            </form>

            <div class="help">
                <strong>First time signing in?</strong><br>
                Your default password is your employee code (e.g. <code>EMP-0001</code>). You can change it from your profile after logging in.
            </div>

            <div class="foot">
                <a href="{{ route('admin.login') }}">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Admin Login
                </a>
                <span class="shield">
                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke-linejoin="round"/></svg>
                    256-bit SSL
                </span>
            </div>
        </section>
    </div>
</div>

<script>
    // Floating dots
    (function() {
        const c = document.getElementById('dots');
        if (!c) return;
        for (let i = 0; i < 22; i++) {
            const d = document.createElement('div');
            d.className = 'dot';
            const size = Math.random() * 3 + 1;
            d.style.cssText = `
                left: ${Math.random() * 100}%;
                width: ${size}px; height: ${size}px;
                animation-duration: ${Math.random() * 18 + 14}s;
                animation-delay: ${Math.random() * -25}s;
                opacity: ${Math.random() * .6 + .3};
            `;
            c.appendChild(d);
        }
    })();
</script>
</x-layout.auth>
