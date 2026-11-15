"""Optimise the «تسوّق حسب القسم» photos for the site.

assets/categories/originals/<name>.png  (untouched ChatGPT downloads)
  -> public/images/categories/<name>.webp  (long side <= 1600 px, WebP q82)

Never overwrites: an existing output is skipped, so re-running is safe.
Run from the project root:  python assets/categories/process_categories.py
"""
from pathlib import Path

from PIL import Image

ROOT = Path(__file__).resolve().parents[2]
SRC = ROOT / "assets" / "categories" / "originals"
OUT = ROOT / "public" / "images" / "categories"
MAX_SIDE = 1600

OUT.mkdir(parents=True, exist_ok=True)
for src in sorted(SRC.glob("*.png")):
    dst = OUT / f"{src.stem}.webp"
    if dst.exists():
        print(f"skip  {dst.name} (exists)")
        continue
    im = Image.open(src).convert("RGB")
    scale = min(1.0, MAX_SIDE / max(im.size))
    if scale < 1:
        im = im.resize((round(im.width * scale), round(im.height * scale)), Image.LANCZOS)
    im.save(dst, "WEBP", quality=82, method=6)
    print(f"wrote {dst.name} {im.width}x{im.height} {dst.stat().st_size // 1024} KB (from {src.stat().st_size // 1024} KB)")
