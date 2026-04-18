<!DOCTYPE html>
<html lang="en" x-data="{ darkMode: localStorage.getItem('docs_dark') === 'true' }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $current['title'] }} — ALTechnics ERP Docs</title>
    <link rel="icon" type="image/png" href="/favicon.ico" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --primary: #4361ee;
            --bg: #f8fafc;
            --bg-card: #ffffff;
            --bg-sidebar: #ffffff;
            --border: #e2e8f0;
            --text: #1e293b;
            --text-muted: #64748b;
            --text-light: #94a3b8;
        }
        .dark {
            --bg: #060818;
            --bg-card: #0e1726;
            --bg-sidebar: #0b1120;
            --border: #1f2d40;
            --text: #e2e8f0;
            --text-muted: #8892a4;
            --text-light: #4a5568;
        }
        html { scroll-behavior: smooth; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; -webkit-font-smoothing: antialiased; transition: background 0.2s, color 0.2s; }
        a { text-decoration: none; color: inherit; }

        /* ── Top Nav ── */
        .top-nav { position: sticky; top: 0; z-index: 50; background: rgba(255,255,255,0.92); backdrop-filter: blur(12px); border-bottom: 1px solid var(--border); }
        .dark .top-nav { background: rgba(11,17,32,0.92); }
        .nav-inner { max-width: 1400px; margin: 0 auto; padding: 0 20px; height: 58px; display: flex; align-items: center; gap: 12px; }
        .nav-brand { font-size: 15px; font-weight: 700; color: var(--text); display: flex; align-items: center; gap: 6px; white-space: nowrap; }
        .nav-sep { color: var(--text-light); font-size: 18px; font-weight: 300; }
        .nav-page { font-size: 14px; color: var(--text-muted); font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1; }
        .nav-actions { display: flex; align-items: center; gap: 8px; margin-left: auto; flex-shrink: 0; }
        .btn-icon { width: 34px; height: 34px; border-radius: 7px; border: 1px solid var(--border); background: var(--bg-card); display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-muted); transition: background 0.15s; }
        .btn-icon:hover { background: var(--border); }
        .back-link { font-size: 12px; font-weight: 600; color: var(--primary); border: 1px solid var(--primary); border-radius: 7px; padding: 5px 12px; display: none; }
        .back-link:hover { background: var(--primary); color: #fff; }
        @media (min-width: 640px) { .back-link { display: block; } }

        /* ── Layout ── */
        .layout { display: flex; max-width: 1400px; margin: 0 auto; }

        /* ── Sidebar ── */
        .sidebar { width: 260px; flex-shrink: 0; position: sticky; top: 58px; height: calc(100vh - 58px); overflow-y: auto; border-right: 1px solid var(--border); background: var(--bg-sidebar); padding: 20px 0 40px; display: none; }
        @media (min-width: 1024px) { .sidebar { display: block; } }
        .sidebar-search { padding: 0 14px 14px; }
        .sidebar-search input { width: 100%; padding: 7px 12px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg); color: var(--text); font-size: 12.5px; font-family: inherit; outline: none; }
        .sidebar-search input:focus { border-color: var(--primary); }
        .sidebar-search input::placeholder { color: var(--text-light); }
        .cat-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--text-light); padding: 10px 16px 4px; display: block; }
        .sidebar-link { display: flex; align-items: center; gap: 8px; padding: 7px 16px; font-size: 13px; color: var(--text-muted); transition: background 0.1s, color 0.1s; border-right: 3px solid transparent; cursor: pointer; }
        .sidebar-link:hover { background: rgba(67,97,238,0.07); color: var(--text); }
        .sidebar-link.active { background: rgba(67,97,238,0.1); color: var(--primary); border-right-color: var(--primary); font-weight: 600; }
        .sidebar-dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; opacity: 0.4; flex-shrink: 0; }
        .sidebar-link.active .sidebar-dot { opacity: 1; }

        /* ── Main Content ── */
        .content { flex: 1; min-width: 0; padding: 40px 40px 80px; max-width: 880px; }
        @media (max-width: 768px) { .content { padding: 24px 20px 60px; } }

        /* Breadcrumb */
        .breadcrumb { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-muted); margin-bottom: 20px; flex-wrap: wrap; }
        .breadcrumb a { color: var(--primary); font-weight: 500; }
        .breadcrumb a:hover { text-decoration: underline; }
        .breadcrumb-sep { color: var(--text-light); }

        /* Section Header */
        .section-header { margin-bottom: 32px; padding-bottom: 24px; border-bottom: 1px solid var(--border); }
        .section-cat { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--primary); margin-bottom: 8px; }
        .section-title { font-size: clamp(22px, 4vw, 32px); font-weight: 800; color: var(--text); line-height: 1.2; margin-bottom: 10px; }
        .section-summary { font-size: 15px; color: var(--text-muted); line-height: 1.65; }

        /* Topics */
        .topic { margin-bottom: 36px; }
        .topic-title { font-size: 17px; font-weight: 700; color: var(--text); margin-bottom: 12px; padding-bottom: 8px; border-bottom: 2px solid var(--border); display: flex; align-items: center; gap: 8px; }
        .topic-title::before { content: ''; display: inline-block; width: 3px; height: 18px; background: var(--primary); border-radius: 2px; flex-shrink: 0; }
        .topic-content { font-size: 14px; color: var(--text-muted); line-height: 1.7; margin-bottom: 12px; }

        /* List */
        .doc-list { list-style: none; padding: 0; margin: 10px 0 14px; }
        .doc-list li { display: flex; align-items: flex-start; gap: 8px; padding: 5px 0; font-size: 13.5px; color: var(--text-muted); line-height: 1.6; border-bottom: 1px dashed var(--border); }
        .doc-list li:last-child { border-bottom: none; }
        .doc-list li::before { content: '–'; color: var(--primary); font-weight: 700; flex-shrink: 0; margin-top: 1px; }

        /* Steps */
        .steps { counter-reset: step; padding: 0; margin: 10px 0 14px; }
        .steps li { display: flex; align-items: flex-start; gap: 12px; padding: 10px 14px; background: var(--bg); border: 1px solid var(--border); border-radius: 8px; margin-bottom: 6px; font-size: 13.5px; color: var(--text-muted); line-height: 1.6; list-style: none; }
        .dark .steps li { background: rgba(255,255,255,0.03); }
        .step-num { counter-increment: step; content: counter(step); width: 22px; height: 22px; border-radius: 50%; background: var(--primary); color: #fff; font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 1px; }

        /* Prerequisites */
        .prereqs { margin: 12px 0; }
        .prereqs-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-light); margin-bottom: 8px; }
        .prereq-tags { display: flex; flex-wrap: wrap; gap: 6px; }
        .prereq-tag { display: flex; align-items: center; gap: 5px; background: #fef3c7; color: #92400e; border-radius: 6px; padding: 4px 10px; font-size: 12px; font-weight: 600; }
        .dark .prereq-tag { background: rgba(245,158,11,0.15); color: #fbbf24; }
        .prereq-desc { font-size: 11px; font-weight: 400; color: #b45309; }
        .dark .prereq-desc { color: #d97706; }

        /* Tip / Warning */
        .callout { display: flex; gap: 12px; padding: 14px 16px; border-radius: 10px; margin: 14px 0; font-size: 13.5px; line-height: 1.6; }
        .callout-tip { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }
        .dark .callout-tip { background: rgba(67,97,238,0.1); border-color: rgba(67,97,238,0.3); color: #93c5fd; }
        .callout-warning { background: #fff7ed; border: 1px solid #fed7aa; color: #9a3412; }
        .dark .callout-warning { background: rgba(234,88,12,0.1); border-color: rgba(234,88,12,0.3); color: #fb923c; }
        .callout-icon { flex-shrink: 0; margin-top: 1px; }

        /* Nav prev/next */
        .page-nav { display: flex; justify-content: space-between; gap: 14px; margin-top: 56px; padding-top: 24px; border-top: 1px solid var(--border); }
        .page-nav-btn { display: flex; align-items: center; gap: 10px; padding: 14px 18px; border: 1px solid var(--border); border-radius: 12px; background: var(--bg-card); transition: border-color 0.15s, box-shadow 0.15s; flex: 1; max-width: 240px; }
        .page-nav-btn:hover { border-color: var(--primary); box-shadow: 0 4px 20px rgba(67,97,238,0.12); }
        .page-nav-btn.next { margin-left: auto; flex-direction: row-reverse; text-align: right; }
        .page-nav-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.8px; font-weight: 700; color: var(--text-light); margin-bottom: 2px; }
        .page-nav-title { font-size: 13px; font-weight: 600; color: var(--text); }
        .page-nav-arrow { color: var(--primary); flex-shrink: 0; }

        /* Right TOC */
        .toc { width: 220px; flex-shrink: 0; position: sticky; top: 58px; height: calc(100vh - 58px); overflow-y: auto; padding: 24px 16px 40px; display: none; }
        @media (min-width: 1280px) { .toc { display: block; } }
        .toc-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--text-light); margin-bottom: 12px; }
        .toc-link { display: block; font-size: 12.5px; color: var(--text-muted); padding: 4px 0 4px 10px; border-left: 2px solid var(--border); margin-bottom: 2px; transition: color 0.1s, border-color 0.1s; }
        .toc-link:hover, .toc-link.active { color: var(--primary); border-left-color: var(--primary); }

        [x-cloak] { display: none !important; }
    </style>
</head>
<body x-data="sectionPage">

    {{-- ── Top Nav ── --}}
    <header class="top-nav">
        <div class="nav-inner">
            <a href="{{ route('documentation.index') }}" class="nav-brand">
                <span style="width:8px;height:8px;background:#4361ee;border-radius:50%;display:inline-block;"></span>
                ALTechnics ERP
            </a>
            <span class="nav-sep">/</span>
            <span class="nav-page">{{ $current['title'] }}</span>
            <div class="nav-actions">
                <button @click="darkMode = !darkMode; localStorage.setItem('docs_dark', darkMode)" class="btn-icon">
                    <svg x-show="!darkMode" style="width:15px;height:15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    <svg x-show="darkMode" x-cloak style="width:15px;height:15px;color:#fbbf24;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </button>
                <a href="{{ route('documentation.index') }}" class="back-link">← All Docs</a>
            </div>
        </div>
    </header>

    <div class="layout">

        {{-- ── Left Sidebar ── --}}
        <aside class="sidebar">
            <div class="sidebar-search">
                <input type="text" id="sidebarFilter" placeholder="Filter…" oninput="filterSidebar(this.value)" autocomplete="off" />
            </div>
            @foreach($categories as $catName => $catSections)
            <span class="cat-label">{{ $catName }}</span>
            @foreach($catSections as $slug => $sec)
            <a href="{{ route('documentation.section', $slug) }}"
               class="sidebar-link {{ $slug === $current['slug'] ? 'active' : '' }}"
               data-title="{{ strtolower($sec['title']) }}">
                <span class="sidebar-dot"></span>
                {{ $sec['title'] }}
            </a>
            @endforeach
            @endforeach
        </aside>

        {{-- ── Content ── --}}
        <main class="content">

            {{-- Breadcrumb --}}
            <div class="breadcrumb">
                <a href="{{ route('documentation.index') }}">Docs</a>
                <span class="breadcrumb-sep">/</span>
                <span>{{ $current['category'] }}</span>
                <span class="breadcrumb-sep">/</span>
                <span style="color:var(--text);font-weight:600;">{{ $current['title'] }}</span>
            </div>

            {{-- Section Header --}}
            <div class="section-header">
                <div class="section-cat">{{ $current['category'] }}</div>
                <h1 class="section-title">{{ $current['title'] }}</h1>
                <p class="section-summary">{{ $current['summary'] }}</p>
            </div>

            {{-- Topics --}}
            @foreach($current['topics'] ?? [] as $idx => $topic)
            @php $topicId = 'topic-' . $idx; @endphp
            <div class="topic" id="{{ $topicId }}">
                <h2 class="topic-title">{{ $topic['title'] }}</h2>

                @if(!empty($topic['content']))
                    <p class="topic-content">{{ $topic['content'] }}</p>
                @endif

                @if(!empty($topic['list']))
                    <ul class="doc-list">
                        @foreach($topic['list'] as $item)
                        <li><span>{{ $item }}</span></li>
                        @endforeach
                    </ul>
                @endif

                @if(!empty($topic['steps']))
                    <ol class="steps">
                        @foreach($topic['steps'] as $step)
                        <li><span class="step-num">{{ $loop->iteration }}</span><span>{{ $step }}</span></li>
                        @endforeach
                    </ol>
                @endif

                @if(!empty($topic['prerequisites']))
                    <div class="prereqs">
                        <div class="prereqs-title">Prerequisites</div>
                        <div class="prereq-tags">
                            @foreach($topic['prerequisites'] as $prereq)
                            <span class="prereq-tag">
                                <svg style="width:11px;height:11px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
                                {{ $prereq['label'] }}
                                @if(!empty($prereq['description']))
                                <span class="prereq-desc">— {{ $prereq['description'] }}</span>
                                @endif
                            </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(!empty($topic['tip']))
                    <div class="callout callout-tip">
                        <svg class="callout-icon" style="width:18px;height:18px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span><strong>Tip:</strong> {{ $topic['tip'] }}</span>
                    </div>
                @endif

                @if(!empty($topic['warning']))
                    <div class="callout callout-warning">
                        <svg class="callout-icon" style="width:18px;height:18px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span><strong>Warning:</strong> {{ $topic['warning'] }}</span>
                    </div>
                @endif
            </div>
            @endforeach

            {{-- Prev / Next navigation --}}
            @php
                $allSlugs = array_keys($sections);
                $currentIdx = array_search($current['slug'], $allSlugs);
                $prevSlug = $currentIdx > 0 ? $allSlugs[$currentIdx - 1] : null;
                $nextSlug = $currentIdx < count($allSlugs) - 1 ? $allSlugs[$currentIdx + 1] : null;
                $prevSec = $prevSlug ? $sections[$prevSlug] : null;
                $nextSec = $nextSlug ? $sections[$nextSlug] : null;
            @endphp
            <div class="page-nav">
                @if($prevSlug)
                <a href="{{ route('documentation.section', $prevSlug) }}" class="page-nav-btn">
                    <span class="page-nav-arrow">
                        <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M15 19l-7-7 7-7"/></svg>
                    </span>
                    <div>
                        <div class="page-nav-label">Previous</div>
                        <div class="page-nav-title">{{ $prevSec['title'] }}</div>
                    </div>
                </a>
                @else
                <div></div>
                @endif

                @if($nextSlug)
                <a href="{{ route('documentation.section', $nextSlug) }}" class="page-nav-btn next">
                    <span class="page-nav-arrow">
                        <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 5l7 7-7 7"/></svg>
                    </span>
                    <div>
                        <div class="page-nav-label">Next</div>
                        <div class="page-nav-title">{{ $nextSec['title'] }}</div>
                    </div>
                </a>
                @endif
            </div>

        </main>

        {{-- ── Right TOC ── --}}
        <aside class="toc">
            <div class="toc-title">On this page</div>
            @foreach($current['topics'] ?? [] as $idx => $topic)
            <a href="#topic-{{ $idx }}" class="toc-link" :class="{ active: activeTopic === 'topic-{{ $idx }}' }">
                {{ $topic['title'] }}
            </a>
            @endforeach
        </aside>

    </div>

    <script>
    function filterSidebar(val) {
        const q = val.toLowerCase().trim();
        // Show/hide links
        document.querySelectorAll('.sidebar-link').forEach(a => {
            a.style.display = (!q || a.dataset.title.includes(q)) ? '' : 'none';
        });
        // Hide category labels that have no visible links
        document.querySelectorAll('.cat-label').forEach(label => {
            let next = label.nextElementSibling;
            let hasVisible = false;
            while (next && !next.classList.contains('cat-label')) {
                if (next.classList.contains('sidebar-link') && next.style.display !== 'none') {
                    hasVisible = true;
                    break;
                }
                next = next.nextElementSibling;
            }
            label.style.display = hasVisible || !q ? '' : 'none';
        });
    }

    document.addEventListener('alpine:init', () => {
        Alpine.data('sectionPage', () => ({
            activeTopic: '',
            init() {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(e => { if (e.isIntersecting) this.activeTopic = e.target.id; });
                }, { rootMargin: '-20% 0px -70% 0px' });
                document.querySelectorAll('.topic[id]').forEach(el => observer.observe(el));

                const active = document.querySelector('.sidebar-link.active');
                if (active) active.scrollIntoView({ block: 'center', behavior: 'smooth' });
            }
        }));
    });
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
