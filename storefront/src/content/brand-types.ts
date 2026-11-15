/**
 * Content model for the "ماركات عالمية" logo strip under the hero.
 * Independent of stock/catalogue data until brands are linked to products.
 */
export type Brand = {
  /** Stable id (never derived from the name). */
  id: string;
  name: string;
  /**
   * Single-colour logo (SVG/WebP with alpha) used as a CSS mask, so it takes
   * the theme's text colour in light and dark mode.
   */
  logo: string;
  /** Original-colour version, used when `mode` is "color". */
  colorLogo?: string;
  /** Width ÷ height of the trimmed logo — drives the optical sizing. */
  aspect: number;
  mode: "mono" | "color";
  /** Fine-tune the optical size (1 = automatic). */
  scale: number;
  visible: boolean;
  /** Where the logo file came from (kept for licensing/review). */
  source: string;
};

export type BrandStripSettings = {
  title: string;
  showTitle: boolean;
  /** Pixels per second — stays calm however many logos there are. */
  speed: number;
  /** Physical direction the logos travel. */
  direction: "left" | "right";
  /** Optical logo height in px on desktop (phones use ~80 %). */
  logoSize: number;
};

export type BrandsFile = {
  version: 1;
  settings: BrandStripSettings;
  brands: Brand[];
};
