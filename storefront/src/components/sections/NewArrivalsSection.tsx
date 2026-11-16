"use client";

import Link from "next/link";
import VirtualFit from "@/components/new-arrivals/VirtualFit";
import { visibleItems } from "@/content/new-arrivals";
import type { NewArrivalsFile } from "@/content/new-arrivals-types";
import { useI18n } from "@/lib/i18n/context";

/**
 * «وصل حديثًا» — VirtualFit-style try-on scene under «تسوّق حسب القسم».
 * Content from src/content/new-arrivals.json (admin: /admin/new-arrivals).
 */
export default function NewArrivalsSection({ data }: { data: NewArrivalsFile }) {
  const { locale, t } = useI18n();
  if (visibleItems(data).length === 0) return null;
  const isEn = locale === "en";
  const { title, subtitle } = data.settings;
  return (
    <section id="new-arrivals" aria-labelledby="new-arrivals-title" className="border-b border-line bg-background">
      <div className="mx-auto max-w-[1440px] px-4 py-10 sm:px-8 sm:py-14">
        <header className="mb-3 flex flex-wrap items-end justify-between gap-4 sm:mb-4">
          <div>
            <h2 id="new-arrivals-title" className="font-display text-[28px] leading-tight font-semibold text-ink sm:text-4xl lg:text-[2.75rem]">
              {title}
            </h2>
            {subtitle && <p className="mt-2 text-[15px] text-muted sm:text-base">{subtitle}</p>}
          </div>
          <Link
            href="/products?sort_by=newest"
            className="inline-flex h-10 shrink-0 items-center gap-1.5 rounded-full border border-line bg-card px-5 text-sm font-medium text-ink transition-colors hover:border-brand hover:text-brand"
          >
            <span>{t.sections.viewAll}</span>
            <span aria-hidden>{isEn ? "→" : "←"}</span>
          </Link>
        </header>
        <VirtualFit data={data} />
      </div>
    </section>
  );
}
