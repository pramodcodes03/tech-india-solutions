<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ucfirst(str_replace('-', ' ', $page)) }} - ALTechnics ERP Documentation</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Clean documentation styles - similar to Read the Docs / Gitbook */
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; }
        .doc-container { display: flex; min-height: 100vh; }
        .doc-sidebar { width: 280px; background: #1e293b; color: #e2e8f0; position: fixed; top: 0; bottom: 0; overflow-y: auto; padding: 20px 0; }
        .doc-sidebar h2 { color: #fff; font-size: 16px; padding: 0 20px; margin-bottom: 20px; }
        .doc-sidebar h2 span { color: #4361ee; }
        .doc-sidebar a { display: block; padding: 8px 20px; color: #94a3b8; text-decoration: none; font-size: 14px; transition: all 0.2s; }
        .doc-sidebar a:hover, .doc-sidebar a.active { color: #fff; background: rgba(67,97,238,0.15); border-right: 3px solid #4361ee; }
        .doc-main { margin-left: 280px; flex: 1; padding: 40px 60px; max-width: 900px; }
        .doc-main h1 { font-size: 32px; font-weight: 700; margin-bottom: 16px; color: #0f172a; }
        .doc-main h2 { font-size: 24px; font-weight: 600; margin-top: 32px; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #e2e8f0; }
        .doc-main h3 { font-size: 18px; font-weight: 600; margin-top: 24px; margin-bottom: 8px; }
        .doc-main p { line-height: 1.7; margin-bottom: 16px; }
        .doc-main ul, .doc-main ol { padding-left: 24px; margin-bottom: 16px; }
        .doc-main li { line-height: 1.7; margin-bottom: 4px; }
        .doc-main code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 13px; }
        .doc-main pre { background: #1e293b; color: #e2e8f0; padding: 16px; border-radius: 8px; overflow-x: auto; margin-bottom: 16px; }
        .doc-main pre code { background: transparent; padding: 0; color: inherit; }
        .doc-main table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .doc-main th, .doc-main td { border: 1px solid #e2e8f0; padding: 10px 14px; text-align: left; font-size: 14px; }
        .doc-main th { background: #f1f5f9; font-weight: 600; }
        .doc-main blockquote { border-left: 4px solid #4361ee; padding: 12px 16px; background: #eff6ff; margin-bottom: 16px; }
        .doc-search { padding: 12px 20px; }
        .doc-search input { width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #334155; background: #0f172a; color: #e2e8f0; font-size: 14px; }
        .doc-search input::placeholder { color: #64748b; }
        @media (max-width: 768px) {
            .doc-sidebar { width: 100%; position: static; }
            .doc-main { margin-left: 0; padding: 20px; }
            .doc-container { flex-direction: column; }
        }
    </style>
</head>
<body>
<div class="doc-container">
    <nav class="doc-sidebar">
        <h2><span>AL</span>Technics ERP</h2>
        <div class="doc-search">
            <input type="text" id="docSearch" placeholder="Search docs..." onkeyup="filterNav()">
        </div>
        @foreach($pages as $p)
            <a href="{{ route('documentation', $p['slug']) }}" class="{{ $page === $p['slug'] ? 'active' : '' }}" data-title="{{ strtolower($p['title']) }}">{{ $p['title'] }}</a>
        @endforeach
    </nav>
    <main class="doc-main">
        {!! $content !!}
    </main>
</div>
<script>
function filterNav() {
    const q = document.getElementById('docSearch').value.toLowerCase();
    document.querySelectorAll('.doc-sidebar a[data-title]').forEach(a => {
        a.style.display = a.dataset.title.includes(q) ? '' : 'none';
    });
}
</script>
</body>
</html>
