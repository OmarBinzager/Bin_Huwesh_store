import type { NaCategory, NaItem, NewArrivalsFile } from "./new-arrivals-types";
import data from "./new-arrivals.json";

/**
 * «وصل حديثًا» content — src/content/new-arrivals.json, edited with the local
 * admin at /admin/new-arrivals (dev only) or by hand. Order = array order.
 * Images: public/images/new-arrivals (originals + sources in assets/new-arrivals).
 * Products and prices are DEMO data until the store catalogue is connected.
 */
export const newArrivalsData = data as NewArrivalsFile;

export function visibleItems(file: NewArrivalsFile = newArrivalsData): NaItem[] {
  return file.items.filter((i) => i.visible && i.colors.length > 0);
}

/** Categories that at least one visible item belongs to, in the file's order. */
export function categoriesInUse(file: NewArrivalsFile = newArrivalsData): NaCategory[] {
  const used = new Set(visibleItems(file).map((i) => i.categoryId));
  return file.categories.filter((c) => used.has(c.id));
}
