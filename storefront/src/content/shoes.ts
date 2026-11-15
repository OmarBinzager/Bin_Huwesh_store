import data from "./shoes.json";
import type { Shoe, ShoesFile } from "./shoes-types";

/**
 * «أحذية تكمل إطلالتك» content — src/content/shoes.json, edited with the local
 * admin at /admin/shoes (dev only) or by hand. Order = array order.
 * Images: public/images/shoes (originals + sources in assets/shoes).
 * The shoes are representative images and DEMO prices until the store
 * catalogue is connected — not the company's stock.
 */
export const shoesData = data as ShoesFile;

export function visibleShoes(file: ShoesFile = shoesData): Shoe[] {
  return file.shoes.filter((s) => s.visible && s.colors.length > 0);
}
