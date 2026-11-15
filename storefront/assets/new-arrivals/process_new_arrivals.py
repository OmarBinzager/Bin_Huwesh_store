"""Cut the «وصل حديثًا» (VirtualFit) sheets into per-item WebP files.

Reads   assets/new-arrivals/originals/{room,model-sheet,garment-sheet}.webp  (never modified)
Writes  public/images/new-arrivals/{room,model-<id>,garment-<id>}.webp       (skips files that exist)

Crop windows are the reference prompt's own maths:
  model   : 524×1360 window of the 2688×1520 sheet, bottom-aligned, x = m% of (2688-524)
  garment : 640×640 box of the 2048×1360 sheet, at g% of (2048-640, 1360-640)
Run from the project root:  python assets/new-arrivals/process_new_arrivals.py
"""
from pathlib import Path
from PIL import Image

ROOT = Path(__file__).resolve().parents[2]
SRC = ROOT / "assets" / "new-arrivals" / "originals"
OUT = ROOT / "public" / "images" / "new-arrivals"
OUT.mkdir(parents=True, exist_ok=True)

# id, model background-position-x %, garment background-position %
ITEMS = [
    ("basic-tee", 0.37, (1.14, 3.06)),
    ("crew-sweatshirt", 25.0, (49.7, 1.39)),
    ("linen-shirt", 49.86, (98.2, 3.33)),
    ("grey-hoodie", 74.77, (1.14, 93.3)),
    ("ringer-tee", 99.35, (49.7, 95.3)),
]


def save(im: Image.Image, name: str, **kw) -> None:
    dst = OUT / name
    if dst.exists():
        print("skip (exists)", dst.name)
        return
    im.save(dst, "WEBP", method=6, **kw)
    print("wrote", dst.name, im.size, f"{dst.stat().st_size // 1024} KB")


room = Image.open(SRC / "room.webp").convert("RGB")
room.thumbnail((1920, 1920), Image.LANCZOS)
save(room, "room.webp", quality=80)

model = Image.open(SRC / "model-sheet.webp").convert("RGBA")
garm = Image.open(SRC / "garment-sheet.webp").convert("RGBA")
mw, mh = model.size
gw, gh = garm.size
for item_id, mx, (gx, gy) in ITEMS:
    left = round(mx / 100 * (mw - 524))
    save(model.crop((left, mh - 1360, left + 524, mh)), f"model-{item_id}.webp", quality=90)
    gl, gt = round(gx / 100 * (gw - 640)), round(gy / 100 * (gh - 640))
    save(garm.crop((gl, gt, gl + 640, gt + 640)), f"garment-{item_id}.webp", quality=90)
