# «تسوّق حسب القسم» photos — sources

Eight representative photos, generated on 2026-11-12 with ChatGPT image
generation (the owner's own account, free use — no paid API), one at a time in
one chat so lighting, set and colour grade match across the series.
They are **illustrative only**: not real Bin Huwesh products, not linked to
stock or prices.

- `originals/<id>-01.png` — the downloads, untouched.
- `public/images/categories/<id>-01.webp` — what the site serves, made by
  `process_categories.py` (long side ≤ 1600 px, WebP q82, never overwrites).
- Crop focal points live in `src/content/shop-categories.json` (`focus`) and
  are editable at `/admin/categories`.

| card | original | garment reference uploaded with the prompt | format |
|---|---|---|---|
| رجالي | men-01.png | assets/looks/references/ref-02-linen.jpg | 1:1 |
| بدلات | suits-01.png | assets/looks/references/ref-01-suit.jpg | 2:3 |
| شبابي | youth-01.png | assets/looks/references/@Image1.png (prints/lettering removed) | 2:3 |
| ولادي | kids-01.png | assets/looks/references/ref-08-kids.jpg (chest letter removed) | 3:2 |
| جامبو | jumbo-01.png | assets/looks/references/ref-07-jumbo.jpg | 1:1 |
| معاوز | maawaz-01.png | assets/looks/references/ref-06-maawaz.jpg | 3:2 |
| أحذية وصنادل | shoes-01.png | none (still life matching shoes seen in men/suits/youth/maawaz) | 1:1 |
| إكسسوارات | accessories-01.png | none (still life) | 3:2 |

Shared style line used in every prompt: realistic premium editorial fashion
photograph, warm neutral studio set with travertine stone and off-white
plaster walls, soft directional daylight from the left with gentle shadows,
muted warm palette (ivory, sand, stone, navy, deep brown), 50 mm look, subject
centred with generous margins for square/portrait/wide crops; no text, no
letters, no logos, no brand marks, no watermark.

Known small flaw: the two watch dials in `accessories-01` carry tiny
illegible marks (not readable at card size). Replace the photo from the admin
if a cleaner one is wanted.
