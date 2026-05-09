#!/usr/bin/env python3
"""
Transform all species HTML pages in the ptice plugin to match the
Crvendac (erithacus-rubecula) reference structure:
- remove duplicate old <nav class="topnav">...</nav>
- replace <div class="card merlin-cta">...</div> with visitor-strip + monitoring-strip + bird-footer-nav
- inject required CSS rules (visitor-strip, crv-monitoring-strip, bird-guide-bottom, bird-footer-nav)
- footer prev/next chained alphabetically by Serbian display name
"""
import os
import re
import sys

VIEWS_DIR = "/home/user/tasa/ptice_plugin/views"

SKIP_SLUGS = {
    "index", "archive", "ptice",
    "fotografisanje-ptica", "ponasanje-prema-pticama",
    "kviz-prepoznavanje-ptica-rajac", "merlin-ptice-rajac",
    "monitoring-ptica-pio-rajac", "kucice-za-ptice",
}

# CSS rules that the reference (Crvendac) has and are used by the new sections.
CSS_INJECT = """
/* RAJAC-DS injected sections (visitor-strip, monitoring-strip, footer-nav) */
.visitor-strip{margin:28px 0 0;padding:0 0 4px}
.visitor-strip-inner{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
.vs-card{display:flex;align-items:center;gap:14px;padding:16px 18px;background:#fff;border:1px solid var(--line);border-radius:14px;box-shadow:0 4px 16px rgba(18,28,22,.06);color:var(--text);transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease;text-decoration:none}
.vs-card:hover{transform:translateY(-2px);box-shadow:0 10px 26px rgba(18,28,22,.10);border-color:rgba(200,168,75,.50)}
.vs-icon{font-size:28px;flex:0 0 auto;width:46px;height:46px;border-radius:12px;background:linear-gradient(135deg,#eef5fb,#fff8e9);border:1px solid #e4d9c8;display:grid;place-items:center}
.vs-text{flex:1;min-width:0}
.vs-text strong{display:block;font-family:var(--serif);font-weight:400;font-size:1.05rem;color:#10213a;line-height:1.15;margin-bottom:3px}
.vs-text span{display:block;font-size:14px;color:var(--muted);line-height:1.35}
.vs-arrow{flex:0 0 auto;color:#c8a84b;font-size:1.1rem;font-weight:700}
@media(max-width:1020px){.visitor-strip-inner{grid-template-columns:repeat(2,1fr)}}
@media(max-width:820px){.visitor-strip-inner{grid-template-columns:1fr}}
.crv-monitoring-strip{display:flex;align-items:center;justify-content:center;width:100%;min-height:44px;max-height:56px;margin:14px 0 0;padding:8px 16px;border-radius:12px;background:linear-gradient(90deg,#0d2748 0%,#173d2b 62%,#c8a84b 100%);color:#fff!important;text-decoration:none!important;box-shadow:0 10px 24px rgba(18,28,22,.10);overflow:hidden}
.crv-monitoring-strip:hover{filter:brightness(1.05)}
.crv-monitoring-strip span{display:inline-flex;align-items:center;gap:10px;max-width:100%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-family:var(--serif);font-size:18px!important;line-height:1.15!important;color:#fff!important}
.crv-monitoring-strip strong{font-weight:600;color:#f3d785}.crv-monitoring-strip i{font-style:normal;opacity:.88}
@media(max-width:640px){.crv-monitoring-strip{min-height:46px;padding:8px 12px}.crv-monitoring-strip span{font-size:16px!important}}
"""

VISITOR_STRIP_HTML = """<section class="visitor-strip" aria-label="Водичи и алати за посетиоце">
<div class="visitor-strip-inner">
<a class="vs-card" href="https://piorajac.rs/ptice/merlin-ptice-rajac/">
  <div class="vs-icon">📱</div>
  <div class="vs-text"><strong>Мерлин и eBird</strong><span>Снимите налаз и пошаљите управљачу</span></div>
  <span class="vs-arrow">→</span>
</a>
<a class="vs-card" href="https://piorajac.rs/ptice/fotografisanje-ptica/">
  <div class="vs-icon">📷</div>
  <div class="vs-text"><strong>Фотографисање птица</strong><span>Теренски савети и етика снимања</span></div>
  <span class="vs-arrow">→</span>
</a>
<a class="vs-card" href="https://piorajac.rs/ptice/ponasanje-prema-pticama/">
  <div class="vs-icon">🐦</div>
  <div class="vs-text"><strong>Понашање током године</strong><span>Кодекс посетилаца и правила</span></div>
  <span class="vs-arrow">→</span>
</a>
<a class="vs-card" href="https://piorajac.rs/ptice/kviz-prepoznavanje-ptica-rajac/">
  <div class="vs-icon">🎧</div>
  <div class="vs-text"><strong>Квиз препознавања птица</strong><span>Пустите песму и погодите врсту</span></div>
  <span class="vs-arrow">→</span>
</a>
</div>
</section>
<a class="crv-monitoring-strip" href="/ptice/monitoring-ptica-pio-rajac/" aria-label="Мониторинг птица у ПИО Рајац"><span>📊 <strong>Мониторинг птица у ПИО Рајац</strong><i>— најновије eBird чеклисте и архива налаза</i> →</span></a>
"""

def extract_title(html):
    m = re.search(r"<title>([^<]+?)\s*—\s*Птице ПИО Рајац</title>", html)
    return m.group(1).strip() if m else ""

def build_footer(slug, prev_slug, prev_name, next_slug, next_name):
    return (
        f'<footer class="bird-footer-nav" aria-label="Навигација између птица" '
        f'data-nav-source="database" data-current-slug="{slug}">'
        f'<a class="bird-footlink bird-footlink-prev" href="/ptice/{prev_slug}/" data-prev-slug="{prev_slug}">'
        f'<span class="bird-foot-arrow" aria-hidden="true">←</span>'
        f'<span><em>ПРЕТХОДНА ПТИЦА</em><strong>{prev_name}</strong></span></a>'
        f'<a class="bird-footlink bird-footlink-next" href="/ptice/{next_slug}/" data-next-slug="{next_slug}">'
        f'<span><em>СЛЕДЕЋА ПТИЦА</em><strong>{next_name}</strong></span>'
        f'<span class="bird-foot-arrow" aria-hidden="true">→</span></a></footer>'
    )

def transform(path, prev_slug, prev_name, next_slug, next_name, slug):
    with open(path, "r", encoding="utf-8") as f:
        html = f.read()

    original = html

    # 1) Remove the old duplicate <nav class="topnav">...</nav> (single line)
    html = re.sub(
        r'<nav class="topnav">.*?</nav>\s*\n?',
        '', html, flags=re.DOTALL)

    # 2) Remove existing merlin-cta block (anywhere in body)
    # Match <div class="card merlin-cta">...</div> taking care of nesting - simple greedy approach since the block is well-formed
    html = re.sub(
        r'<div class="card merlin-cta">.*?</div></div>\s*\n?',
        '', html, flags=re.DOTALL)

    # 2b) Remove any pre-existing <footer class="bird-footer-nav">...</footer>
    html = re.sub(
        r'<footer class="bird-footer-nav".*?</footer>\s*\n?',
        '', html, flags=re.DOTALL)

    # 2c) Remove any pre-existing visitor-strip / crv-monitoring-strip we may have added before
    html = re.sub(
        r'<section class="visitor-strip".*?</section>\s*\n?',
        '', html, flags=re.DOTALL)
    html = re.sub(
        r'<a class="crv-monitoring-strip".*?</a>\s*\n?',
        '', html, flags=re.DOTALL)

    # 3) Inject CSS just before </style> of the FIRST style block
    if 'visitor-strip-inner' not in html:
        html = re.sub(
            r'</style>',
            CSS_INJECT + '</style>',
            html, count=1)

    # 4) Insert visitor-strip + monitoring-strip + footer-nav before </main>
    footer_html = build_footer(slug, prev_slug, prev_name, next_slug, next_name)
    insert_block = VISITOR_STRIP_HTML + footer_html + "\n"

    html = re.sub(
        r'(\s*)</main>',
        lambda m: '\n' + insert_block + m.group(1) + '</main>',
        html, count=1)

    if html != original:
        with open(path, "w", encoding="utf-8") as f:
            f.write(html)
        return True
    return False

def main():
    files = []
    for fn in sorted(os.listdir(VIEWS_DIR)):
        if not fn.endswith(".html"):
            continue
        slug = fn[:-5]
        if slug in SKIP_SLUGS:
            continue
        path = os.path.join(VIEWS_DIR, fn)
        with open(path, "r", encoding="utf-8") as f:
            title = extract_title(f.read())
        files.append((slug, title, path))

    # Sort by Serbian name
    files.sort(key=lambda x: x[1])

    n = len(files)
    changed = 0
    for i, (slug, title, path) in enumerate(files):
        prev_slug, prev_name, _ = files[(i - 1) % n]
        next_slug, next_name, _ = files[(i + 1) % n]
        if transform(path, prev_slug, prev_name, next_slug, next_name, slug):
            changed += 1
            print(f"  ✓ {slug} ({title})")

    print(f"\nProcessed {n} pages, modified {changed}.")

if __name__ == "__main__":
    main()
