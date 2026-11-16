import type { Metadata } from "next";
import Image from "next/image";
import Link from "next/link";
import { notFound } from "next/navigation";
import SiteFooter from "@/components/closing/SiteFooter";
import CatalogProductGrid from "@/components/store/CatalogProductGrid";
import CatalogSidebarFilters from "@/components/store/CatalogSidebarFilters";
import { DemoBagProvider } from "@/components/store/DemoBag";
import StoreHeader from "@/components/store/StoreHeader";
import { STORE_CATEGORIES, categoryHref, getStoreCategory } from "@/content/categories";
import { shopCategoriesData } from "@/content/shop-categories";
import { getDictionary } from "@/lib/i18n/dictionaries";
import { getServerLocale } from "@/lib/i18n/server";
import { fetchCategoryCatalog } from "@/lib/storefront-api";

export function generateStaticParams() {
  return STORE_CATEGORIES.map((c) => ({ slug: c.id }));
}

type CategorySearchParams = {
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
  sub_category_id?: string;
};

export async function generateMetadata({
  params,
}: {
  params: Promise<{ slug: string }>;
}): Promise<Metadata> {
  const { slug } = await params;
  const locale = await getServerLocale();
  const t = getDictionary(locale);
  const cat = getStoreCategory(slug);
  const localizedCat = t.categories[slug];
  const siteUrl = process.env.NEXT_PUBLIC_SITE_URL || "https://binhuwesh.com";

  let title = t.meta.siteTitle;
  let description = t.meta.siteDescription;

  if (cat) {
    title = `${localizedCat?.name ?? cat.name} — ${t.meta.brandSuffix}`;
    description = localizedCat?.blurb ?? cat.blurb;
  } else {
    const catalog = await fetchCategoryCatalog(slug, { limit: 1 }, locale);
    if (catalog) {
      title = `${catalog.category.name} — ${t.meta.brandSuffix}`;
      description =
        locale === "en"
          ? `Browse the latest ${catalog.category.name} products at Bin Huwesh Store in Yemen.`
          : `تصفح أحدث وأفضل منتجات ${catalog.category.name} في متجر بن هويش للأزياء باليمن.`;
    }
  }

  return {
    title,
    description,
    alternates: {
      canonical: `/categories/${slug}`,
    },
    openGraph: {
      type: "website",
      locale: locale === "ar" ? "ar_YE" : "en_US",
      url: `${siteUrl}/categories/${slug}`,
      title,
      description,
    },
    twitter: {
      card: "summary_large_image",
      title,
      description,
    },
  };
}

export default async function CategoryCatalogPage({
  params,
  searchParams,
}: {
  params: Promise<{ slug: string }>;
  searchParams: Promise<CategorySearchParams>;
}) {
  const { slug } = await params;
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

  const cat = getStoreCategory(slug);
  const localizedCat = t.categories[slug];
  const catalog = await fetchCategoryCatalog(
    slug,
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
      sub_category_id: sp.sub_category_id,
    },
    locale,
  );

  if (!cat && !catalog) {
    notFound();
  }

  const title = catalog?.category.name || localizedCat?.name || cat?.name || slug;
  const blurb = localizedCat?.blurb || cat?.blurb || t.catalog.defaultBlurb(title);
  const card = cat ? shopCategoriesData.cards.find((c) => c.id === cat.id) : undefined;
  const heroImage = card?.image || catalog?.category.image || "";
  const others = STORE_CATEGORIES.filter((c) => c.id !== cat?.id);
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
      sub_category_id: sp.sub_category_id,
      offset: page,
      ...overrides,
    };
    for (const [k, v] of Object.entries(merged)) {
      if (v !== undefined && v !== "" && !(k === "offset" && Number(v) <= 1)) {
        qs.set(k, String(v));
      }
    }
    const query = qs.toString();
    return `/categories/${encodeURIComponent(slug)}${query ? `?${query}` : ""}`;
  };

  return (
    <DemoBagProvider>
      <StoreHeader current={cat?.id ?? slug} />
      <main className="bg-background">
        <div className="mx-auto max-w-[1440px] px-4 py-6 sm:px-8 sm:py-10">
          <nav aria-label={t.breadcrumbs.ariaLabel} className="mb-5 text-[13px] text-muted">
            <ol className="flex flex-wrap items-center gap-1.5">
              <li>
                <Link href="/" className="hover:text-ink">
                  {t.breadcrumbs.home}
                </Link>
              </li>
              <li aria-hidden>/</li>
              <li>
                <Link href="/#shop-by-category" className="hover:text-ink">
                  {t.breadcrumbs.categories}
                </Link>
              </li>
              <li aria-hidden>/</li>
              <li aria-current="page" className="text-ink">
                {title}
              </li>
            </ol>
          </nav>

          <section className="relative isolate grid overflow-hidden rounded-3xl bg-stage sm:grid-cols-2">
            <div className="absolute inset-x-0 bottom-0 z-10 p-6 sm:static sm:flex sm:flex-col sm:justify-center sm:p-12 lg:p-16">
              <span aria-hidden className="mb-3 block h-[3px] w-10 rounded-full bg-brand" />
              <h1 className="font-display text-4xl leading-tight font-semibold text-white sm:text-5xl sm:text-ink lg:text-6xl">
                {title}
              </h1>
              <p className="mt-3 text-[15px] leading-relaxed text-white/85 sm:max-w-md sm:text-lg sm:text-muted">
                {blurb}
              </p>
              {catalog && (
                <p className="mt-4 text-[13px] font-medium text-white/75 sm:text-muted">
                  {t.catalog.availableCount(totalSize)}
                </p>
              )}
            </div>
            <div className="relative aspect-[4/5] sm:aspect-auto sm:h-[min(520px,62vh)]">
              {heroImage && (
                <Image
                  src={heroImage}
                  alt={card?.alt || title}
                  fill
                  priority
                  sizes="(min-width: 640px) 50vw, 100vw"
                  style={
                    card
                      ? { objectPosition: `${card.focus.x}% ${card.focus.y}%` }
                      : undefined
                  }
                  className="object-cover"
                />
              )}
              <span
                aria-hidden
                className="absolute inset-0 bg-gradient-to-t from-black/75 via-black/20 to-transparent sm:hidden"
              />
            </div>
          </section>

          {/* Subcategories */}
          {catalog?.category.children && catalog.category.children.length > 0 && (
            <div className="mt-6 flex flex-wrap gap-2">
              {catalog.category.children.map((sub) => (
                <Link
                  key={sub.id}
                  href={`/categories/${sub.id}`}
                  className="inline-flex items-center gap-2 rounded-full border border-line bg-card px-4 py-2 text-[13px] font-medium text-ink transition-colors hover:border-brand hover:text-brand"
                >
                  {sub.name}
                </Link>
              ))}
            </div>
          )}

          {/* Filter & Sort bar */}
          <div className="mt-8 flex flex-wrap items-center justify-between gap-4 border-b border-line pb-4">
            <form action={`/categories/${encodeURIComponent(slug)}`} method="GET" className="flex items-center gap-2">
              <input type="hidden" name="sort_by" value={sortBy} />
              <input
                type="search"
                name="q"
                defaultValue={sp.q || ""}
                placeholder={t.catalog.searchPlaceholder(title)}
                className="h-10 w-56 rounded-full border border-line bg-card px-4 text-[13px] text-ink outline-none focus:border-brand sm:w-64"
              />
              <button
                type="submit"
                className="h-10 rounded-full bg-ink px-4 text-[13px] font-medium text-on-ink transition-colors hover:bg-brand hover:text-white"
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

          {/* Sidebar Filters + Product Grid */}
          <div className="mt-8 flex flex-col gap-8 lg:flex-row lg:items-start">
            <CatalogSidebarFilters
              basePath={`/categories/${encodeURIComponent(slug)}`}
              facets={catalog?.facets}
              subCategories={catalog?.category.children}
            />

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
                    href="/#shop-by-category"
                    className="mt-4 inline-flex items-center gap-1.5 rounded-full bg-ink px-5 py-2.5 text-[13px] font-medium text-on-ink transition-colors hover:bg-brand hover:text-white"
                  >
                    {t.catalog.backToCategories}
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
                  emptyBackHref={`/categories/${encodeURIComponent(slug)}`}
                />
              )}
            </div>
          </div>

          <section aria-labelledby="other-cats" className="mt-12 sm:mt-16">
            <h2 id="other-cats" className="mb-4 font-display text-xl font-semibold text-ink">
              {t.catalog.otherCategoriesTitle}
            </h2>
            <ul className="flex flex-wrap gap-2">
              {others.map((c) => (
                <li key={c.id}>
                  <Link
                    href={categoryHref(c.id)}
                    className="inline-block rounded-full border border-line px-4 py-2 text-[14px] text-ink transition-colors hover:border-brand hover:text-brand"
                  >
                    {t.categories[c.id]?.name ?? c.name}
                  </Link>
                </li>
              ))}
            </ul>
          </section>
        </div>
      </main>
      <SiteFooter />
    </DemoBagProvider>
  );
}
