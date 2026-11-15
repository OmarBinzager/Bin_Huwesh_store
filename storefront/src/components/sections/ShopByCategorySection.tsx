import CurvedLensCarousel, { type LensSlide } from "@/components/ui/3d-curved-carousel";
import { getStoreCategory } from "@/content/categories";
import type { ShopCategoriesFile } from "@/content/category-card-types";
import { visibleCards } from "@/content/shop-categories";

/**
 * «تسوّق حسب القسم» — department carousel under the clothing rail.
 * Content (names, descriptions, images, order, links, crop) comes from
 * src/content/shop-categories.json; motion from the curved-lens carousel.
 * The previous layout (components/ui/category-bento-grid.tsx) is kept, unused.
 */
export default function ShopByCategorySection({ data }: { data: ShopCategoriesFile }) {
  const cards = visibleCards(data.cards);
  if (cards.length === 0) return null;
  const { title, subtitle, cta } = data.settings;
  const slides: LensSlide[] = cards.map((c) => ({
    title: c.name,
    caption: c.caption?.trim() || getStoreCategory(c.categoryId)?.blurb,
    image: c.image,
    alt: c.alt || c.name,
    href: c.href,
    focus: c.focus,
  }));

  return (
    <section id="shop-by-category" aria-labelledby="shop-by-category-title" className="border-b border-line bg-background">
      <div className="mx-auto max-w-[1440px] px-4 py-10 sm:px-8 sm:py-14">
        <header className="mb-3 sm:mb-4">
          <h2 id="shop-by-category-title" className="font-display text-[28px] leading-tight font-semibold text-ink sm:text-4xl lg:text-[2.75rem]">
            {title}
          </h2>
          {subtitle && <p className="mt-2 text-[15px] text-muted sm:text-base">{subtitle}</p>}
        </header>
        <CurvedLensCarousel
          slides={slides}
          dir="rtl"
          label="أقسام بن هويش"
          hint="اسحب لاستكشاف الأقسام"
          cta={cta}
          className="rounded-3xl"
        />
      </div>
    </section>
  );
}
