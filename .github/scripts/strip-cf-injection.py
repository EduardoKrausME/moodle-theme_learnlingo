#!/usr/bin/env python3
"""Strip Cloudflare's per-request bot verification snippet from an HTML mirror.

It is injected by the source hosting layer, is not part of LearnLingo, and
would attempt to load a non-existent /cdn-cgi challenge URL on GitHub Pages.
"""
from pathlib import Path
import re

path = Path("docs/index.html")
source = path.read_text(encoding="utf-8")
cleaned, removed = re.subn(
    r"<script>\(function\(\)\{function c\(\)\{.*?window\.__CF\$cv\$params=.*?</script>",
    "",
    source,
    flags=re.DOTALL,
)
if "__CF$cv$params" in cleaned or "/cdn-cgi/challenge-platform/" in cleaned:
    raise SystemExit("Cloudflare injection was detected but not fully removed")
if removed > 1:
    raise SystemExit(f"Unexpected number of Cloudflare snippets: {removed}")
path.write_text(cleaned, encoding="utf-8")
print(f"Removed {removed} Cloudflare verification snippet(s)")
