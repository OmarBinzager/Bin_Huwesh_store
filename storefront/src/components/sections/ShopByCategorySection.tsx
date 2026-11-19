import Link from "next/link";
import CurvedLensCarousel, { type LensSlide } from "@/components/ui/3d-curved-carousel";
import { getStoreCategory } from "@/content/categories";
import type { ShopCategoriesFile } from "@/content/category-card-types";
import { visibleCards } from "@/content/shop-categories";
import { getDirection } from "@/lib/i18n/config";
import { getDictionary } from "@/lib/i18n/dictionaries";

export default function ShopByCategorySection({
  data,
  locale = "ar",
}: {
  data: ShopCategoriesFile;
  locale?: "ar" | "en";
}) {
  const cards = visibleCards(data.cards);
  if (cards.length === 0) return null;
  const t = getDictionary(locale);
  const dir = getDirection(locale);
  const { title, subtitle, cta } = data.settings;
  const slides: LensSlide[] = cards.map((c) => ({
    title: c.name,
    caption: c.caption?.trim() || t.categories[c.categoryId]?.blurb || getStoreCategory(c.categoryId)?.blurb,
    image: c.image,
    alt: c.alt || c.name,
    href: c.href,
    focus: c.focus,
  }));

  return (
    <section id="shop-by-category" aria-labelledby="shop-by-category-title" className="border-b border-line bg-background">
      <div className="mx-auto max-w-[1440px] px-4 py-10 sm:px-8 sm:py-14">
        <header className="mb-3 flex flex-wrap items-end justify-between gap-4 sm:mb-4">
          <div>
            <h2 id="shop-by-category-title" className="font-display text-[28px] leading-tight font-semibold text-ink sm:text-4xl lg:text-[2.75rem]">
              {title}
            </h2>
            {subtitle && <p className="mt-2 text-[15px] text-muted sm:text-base">{subtitle}</p>}
          </div>
          <Link
            href="/products"
            className="inline-flex h-10 shrink-0 items-center gap-1.5 rounded-full border border-line bg-card px-5 text-sm font-medium text-ink transition-colors hover:border-brand hover:text-brand"
          >
            <span>{t.sections.viewAll}</span>
            <span aria-hidden>{locale === "en" ? "→" : "←"}</span>
          </Link>
        </header>
        <CurvedLensCarousel
          slides={slides}
          dir={dir}
          label={t.sections.categoriesAriaLabel}
          hint={t.sections.categoriesHint}
          cta={cta}
          className="rounded-3xl"
        />
      </div>
    </section>
  );
}
