#!/usr/bin/env python3
"""Validate the website copied to docs/ for GitHub Pages."""
from __future__ import annotations

import re
import sys
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import unquote, urlsplit

ROOT = Path("docs")
HTML = ROOT / "index.html"
EXPECTED = [
    "index.html", "styles.css", "app.js", "i18n.js",
    "translations.js", "preferences.js",
    "assets/favicon.svg", "assets/lio.svg", ".nojekyll",
]
errors: list[str] = []
warnings: list[str] = []
all_links: list[tuple[str, str, Path]] = []
ids: set[str] = set()
external: set[str] = set()

class PageParser(HTMLParser):
    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if attrs.get("id"):
            ids.add(attrs["id"])
        for attr in ("href", "src", "poster"):
            if attrs.get(attr):
                all_links.append((tag, attrs[attr], HTML))
        if attrs.get("srcset"):
            for part in attrs["srcset"].split(","):
                all_links.append((tag, part.strip().split(" ")[0], HTML))

for name in EXPECTED:
    path = ROOT / name
    if not path.exists():
        errors.append(f"Required file not found: {path}")
    elif path.name != ".nojekyll" and path.stat().st_size == 0:
        errors.append(f"Required file is empty: {path}")

if not HTML.exists():
    print("\n".join(errors))
    sys.exit(1)

html = HTML.read_text(encoding="utf-8")
parser = PageParser()
parser.feed(html)

for css in ROOT.rglob("*.css"):
    content = css.read_text(encoding="utf-8")
    for match in re.finditer(r"url\(\s*['\"]?([^'\")]+)", content):
        all_links.append(("css-url", match.group(1).strip(), css))

for js in ROOT.rglob("*.js"):
    content = js.read_text(encoding="utf-8")
    # Catch images dynamically injected by template literals.
    for asset in set(re.findall(r"assets/[\w./-]+\.(?:svg|png|jpe?g|webp|gif|woff2?|ico)", content)):
        all_links.append(("js-asset", asset, ROOT / "index.html"))

local_checked = 0
fragment_checked = 0
for tag, value, source in all_links:
    if not value or value.startswith(("data:", "mailto:", "tel:", "javascript:", "//")):
        continue
    parts = urlsplit(value)
    if parts.scheme in {"http", "https"}:
        external.add(parts.netloc)
        continue
    if parts.scheme:
        warnings.append(f"Unexpected URL scheme in {source}: {value}")
        continue
    if parts.path:
        if parts.path.startswith("/"):
            errors.append(f"Root-absolute local path breaks project Pages: {value}")
            continue
        target = source.parent / unquote(parts.path)
        local_checked += 1
        if not target.is_file():
            errors.append(f"Missing local reference ({tag} in {source}): {value}")
    if parts.fragment and (not parts.path or parts.path == "index.html"):
        fragment_checked += 1
        if parts.fragment not in ids:
            errors.append(f"Missing anchor ID ({tag} in {source}): {value}")

for fragment in ("conteudo", "experiencia", "sua-marca", "mcp", "comecar"):
    if fragment not in ids:
        errors.append(f"Main section id missing: {fragment}")
if "__CF$cv$params" in html or "/cdn-cgi/challenge-platform/" in html:
    errors.append("Cloudflare injected challenge code must not be included in the GitHub Pages copy")

for selector in ('id="theme-toggle"', 'data-language="pt"', 'data-language="en"',
                 'data-screen="home"', 'data-screen="course"', 'data-screen="login"'):
    if selector not in html:
        errors.append(f"Expected interactive control missing: {selector}")

translations = (ROOT / "translations.js").read_text(encoding="utf-8") if (ROOT / "translations.js").exists() else ""
i18n = (ROOT / "i18n.js").read_text(encoding="utf-8") if (ROOT / "i18n.js").exists() else ""
app = (ROOT / "app.js").read_text(encoding="utf-8") if (ROOT / "app.js").exists() else ""
if "LearnLingoEnglish" not in translations or "setLanguage" not in i18n:
    errors.append("Portuguese/English translation implementation missing")
if "setTheme" not in i18n or "theme-toggle" not in i18n:
    errors.append("Dark Mode implementation missing")
if "wizardSteps" not in app or "screens" not in app:
    errors.append("Dynamic preview or setup wizard implementation missing")

files = [p for p in ROOT.rglob("*") if p.is_file()]
total = sum(p.stat().st_size for p in files)
summary = (
    f"## LearnLingo website mirror audit\n\n"
    f"- Files in docs: **{len(files)}**, total **{total:,} bytes**\n"
    f"- Local references checked: **{local_checked}**\n"
    f"- Internal anchors checked: **{fragment_checked}**\n"
    f"- Source page sections: **{len(ids)}** unique element IDs\n"
    f"- External resource domains (intentionally not mirrored): "
    f"**{', '.join(sorted(external)) or 'none'}**\n"
    f"- Validation: **{'FAILED' if errors else 'PASSED'}**\n"
)
with open("/tmp/learnlingo-site-audit.md", "w", encoding="utf-8") as f:
    f.write(summary)
    if errors:
        f.write("\n### Errors\n" + "\n".join(f"- {e}" for e in errors) + "\n")
    if warnings:
        f.write("\n### Warnings\n" + "\n".join(f"- {w}" for w in warnings) + "\n")
print(summary)
for error in errors:
    print("ERROR:", error)
for warning in warnings:
    print("WARNING:", warning)
sys.exit(bool(errors))
