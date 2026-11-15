"use client";

import { motion, useReducedMotion } from "framer-motion";
import Image from "next/image";
import Link from "next/link";
import type { CategoryCard, CategoryCardSize } from "@/content/category-card-types";
import { cn } from "@/lib/utils";

/**
 * Category image grid — adapted from 21st.dev "Shop by Category Grid"
 * (ziegfiroyt/ecommerce4: image cards, bottom gradient, title + link,
 * slow image zoom on hover). Changes for Bin Huwesh:
 *  - bento layout: each card's `size` spans 1–2 columns/rows (4 columns on
 *    desktop, 2 on phones; `grid-flow-dense` closes gaps);
 *  - Arabic/RTL: text starts on the right, the arrow points left (forward);
 *  - touch: the title and «اكتشف التشكيلة» are always visible — hover only
 *    adds the zoom, the underline and the arrow nudge;
 *  - crop focal point from data (object-position), next/image instead of
 *    <img>, no Badge/lucide dependency, no product counts;
 *  - light entrance motion that is skipped with prefers-reduced-motion.
 */

const SPAN: Record<CategoryCardSize, string> = {
  large: "col-span-2 row-span-2",
  tall: "row-span-2",
  wide: "col-span-2",
  regular: "",
};

/** Rendered width of each size, so next/image picks a sensible file. */
const SIZES: Record<CategoryCardSize, string> = {
  large: "(min-width: 1024px) 50vw, 100vw",
  wide: "(min-width: 1024px) 50vw, 100vw",
  tall: "(min-width: 1024px) 25vw, 50vw",
  regular: "(min-width: 1024px) 25vw, 50vw",
};

const TITLE: Record<CategoryCardSize, string> = {
  large: "text-[22px] sm:text-3xl lg:text-[2.6rem]",
  tall: "text-[17px] sm:text-2xl lg:text-[1.9rem]",
  wide: "text-[19px] sm:text-2xl lg:text-[1.9rem]",
  regular: "text-[16px] sm:text-xl lg:text-[1.55rem]",
};

export type CategoryBentoGridProps = {
  cards: CategoryCard[];
  /** Link label shown on every card. */
  cta: string;
  className?: string;
};

export function CategoryBentoGrid({ cards, cta, className }: CategoryBentoGridProps) {
  const reduce = useReducedMotion();
  if (cards.length === 0) return null;

  return (
    <ul
      className={cn(
        "grid grid-flow-row-dense grid-cols-2 gap-2.5 sm:gap-3 lg:grid-cols-4 lg:gap-4",
        "auto-rows-[clamp(150px,46vw,230px)] sm:auto-rows-[230px] lg:auto-rows-[clamp(210px,19vw,290px)]",
        className,
      )}
    >
      {cards.map((card, i) => (
        <motion.li
          key={card.id}
          className={cn("min-w-0", SPAN[card.size])}
          // Same initial on server and client (useReducedMotion is null during
          // SSR) — reduced motion only drops the duration, so no hydration diff.
          initial={{ opacity: 0, y: 18 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, amount: 0.15 }}
          transition={reduce ? { duration: 0 } : { duration: 0.6, delay: Math.min(i, 7) * 0.06, ease: [0.2, 0.7, 0.2, 1] }}
        >
          <Link
            href={card.href}
            className={cn(
              "group/cat relative isolate block size-full overflow-hidden rounded-2xl bg-stage",
              "outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 focus-visible:ring-offset-background",
            )}
          >
            <Image
              src={card.image}
              alt={card.alt || card.name}
              fill
              sizes={SIZES[card.size]}
              style={{ objectPosition: `${card.focus.x}% ${card.focus.y}%` }}
              className={cn(
                "object-cover transition-transform duration-700 ease-[cubic-bezier(0.2,0.7,0.2,1)]",
                "group-hover/cat:scale-[1.06] group-focus-visible/cat:scale-[1.06]",
                "motion-reduce:transition-none motion-reduce:group-hover/cat:scale-100 motion-reduce:group-focus-visible/cat:scale-100",
              )}
            />
            {/* legibility: same gradient in both themes (the photo keeps its colours) */}
            <span
              aria-hidden
              className="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/75 via-black/20 via-45% to-transparent transition-opacity duration-500 group-hover/cat:opacity-95"
            />
            <span className="absolute inset-x-0 bottom-0 flex flex-col items-start gap-1.5 p-3.5 sm:gap-2 sm:p-5 lg:p-6">
              <span aria-hidden className="h-[3px] w-6 rounded-full bg-brand transition-[width] duration-500 group-hover/cat:w-10 motion-reduce:transition-none" />
              <h3 className={cn("font-display leading-[1.15] font-semibold text-balance text-white", TITLE[card.size])}>
                {card.name}
              </h3>
              <span className="flex items-center gap-1.5 text-[12px] font-medium text-white/90 sm:text-[13.5px]">
                <span className="relative">
                  {cta}
                  <span
                    aria-hidden
                    className="absolute inset-x-0 -bottom-0.5 h-px origin-right scale-x-0 bg-white transition-transform duration-500 group-hover/cat:scale-x-100 group-focus-visible/cat:scale-x-100 motion-reduce:transition-none"
                  />
                </span>
                <svg
                  aria-hidden
                  viewBox="0 0 24 24"
                  className="size-4 transition-transform duration-500 group-hover/cat:-translate-x-1 motion-reduce:transition-none"
                  fill="none"
                  stroke="currentColor"
                  strokeWidth={1.8}
                  strokeLinecap="round"
                  strokeLinejoin="round"
                >
                  <path d="M19 12H5M11 6l-6 6 6 6" />
                </svg>
              </span>
            </span>
          </Link>
        </motion.li>
      ))}
    </ul>
  );
}

export default CategoryBentoGrid;
