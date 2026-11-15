"""Turn the downloaded look PNGs into web-ready cut-outs.

Usage (from the repo root):
    python -I assets/looks/process_looks.py

For every assets/looks/originals/NN-name.png (files ending in -vN are older
attempts and are skipped):
  1. verify a real alpha channel exists (corner pixel must be transparent);
  2. drop the faint haze ChatGPT leaves around cut-outs (alpha < 12 -> 0);
  3. trim to the subject with a small uniform margin;
  4. write public/images/looks/NN-name.webp (1400 px tall, alpha kept);
  5. print a manifest (size + subject bounds) used by src/content/looks.ts.
"""

from __future__ import annotations

import json
import re
import sys
from pathlib import Path

from PIL import Image

ROOT = Path(__file__).resolve().parents[2]
SRC = ROOT / "assets" / "looks" / "originals"
OUT = ROOT / "public" / "images" / "looks"
TARGET_H = 1400
MARGIN = 0.02  # of the subject height, on every side


def process(path: Path) -> dict:
    im = Image.open(path)
    if im.mode != "RGBA":
        raise SystemExit(f"{path.name}: no alpha channel ({im.mode})")
    if im.getpixel((5, 5))[3] != 0:
        raise SystemExit(f"{path.name}: background is not transparent")

    r, g, b, a = im.split()
    a = a.point(lambda v: 0 if v < 12 else v)
    im = Image.merge("RGBA", (r, g, b, a))

    bbox = a.point(lambda v: 255 if v > 16 else 0).getbbox()
    if not bbox:
        raise SystemExit(f"{path.name}: empty alpha")
    x0, y0, x1, y1 = bbox
    pad = round((y1 - y0) * MARGIN)
    box = (max(0, x0 - pad), max(0, y0 - pad), min(im.width, x1 + pad), min(im.height, y1 + pad))
    cut = im.crop(box)

    scale = TARGET_H / cut.height
    out_w = round(cut.width * scale)
    cut = cut.resize((out_w, TARGET_H), Image.LANCZOS)

    OUT.mkdir(parents=True, exist_ok=True)
    dest = OUT / f"{path.stem}.webp"
    cut.save(dest, "WEBP", quality=84, method=6, exact=False)

    return {
        "file": f"/images/looks/{dest.name}",
        "width": out_w,
        "height": TARGET_H,
        "kb": round(dest.stat().st_size / 1024),
        "subjectWidthRatio": round((x1 - x0) / (y1 - y0), 3),
    }


def main() -> None:
    files = sorted(p for p in SRC.glob("*.png") if re.fullmatch(r"\d\d-[a-z-]+", p.stem) and not re.search(r"-v\d+$", p.stem))
    if not files:
        sys.exit("no originals found")
    manifest = {p.stem: process(p) for p in files}
    print(json.dumps(manifest, indent=2))


if __name__ == "__main__":
    main()
