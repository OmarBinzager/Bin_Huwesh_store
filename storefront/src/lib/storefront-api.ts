import type { BrandsFile } from "@/content/brand-types";
import { brandsData } from "@/content/brands";
import type { ShopCategoriesFile } from "@/content/category-card-types";
import { shopCategoriesData } from "@/content/shop-categories";
import type { ClosingFile } from "@/content/closing-types";
import { closingData } from "@/content/closing";
import { getPublishedLooks } from "@/content/looks";
import type { NewArrivalsFile } from "@/content/new-arrivals-types";
import { newArrivalsData } from "@/content/new-arrivals";
import { RAIL_ITEMS } from "@/content/rail";
import type { ShoesFile } from "@/content/shoes-types";
import { shoesData } from "@/content/shoes";
import type { Look } from "@/content/types";
import type { RailGarment } from "@/components/ui/product-rail-gallery";
import type { Locale } from "@/lib/i18n/config";

export const API_BASE_URL =
  (process.env.NEXT_PUBLIC_API_BASE_URL || "http://127.0.0.1:8000").replace(/\/+$/, "");

export type StorefrontBootstrap = {
  looks: Look[];
  virtualFit: NewArrivalsFile;
  shoes: ShoesFile;
  rail: RailGarment[];
  categories: ShopCategoriesFile;
  brands: BrandsFile;
  closing: ClosingFile;
  source: "api" | "fallback";
};

type RawBootstrapResponse = {
  looks?: Look[] | null;
  virtualFit?: NewArrivalsFile | null;
  shoes?: ShoesFile | null;
  rail?: RailGarment[] | null;
  categories?: ShopCategoriesFile | null;
  brands?: BrandsFile | null;
  closing?: Partial<ClosingFile> & { social?: ClosingFile["footer"]["social"] } | null;
};

/**
 * Fetches the unified storefront bootstrap payload from Laravel with ISR caching
 * (`storefront` tag) and resilient per-section fallback to local JSON content.
 */
export async function fetchStorefrontBootstrap(locale: Locale = "ar"): Promise<StorefrontBootstrap> {
  const fallbackLooks = getPublishedLooks();

  try {
    const res = await fetch(`${API_BASE_URL}/api/v1/storefront/bootstrap?lang=${locale}`, {
      next: { tags: ["storefront", `storefront-${locale}`], revalidate: 10 },
      headers: { Accept: "application/json", "X-localization": locale },
    });

    if (!res.ok) {
      throw new Error(`Bootstrap HTTP ${res.status}`);
    }

    const raw = (await res.json()) as RawBootstrapResponse;

    const mergedClosing: ClosingFile = raw.closing
      ? {
          version: 1,
          testimonials: raw.closing.testimonials?.items?.length
            ? raw.closing.testimonials
            : closingData.testimonials,
          contact: raw.closing.contact?.phoneDisplay
            ? raw.closing.contact
            : closingData.contact,
          footer: {
            tagline: closingData.footer.tagline,
            groups: closingData.footer.groups,
            social: raw.closing.social?.length ? raw.closing.social : closingData.footer.social,
          },
        }
      : closingData;

    return localizeBootstrap(
      {
        looks: raw.looks?.length ? raw.looks : fallbackLooks,
        virtualFit: raw.virtualFit?.items?.length ? raw.virtualFit : newArrivalsData,
        shoes: raw.shoes?.shoes?.length ? raw.shoes : shoesData,
        rail: raw.rail?.length ? raw.rail : RAIL_ITEMS,
        categories: raw.categories?.cards?.length ? raw.categories : shopCategoriesData,
        brands: raw.brands?.brands?.length ? raw.brands : brandsData,
        closing: mergedClosing,
        source: "api",
      },
      locale,
    );
  } catch {
    return localizeBootstrap(
      {
        looks: fallbackLooks,
        virtualFit: newArrivalsData,
        shoes: shoesData,
        rail: RAIL_ITEMS,
        categories: shopCategoriesData,
        brands: brandsData,
        closing: closingData,
        source: "fallback",
      },
      locale,
    );
  }
}

const HAS_ARABIC_RE = /[\u0600-\u06FF]/;

const EN_CATEGORY_NAMES: Record<string, string> = {
  men: "Men",
  youth: "Youth",
  kids: "Kids",
  jumbo: "Jumbo Sizes",
  suits: "Suits",
  maawaz: "Maawaz",
  shoes: "Shoes & Sandals",
  accessories: "Accessories",
  sportswear: "Sportswear",
  formal: "Formal",
  casual: "Casual",
  "رجالي": "Men",
  "شبابي": "Youth",
  "ولادي": "Kids",
  "جامبو": "Jumbo Sizes",
  "بدلات": "Suits",
  "معاوز": "Maawaz",
  "أحذية": "Shoes",
  "أحذية وصنادل": "Shoes & Sandals",
  "إكسسوارات": "Accessories",
  "اكسسوارات": "Accessories",
  "الكل": "All",
};

function translateTextIfArabic(value: string | undefined, enFallback: string): string {
  if (!value || HAS_ARABIC_RE.test(value)) return enFallback;
  return value;
}

function translateCatLabel(name: string, slugOrId?: string): string {
  if (slugOrId) {
    const key = slugOrId.toLowerCase().trim();
    if (EN_CATEGORY_NAMES[key]) return EN_CATEGORY_NAMES[key];
  }
  const trimmed = name.trim();
  if (EN_CATEGORY_NAMES[trimmed]) return EN_CATEGORY_NAMES[trimmed];
  return trimmed;
}

function localizeBootstrap(data: StorefrontBootstrap, locale: Locale): StorefrontBootstrap {
  if (locale !== "en") return data;

  return {
    ...data,
    categories: {
      ...data.categories,
      settings: {
        ...data.categories.settings,
        title: translateTextIfArabic(data.categories.settings.title, "Shop by Category"),
        subtitle: translateTextIfArabic(data.categories.settings.subtitle, "Explore Bin Huwesh collections by category"),
        cta: translateTextIfArabic(data.categories.settings.cta, "Browse Category"),
      },
      cards: data.categories.cards.map((c) => {
        const slug = c.href?.split("/").filter(Boolean).pop();
        const translatedName = HAS_ARABIC_RE.test(c.name)
          ? translateCatLabel(c.name, slug || c.categoryId)
          : c.name;
        return {
          ...c,
          name: translatedName,
          caption: c.caption && HAS_ARABIC_RE.test(c.caption) ? "" : c.caption,
          alt: c.alt && HAS_ARABIC_RE.test(c.alt) ? translatedName : c.alt,
        };
      }),
    },
    virtualFit: {
      ...data.virtualFit,
      settings: {
        ...data.virtualFit.settings,
        title: translateTextIfArabic(data.virtualFit.settings.title, "New Arrivals"),
        subtitle: translateTextIfArabic(
          data.virtualFit.settings.subtitle,
          "Try the piece on the model and pick your preferred size and color",
        ),
      },
      categories: data.virtualFit.categories.map((cat) => ({
        ...cat,
        name: HAS_ARABIC_RE.test(cat.name) ? translateCatLabel(cat.name, cat.id) : cat.name,
      })),
    },
    shoes: {
      ...data.shoes,
      settings: {
        ...data.shoes.settings,
        title: translateTextIfArabic(data.shoes.settings.title, "Shoes to Complete Your Look"),
        subtitle: translateTextIfArabic(
          data.shoes.settings.subtitle,
          "A curated selection of classic and casual footwear",
        ),
      },
    },
    brands: {
      ...data.brands,
      settings: {
        ...data.brands.settings,
        title: translateTextIfArabic(data.brands.settings.title, "Trusted Global Brands"),
      },
    },
    rail: data.rail.map((item) => ({
      ...item,
      tag: item.tag ? item.tag.replace("ر.ي", "YER") : item.tag,
    })),
    closing: {
      ...data.closing,
      footer: {
        ...data.closing.footer,
        tagline: translateTextIfArabic(data.closing.footer.tagline, "Choices that reflect you"),
        groups: data.closing.footer.groups.map((g) => {
          const groupTitleMap: Record<string, string> = {
            departments: "Departments",
            browse: "Browse",
            policies: "Policies & Terms",
            help: "Help & Support",
          };
          const linkLabelMap: Record<string, string> = {
            "/categories/men": "Men",
            "/categories/youth": "Youth",
            "/categories/kids": "Kids",
            "/categories/jumbo": "Jumbo Sizes",
            "/categories/suits": "Suits",
            "/categories/maawaz": "Maawaz",
            "/categories/shoes": "Shoes & Sandals",
            "/categories/accessories": "Accessories",
            "/#shop-by-category": "Shop by Category",
            "/#new-arrivals": "New Arrivals",
            "/#shoes": "Shoes",
            "/#testimonials": "Customer Reviews",
            "/#visit": "Visit Showroom",
            "/policies/terms": "Terms & Conditions",
            "/policies/privacy": "Privacy Policy",
            "/policies/return": "Return Policy",
            "/policies/refund": "Refund Policy",
            "/policies/cancellation": "Cancellation Policy",
            "/policies/about": "About Us",
            "/policies/faq": "FAQ",
          };
          return {
            ...g,
            title: groupTitleMap[g.id] || translateTextIfArabic(g.title, g.title),
            links: g.links.map((l) => ({
              ...l,
              label: linkLabelMap[l.href] || translateTextIfArabic(l.label, l.label),
            })),
          };
        }),
      },
      testimonials: {
        ...data.closing.testimonials,
        title: translateTextIfArabic(data.closing.testimonials.title, "What They Say About Bin Huwesh"),
        subtitle: translateTextIfArabic(data.closing.testimonials.subtitle, "Verified customer reviews"),
      },
      contact: {
        ...data.closing.contact,
        title: translateTextIfArabic(data.closing.contact.title, "Visit Us at Bin Huwesh"),
        subtitle: translateTextIfArabic(data.closing.contact.subtitle, "Visit our showroom or reach out directly"),
        company: translateTextIfArabic(data.closing.contact.company, "Bin Huwesh Trading Co."),
        address: translateTextIfArabic(data.closing.contact.address, "Aden, Yemen"),
      },
    },
  };
}

export type CatalogProduct = {
  id: number;
  name: string;
  price: number;
  final_price: number;
  discount: number;
  discount_type: string | null;
  total_stock: number;
  image: string;
  images: string[];
  color_images: Array<{ color?: string; color_code?: string; color_name?: string; image?: string; image_url?: string }>;
  sizes: string[];
  rating: number;
  brand: { id: number; name: string; image: string } | null;
};

export type CatalogFacets = {
  categories: Array<{ id: number; name: string; slug: string; count: number }>;
  brands: Array<{ id: number; name: string; count: number }>;
  colors: Array<{ name: string; code: string | null; count: number }>;
  attributes: Array<{
    title: string;
    options: Array<{ value: string; count: number }>;
  }>;
  price_range: { min: number; max: number };
  in_stock_count: number;
  on_sale_count: number;
};

export type CatalogFilterParams = {
  limit?: number;
  offset?: number;
  sort_by?: string;
  brand_id?: string;
  min_price?: string;
  max_price?: string;
  q?: string;
  color?: string;
  attrs?: string;
  in_stock?: string;
  on_sale?: string;
  sub_category_id?: string;
  category?: string;
};

export type CategoryCatalogResponse = {
  category: {
    id: number;
    name: string;
    image: string;
    children: Array<{ id: number; name: string; image: string }>;
  };
  total_size: number;
  limit: number;
  offset: number;
  products: CatalogProduct[];
  facets?: CatalogFacets;
};

export type ProductsCatalogResponse = {
  total_size: number;
  limit: number;
  offset: number;
  products: CatalogProduct[];
  facets: CatalogFacets;
};

export async function fetchProductsCatalog(
  params?: CatalogFilterParams,
  locale: Locale = "ar",
): Promise<ProductsCatalogResponse | null> {
  try {
    const qs = new URLSearchParams();
    qs.set("lang", locale);
    if (params) {
      for (const [k, v] of Object.entries(params)) {
        if (v !== undefined && v !== "") qs.set(k, String(v));
      }
    }
    const query = qs.toString() ? `?${qs.toString()}` : "";
    const res = await fetch(`${API_BASE_URL}/api/v1/storefront/products${query}`, {
      cache: "no-store",
      headers: { Accept: "application/json", "X-localization": locale },
    });
    if (!res.ok) return null;
    return (await res.json()) as ProductsCatalogResponse;
  } catch {
    return null;
  }
}

export async function fetchCategoryCatalog(
  slugOrId: string,
  params?: CatalogFilterParams,
  locale: Locale = "ar",
): Promise<CategoryCatalogResponse | null> {
  try {
    const qs = new URLSearchParams();
    qs.set("lang", locale);
    if (params) {
      for (const [k, v] of Object.entries(params)) {
        if (v !== undefined && v !== "") qs.set(k, String(v));
      }
    }
    const query = qs.toString() ? `?${qs.toString()}` : "";
    const res = await fetch(
      `${API_BASE_URL}/api/v1/storefront/categories/${encodeURIComponent(slugOrId)}${query}`,
      {
        cache: "no-store",
        headers: { Accept: "application/json", "X-localization": locale },
      },
    );
    if (!res.ok) return null;
    return (await res.json()) as CategoryCatalogResponse;
  } catch {
    return null;
  }
}

export type ProductPatternOption = {
  name: string;
  type: string;
  image: string | null;
  image_url: string;
  price: number;
  stock: number;
  is_sold: boolean;
};

export type ProductDetailResponse = {
  product: CatalogProduct & {
    description: string | null;
    variant_mode?: "standard" | "unique_patterns";
    patterns?: ProductPatternOption[];
    variations: Array<{ type: string; price: number; stock: number }>;
    choice_options: Array<{ name: string; title: string; options: string[] }>;
    specifications?: Array<{ label: string; value: string }>;
    size_chart: {
      id: number;
      name: string;
      columns: string[];
      rows: string[][];
      note?: string | null;
    } | null;
    reviews: Array<{
      id: number;
      rating: number;
      comment: string;
      customer: string;
      created_at: string | null;
    }>;
  };
  complete_the_look?: CatalogProduct[];
  related_products: CatalogProduct[];
};

export async function fetchProductDetail(
  id: string | number,
  locale: Locale = "ar",
): Promise<ProductDetailResponse | null> {
  try {
    const res = await fetch(
      `${API_BASE_URL}/api/v1/storefront/products/${encodeURIComponent(String(id))}?lang=${locale}`,
      {
        next: { revalidate: 60 },
        headers: { Accept: "application/json", "X-localization": locale },
      },
    );
    if (!res.ok) return null;
    return (await res.json()) as ProductDetailResponse;
  } catch {
    return null;
  }
}

export type PoliciesResponse = {
  terms_and_conditions: string;
  privacy_policy: string;
  return_policy: string;
  refund_policy: string;
  cancellation_policy: string;
  about_us: string;
  faq: string;
};

export async function fetchPolicies(locale: Locale = "ar"): Promise<PoliciesResponse | null> {
  try {
    const res = await fetch(`${API_BASE_URL}/api/v1/storefront/policies?lang=${locale}`, {
      next: { tags: ["storefront", `storefront-${locale}`], revalidate: 600 },
      headers: { Accept: "application/json", "X-localization": locale },
    });
    if (!res.ok) return null;
    return (await res.json()) as PoliciesResponse;
  } catch {
    return null;
  }
}
