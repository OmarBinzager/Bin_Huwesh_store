import type { Brand, BrandsFile } from "./brand-types";
import data from "./brands.json";

/**
 * Brand strip content — src/content/brands.json, edited with the local admin
 * at /admin/brands (dev only) or by hand. Order = array order.
 * Logo files live in public/images/brands (originals + sources in
 * assets/brands). Not linked to stock until the store catalogue is connected.
 */
export const brandsData = data as BrandsFile;

export function visibleBrands(list: Brand[] = brandsData.brands): Brand[] {
  return list.filter((b) => b.visible);
}
