<!DOCTYPE html>
<html lang="en" x-data="{ darkMode: localStorage.getItem('docs_dark') === 'true' }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Documentation — ALTechnics ERP</title>
    <meta name="description" content="Complete documentation for ALTechnics ERP — open-source Laravel business management system." />
    <link rel="icon" type="image/png" href="/favicon.ico" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --primary: #4361ee;
            --primary-dark: #3651d4;
            --bg: #f8fafc;
            --bg-card: #ffffff;
            --border: #e2e8f0;
            --text: #1e293b;
            --text-muted: #64748b;
            --text-light: #94a3b8;
        }
        .dark {
            --bg: #060818;
            --bg-card: #0e1726;
            --border: #1f2d40;
            --text: #e2e8f0;
            --text-muted: #8892a4;
            --text-light: #4a5568;
        }
        html { scroll-behavior: smooth; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; -webkit-font-smoothing: antialiased; transition: background 0.2s, color 0.2s; }
        a { text-decoration: none; color: inherit; }

        /* ── Nav ── */
        .top-nav { position: sticky; top: 0; z-index: 50; background: rgba(255,255,255,0.92); backdrop-filter: blur(12px); border-bottom: 1px solid var(--border); transition: background 0.2s; }
        .dark .top-nav { background: rgba(14,23,38,0.92); }
        .nav-inner { max-width: 1280px; margin: 0 auto; padding: 0 24px; height: 64px; display: flex; align-items: center; justify-content: space-between; }
        .nav-brand { display: flex; align-items: center; gap: 10px; font-size: 17px; font-weight: 700; color: var(--text); }
        .nav-brand-dot { width: 9px; height: 9px; background: var(--primary); border-radius: 50%; display: inline-block; }
        .nav-actions { display: flex; align-items: center; gap: 10px; }
        .btn-icon { width: 36px; height: 36px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-card); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.15s; color: var(--text-muted); }
        .btn-icon:hover { background: var(--border); }
        .nav-link { font-size: 13px; font-weight: 600; color: var(--primary); padding: 6px 14px; border-radius: 7px; border: 1px solid var(--primary); transition: background 0.15s; display: none; }
        .nav-link:hover { background: var(--primary); color: #fff; }
        @media (min-width: 640px) { .nav-link { display: block; } }

        /* ── Hero ── */
        .hero { background: linear-gradient(135deg, #4361ee 0%, #3b5de7 40%, #2196f3 100%); padding: 64px 24px 72px; text-align: center; position: relative; overflow: hidden; }
        .dark .hero { background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #1d4ed8 100%); }
        .hero-blob { position: absolute; border-radius: 50%; background: rgba(255,255,255,0.06); pointer-events: none; }
        .hero-blob-1 { width: 500px; height: 500px; top: -200px; right: -100px; }
        .hero-blob-2 { width: 300px; height: 300px; bottom: -100px; left: -50px; }
        .hero-inner { position: relative; z-index: 1; max-width: 680px; margin: 0 auto; }
        .hero-badge { display: inline-flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.15); backdrop-filter: blur(4px); border-radius: 100px; padding: 5px 14px; font-size: 12px; font-weight: 600; color: rgba(255,255,255,0.9); margin-bottom: 20px; letter-spacing: 0.3px; }
        .hero h1 { font-size: clamp(28px, 5vw, 44px); font-weight: 800; color: #fff; margin-bottom: 14px; line-height: 1.15; }
        .hero p { font-size: 16px; color: rgba(255,255,255,0.8); margin-bottom: 32px; line-height: 1.65; }
        .search-wrap { position: relative; max-width: 520px; margin: 0 auto; }
        .search-input { width: 100%; padding: 14px 44px 14px 48px; border: none; border-radius: 14px; font-size: 15px; background: #fff; color: #1e293b; box-shadow: 0 8px 40px rgba(0,0,0,0.18); outline: none; font-family: inherit; }
        .dark .search-input { background: #1b2e4b; color: #e2e8f0; }
        .search-input::placeholder { color: #94a3b8; }
        .search-icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; }
        .search-clear { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #94a3b8; padding: 4px; display: flex; }
        .search-clear:hover { color: #4361ee; }
        .search-results { position: absolute; left: 0; right: 0; top: calc(100% + 8px); background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 20px 60px rgba(0,0,0,0.15); overflow: hidden; max-height: 360px; overflow-y: auto; z-index: 100; }
        .dark .search-results { background: #1b2e4b; border-color: #1f2d40; }
        .search-results-header { padding: 8px 16px; background: #f8fafc; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; border-bottom: 1px solid #e2e8f0; }
        .dark .search-results-header { background: #0e1726; border-color: #1f2d40; }
        .search-result-item { display: flex; align-items: center; gap: 12px; padding: 11px 16px; border-bottom: 1px solid #f1f5f9; transition: background 0.1s; cursor: pointer; }
        .dark .search-result-item { border-color: rgba(255,255,255,0.05); }
        .search-result-item:last-child { border-bottom: none; }
        .search-result-item:hover { background: #eff6ff; }
        .dark .search-result-item:hover { background: rgba(67,97,238,0.12); }
        .search-result-icon { width: 34px; height: 34px; border-radius: 8px; background: #dbeafe; display: flex; align-items: center; justify-content: center; shrink: 0; flex-shrink: 0; }
        .dark .search-result-icon { background: rgba(67,97,238,0.2); }
        .search-result-title { font-size: 13px; font-weight: 600; color: var(--text); }
        .search-result-section { font-size: 11px; color: var(--text-muted); margin-top: 1px; }
        .search-no-result { padding: 24px; text-align: center; font-size: 13px; color: var(--text-muted); }

        /* ── Main ── */
        .main { max-width: 1280px; margin: 0 auto; padding: 40px 24px 80px; }

        /* ── Quick Links ── */
        .quick-links { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 48px; }
        @media (min-width: 768px) { .quick-links { grid-template-columns: repeat(4, 1fr); } }
        .quick-link { display: flex; align-items: center; gap: 12px; padding: 16px; border: 1px solid var(--border); border-radius: 12px; background: var(--bg-card); transition: box-shadow 0.15s, border-color 0.15s, transform 0.15s; }
        .quick-link:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.08); border-color: #93c5fd; transform: translateY(-1px); }
        .dark .quick-link:hover { border-color: #1e40af; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
        .quick-link-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .quick-link-title { font-size: 13px; font-weight: 700; color: var(--text); }
        .quick-link-desc { font-size: 11px; color: var(--text-muted); margin-top: 1px; }

        /* ── Category Sections ── */
        .cat-section { margin-bottom: 48px; }
        .cat-header { display: flex; align-items: center; gap: 10px; margin-bottom: 18px; }
        .cat-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .cat-title { font-size: 18px; font-weight: 800; color: var(--text); }
        .cat-count { font-size: 11px; font-weight: 600; background: var(--border); color: var(--text-muted); border-radius: 100px; padding: 2px 9px; }
        .cards-grid { display: grid; grid-template-columns: 1fr; gap: 14px; }
        @media (min-width: 640px) { .cards-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (min-width: 1024px) { .cards-grid { grid-template-columns: repeat(3, 1fr); } }
        .doc-card { display: flex; align-items: flex-start; gap: 14px; padding: 20px; border: 1px solid var(--border); border-radius: 14px; background: var(--bg-card); transition: box-shadow 0.15s, border-color 0.15s, transform 0.15s; }
        .doc-card:hover { box-shadow: 0 6px 30px rgba(0,0,0,0.09); border-color: #93c5fd; transform: translateY(-2px); }
        .dark .doc-card:hover { border-color: #1e3a8a; box-shadow: 0 6px 30px rgba(0,0,0,0.35); }
        .card-icon { width: 42px; height: 42px; border-radius: 10px; background: #eff6ff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: background 0.15s; }
        .dark .card-icon { background: rgba(67,97,238,0.15); }
        .doc-card:hover .card-icon { background: #4361ee; }
        .card-icon svg { transition: color 0.15s; }
        .doc-card:hover .card-icon svg { color: #fff !important; }
        .card-body { flex: 1; min-width: 0; }
        .card-title { font-size: 14px; font-weight: 700; color: var(--text); margin-bottom: 4px; transition: color 0.15s; }
        .doc-card:hover .card-title { color: #4361ee; }
        .card-summary { font-size: 12.5px; color: var(--text-muted); line-height: 1.6; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .card-footer { display: flex; align-items: center; gap: 6px; margin-top: 10px; }
        .card-topics { font-size: 11px; color: var(--text-light); }
        .card-read { font-size: 11px; font-weight: 600; color: #4361ee; opacity: 0; transition: opacity 0.15s; display: flex; align-items: center; gap: 2px; margin-left: auto; }
        .doc-card:hover .card-read { opacity: 1; }

        /* ── Footer ── */
        .doc-footer { border-top: 1px solid var(--border); background: var(--bg-card); padding: 28px 24px; text-align: center; font-size: 13px; color: var(--text-muted); }
        .doc-footer a { color: var(--primary); font-weight: 600; }
        .doc-footer a:hover { text-decoration: underline; }

        /* ── Utilities ── */
        [x-cloak] { display: none !important; }
    </style>
</head>
<body x-data="docsHome">

    {{-- ── Top Nav ── --}}
    <header class="top-nav">
        <div class="nav-inner">
            <a href="{{ route('documentation.index') }}" class="nav-brand">
                <span class="nav-brand-dot"></span>
                ALTechnics ERP
                <span style="font-weight:400; color: var(--text-muted); font-size:13px; margin-left:4px;">Docs</span>
            </a>
            <div class="nav-actions">
                <button @click="darkMode = !darkMode; localStorage.setItem('docs_dark', darkMode)" class="btn-icon" title="Toggle dark mode">
                    <svg x-show="!darkMode" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    <svg x-show="darkMode" x-cloak style="width:16px;height:16px;color:#fbbf24;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </button>
                <a href="{{ route('admin.login') }}" class="nav-link">Admin Portal →</a>
            </div>
        </div>
    </header>

    {{-- ── Hero ── --}}
    <section class="hero">
        <div class="hero-blob hero-blob-1"></div>
        <div class="hero-blob hero-blob-2"></div>
        <div class="hero-inner">
            <h1>ERP Documentation</h1>
            <p>Everything you need to install, configure, and use ALTechnics ERP. Step-by-step guides, module overviews, API references, and troubleshooting.</p>

            {{-- Search --}}
            <div class="search-wrap">
                <svg class="search-icon" style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input class="search-input" type="text" placeholder="Search docs… (e.g. quotation, inventory, roles)"
                    x-model="q" @input.debounce.280ms="search()" @keydown.escape="clear()" autocomplete="off" />
                <button x-show="q" @click="clear()" class="search-clear">
                    <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                <div x-show="results.length > 0" x-transition.opacity class="search-results" @click.outside="clear()">
                    <div class="search-results-header" x-text="results.length + ' results'"></div>
                    <template x-for="r in results" :key="r.slug+'|'+r.title">
                        <a :href="`{{ url('documentation') }}/${r.slug}`" class="search-result-item">
                            <div class="search-result-icon">
                                <svg style="width:15px;height:15px;color:#4361ee;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <div>
                                <div class="search-result-title" x-text="r.title"></div>
                                <div class="search-result-section" x-text="r.section"></div>
                            </div>
                            <svg style="width:14px;height:14px;color:#cbd5e1;margin-left:auto;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m9 5 7 7-7 7"/></svg>
                        </a>
                    </template>
                </div>
                <div x-show="q.length >= 2 && results.length === 0 && done" x-transition.opacity class="search-results">
                    <div class="search-no-result">No results for "<span x-text="q" style="font-weight:600;"></span>"</div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── Main Content ── --}}
    <main class="main">

        {{-- Quick Links --}}
        @php
            $quickLinks = [
                ['slug' => 'first-time-setup', 'title' => 'First-Time Setup',  'desc' => 'Get up and running',  'bg' => '#d1fae5', 'text' => '#065f46', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'],
                ['slug' => 'quotations',        'title' => 'Quotations',        'desc' => 'Create & send quotes', 'bg' => '#dbeafe', 'text' => '#1e40af', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                ['slug' => 'inventory',         'title' => 'Inventory',         'desc' => 'Track stock levels',  'bg' => '#fef3c7', 'text' => '#92400e', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                ['slug' => 'troubleshooting',   'title' => 'Troubleshooting',   'desc' => 'Common questions',    'bg' => '#fee2e2', 'text' => '#991b1b', 'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
            ];
        @endphp
        <div class="quick-links">
            @foreach($quickLinks as $ql)
            <a href="{{ route('documentation.section', $ql['slug']) }}" class="quick-link">
                <div class="quick-link-icon" style="background: {{ $ql['bg'] }};" x-data x-bind:style="$el.closest('.quick-link').matches(':hover') ? 'background: {{ $ql['text'] }}' : 'background: {{ $ql['bg'] }}'">
                    <svg style="width:18px;height:18px;color:{{ $ql['text'] }};" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="{{ $ql['icon'] }}"/></svg>
                </div>
                <div>
                    <div class="quick-link-title">{{ $ql['title'] }}</div>
                    <div class="quick-link-desc">{{ $ql['desc'] }}</div>
                </div>
            </a>
            @endforeach
        </div>

        {{-- Category Sections --}}
        @php
            $catMeta = [
                'Getting Started'     => ['bg' => '#d1fae5', 'text' => '#065f46', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'],
                'CRM'                 => ['bg' => '#f0fdf4', 'text' => '#166534', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                'Sales'               => ['bg' => '#eff6ff', 'text' => '#1d4ed8', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
                'Purchasing'          => ['bg' => '#fff7ed', 'text' => '#9a3412', 'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z'],
                'Products & Inventory'=> ['bg' => '#faf5ff', 'text' => '#6b21a8', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                'Service'             => ['bg' => '#fef2f2', 'text' => '#991b1b', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
                'Reports'             => ['bg' => '#ecfdf5', 'text' => '#065f46', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                'Admin & Settings'    => ['bg' => '#f0f9ff', 'text' => '#0369a1', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
                'Help'                => ['bg' => '#fff1f2', 'text' => '#9f1239', 'icon' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            ];
        @endphp

        @foreach($categories as $catName => $catSections)
        @php $cm = $catMeta[$catName] ?? ['bg' => '#f1f5f9', 'text' => '#475569', 'dbg' => '#f1f5f9', 'icon' => '']; @endphp
        <div class="cat-section">
            <div class="cat-header">
                <div class="cat-icon" style="background: {{ $cm['bg'] }};">
                    <svg style="width:15px;height:15px;color:{{ $cm['text'] }};" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="{{ $cm['icon'] }}"/></svg>
                </div>
                <h2 class="cat-title">{{ $catName }}</h2>
                <span class="cat-count">{{ count($catSections) }}</span>
            </div>

            <div class="cards-grid">
                @foreach($catSections as $slug => $sec)
                <a href="{{ route('documentation.section', $slug) }}" class="doc-card">
                    <div class="card-icon">
                        <svg style="width:20px;height:20px;color:#4361ee;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            @switch($sec['icon'])
                                @case('home') <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/> @break
                                @case('rocket') <path d="M13 10V3L4 14h7v7l9-11h-7z"/> @break
                                @case('sitemap') <path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/> @break
                                @case('users') <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/> @break
                                @case('briefcase') <path d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/> @break
                                @case('document') <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/> @break
                                @case('shopping-cart') <path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/> @break
                                @case('receipt') <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/> @break
                                @case('wallet') <path d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/> @break
                                @case('truck') <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/> @break
                                @case('clipboard') <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/> @break
                                @case('package') <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/> @break
                                @case('warehouse') <path d="M3 12l9-9 9 9M5 10v9a1 1 0 001 1h4v-5h4v5h4a1 1 0 001-1v-9"/> @break
                                @case('wrench') <path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/> @break
                                @case('chart') <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/> @break
                                @case('shield') <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/> @break
                                @case('user-shield') <path d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/> @break
                                @case('cog') <path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/> @break
                                @case('code') <path d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/> @break
                                @case('database') <path d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/> @break
                                @case('bug') <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/> @break
                                @default <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            @endswitch
                        </svg>
                    </div>
                    <div class="card-body">
                        <div class="card-title">{{ $sec['title'] }}</div>
                        <div class="card-summary">{{ $sec['summary'] }}</div>
                        <div class="card-footer">
                            <span class="card-topics">{{ count($sec['topics'] ?? []) }} topics</span>
                            <span style="color:var(--text-light);">&middot;</span>
                            <span class="card-read">Read <svg style="width:10px;height:10px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="m9 5 7 7-7 7"/></svg></span>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endforeach

    </main>

    {{-- Footer --}}

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        Alpine.data('docsHome', () => ({
            q: '', results: [], done: false,
            async search() {
                const v = this.q.trim();
                if (v.length < 2) { this.results = []; this.done = false; return; }
                const res = await fetch(`{{ route('documentation.search') }}?q=${encodeURIComponent(v)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                this.results = data.results;
                this.done = true;
            },
            clear() { this.q = ''; this.results = []; this.done = false; }
        }));
    });
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
