# Hero looks — generation log

Preview assets for the Bin Huwesh hero ring. Generated with ChatGPT (user's own
account, via Claude in Chrome) in one chat named "Generate Fashion Image".

**These are AI preview images, not product photography.** Garments only
approximate the reference photos. Do not link a look item to a real product
until the garment has been matched against that product's own photo.

- `references/` — resized garment references uploaded with each prompt
  (source files stay untouched in `brand/images/`).
- `originals/` — the files exactly as downloaded from ChatGPT.
- `public/images/looks/` — cut-out, trimmed, resized WebP used by the site.

## Shared specification (all 8 looks)

High-angle shot, camera ~2.5 m high tilted down ~45°, 40 mm lens; model looks
up into the lens; full body head-to-soles, centered, generous margins; soft
even studio light from above-front; transparent background, no floor, no
shadow (ground shadows are drawn in code); portrait 2:3; no text/logos.

## Prompts

### 01 — بدلة رسمية (suit)
Reference: `references/ref-01-suit.jpg` (brand/images/freepik__-__72078.png)

> Generate an image: Photorealistic full-body fashion e-commerce photo of ONE
> adult Arab man (late 20s, short dark hair, neat trimmed beard) wearing exactly
> the navy two-piece suit from the attached reference: navy slim-fit two-button
> blazer, crisp white dress shirt, navy tie, matching navy trousers, black
> leather oxford shoes, silver wristwatch. Keep the garment colors, fabric, cut
> and details identical to the reference. CAMERA (very important): high-angle
> shot from above, camera about 2.5 m high tilted down about 45 degrees, 40mm
> lens; the man stands on the floor and tilts his head UP to look straight into
> the lens with a calm confident expression; natural top-down foreshortening
> (head and shoulders slightly larger, body tapering toward the feet). The
> ENTIRE body is visible from the top of the head to the soles of the shoes,
> centered, with generous empty margin on every side, nothing cropped. Pose:
> relaxed natural standing pose, one hand in trouser pocket, other arm relaxed.
> Lighting: soft even studio light from above-front, realistic fabric texture,
> sharp focus. Background: fully transparent background, real alpha-channel PNG
> cut-out, no floor, no cast shadow, no backdrop, no checkerboard. Portrait 2:3.
> Correct anatomy, natural hands with five fingers, well-formed shoes. No text,
> no logos, no watermark, no borders.

Result: `originals/01-suit-v1.png` — good garments and real alpha, but the
camera read as almost eye level. Kept for the record; not used.

### 01 — correction (final `01-suit.png`, the style master)

> Generate an image: Good garments and transparency, but the camera angle is
> not high enough - this looks almost eye level. Regenerate the same man in the
> same navy suit, but shot from a much steeper overhead angle: the camera is
> above him, roughly 3 m high, pointing down at about 55-60 degrees, like a
> photographer standing on a ladder. He tilts his chin up and looks straight up
> into the lens. Strong but natural top-down perspective: we see the top of his
> hair and the tops of his shoulders, the head is relatively large, the body
> clearly tapers toward small feet, and the shoes are seen from above (toe caps
> visible, soles hidden). Full body still entirely inside the frame with margin
> on all sides. Same soft studio light, same transparent alpha-channel PNG
> background, no floor, no shadow, no checkerboard. Portrait 2:3. No text, no
> logos, no watermark.

### Template used for 02–08

Each prompt below started with: *"Generate an image: Same style master as the
overhead navy-suit image: EXACTLY the same camera height and angle (about 3 m
high, pointing down 55-60 degrees), lens, soft studio lighting, scale and
framing."* and ended with: *"Full body from top of hair to shoes completely in
frame with margins, strong top-down foreshortening, shoes seen from above.
Transparent alpha-channel PNG background, no floor, no shadow, no checkerboard.
Portrait 2:3. Photorealistic, correct anatomy and hands. No text, no logos, no
watermark."* The middle part per look:

| Look | Reference (from brand/images) | Model + outfit |
|---|---|---|
| 02 كاجوال كتان | `تنزيل (21).jpg` | Arab man, early 30s, short wavy hair, light stubble. Cream/beige fine-stripe linen shirt, sleeves rolled, top button open, tucked into camel-khaki high-waisted pleated linen trousers with side-adjuster buttons; brown suede loafers; gold watch. Hands loosely in front pockets. |
| 03 جاكيت وجينز | `freepik__a-sophisticated-photoshoot…57709.png` | Young Arab man ~21, curly hair, thin moustache, no sunglasses. Oversized grey/black/off-white bouclé check overshirt-jacket with black chest pocket, open over black crew tee; dark indigo straight jeans; white leather sneakers. Hands in jacket pockets. |
| 04 بولو | `Navy Knitted Polo _ Old Money Summer Outfit for Men.jpg` | Arab man mid 20s, combed hair, short beard. Navy fine-rib knit johnny-collar polo, tucked into cream pleated high-waisted trousers, black belt, dark-brown penny loafers. Holds a takeaway coffee. |
| 05 شتوي | `Gemini_Generated_Image_b5p3e2b5p3e2b5p3.png` | Arab man early 30s, full beard. Oatmeal chunky-knit quarter-zip sweater over light shirt and dark tie, navy-charcoal chinos, brown derby shoes; plus one added piece: charcoal wool scarf. |
| 06 معوز يمني | `Gemini_Generated_Image_9j3z0c9j3z0c9j3z.png` | Yemeni man early 30s, neat beard. White long-sleeve shirt, the reference's woven plaid tasselled shawl over both shoulders, striped ma'awaz to mid-calf, watch, white slide sandals. No dagger or weapons. Hand holding shawl end. |
| 07 جامبو | `تنزيل (25).jpg` | Big-and-tall Arab man early 40s (~130 kg), salt-and-pepper beard. Olive shirt with fine cream pinstripes, relaxed big-and-tall fit, tucked into navy trousers, brown belt, silver watch, brown lace-ups. Respectful, well-fitted. |
| 08 ولادي | `تنزيل (29).jpg` | Cheerful Arab boy ~6. Cream knit short-sleeve polo with brown chest stripes (no logo), tan-camel shorts, white socks, white sneakers. Hands in pockets. (ChatGPT added a short zip to the collar.) |

## Checks done on every file

- Real alpha: corner pixel alpha = 0, 53–64 % fully transparent pixels.
- Faint haze (alpha < 12) cleared, trimmed to the subject with 2 % margin,
  resized to 1400 px tall WebP (90–170 KB each, 1.1 MB for all eight).
- `contact-sheet.jpg` shows all eight side by side for angle/scale consistency.

## 03 — replacement youth model («طاقة شبابية», 2026-11-09)

The check-jacket model was replaced by the brand's own youth model.
Reference: `references/@Image1.png` (multi-view sheet: black backwards cap
with gold buckle, wraparound mirrored sunglasses, layered black graphic tee,
light corduroy jeans, black sneakers). Style master attached as image 2:
`public/images/looks/01-suit.webp`. Result: `originals/03-youth.png`
(real alpha, 62 % transparent). The look keeps its id `youth-check`.
`originals/03-jacket.png` stays for the record; its web copy was removed.

> Generate an image: Attached are two images. Image 1 is a reference sheet
> (several views) of our youth model: a slim young Arab man with a thin
> moustache, wearing a black baseball cap turned backwards with a gold strap
> buckle, black wraparound sport sunglasses with a mirrored silver lens, an
> oversized black short-sleeve graphic T-shirt layered over a black
> long-sleeve tee, light-blue fine-corduroy straight-leg jeans and black
> low-top sneakers. Image 2 is the style master (the overhead navy-suit
> image). Create ONE single full-body photo of THIS same young man (same face,
> moustache, cap, sunglasses and exact outfit, keep the T-shirt print layout
> and colors as close as possible) - one person only, NOT a collage, NOT
> multiple views. Use EXACTLY the camera height and angle of the style master
> (about 3 m high, pointing down 55-60 degrees), the same lens, soft studio
> lighting, scale and framing. He tilts his head up toward the lens; relaxed
> confident pose, hands loosely at his sides or one hand in pocket. Full body
> from top of the cap to the sneakers completely in frame with margins, strong
> top-down foreshortening, sneakers seen from above. Transparent alpha-channel
> PNG background, no floor, no shadow, no checkerboard. Portrait 2:3.
> Photorealistic, correct anatomy and hands. No watermark, no extra text.
