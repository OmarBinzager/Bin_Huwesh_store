import Link from "next/link";
import { Price } from "@/components/store/Price";
import { TryOnButton } from "@/components/store/TryOnButton";
import type { Dictionary } from "@/lib/i18n/dictionaries";
import { productHref } from "@/lib/product-url";
import type { CatalogProduct } from "@/lib/storefront-api";

type CatalogProductGridProps = {
  products: CatalogProduct[];
  totalSize: number;
  limit: number;
  page: number;
  t: Dictionary;
  buildUrl: (overrides: Record<string, string | number | undefined>) => string;
  emptyBackHref?: string;
};

export default function CatalogProductGrid({
  products,
  totalSize,
  limit,
  page,
  t,
  buildUrl,
  emptyBackHref = "/products",
}: CatalogProductGridProps) {
  if (products.length === 0) {
    return (
      <div className="rounded-2xl border border-line bg-card p-8 text-center">
        <p className="font-display text-lg font-semibold text-ink">{t.catalog.emptyTitle}</p>
        <p className="mt-2 text-[14px] text-muted">{t.catalog.emptyBody}</p>
        <Link
          href={emptyBackHref}
          className="mt-4 inline-flex h-10 items-center rounded-full bg-ink px-5 text-[13px] font-medium text-on-ink transition-colors hover:bg-brand hover:text-white"
        >
          {t.catalog.clearAllFilters}
        </Link>
      </div>
    );
  }

  const from = (page - 1) * limit + 1;
  const to = Math.min((page - 1) * limit + products.length, totalSize);

  return (
    <>
      <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4 lg:gap-5">
        {products.map((product) => {
          const hasDiscount = product.discount > 0 && product.final_price < product.price;
          const outOfStock = product.total_stock <= 0;
          return (
            <Link
              key={product.id}
              href={productHref(product.id, product.name)}
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
                    {t.catalog.noImage}
                  </div>
                )}
                {hasDiscount && (
                  <span className="absolute top-3 right-3 rounded-full bg-brand px-2.5 py-1 text-[11px] font-semibold text-white">
                    {product.discount_type === "percent" ? (
                      t.catalog.discountPct(Math.round(product.discount))
                    ) : (
                      <>
                        −<Price amount={product.price - product.final_price} />
                      </>
                    )}
                  </span>
                )}
                {outOfStock && (
                  <span className="absolute inset-x-3 bottom-3 rounded-full bg-black/75 py-1 text-center text-[11px] font-medium text-white">
                    {t.catalog.outOfStock}
                  </span>
                )}
                {product.try_on_enabled && product.image && !outOfStock && (
                  <div className="absolute bottom-3 left-3 z-10">
                    <TryOnButton
                      productId={product.id}
                      productName={product.name}
                      garmentImage={product.image}
                      locale={t.catalog.outOfStock === "Out of stock" ? "en" : "ar"}
                      variant="card"
                    />
                  </div>
                )}
              </div>

              <div className="flex flex-1 flex-col justify-between p-3.5 sm:p-4">
                <div>
                  {product.brand?.name && (
                    <p className="text-[11px] font-medium text-muted">{product.brand.name}</p>
                  )}
                  <h2 className="mt-1 line-clamp-2 text-[14px] font-semibold text-ink group-hover:text-brand">
                    {product.name}
                  </h2>
                </div>

                <div className="mt-3 flex items-baseline justify-between gap-2">
                  <div className="flex items-baseline gap-2">
                    <span className="nums text-[15px] font-bold text-ink">
                      <Price amount={product.final_price} />
                    </span>
                    {hasDiscount && (
                      <span className="nums text-[12px] text-muted line-through">
                        <Price amount={product.price} />
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

      {totalSize > limit && (
        <div className="mt-10 flex items-center justify-center gap-3">
          {page > 1 && (
            <Link
              href={buildUrl({ offset: page - 1 })}
              className="rounded-full border border-line bg-card px-5 py-2 text-[13px] font-medium text-ink hover:border-brand hover:text-brand"
            >
              {t.catalog.prev}
            </Link>
          )}
          <span className="nums text-[13px] text-muted">
            {t.catalog.showingRange(from, to, totalSize)}
          </span>
          {to < totalSize && (
            <Link
              href={buildUrl({ offset: page + 1 })}
              className="rounded-full border border-line bg-card px-5 py-2 text-[13px] font-medium text-ink hover:border-brand hover:text-brand"
            >
              {t.catalog.next}
            </Link>
          )}
        </div>
      )}
    </>
  );
}
