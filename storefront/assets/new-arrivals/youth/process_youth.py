"""Prepare the youth collection (ChatGPT-generated) for «وصل حديثًا».

Reads   assets/new-arrivals/youth/originals/youth-0N-{model,garment}-<slug>.png   (never modified)
Writes  public/images/new-arrivals/{model,garment}-<id>.webp                      (skips files that exist)

Models: every original is 1024×1536 and was generated from the same framing
reference (reference-model-framing.png: the original figure scaled ×1536/1360,
centred). The head is registered back onto the reference (top of hair and
head centre) with a small translation, then a 723×1536 window is taken and
scaled to 640×1360 — the same scale as the original 524×1360 figures, just
wider so jackets and oversized sleeves are not clipped.
Garments: trimmed to their alpha box and centred in 640×640 (long side 576).
Run from the project root:  python assets/new-arrivals/youth/process_youth.py
"""
from pathlib import Path

import numpy as np
from PIL import Image

ROOT = Path(__file__).resolve().parents[3]
SRC = ROOT / "assets" / "new-arrivals" / "youth" / "originals"
OUT = ROOT / "public" / "images" / "new-arrivals"

ITEMS = [  # (original slug, site id)
    ("01", "olive-polo", "olive-polo"),
    ("02", "leather-jacket", "leather-jacket"),
    ("03", "black-oversized-tee", "black-oversized-tee"),
    ("04", "petrol-hoodie", "petrol-hoodie"),
    ("05", "burgundy-knit", "burgundy-knit"),
]
# head registration target, measured on the framing reference
REF_TOP, REF_CX = 28, 512.1
WIN_W = 723  # 640 * 1536 / 1360


def head(alpha: np.ndarray) -> tuple[int, float]:
    m = alpha > 128
    top = int(np.where(m[:, 300:724].any(axis=1))[0][0])
    ys, xs = np.where(m[top : top + 260, 300:724])
    return top, float(xs.mean() + 300)


def save(im: Image.Image, name: str) -> None:
    dst = OUT / name
    if dst.exists():
        print("skip (exists)", name)
        return
    im.save(dst, "WEBP", quality=90, method=6)
    print("wrote", name, im.size, f"{dst.stat().st_size // 1024} KB")


for num, slug, site_id in ITEMS:
    model = Image.open(SRC / f"youth-{num}-model-{slug}.png").convert("RGBA")
    top, cx = head(np.array(model)[:, :, 3])
    dx, dy = round(REF_CX - cx), REF_TOP - top
    shifted = Image.new("RGBA", model.size, (0, 0, 0, 0))
    shifted.alpha_composite(model, (dx, dy)) if dx >= 0 and dy >= 0 else shifted.paste(model, (dx, dy), model)
    x0 = round(512 - WIN_W / 2)
    window = shifted.crop((x0, 0, x0 + WIN_W, 1536))
    print(f"{site_id}: head shift dx={dx} dy={dy}")
    save(window.resize((640, 1360), Image.LANCZOS), f"model-{site_id}.webp")

    garment = Image.open(SRC / f"youth-{num}-garment-{slug}.png").convert("RGBA")
    g = garment.crop(garment.getchannel("A").point(lambda v: 255 if v > 8 else 0).getbbox())
    k = 576 / max(g.size)
    g = g.resize((round(g.width * k), round(g.height * k)), Image.LANCZOS)
    canvas = Image.new("RGBA", (640, 640), (0, 0, 0, 0))
    canvas.alpha_composite(g, ((640 - g.width) // 2, (640 - g.height) // 2))
    save(canvas, f"garment-{site_id}.webp")
