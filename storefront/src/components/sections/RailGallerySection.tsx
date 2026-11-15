"use client";

import ClothingRailHero, { type RailGarment } from "@/components/ui/product-rail-gallery";
import { RAIL_ITEMS } from "@/content/rail";
import { useI18n } from "@/lib/i18n/context";

export default function RailGallerySection({ items = RAIL_ITEMS }: { items?: RailGarment[] }) {
  const { t } = useI18n();
  return (
    <div dir="ltr" className="border-b border-line bg-background">
      <div className="mx-auto max-w-[1440px]">
        <ClothingRailHero
          items={items}
          hanger="none"
          autoplay
          eyebrow={t.sections.rail.eyebrow}
          heading={t.sections.rail.heading}
          caption={t.sections.rail.caption}
          labels={t.sections.rail.labels}
          className="[&_h2]:font-display"
          action={
            <button
              type="button"
              className="rounded-full bg-foreground px-5 py-2.5 text-sm font-medium text-background shadow-sm outline-none transition-[transform,box-shadow] hover:-translate-y-px hover:shadow-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background motion-reduce:transition-none"
            >
              {t.sections.rail.action}
            </button>
          }
        />
      </div>
    </div>
  );
}
