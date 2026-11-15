import type { Look } from "./types";
import data from "./looks.json";

/**
 * Hero looks — the content source is looks.json, edited with the local admin
 * tool at /admin/looks (dev only) or by hand.
 *
 * - `id` is stable: never derived from the title, never changed on rename.
 * - Order: `order` (ascending). `published: false` hides a look.
 * - Image: trimmed transparent WebP in public/images/looks; `scale`,
 *   `offsetX`, `offsetY` place it in the scene.
 * - Hotspots: x / y are percentages of the image box (0,0 = top-left).
 * - Products: set `productId` ONLY after matching the garment in the image to
 *   the real product photo. While it is null the piece uses `demoPrice`,
 *   which the UI labels as a preview price.
 *
 * All images are AI-generated previews (see assets/looks/PROMPTS.md), and
 * every price is a placeholder — none of this is catalogue data yet.
 */
export const looks = data.looks as Look[];

/** Looks that should appear, in display order. */
export function getPublishedLooks(list: Look[] = looks): Look[] {
  return list.filter((l) => l.published).sort((a, b) => a.order - b.order);
}
