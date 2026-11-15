/**
 * Content model for «أحذية تكمل إطلالتك» (the lit shoe shelves).
 * Data: src/content/shoes.json — edited at /admin/shoes (dev only).
 * Motion and layout live in components/shoes/*; nothing here knows about
 * the animation.
 *
 * Every colour is a PHOTOGRAPHED variant with its own cut-out, so the site
 * never recolours a shoe. When a shoe has a single colour the picker is
 * hidden and only the colour's name is shown.
 *
 * Images: transparent WebP, side profile with the toe pointing left, on the
 * shared 1400×800 canvas (sole resting on y≈760, shoe centred) so every shoe
 * sits on its shelf the same way. See assets/shoes/process_shoes.py.
 */

/** EU sizes the admin may offer. A shoe lists only the sizes it has. */
export const SHOE_SIZES = ["38", "39", "40", "41", "42", "43", "44", "45", "46", "47"] as const;
export type ShoeSize = (typeof SHOE_SIZES)[number];

export type ShoeColor = {
  /** Stable id within the shoe. */
  id: string;
  /** «اللون · بني». */
  name: string;
  /** Dot colour (#rrggbb). */
  swatch: string;
  /** Transparent side-profile cut-out under /images/shoes/. */
  image: string;
};

export type Shoe = {
  /** Stable id (never derived from the name). */
  id: string;
  name: string;
  /** One or two short lines for the detail panel. */
  description: string;
  /** DEMO price in Yemeni rial — not a real price list. */
  price: number;
  sizes: ShoeSize[];
  colors: ShoeColor[];
  /** Image description for screen readers. */
  alt: string;
  visible: boolean;
};

export type ShoesSettings = {
  title: string;
  subtitle: string;
};

export type ShoesFile = {
  version: 1;
  settings: ShoesSettings;
  shoes: Shoe[];
};
