/**
 * Content model for the hero "looks" ring.
 *
 * This file describes WHAT is shown; src/components/hero/* decides HOW it
 * moves. Nothing here knows about animation, and the hero only ever reads
 * these shapes, so the same data can later come from an admin panel or API
 * without touching the motion code.
 */

/** A point on the look image, in percent of the image box (0–100). */
export type Hotspot = { x: number; y: number };

export type LookItem = {
  /** Stable id inside the look (used for keys and hotspot ↔ row linking). */
  id: string;
  /** Short Arabic name shown on the hotspot pill and the receipt row. */
  label: string;
  /** Garment family, e.g. "جاكيت", "بنطال", "حذاء". */
  category: string;
  hotspot: Hotspot;
  /** Real catalogue product id. */
  productId: string | null;
  /** Live price in YER from Laravel catalog when productId is linked. */
  livePrice?: number;
  /** Product image from catalog if linked. */
  image?: string;
  /** True when using preview demoPrice instead of live catalog price. */
  isDemo?: boolean;
  /**
   * PREVIEW-ONLY price in YER, used while `productId` is null. Never treated
   * as a real price: the UI labels it as a demo value.
   */
  demoPrice: number;
  /** Sizes offered in the demo bag until real stock data is connected. */
  sizes: string[];
};

export type Look = {
  id: string;
  /** Display order in the ring and grid (ascending). */
  order: number;
  /** Hide without deleting. */
  published: boolean;
  title: string;
  subtitle: string;
  image: {
    src: string;
    /** Intrinsic size of the trimmed cut-out (from process_looks.py). */
    width: number;
    height: number;
    alt: string;
    /**
     * Size in the scene relative to an adult (1). A child look uses ~0.74 so
     * the ring keeps real-world proportions. Editable in the admin tool.
     */
    scale: number;
    /**
     * Nudge inside the scene, in percent of the figure's height
     * (x: + moves left in RTL reading order is NOT applied — + is screen-right;
     *  y: + moves down). Used to line up feet/heads across different photos.
     */
    offsetX: number;
    offsetY: number;
    /** True while the image is an AI preview, not a product photograph. */
    isPreview: boolean;
  };
  items: LookItem[];
  /** Whole-look discount in percent, shown only when set. */
  wholeLookDiscount?: number;
  /** Optional link for "view all pieces" (category or collection page). */
  href?: string;
};
