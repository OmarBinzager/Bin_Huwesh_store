# «وصل حديثًا» (VirtualFit) — image sources

Downloaded 2026-11-12 from the three asset URLs given in the VirtualFit reference
prompt (`نص ملصق(1).txt`, section 2), unchanged, into `originals/`:

| File | Reference variable | URL |
|---|---|---|
| `originals/room.webp` (2400×1357, RGB) | `--room` | https://images.higgs.ai/?default=1&output=webp&url=https%3A%2F%2Fd8j0ntlcm91z4.cloudfront.net%2Fuser_38xzZboKViGWJOttwIXH07lWA1P%2Fhf_20261104_080623_c94c85a3-23d5-4a0c-afff-41be1b5b8191.png&w=2400&q=85 |
| `originals/model-sheet.webp` (2688×1520, RGBA) | `--model` | https://images.higgs.ai/?default=1&output=webp&url=https%3A%2F%2Fd8j0ntlcm91z4.cloudfront.net%2Fuser_38xzZboKViGWJOttwIXH07lWA1P%2Fhf_20261104_080717_e02cb856-b812-4c9b-9b86-8a741c7f65eb.png&w=2688&q=85 |
| `originals/garment-sheet.webp` (2048×1360, RGBA) | `--garm` | https://images.higgs.ai/?default=1&output=webp&url=https%3A%2F%2Fd8j0ntlcm91z4.cloudfront.net%2Fuser_38xzZboKViGWJOttwIXH07lWA1P%2Fhf_20261104_080719_f5a4cca2-cf42-4957-ab3b-ddec34d2b71a.png&w=2048&q=85 |

`process_new_arrivals.py` cuts them with the prompt's own crop maths into
`public/images/new-arrivals/`:

- `room.webp` — the room, downsized to 1920 px (it is shown blurred).
- `model-<id>.webp` — one 524×1360 transparent figure per outfit (same man, pose,
  camera and light in all five).
- `garment-<id>.webp` — one 640×640 transparent ghost-mannequin garment per item.

| id | model figure | garment |
|---|---|---|
| basic-tee | white crew tee | white tee |
| crew-sweatshirt | oversized white sweatshirt | white sweatshirt |
| linen-shirt | white linen shirt | white linen shirt |
| grey-hoodie | heather-grey hoodie | grey hoodie |
| ringer-tee | white ringer tee, black trims | ringer tee |

Each garment has ONE photographed colour, so the site offers exactly that colour.
The navy tee on the garment sheet has no matching model figure and is not used.
These are prepared preview images for a swap animation — not an AI try-on or a
body measurement. The figures touch each other on the sheet; the prompt's
524 px windows are used as-is (the reference crops them the same way).
