"use client";

import Link from "next/link";
import ShoeShelf from "@/components/shoes/ShoeShelf";
import { visibleShoes } from "@/content/shoes";
import type { ShoesFile } from "@/content/shoes-types";
import { useI18n } from "@/lib/i18n/context";

/**
 * «أحذية تكمل إطلالتك» — lit shoe shelves under «وصل حديثًا».
 * Content from src/content/shoes.json (admin: /admin/shoes).
 */
export default function ShoesSection({ data }: { data: ShoesFile }) {
  const { locale, t } = useI18n();
  const shoes = visibleShoes(data);
  if (shoes.length === 0) return null;
  const isEn = locale === "en";
  const { title, subtitle } = data.settings;
  return (
    <section id="shoes" aria-labelledby="shoes-title" className="border-b border-line bg-background">
      <div className="mx-auto max-w-[1440px] px-4 py-10 sm:px-8 sm:py-14">
        <header className="mb-4 flex flex-wrap items-end justify-between gap-4 sm:mb-6">
          <div>
            <h2 id="shoes-title" className="font-display text-[28px] leading-tight font-semibold text-ink sm:text-4xl lg:text-[2.75rem]">
              {title}
            </h2>
            {subtitle && <p className="mt-2 text-[15px] text-muted sm:text-base">{subtitle}</p>}
          </div>
          <Link
            href="/categories/shoes"
            className="inline-flex h-10 shrink-0 items-center gap-1.5 rounded-full border border-line bg-card px-5 text-sm font-medium text-ink transition-colors hover:border-brand hover:text-brand"
          >
            <span>{t.sections.viewAll}</span>
            <span aria-hidden>{isEn ? "→" : "←"}</span>
          </Link>
        </header>
        <ShoeShelf shoes={shoes} />
      </div>
    </section>
  );
}
