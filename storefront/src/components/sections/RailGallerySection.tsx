"use client";

import ClothingRailHero, { type RailGarment } from "@/components/ui/product-rail-gallery";
import { RAIL_ITEMS } from "@/content/rail";

/**
 * Clothing-rail section under the brand strip — the supplied
 * product-rail-gallery, used as in its demo (photos with built-in hangers,
 * autoplay), with Arabic copy and the site's light/dark palette.
 *
 * `dir="ltr"`: the rail's physics, swipe and dots are laid out left→right in
 * the original; keeping that inside the Arabic page preserves its behaviour
 * exactly (the Arabic headings still render correctly and stay centred).
 */
export default function RailGallerySection({ items = RAIL_ITEMS }: { items?: RailGarment[] }) {
  return (
    <div dir="ltr" className="border-b border-line bg-background">
      <div className="mx-auto max-w-[1440px]">
        <ClothingRailHero
          items={items}
          hanger="none"
          autoplay
          eyebrow="تشكيلة الموسم · عشر قطع على العلّاقة"
          heading="علّاقة ملابس تتصفّحها بيدك"
          caption="مرّر على العلّاقة، حرّك علّاقة، وارفع القطعة لترى ظهرها."
          labels={{ front: "الأمام", back: "الخلف", close: "إغلاق", show: "عرض" }}
          className="[&_h2]:font-display"
          action={
            <button
              type="button"
              className="rounded-full bg-foreground px-5 py-2.5 text-sm font-medium text-background shadow-sm outline-none transition-[transform,box-shadow] hover:-translate-y-px hover:shadow-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background motion-reduce:transition-none"
            >
              تسوّق التشكيلة
            </button>
          }
        />
      </div>
    </div>
  );
}
