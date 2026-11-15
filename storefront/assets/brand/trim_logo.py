"""Trim the full logos to their visible content (no recolouring, no reshaping)."""
from pathlib import Path
from PIL import Image
ROOT = Path(__file__).resolve().parents[2]
for name in ("logo-full-dark-text", "logo-full-light-text"):
    im = Image.open(ROOT / "assets/brand" / f"{name}.webp").convert("RGBA")
    a = im.getchannel("A")
    print(name, "corner alpha", im.getpixel((3, 3))[3], "alpha bbox", a.point(lambda v: 255 if v > 8 else 0).getbbox())
    x0, y0, x1, y1 = a.point(lambda v: 255 if v > 8 else 0).getbbox()
    pad = 6
    cut = im.crop((max(0, x0 - pad), max(0, y0 - pad), min(im.width, x1 + pad), min(im.height, y1 + pad)))
    out = ROOT / "public/images/brand" / f"{name}.webp"
    cut.save(out, "WEBP", lossless=True, method=6)
    print(" ->", out.name, cut.size, round(out.stat().st_size / 1024), "KB")
