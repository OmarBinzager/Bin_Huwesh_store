/**
 * Content model for the «تسوّق حسب القسم» cards on the home page
 * (src/content/shop-categories.json, edited at /admin/categories in dev).
 * Display and motion live in components/ui/3d-curved-carousel.tsx (the
 * previous grid, components/ui/category-bento-grid.tsx, is kept but unused).
 */

/**
 * Cell shape in the previous grid (desktop: 4 columns, phone: 2 columns).
 * Kept in the data so the grid can be restored; the carousel ignores it.
 * large = 2×2 · tall = 1×2 · wide = 2×1 · regular = 1×1.
 */
export type CategoryCardSize = "large" | "tall" | "wide" | "regular";

export type CategoryCard = {
  /** Stable card id (never derived from the name). */
  id: string;
  /** Department this card opens — an id from STORE_CATEGORIES. */
  categoryId: string;
  /** Department name shown under the carousel when this card is selected. */
  name: string;
  /**
   * Short description shown under the name. Optional: when empty the
   * department's own blurb from STORE_CATEGORIES is used.
   */
  caption?: string;
  /** Internal link (starts with "/"). Defaults to /categories/<categoryId>. */
  href: string;
  /** Local image under /images/categories/. */
  image: string;
  /** Short description for screen readers. */
  alt: string;
  /** Crop focal point in % (CSS object-position) — keeps the clothes in frame. */
  focus: { x: number; y: number };
  size: CategoryCardSize;
  visible: boolean;
};

export type ShopCategoriesSettings = {
  title: string;
  subtitle: string;
  /** Link label on every card. */
  cta: string;
};

export type ShopCategoriesFile = {
  version: 1;
  settings: ShopCategoriesSettings;
  cards: CategoryCard[];
};

/** Grid cells each size occupies on desktop (4 columns). */
export const CARD_CELLS: Record<CategoryCardSize, number> = { large: 4, tall: 2, wide: 2, regular: 1 };
