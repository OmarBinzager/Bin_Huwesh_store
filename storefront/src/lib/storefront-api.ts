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
      next: { tags: ["storefront", `storefront-${locale}`], revalidate: 300 },
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

    return {
      looks: raw.looks?.length ? raw.looks : fallbackLooks,
      virtualFit: raw.virtualFit?.items?.length ? raw.virtualFit : newArrivalsData,
      shoes: raw.shoes?.shoes?.length ? raw.shoes : shoesData,
      rail: raw.rail?.length ? raw.rail : RAIL_ITEMS,
      categories: raw.categories?.cards?.length ? raw.categories : shopCategoriesData,
      brands: raw.brands?.brands?.length ? raw.brands : brandsData,
      closing: mergedClosing,
      source: "api",
    };
  } catch {
    return {
      looks: fallbackLooks,
      virtualFit: newArrivalsData,
      shoes: shoesData,
      rail: RAIL_ITEMS,
      categories: shopCategoriesData,
      brands: brandsData,
      closing: closingData,
      source: "fallback",
    };
  }
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
};

export async function fetchCategoryCatalog(
  slugOrId: string,
  params?: {
    limit?: number;
    offset?: number;
    sort_by?: string;
    brand_id?: string;
    min_price?: string;
    max_price?: string;
    q?: string;
  },
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
    const res = await fetch(`${API_BASE_URL}/api/v1/storefront/categories/${encodeURIComponent(slugOrId)}${query}`, {
      next: { revalidate: 60 },
      headers: { Accept: "application/json", "X-localization": locale },
    });
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
