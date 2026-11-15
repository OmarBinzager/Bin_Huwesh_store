/**
 * Content model for «وصل حديثًا» (the VirtualFit try-on arc).
 * Data: src/content/new-arrivals.json — edited at /admin/new-arrivals (dev only).
 * Motion and layout live in components/new-arrivals/*; nothing here knows
 * about the animation.
 *
 * Every colour is a PHOTOGRAPHED variant: its own garment cut-out and its own
 * model figure (same man, pose, camera and light). A colour without matching
 * photos cannot exist, so the site never tints clothes with filters.
 */

export const NA_SIZES = ["S", "M", "L", "XL", "XXL"] as const;
export type NaSize = (typeof NA_SIZES)[number];

export type NaCategory = {
  /** Stable id (tab key). */
  id: string;
  name: string;
};

export type NaColor = {
  /** Stable id within the item. */
  id: string;
  /** Shown in the detail panel: «اللون · أبيض». */
  name: string;
  /** Dot colour (#rrggbb). */
  swatch: string;
  /** Transparent ghost-mannequin garment (square), under /images/new-arrivals/. */
  garment: string;
  /**
   * Transparent model figure wearing this garment, under /images/new-arrivals/.
   * Framing: head to mid-thigh, 1360 px tall, head top at y≈25 and centred —
   * 640×1360 (current set) or 524×1360 (first set); both line up because the
   * scene fits the figure by height and centres it.
   */
  model: string;
};

export type NaItem = {
  /** Stable item id (never derived from the name). */
  id: string;
  name: string;
  /** One short line shown under the name in the detail panel (optional). */
  description?: string;
  categoryId: string;
  /** DEMO price in Yemeni rial — not a real price list. */
  price: number;
  sizes: NaSize[];
  colors: NaColor[];
  visible: boolean;
};

export type NaSettings = {
  title: string;
  subtitle: string;
  /** Faint word behind the model. */
  watermark: string;
  /** Blurred room behind the scene. */
  room: string;
};

export type NewArrivalsFile = {
  version: 1;
  settings: NaSettings;
  categories: NaCategory[];
  items: NaItem[];
};
