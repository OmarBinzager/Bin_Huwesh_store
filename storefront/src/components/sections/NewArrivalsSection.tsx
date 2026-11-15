import VirtualFit from "@/components/new-arrivals/VirtualFit";
import { visibleItems } from "@/content/new-arrivals";
import type { NewArrivalsFile } from "@/content/new-arrivals-types";

/**
 * «وصل حديثًا» — VirtualFit-style try-on scene under «تسوّق حسب القسم».
 * Content from src/content/new-arrivals.json (admin: /admin/new-arrivals).
 */
export default function NewArrivalsSection({ data }: { data: NewArrivalsFile }) {
  if (visibleItems(data).length === 0) return null;
  const { title, subtitle } = data.settings;
  return (
    <section id="new-arrivals" aria-labelledby="new-arrivals-title" className="border-b border-line bg-background">
      <div className="mx-auto max-w-[1440px] px-4 py-10 sm:px-8 sm:py-14">
        <header className="mb-3 sm:mb-4">
          <h2 id="new-arrivals-title" className="font-display text-[28px] leading-tight font-semibold text-ink sm:text-4xl lg:text-[2.75rem]">
            {title}
          </h2>
          {subtitle && <p className="mt-2 text-[15px] text-muted sm:text-base">{subtitle}</p>}
        </header>
        <VirtualFit data={data} />
        <p className="mt-3 text-[12px] text-muted">
          معاينة تجريبية: صور مجهّزة مسبقًا لعرض القطع على المودل، والأسعار تجريبية حتى يُربط كتالوج المتجر.
        </p>
      </div>
    </section>
  );
}
