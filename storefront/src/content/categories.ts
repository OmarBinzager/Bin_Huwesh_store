/**
 * Bin Huwesh store departments — the ONE list the header nav, the
 * «تسوّق حسب القسم» cards and the /categories/[slug] preview pages read.
 *
 * These are exactly the departments the store header already showed (no
 * women's department, nothing invented). Ids/slugs are stable: change a
 * label here, never an id. When the company's catalogue API is connected,
 * map each `id` to its real category there.
 */
export type StoreCategory = {
  /** Stable id, also the URL slug: /categories/<id>. */
  id: string;
  name: string;
  /** One neutral line for the preview page — no stock or price claims. */
  blurb: string;
};

export const STORE_CATEGORIES: readonly StoreCategory[] = [
  { id: "men", name: "رجالي", blurb: "قمصان وبناطيل وإطلالات يومية ورسمية للرجال." },
  { id: "youth", name: "شبابي", blurb: "قطع عصرية وكاجوال للشباب." },
  { id: "kids", name: "ولادي", blurb: "ملابس الأولاد للمناسبات والأيام العادية." },
  { id: "jumbo", name: "جامبو", blurb: "مقاسات كبيرة بقصّات مريحة." },
  { id: "suits", name: "بدلات", blurb: "بدلات رسمية للمناسبات والعمل." },
  { id: "maawaz", name: "معاوز", blurb: "معاوز يمنية بخامات ونقوش متنوعة." },
  { id: "shoes", name: "أحذية وصنادل", blurb: "أحذية رسمية وكاجوال وصنادل." },
  { id: "accessories", name: "إكسسوارات", blurb: "ساعات وأحزمة ونظارات ولمسات تكمّل الإطلالة." },
] as const;

export function categoryHref(id: string): string {
  return `/categories/${id}`;
}

export function getStoreCategory(id: string): StoreCategory | undefined {
  return STORE_CATEGORIES.find((c) => c.id === id);
}
