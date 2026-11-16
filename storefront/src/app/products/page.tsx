import type { Metadata } from "next";
import Link from "next/link";
import SiteFooter from "@/components/closing/SiteFooter";
import CatalogProductGrid from "@/components/store/CatalogProductGrid";
import CatalogSidebarFilters from "@/components/store/CatalogSidebarFilters";
import { DemoBagProvider } from "@/components/store/DemoBag";
import StoreHeader from "@/components/store/StoreHeader";
import { getDictionary } from "@/lib/i18n/dictionaries";
import { getServerLocale } from "@/lib/i18n/server";
import { fetchProductsCatalog } from "@/lib/storefront-api";

type ProductsSearchParams = {
  sort_by?: string;
  q?: string;
  offset?: string;
  brand_id?: string;
  min_price?: string;
  max_price?: string;
  color?: string;
  attrs?: string;
  in_stock?: string;
  on_sale?: string;
  category?: string;
};

export async function generateMetadata(): Promise<Metadata> {
  const locale = await getServerLocale();
  const t = getDictionary(locale);
  return {
    title: `${t.catalog.allProductsTitle} — ${t.meta.brandSuffix}`,
    description: t.catalog.allProductsSubtitle,
  };
}

export default async function ProductsSearchPage({
  searchParams,
}: {
  searchParams: Promise<ProductsSearchParams>;
}) {
  const sp = await searchParams;
  const locale = await getServerLocale();
  const t = getDictionary(locale);
  const sortBy = sp.sort_by || "newest";
  const page = Math.max(1, Number(sp.offset || 1) || 1);
  const limit = 24;

  const SORT_OPTIONS = [
    { id: "newest", label: t.catalog.sortNewest },
    { id: "popular", label: t.catalog.sortPopular },
    { id: "price_asc", label: t.catalog.sortPriceAsc },
    { id: "price_desc", label: t.catalog.sortPriceDesc },
  ];

  const catalog = await fetchProductsCatalog(
    {
      limit,
      offset: page,
      sort_by: sortBy,
      q: sp.q,
      brand_id: sp.brand_id,
      min_price: sp.min_price,
      max_price: sp.max_price,
      color: sp.color,
      attrs: sp.attrs,
      in_stock: sp.in_stock,
      on_sale: sp.on_sale,
      category: sp.category,
    },
    locale,
  );

  const products = catalog?.products ?? [];
  const totalSize = catalog?.total_size ?? 0;

  const buildUrl = (overrides: Record<string, string | number | undefined>) => {
    const qs = new URLSearchParams();
    const merged: Record<string, string | number | undefined> = {
      sort_by: sortBy,
      q: sp.q,
      brand_id: sp.brand_id,
      min_price: sp.min_price,
      max_price: sp.max_price,
      color: sp.color,
      attrs: sp.attrs,
      in_stock: sp.in_stock,
      on_sale: sp.on_sale,
      category: sp.category,
      offset: page,
      ...overrides,
    };
    for (const [k, v] of Object.entries(merged)) {
      if (v !== undefined && v !== "" && !(k === "offset" && Number(v) <= 1)) {
        qs.set(k, String(v));
      }
    }
    const query = qs.toString();
    return `/products${query ? `?${query}` : ""}`;
  };

  return (
    <DemoBagProvider>
      <StoreHeader current="products" />
      <main className="min-h-screen bg-background">
        <div className="mx-auto max-w-[1440px] px-4 py-6 sm:px-8 sm:py-10">
          <nav aria-label={t.breadcrumbs.ariaLabel} className="mb-5 text-[13px] text-muted">
            <ol className="flex flex-wrap items-center gap-1.5">
              <li>
                <Link href="/" className="hover:text-ink">
                  {t.breadcrumbs.home}
                </Link>
              </li>
              <li aria-hidden>/</li>
              <li aria-current="page" className="text-ink">
                {t.catalog.allProductsTitle}
              </li>
            </ol>
          </nav>

          {/* Header Banner */}
          <section className="rounded-3xl border border-line bg-stage p-6 sm:p-10">
            <span aria-hidden className="mb-3 block h-[3px] w-10 rounded-full bg-brand" />
            <div className="flex flex-wrap items-end justify-between gap-4">
              <div>
                <h1 className="font-display text-3xl font-semibold text-ink sm:text-4xl">
                  {t.catalog.allProductsTitle}
                </h1>
                <p className="mt-2 max-w-2xl text-sm text-muted sm:text-base">
                  {t.catalog.allProductsSubtitle}
                </p>
              </div>
              {catalog && (
                <span className="nums rounded-full border border-line bg-card px-4 py-1.5 text-xs font-medium text-ink">
                  {t.catalog.availableCount(totalSize)}
                </span>
              )}
            </div>
          </section>

          {/* Search & Sort bar */}
          <div className="mt-8 flex flex-wrap items-center justify-between gap-4 border-b border-line pb-4">
            <form action="/products" method="GET" className="flex flex-wrap items-center gap-2">
              <input type="hidden" name="sort_by" value={sortBy} />
              {sp.category && <input type="hidden" name="category" value={sp.category} />}
              {sp.brand_id && <input type="hidden" name="brand_id" value={sp.brand_id} />}
              {sp.color && <input type="hidden" name="color" value={sp.color} />}
              {sp.attrs && <input type="hidden" name="attrs" value={sp.attrs} />}
              {sp.in_stock && <input type="hidden" name="in_stock" value={sp.in_stock} />}
              {sp.on_sale && <input type="hidden" name="on_sale" value={sp.on_sale} />}
              {sp.min_price && <input type="hidden" name="min_price" value={sp.min_price} />}
              {sp.max_price && <input type="hidden" name="max_price" value={sp.max_price} />}
              <input
                type="search"
                name="q"
                defaultValue={sp.q || ""}
                placeholder={t.catalog.allProductsSearchPlaceholder}
                className="h-10 w-60 rounded-full border border-line bg-card px-4 text-[13px] text-ink outline-none focus:border-brand sm:w-72"
              />
              <button
                type="submit"
                className="h-10 rounded-full bg-ink px-5 text-[13px] font-medium text-on-ink transition-colors hover:bg-brand hover:text-white"
              >
                {t.catalog.searchBtn}
              </button>
              {sp.q && (
                <Link
                  href={buildUrl({ q: undefined, offset: 1 })}
                  className="text-[12px] text-muted underline hover:text-ink"
                >
                  {t.catalog.clearBtn}
                </Link>
              )}
            </form>

            <div className="flex flex-wrap items-center gap-1.5">
              <span className="me-1 text-[12px] text-muted">{t.catalog.sortLabel}</span>
              {SORT_OPTIONS.map((opt) => {
                const active = sortBy === opt.id;
                return (
                  <Link
                    key={opt.id}
                    href={buildUrl({ sort_by: opt.id, offset: 1 })}
                    className={`rounded-full px-3.5 py-1.5 text-[12px] font-medium transition-colors ${
                      active
                        ? "bg-ink text-on-ink"
                        : "border border-line bg-card text-ink hover:border-brand hover:text-brand"
                    }`}
                  >
                    {opt.label}
                  </Link>
                );
              })}
            </div>
          </div>

          {/* Sidebar Filters + Products Grid */}
          <div className="mt-8 flex flex-col gap-8 lg:flex-row lg:items-start">
            <CatalogSidebarFilters basePath="/products" facets={catalog?.facets} />

            <div className="min-w-0 flex-1">
              {catalog === null ? (
                <div className="rounded-2xl border border-line bg-card p-6 sm:p-8">
                  <h2 className="font-display text-lg font-semibold text-ink">
                    {t.catalog.offlineTitle}
                  </h2>
                  <p className="mt-2 max-w-2xl text-[14px] leading-relaxed text-muted">
                    {t.catalog.offlineBody}
                  </p>
                  <Link
                    href="/"
                    className="mt-4 inline-flex items-center gap-1.5 rounded-full bg-ink px-5 py-2.5 text-[13px] font-medium text-on-ink transition-colors hover:bg-brand hover:text-white"
                  >
                    {t.breadcrumbs.home}
                  </Link>
                </div>
              ) : (
                <CatalogProductGrid
                  products={products}
                  totalSize={totalSize}
                  limit={limit}
                  page={page}
                  t={t}
                  buildUrl={buildUrl}
                  emptyBackHref="/products"
                />
              )}
            </div>
          </div>
        </div>
      </main>
      <SiteFooter />
    </DemoBagProvider>
  );
}
