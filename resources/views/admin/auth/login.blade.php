<x-layout.auth>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

    * { box-sizing: border-box; margin: 0; padding: 0; }

    .login-root {
        font-family: 'Inter', sans-serif;
        min-height: 100vh;
        background: #020817;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        position: relative;
    }

    /* ── Animated mesh gradient background ── */
    .bg-mesh {
        position: fixed;
        inset: 0;
        z-index: 0;
        background:
            radial-gradient(ellipse 80% 60% at 20% 10%, rgba(18,46,109,0.28) 0%, transparent 60%),
            radial-gradient(ellipse 60% 50% at 80% 80%, rgba(139,92,246,0.15) 0%, transparent 60%),
            radial-gradient(ellipse 50% 40% at 60% 20%, rgba(16,185,129,0.08) 0%, transparent 60%),
            #020817;
        animation: meshShift 12s ease-in-out infinite alternate;
    }
    @keyframes meshShift {
        0%   { filter: hue-rotate(0deg); }
        100% { filter: hue-rotate(30deg); }
    }

    /* ── Floating orbs ── */
    .orb {
        position: fixed;
        border-radius: 50%;
        filter: blur(80px);
        opacity: 0.25;
        pointer-events: none;
        animation: orbFloat linear infinite;
    }
    .orb-1 { width:500px; height:500px; background:#122e6d; top:-120px; left:-120px; animation-duration:20s; }
    .orb-2 { width:400px; height:400px; background:#8b5cf6; bottom:-100px; right:-100px; animation-duration:25s; animation-delay:-8s; }
    .orb-3 { width:300px; height:300px; background:#10b981; top:40%; left:60%; animation-duration:18s; animation-delay:-4s; }
    @keyframes orbFloat {
        0%,100% { transform: translate(0,0) scale(1); }
        33%      { transform: translate(40px,-30px) scale(1.05); }
        66%      { transform: translate(-20px,40px) scale(0.95); }
    }

    /* ── Grid lines ── */
    .grid-lines {
        position: fixed;
        inset: 0;
        background-image:
            linear-gradient(rgba(18,46,109,0.06) 1px, transparent 1px),
            linear-gradient(90deg, rgba(18,46,109,0.06) 1px, transparent 1px);
        background-size: 60px 60px;
        pointer-events: none;
        z-index: 0;
    }

    /* ── Floating particles ── */
    .particles { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; }
    .particle {
        position: absolute;
        width: 3px; height: 3px;
        border-radius: 50%;
        background: rgba(18,46,109,0.7);
        animation: particleFly linear infinite;
    }
    @keyframes particleFly {
        0%   { transform: translateY(100vh) rotate(0deg); opacity: 0; }
        10%  { opacity: 1; }
        90%  { opacity: 1; }
        100% { transform: translateY(-10vh) rotate(720deg); opacity: 0; }
    }

    /* ── Main card ── */
    .login-card {
        position: relative;
        z-index: 10;
        width: 100%;
        max-width: 1100px;
        margin: 2rem auto;
        display: grid;
        grid-template-columns: 1fr 1fr;
        border-radius: 28px;
        overflow: hidden;
        box-shadow:
            0 0 0 1px rgba(18,46,109,0.3),
            0 0 80px rgba(18,46,109,0.12),
            0 40px 120px rgba(0,0,0,0.6);
        animation: cardIn 0.8s cubic-bezier(0.16,1,0.3,1) both;
    }
    @keyframes cardIn {
        from { opacity:0; transform: translateY(40px) scale(0.97); }
        to   { opacity:1; transform: translateY(0) scale(1); }
    }

    /* ── Left panel ── */
    .left-panel {
        background: linear-gradient(145deg, #0f1a2e 0%, #0a1628 40%, #0d1f3c 100%);
        padding: 56px 52px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
        min-height: 680px;
    }

    /* Left panel decorative circuit lines */
    .circuit-line {
        position: absolute;
        background: linear-gradient(90deg, transparent, rgba(18,46,109,0.5), transparent);
        height: 1px;
        animation: circuitPulse 3s ease-in-out infinite;
    }
    @keyframes circuitPulse {
        0%,100% { opacity:0.2; }
        50%      { opacity:0.8; }
    }

    /* Rotating ring */
    .ring {
        position: absolute;
        border-radius: 50%;
        border: 1px solid rgba(18,46,109,0.3);
        animation: ringRotate linear infinite;
    }
    .ring::after {
        content: '';
        position: absolute;
        width: 8px; height: 8px;
        background: #122e6d;
        border-radius: 50%;
        top: -4px; left: 50%;
        transform: translateX(-50%);
        box-shadow: 0 0 12px #122e6d;
    }
    @keyframes ringRotate { to { transform: rotate(360deg); } }
    .ring-1 { width:300px; height:300px; bottom:-80px; right:-80px; animation-duration:20s; }
    .ring-2 { width:180px; height:180px; bottom:-20px; right:-20px; animation-duration:12s; animation-direction:reverse; }
    .ring-3 { width:80px;  height:80px;  top:40px;    left:40px;   animation-duration:8s; }

    /* Brand */
    .brand-logo {
        display: flex;
        align-items: center;
        gap: 14px;
        animation: slideDown 0.6s 0.3s cubic-bezier(0.16,1,0.3,1) both;
    }
    @keyframes slideDown { from { opacity:0; transform:translateY(-20px); } to { opacity:1; transform:translateY(0); } }

    .brand-icon {
        width: 52px; height: 52px;
        background: linear-gradient(135deg, #122e6d, #1e4aad);
        border-radius: 16px;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 0 30px rgba(18,46,109,0.5);
        flex-shrink: 0;
    }
    .brand-name { font-size: 1.25rem; font-weight: 800; color: #fff; letter-spacing: -0.02em; line-height: 1.2; }
    .brand-sub  { font-size: 0.7rem; color: #64748b; letter-spacing: 0.15em; text-transform: uppercase; font-weight: 500; }

    /* Headline */
    .left-headline {
        animation: slideUp 0.6s 0.45s cubic-bezier(0.16,1,0.3,1) both;
    }
    @keyframes slideUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }

    .left-headline h2 {
        font-size: 2.8rem;
        font-weight: 900;
        line-height: 1.1;
        letter-spacing: -0.04em;
        color: #fff;
        margin-bottom: 16px;
    }
    .left-headline h2 .gradient-text {
        background: linear-gradient(135deg, #122e6d 0%, #8b5cf6 50%, #1e4aad 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        animation: gradientShift 4s ease infinite;
        background-size: 200%;
    }
    @keyframes gradientShift { 0%,100%{background-position:0% 50%} 50%{background-position:100% 50%} }
    .left-headline p { font-size: 0.95rem; color: #64748b; line-height: 1.6; max-width: 320px; }

    /* Stats */
    .stats-row {
        display: flex;
        gap: 20px;
        animation: slideUp 0.6s 0.6s cubic-bezier(0.16,1,0.3,1) both;
    }
    .stat-card {
        flex: 1;
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 16px;
        padding: 18px 16px;
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
    }
    .stat-card:hover {
        background: rgba(18,46,109,0.12);
        border-color: rgba(18,46,109,0.3);
        transform: translateY(-2px);
    }
    .stat-value { font-size: 1.5rem; font-weight: 800; color: #fff; letter-spacing: -0.04em; }
    .stat-label { font-size: 0.72rem; color: #475569; text-transform: uppercase; letter-spacing: 0.1em; margin-top: 2px; }

    /* Features list */
    .features {
        display: flex; flex-direction: column; gap: 14px;
        animation: slideUp 0.6s 0.75s cubic-bezier(0.16,1,0.3,1) both;
    }
    .feature-item {
        display: flex; align-items: center; gap: 12px;
        padding: 12px 16px;
        border-radius: 12px;
        background: rgba(255,255,255,0.02);
        border: 1px solid rgba(255,255,255,0.04);
        transition: all 0.3s ease;
    }
    .feature-item:hover { background: rgba(18,46,109,0.08); border-color: rgba(18,46,109,0.2); }
    .feature-dot {
        width: 32px; height: 32px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .feature-dot.blue   { background: rgba(18,46,109,0.2); }
    .feature-dot.purple { background: rgba(139,92,246,0.15); }
    .feature-dot.green  { background: rgba(16,185,129,0.15); }
    .feature-item span  { font-size: 0.85rem; color: #94a3b8; font-weight: 500; }

    /* ── Right panel ── */
    .right-panel {
        background: #080f1e;
        padding: 56px 52px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }
    .right-panel::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(18,46,109,0.6), transparent);
        animation: scanLine 3s ease-in-out infinite;
    }
    @keyframes scanLine { 0%,100%{opacity:0.3} 50%{opacity:1} }

    .form-header {
        margin-bottom: 40px;
        animation: fadeIn 0.6s 0.5s both;
    }
    @keyframes fadeIn { from{opacity:0} to{opacity:1} }

    .form-header .eyebrow {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 6px 12px;
        background: rgba(18,46,109,0.12);
        border: 1px solid rgba(18,46,109,0.25);
        border-radius: 100px;
        margin-bottom: 20px;
    }
    .form-header .eyebrow span { font-size: 0.72rem; color: #4a7fd4; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; }
    .eyebrow-dot { width:6px; height:6px; background:#122e6d; border-radius:50%; animation: dotPulse 1.5s ease infinite; }
    @keyframes dotPulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:0.5;transform:scale(0.8)} }

    .form-header h1 {
        font-size: 2.4rem;
        font-weight: 900;
        color: #fff;
        letter-spacing: -0.04em;
        line-height: 1.1;
        margin-bottom: 10px;
    }
    .form-header p { font-size: 0.9rem; color: #475569; }

    /* Input groups */
    .input-group { margin-bottom: 22px; animation: slideInRight 0.5s cubic-bezier(0.16,1,0.3,1) both; }
    .input-group:nth-child(1) { animation-delay: 0.55s; }
    .input-group:nth-child(2) { animation-delay: 0.65s; }
    @keyframes slideInRight { from{opacity:0;transform:translateX(20px)} to{opacity:1;transform:translateX(0)} }

    .input-label {
        display: block;
        font-size: 0.72rem;
        font-weight: 700;
        color: #475569;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        margin-bottom: 10px;
    }
    .input-wrap { position: relative; }
    .input-icon {
        position: absolute;
        left: 18px; top: 50%; transform: translateY(-50%);
        color: #334155;
        transition: color 0.2s;
        pointer-events: none;
    }
    .input-field {
        width: 100%;
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 14px;
        padding: 16px 18px 16px 50px;
        font-size: 0.95rem;
        color: #fff;
        outline: none;
        transition: all 0.3s ease;
        font-family: 'Inter', sans-serif;
    }
    .input-field::placeholder { color: #334155; }
    .input-field:focus {
        border-color: rgba(18,46,109,0.6);
        background: rgba(18,46,109,0.06);
        box-shadow: 0 0 0 3px rgba(18,46,109,0.12), inset 0 0 20px rgba(18,46,109,0.04);
    }
    .input-field:focus + .focus-ring, .input-wrap:focus-within .input-icon { color: #4a7fd4; }
    .focus-ring {
        position: absolute; inset: -1px;
        border-radius: 15px;
        border: 1px solid transparent;
        pointer-events: none;
        transition: border-color 0.3s;
    }
    .input-field:focus ~ .focus-ring { border-color: rgba(18,46,109,0.4); }
    .eye-btn {
        position: absolute; right: 16px; top: 50%; transform: translateY(-50%);
        background: none; border: none; cursor: pointer;
        color: #334155; transition: color 0.2s;
        padding: 4px; display: flex; align-items: center;
    }
    .eye-btn:hover { color: #64748b; }

    /* Remember / forgot */
    .form-meta {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 28px;
        animation: fadeIn 0.5s 0.75s both;
    }
    .remember {
        display: flex; align-items: center; gap: 10px;
        cursor: pointer;
    }
    .remember input[type="checkbox"] {
        width: 16px; height: 16px;
        accent-color: #122e6d;
        cursor: pointer;
    }
    .remember-label { font-size: 0.85rem; color: #475569; font-weight: 500; }

    /* Submit button */
    .btn-submit {
        width: 100%;
        padding: 17px 24px;
        background: linear-gradient(135deg, #122e6d 0%, #1e4aad 100%);
        color: #fff;
        font-size: 0.95rem;
        font-weight: 700;
        border: none;
        border-radius: 14px;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        letter-spacing: 0.02em;
        transition: all 0.3s ease;
        font-family: 'Inter', sans-serif;
        animation: fadeIn 0.5s 0.85s both;
        display: flex; align-items: center; justify-content: center; gap: 10px;
    }
    .btn-submit::before {
        content: '';
        position: absolute; inset: 0;
        background: linear-gradient(135deg, #0d2050, #0f2e6b);
        opacity: 0;
        transition: opacity 0.3s;
    }
    .btn-submit:hover::before { opacity: 1; }
    .btn-submit::after {
        content: '';
        position: absolute;
        top: -50%; left: -60%;
        width: 40%; height: 200%;
        background: rgba(255,255,255,0.15);
        transform: skewX(-20deg);
        transition: left 0.5s ease;
    }
    .btn-submit:hover::after { left: 130%; }
    .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 8px 30px rgba(18,46,109,0.5); }
    .btn-submit:active { transform: translateY(0); }
    .btn-submit span { position: relative; z-index: 1; }
    .btn-submit svg  { position: relative; z-index: 1; }

    /* Shimmer loader inside button on submit */
    .btn-submit.loading { pointer-events: none; }
    .btn-submit.loading span { opacity: 0.6; }

    /* Divider */
    .divider {
        display: flex; align-items: center; gap: 12px;
        margin: 28px 0;
        animation: fadeIn 0.5s 0.9s both;
    }
    .divider-line { flex:1; height:1px; background: rgba(255,255,255,0.05); }
    .divider span  { font-size: 0.75rem; color: #1e293b; white-space: nowrap; }

    /* Footer */
    .form-footer {
        text-align: center;
        animation: fadeIn 0.5s 1s both;
    }
    .form-footer p { font-size: 0.8rem; color: #1e293b; }
    .shield-badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 8px 16px;
        background: rgba(16,185,129,0.06);
        border: 1px solid rgba(16,185,129,0.12);
        border-radius: 100px;
        font-size: 0.75rem; color: #10b981; font-weight: 600;
    }

    /* Error alert */
    .alert-error {
        background: rgba(239,68,68,0.08);
        border: 1px solid rgba(239,68,68,0.2);
        border-left: 3px solid #ef4444;
        border-radius: 12px;
        padding: 14px 16px;
        margin-bottom: 24px;
        animation: shake 0.4s ease;
    }
    @keyframes shake {
        0%,100%{transform:translateX(0)} 20%{transform:translateX(-6px)} 40%{transform:translateX(6px)} 60%{transform:translateX(-4px)} 80%{transform:translateX(4px)}
    }
    .alert-error p { font-size: 0.85rem; color: #fca5a5; }

    /* Responsive */
    @media (max-width: 900px) {
        .login-card { grid-template-columns: 1fr; max-width: 480px; }
        .left-panel  { display: none; }
        .right-panel { padding: 48px 36px; }
    }
    @media (max-width: 480px) {
        .right-panel { padding: 36px 24px; }
        .form-header h1 { font-size: 2rem; }
    }
</style>

<div class="login-root">

    <!-- Background layers -->
    <div class="bg-mesh"></div>
    <div class="grid-lines"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <!-- Floating particles -->
    <div class="particles" id="particles"></div>

    <!-- Main card -->
    <div class="login-card" style="padding: 1px;">
        <div style="display:contents">

        <!-- Left Panel -->
        <div class="left-panel">

            <!-- Decorative rings -->
            <div class="ring ring-1"></div>
            <div class="ring ring-2"></div>
            <div class="ring ring-3"></div>

            <!-- Circuit lines -->
            <div class="circuit-line" style="width:60%; top:35%; left:0; animation-delay:0s;"></div>
            <div class="circuit-line" style="width:40%; top:55%; left:20%; animation-delay:1.2s;"></div>
            <div class="circuit-line" style="width:30%; top:70%; left:10%; animation-delay:0.6s;"></div>

            <!-- Brand -->
            <div class="brand-logo">
                <div class="brand-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                    </svg>
                </div>
                <div>
                    <div class="brand-name">Tech India</div>
                    <div class="brand-sub">Solutions</div>
                </div>
            </div>

            <!-- Headline -->
            <div class="left-headline">
                <h2>
                    Power Your<br>
                    <span class="gradient-text">Business</span><br>
                    Forward
                </h2>
                <p>Streamline operations, track growth and manage your entire business from one intelligent platform.</p>
            </div>

            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-value">99.9%</div>
                    <div class="stat-label">Uptime</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">256-bit</div>
                    <div class="stat-label">Encryption</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">24/7</div>
                    <div class="stat-label">Support</div>
                </div>
            </div>

            <!-- Features -->
            <div class="features">
                <div class="feature-item">
                    <div class="feature-dot blue">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#4a7fd4" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <span>Enterprise-grade security & compliance</span>
                </div>
                <div class="feature-item">
                    <div class="feature-dot purple">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#8b5cf6" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <span>Real-time analytics & insights</span>
                </div>
                <div class="feature-item">
                    <div class="feature-dot green">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#10b981" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    </div>
                    <span>Unified CRM, Inventory & Finance</span>
                </div>
            </div>
        </div>

        <!-- Right Panel -->
        <div class="right-panel" x-data="loginForm()">

            <!-- Form header -->
            <div class="form-header">
                <div class="eyebrow">
                    <div class="eyebrow-dot"></div>
                    <span>Secure Access</span>
                </div>
                <h1>Welcome<br>Back</h1>
                <p>Sign in to your Tech India Solutions admin panel</p>
            </div>

            <!-- Errors -->
            @if (session('error'))
                <div class="alert-error">
                    <p>{{ session('error') }}</p>
                </div>
            @endif
            @if ($errors->any())
                <div class="alert-error">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <!-- Form -->
            <form method="POST" action="{{ route('admin.signin') }}" @submit="loading = true">
                @csrf

                <!-- Email -->
                <div class="input-group">
                    <label class="input-label">Email Address</label>
                    <div class="input-wrap">
                        <div class="input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <input
                            id="email" name="email" type="email"
                            value="{{ old('email') }}" required autocomplete="email"
                            class="input-field"
                            placeholder="admin@techindia.com" />
                        <div class="focus-ring"></div>
                    </div>
                </div>

                <!-- Password -->
                <div class="input-group">
                    <label class="input-label">Password</label>
                    <div class="input-wrap">
                        <div class="input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <input
                            id="password" name="password"
                            :type="showPassword ? 'text' : 'password'"
                            required autocomplete="current-password"
                            class="input-field"
                            placeholder="••••••••••••" />
                        <button type="button" class="eye-btn" @click="showPassword = !showPassword">
                            <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg x-show="showPassword" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                        </button>
                        <div class="focus-ring"></div>
                    </div>
                </div>

                <!-- Meta row -->
                <div class="form-meta">
                    <label class="remember">
                        <input type="checkbox" name="remember" />
                        <span class="remember-label">Keep me signed in</span>
                    </label>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn-submit" :class="loading ? 'loading' : ''">
                    <span x-show="!loading">Sign In to Dashboard</span>
                    <span x-show="loading" style="display:none">Authenticating...</span>
                    <svg x-show="!loading" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    <svg x-show="loading" style="display:none;animation:spin 1s linear infinite" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </button>
            </form>

            <div class="divider">
                <div class="divider-line"></div>
                <span>Tech India Solutions © {{ date('Y') }}</span>
                <div class="divider-line"></div>
            </div>

            <div class="form-footer">
                <div class="shield-badge">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    Protected by 256-bit SSL encryption
                </div>
            </div>
        </div>

        </div>
    </div>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<script>
    function loginForm() {
        return { showPassword: false, loading: false }
    }

    // Generate floating particles
    (function() {
        const container = document.getElementById('particles');
        if (!container) return;
        const colors = ['rgba(18,46,109,0.8)', 'rgba(139,92,246,0.5)', 'rgba(16,185,129,0.4)', 'rgba(30,74,173,0.7)'];
        for (let i = 0; i < 30; i++) {
            const p = document.createElement('div');
            p.className = 'particle';
            const size = Math.random() * 3 + 1;
            p.style.cssText = `
                left: ${Math.random() * 100}%;
                width: ${size}px;
                height: ${size}px;
                background: ${colors[Math.floor(Math.random() * colors.length)]};
                animation-duration: ${Math.random() * 15 + 10}s;
                animation-delay: ${Math.random() * -20}s;
                border-radius: 50%;
            `;
            container.appendChild(p);
        }
    })();
</script>
</x-layout.auth>
