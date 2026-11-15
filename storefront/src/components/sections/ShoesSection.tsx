import ShoeShelf from "@/components/shoes/ShoeShelf";
import { visibleShoes } from "@/content/shoes";
import type { ShoesFile } from "@/content/shoes-types";

/**
 * «أحذية تكمل إطلالتك» — lit shoe shelves under «وصل حديثًا».
 * Content from src/content/shoes.json (admin: /admin/shoes).
 */
export default function ShoesSection({ data }: { data: ShoesFile }) {
  const shoes = visibleShoes(data);
  if (shoes.length === 0) return null;
  const { title, subtitle } = data.settings;
  return (
    <section id="shoes" aria-labelledby="shoes-title" className="border-b border-line bg-background">
      <div className="mx-auto max-w-[1440px] px-4 py-10 sm:px-8 sm:py-14">
        <header className="mb-4 sm:mb-6">
          <h2 id="shoes-title" className="font-display text-[28px] leading-tight font-semibold text-ink sm:text-4xl lg:text-[2.75rem]">
            {title}
          </h2>
          {subtitle && <p className="mt-2 text-[15px] text-muted sm:text-base">{subtitle}</p>}
        </header>
        <ShoeShelf shoes={shoes} />
        <p className="mt-3 text-[12px] text-muted">
          صور تمثيلية مجهّزة للعرض وليست من مخزون المتجر، والأسعار والمقاسات تجريبية حتى يُربط كتالوج المتجر.
        </p>
      </div>
    </section>
  );
}
