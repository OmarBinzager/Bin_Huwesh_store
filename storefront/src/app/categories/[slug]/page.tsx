import type { Metadata } from "next";
import Image from "next/image";
import Link from "next/link";
import { notFound } from "next/navigation";
import { DemoBagProvider } from "@/components/store/DemoBag";
import StoreHeader from "@/components/store/StoreHeader";
import { STORE_CATEGORIES, categoryHref, getStoreCategory } from "@/content/categories";
import { shopCategoriesData } from "@/content/shop-categories";
import { formatYER as formatPrice } from "@/lib/catalog";
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
};

export async function generateMetadata({
  params,
}: {
  params: Promise<{ slug: string }>;
}): Promise<Metadata> {
  const { slug } = await params;
  const cat = getStoreCategory(slug);
  if (cat) {
    return { title: `${cat.name} — بن هويش`, description: cat.blurb };
  }
  const catalog = await fetchCategoryCatalog(slug, { limit: 1 });
  if (catalog) {
    return { title: `${catalog.category.name} — بن هويش` };
  }
  return {};
}

const SORT_OPTIONS = [
  { id: "newest", label: "الأحدث" },
  { id: "popular", label: "الأكثر مبيعاً" },
  { id: "price_asc", label: "السعر: من الأقل" },
  { id: "price_desc", label: "السعر: من الأعلى" },
];

export default async function CategoryCatalogPage({
  params,
  searchParams,
}: {
  params: Promise<{ slug: string }>;
  searchParams: Promise<CategorySearchParams>;
}) {
  const { slug } = await params;
  const sp = await searchParams;
  const sortBy = sp.sort_by || "newest";
  const offset = Math.max(0, Number(sp.offset || 0) || 0);
  const limit = 24;

  const cat = getStoreCategory(slug);
  const catalog = await fetchCategoryCatalog(slug, {
    limit,
    offset,
    sort_by: sortBy,
    q: sp.q,
    brand_id: sp.brand_id,
    min_price: sp.min_price,
    max_price: sp.max_price,
  });

  if (!cat && !catalog) {
    notFound();
  }

  const title = catalog?.category.name || cat?.name || slug;
  const blurb =
    cat?.blurb || `تصفّح أحدث منتجات ${title} المتوفرة في متجر بن هويش.`;
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
      offset,
      ...overrides,
    };
    for (const [k, v] of Object.entries(merged)) {
      if (v !== undefined && v !== "" && !(k === "offset" && Number(v) === 0)) {
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
          <nav aria-label="مسار التصفح" className="mb-5 text-[13px] text-muted">
            <ol className="flex flex-wrap items-center gap-1.5">
              <li>
                <Link href="/" className="hover:text-ink">
                  الرئيسية
                </Link>
              </li>
              <li aria-hidden>/</li>
              <li>
                <Link href="/#shop-by-category" className="hover:text-ink">
                  الأقسام
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
                  {totalSize} منتج متاح
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
                placeholder={`ابحث في ${title}...`}
                className="h-10 w-56 rounded-full border border-line bg-card px-4 text-[13px] text-ink outline-none focus:border-brand sm:w-64"
              />
              <button
                type="submit"
                className="h-10 rounded-full bg-ink px-4 text-[13px] font-medium text-on-ink transition-colors hover:bg-brand hover:text-white"
              >
                بحث
              </button>
              {sp.q && (
                <Link
                  href={buildUrl({ q: undefined, offset: 0 })}
                  className="text-[12px] text-muted underline hover:text-ink"
                >
                  مسح
                </Link>
              )}
            </form>

            <div className="flex flex-wrap items-center gap-1.5">
              <span className="ml-1 text-[12px] text-muted">ترتيب:</span>
              {SORT_OPTIONS.map((opt) => {
                const active = sortBy === opt.id;
                return (
                  <Link
                    key={opt.id}
                    href={buildUrl({ sort_by: opt.id, offset: 0 })}
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

          {/* Product Grid */}
          {catalog === null ? (
            <div className="mt-8 rounded-2xl border border-line bg-card p-6 sm:p-8">
              <h2 className="font-display text-lg font-semibold text-ink">
                جاري تحميل الكتالوج أو الخادم غير متصل
              </h2>
              <p className="mt-2 max-w-2xl text-[14px] leading-relaxed text-muted">
                تعذر الاتصال بخادم المتجر لعرض منتجات هذا القسم حالياً. يمكنك العودة إلى الصفحة الرئيسية أو المحاولة لاحقاً.
              </p>
              <Link
                href="/#shop-by-category"
                className="mt-4 inline-flex items-center gap-1.5 rounded-full bg-ink px-5 py-2.5 text-[13px] font-medium text-on-ink transition-colors hover:bg-brand hover:text-white"
              >
                العودة إلى الأقسام
              </Link>
            </div>
          ) : products.length === 0 ? (
            <div className="mt-8 rounded-2xl border border-line bg-card p-8 text-center">
              <p className="font-display text-lg font-semibold text-ink">
                لا توجد منتجات مطابقة في هذا القسم حالياً
              </p>
              <p className="mt-2 text-[14px] text-muted">
                جرّب تعديل كلمات البحث أو تصفّح أقسام المتجر الأخرى.
              </p>
            </div>
          ) : (
            <>
              <div className="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 lg:gap-6">
                {products.map((product) => {
                  const hasDiscount = product.discount > 0 && product.final_price < product.price;
                  const outOfStock = product.total_stock <= 0;
                  return (
                    <Link
                      key={product.id}
                      href={`/products/${product.id}`}
                      className="group flex flex-col overflow-hidden rounded-2xl border border-line bg-card transition-all hover:border-brand/50 hover:shadow-md"
                    >
                      <div className="relative aspect-[3/4] w-full overflow-hidden bg-stage">
                        {product.image ? (
                          <img
                            src={product.image}
                            alt={product.name}
                            loading="lazy"
                            className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                          />
                        ) : (
                          <div className="flex h-full w-full items-center justify-center text-xs text-muted">
                            بدون صورة
                          </div>
                        )}
                        {hasDiscount && (
                          <span className="absolute top-3 right-3 rounded-full bg-brand px-2.5 py-1 text-[11px] font-semibold text-white">
                            خصم
                          </span>
                        )}
                        {outOfStock && (
                          <span className="absolute inset-x-3 bottom-3 rounded-full bg-black/75 py-1 text-center text-[11px] font-medium text-white">
                            نفذت الكمية
                          </span>
                        )}
                      </div>

                      <div className="flex flex-1 flex-col justify-between p-3.5 sm:p-4">
                        <div>
                          {product.brand?.name && (
                            <p className="text-[11px] font-medium text-muted">
                              {product.brand.name}
                            </p>
                          )}
                          <h2 className="mt-1 line-clamp-2 text-[14px] font-semibold text-ink group-hover:text-brand">
                            {product.name}
                          </h2>
                        </div>

                        <div className="mt-3 flex items-baseline justify-between gap-2">
                          <div className="flex items-baseline gap-2">
                            <span className="text-[15px] font-bold text-ink">
                              {formatPrice(product.final_price)}
                            </span>
                            {hasDiscount && (
                              <span className="text-[12px] text-muted line-through">
                                {formatPrice(product.price)}
                              </span>
                            )}
                          </div>
                          {product.sizes.length > 0 && (
                            <span className="text-[11px] text-muted">
                              {product.sizes.slice(0, 3).join(" · ")}
                            </span>
                          )}
                        </div>
                      </div>
                    </Link>
                  );
                })}
              </div>

              {/* Pagination */}
              {totalSize > limit && (
                <div className="mt-10 flex items-center justify-center gap-3">
                  {offset > 0 && (
                    <Link
                      href={buildUrl({ offset: Math.max(0, offset - limit) })}
                      className="rounded-full border border-line bg-card px-5 py-2 text-[13px] font-medium text-ink hover:border-brand hover:text-brand"
                    >
                      السابق
                    </Link>
                  )}
                  <span className="text-[13px] text-muted">
                    عرض {offset + 1} - {Math.min(offset + products.length, totalSize)} من {totalSize}
                  </span>
                  {offset + products.length < totalSize && (
                    <Link
                      href={buildUrl({ offset: offset + limit })}
                      className="rounded-full border border-line bg-card px-5 py-2 text-[13px] font-medium text-ink hover:border-brand hover:text-brand"
                    >
                      التالي
                    </Link>
                  )}
                </div>
              )}
            </>
          )}

          <section aria-labelledby="other-cats" className="mt-12 sm:mt-16">
            <h2 id="other-cats" className="mb-4 font-display text-xl font-semibold text-ink">
              أقسام أخرى
            </h2>
            <ul className="flex flex-wrap gap-2">
              {others.map((c) => (
                <li key={c.id}>
                  <Link
                    href={categoryHref(c.id)}
                    className="inline-block rounded-full border border-line px-4 py-2 text-[14px] text-ink transition-colors hover:border-brand hover:text-brand"
                  >
                    {c.name}
                  </Link>
                </li>
              ))}
            </ul>
          </section>
        </div>
      </main>
    </DemoBagProvider>
  );
}
