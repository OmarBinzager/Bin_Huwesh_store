# Shoe images — «أحذية تكمل إطلالتك»

**Representative images, not company stock.** The project had no usable
side-profile shoe photos (only the angled group shot behind
`public/images/categories/shoes-01.webp`), so all ten shoes were generated on
2026-11-13 with ChatGPT image generation on the owner's own account (no paid
service), one shoe per image in a single conversation so the camera, light
and scale match.

They are generic designs: the prompts forbid logos, brand marks, text and
brand-like stripes, and none is presented as a real product. Prices and sizes
in `src/content/shoes.json` are demo values.

## Shared prompt (shoe 1, repeated as "same specs" for 2–10)

> a single men's shoe (one shoe, not a pair), exact side profile of the outer
> side, camera at sole height, perfectly horizontal, no 3/4 angle, no
> perspective tilt; toe pointing LEFT, heel on the RIGHT; sole flat and level.
> Fully TRANSPARENT background (real alpha PNG, no checkerboard pattern, no
> floor, no cast shadow, no reflection). The whole shoe visible with clear
> margin on all sides. Landscape 3:2 canvas (1536x1024) … Soft even studio
> light, true natural colours and materials, crisp detail, photorealistic.
> Completely unbranded: no logos, no brand marks, no text, no stripes or
> swoosh-like shapes from any real brand.

| file (originals/) | shoe |
|---|---|
| shoe-01-white-sneaker.png | white smooth-leather minimal low-top sneaker |
| shoe-02-cognac-loafer.png | cognac calf-leather penny loafer |
| shoe-03-black-oxford.png | black cap-toe oxford |
| shoe-04-sand-chukka.png | sand suede chukka boot, crepe sole |
| shoe-05-brown-chelsea.png | dark-brown leather Chelsea boot |
| shoe-06-navy-moccasin.png | navy suede driving moccasin |
| shoe-07-grey-knit-runner.png | grey knit running sneaker |
| shoe-08-leather-sandal.png | traditional Arabian leather sandal (naal) |
| shoe-09-retro-court.png | ivory leather court sneaker, suede overlays, gum sole |
| shoe-10-oxblood-tassel.png | oxblood tassel loafer |

Originals are kept untouched (1536×1024 RGBA PNG).
`process_shoes.py` makes the display copies in `public/images/shoes/`
(1400×800 transparent WebP, trimmed, uniform scale only, sole on y = 760,
no colour change). The admin's upload path (`src/components/admin/process-shoe.ts`)
applies the same framing in the browser.
