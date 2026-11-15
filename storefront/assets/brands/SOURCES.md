# Brand logos — sources and processing

Initial list for the «ماركات عالمية» strip under the hero (2026-11-09).
The same source is stored per brand in `src/content/brands.json` (`source`)
and is editable in the local admin at `/admin/brands`.

| Brand | Source | Notes |
|---|---|---|
| Tommy Hilfiger | https://usa.tommy.com/on/demandware.static/-/Sites-PVHTHUS-Library/default/dw4bd11dac/logo/Logo.svg | Official site, header logo (wordmark + flag) |
| Ralph Lauren | https://www.ralphlauren.com/on/demandware.static/-/Library-Sites-RalphLauren_NA_Library/en_US/v1794221898878/img/Brand_Logo_Library/_V2/POLO/2025_offset_polo_logo_white.svg | Official brand logo library — this is the **Polo Ralph Lauren** logo |
| Nike | https://www.nike.com/ | Official site, inline header SVG ("Nike Home Page") |
| Adidas | https://www.adidas.com/us | Official site, inline header SVG ("Homepage") |
| Puma | https://us.puma.com/_next/static/assets/icons/puma-logo.svg | Official site |
| Skechers | https://commons.wikimedia.org/wiki/File:Skechers.svg | skechers.com blocked automated access with a Cloudflare check (not bypassed) |
| Lacoste | https://www.lacoste.com/on/demandware.static/Sites-FlagShip-Site/-/en_US/v1794212195170/img/svg/critical.svg#icon-lacoste | Official site sprite, wordmark symbol |
| Calvin Klein | https://www.calvinklein.us/on/demandware.static/-/Sites-PVHCKUS-Library/default/dwa0e4bc21/logo/CK-logo-Dark.svg | Official site |
| Levi's | https://www.levi.com/US/en_US/ | Official site, inline header SVG ("levi Logo graphic link") |
| New Balance | https://commons.wikimedia.org/wiki/File:New_Balance_logo.svg | newbalance.com returned 403 to automated access |

`originals/` holds the files exactly as obtained (vector SVG, no raster, no
drawn checkerboards or background boxes).

## Processing (headless Chrome, same steps as the admin upload)

1. Remove metadata/editor attributes; trim the viewBox to the drawing (+1 %).
2. `public/images/brands/<id>.svg` — single-colour version used by the strip
   as a CSS mask, so it takes the theme's text colour (black on white, white
   on black). Where a logo mixes dark and light parts (Levi's white lettering
   on the red batwing, the white square of the Tommy flag) the light parts
   become cut-outs instead of disappearing.
3. `public/images/brands/<id>-color.svg` — trimmed original colours, used only
   if a brand is switched to "الألوان الأصلية" in the admin (several are black
   and would vanish in dark mode, so single-colour is the default).

Logo trademarks belong to their owners; shown to indicate brands carried by
the store. Review usage rights with the brands/distributors before launch.
