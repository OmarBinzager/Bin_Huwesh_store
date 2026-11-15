import type { CategoryCard, ShopCategoriesFile } from "./category-card-types";
import data from "./shop-categories.json";

/**
 * «تسوّق حسب القسم» content — src/content/shop-categories.json, edited with
 * the local admin at /admin/categories (dev only) or by hand.
 * Order = array order. Images live in public/images/categories (originals
 * in assets/categories). The photos are representative — not real products.
 */
export const shopCategoriesData = data as ShopCategoriesFile;

export function visibleCards(list: CategoryCard[] = shopCategoriesData.cards): CategoryCard[] {
  return list.filter((c) => c.visible);
}
