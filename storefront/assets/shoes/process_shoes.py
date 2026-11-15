"""
Frames the shoe cut-outs for «أحذية تكمل إطلالتك».

  assets/shoes/originals/shoe-NN-<id>.png   (ChatGPT renders, kept untouched)
  -> public/images/shoes/<id>.webp          (1400x800, transparent)

Every shoe is trimmed to its alpha bounds and fitted inside the same box
(at most 1180 px long and 620 px tall), centred horizontally, with the sole
resting on y = 760. The shelves in the section rely on that shared framing.
No colour correction, no stretching (uniform scale only).

Usage: python assets/shoes/process_shoes.py [--force]
"""
import pathlib
import re
import sys

from PIL import Image

ROOT = pathlib.Path(__file__).resolve().parents[2]
SRC = ROOT / "assets" / "shoes" / "originals"
OUT = ROOT / "public" / "images" / "shoes"
W, H, BASE = 1400, 800, 760
MAX_LEN, MAX_H = 1180, 620


def frame(path: pathlib.Path) -> Image.Image:
    im = Image.open(path).convert("RGBA")
    alpha = im.getchannel("A").point(lambda a: 255 if a > 8 else 0)
    box = alpha.getbbox()
    if box is None:
        raise SystemExit(f"{path.name}: fully transparent")
    shoe = im.crop(box)
    scale = min(MAX_LEN / shoe.width, MAX_H / shoe.height)
    size = (round(shoe.width * scale), round(shoe.height * scale))
    shoe = shoe.resize(size, Image.LANCZOS)
    canvas = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    canvas.alpha_composite(shoe, ((W - size[0]) // 2, BASE - size[1]))
    return canvas


def main() -> None:
    force = "--force" in sys.argv
    OUT.mkdir(parents=True, exist_ok=True)
    for path in sorted(SRC.glob("shoe-*.png")):
        m = re.match(r"shoe-\d+-(.+)\.png$", path.name)
        if not m:
            continue
        dest = OUT / f"{m.group(1)}.webp"
        if dest.exists() and not force:
            print("skip", dest.name)
            continue
        frame(path).save(dest, "WEBP", quality=90, method=6, exact=False)
        print("wrote", dest.name, dest.stat().st_size // 1024, "KB")


if __name__ == "__main__":
    main()
