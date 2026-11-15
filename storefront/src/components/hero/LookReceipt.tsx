"use client";

import { useEffect, useRef, useState } from "react";
import type { Look } from "@/content/types";
import { formatYER, resolvePrice } from "@/lib/catalog";
import { useI18n } from "@/lib/i18n/context";
import BrandLogo from "@/components/store/BrandLogo";
import { useDemoBag } from "@/components/store/DemoBag";

type Props = {
  look: Look;
  index: number;
  selectedItem: string | null;
  onSelectItem: (id: string) => void;
};

/**
 * "فاتورة الإطلالة" — the look's pieces as a shop receipt. A size must be
 * picked before a piece goes in the bag (the reference skips this, but a
 * real order cannot). Everything it adds goes to the DEMO bag.
 */
export default function LookReceipt({ look, index, selectedItem, onSelectItem }: Props) {
  const { add } = useDemoBag();
  const { locale } = useI18n();
  const isEn = locale === "en";
  const [sizes, setSizes] = useState<Record<string, string>>({});
  const [missing, setMissing] = useState<string[]>([]);
  const rowRefs = useRef<Record<string, HTMLLIElement | null>>({});

  useEffect(() => {
    if (selectedItem) rowRefs.current[selectedItem]?.scrollIntoView({ block: "nearest", behavior: "smooth" });
  }, [selectedItem]);

  const prices = look.items.map((it) => resolvePrice(it));
  const subtotal = prices.reduce((s, p) => s + p.amount, 0);
  const discount = look.wholeLookDiscount ? Math.round((subtotal * look.wholeLookDiscount) / 100) : 0;
  const total = subtotal - discount;

  const pick = (id: string, size: string) => {
    setSizes((s) => ({ ...s, [id]: size }));
    setMissing((m) => m.filter((x) => x !== id));
  };

  const addOne = (id: string) => {
    const it = look.items.find((x) => x.id === id)!;
    const size = sizes[id];
    if (!size) {
      setMissing((m) => [...new Set([...m, id])]);
      onSelectItem(id);
      return;
    }
    add([{ lookId: look.id, itemId: id, productId: it.productId, label: it.label, size, price: resolvePrice(it).amount }]);
  };

  const addAll = () => {
    const lacking = look.items.filter((it) => !sizes[it.id]).map((it) => it.id);
    if (lacking.length) {
      setMissing(lacking);
      onSelectItem(lacking[0]);
      return;
    }
    add(
      look.items.map((it) => ({
        lookId: look.id,
        itemId: it.id,
        productId: it.productId,
        label: it.label,
        size: sizes[it.id],
        price: resolvePrice(it).amount,
        lookDiscountPct: look.wholeLookDiscount,
      })),
    );
  };

  return (
    <div className="flex h-full flex-col">
      <div className="flex min-h-0 flex-1 flex-col overflow-y-auto overscroll-contain bg-card text-ink [scrollbar-width:thin] px-5 pt-5 pb-4 shadow-[0_18px_50px_-20px_rgba(0,0,0,0.35)]">
        <header className="border-b border-dashed border-ink/25 pb-4 text-center">
          <div className="flex justify-center">
            <BrandLogo height={34} />
          </div>
          <p className="mt-1 text-[11px] text-muted">
            {isEn ? "Look Receipt · Aden" : "فاتورة الإطلالة · عدن"}
          </p>
          <p className="nums mt-1 text-[12px] font-medium">
            {isEn ? "Look" : "إطلالة"} {String(index + 1).padStart(2, "0")} — {look.title}
          </p>
          <p className="mt-3 inline-flex rounded-full bg-brand/8 px-3 py-1 text-[11px] font-medium text-brand-deep dark:bg-brand/20 dark:text-[#ff7a80]">
            {isEn ? "Preview: prices are sample estimates" : "معاينة: الأسعار تجريبية وغير نهائية"}
          </p>
        </header>

        <ol className="divide-y divide-dashed divide-ink/15">
          {look.items.map((it, n) => {
            const active = selectedItem === it.id;
            const lacks = missing.includes(it.id);
            return (
              <li
                key={it.id}
                ref={(el) => {
                  rowRefs.current[it.id] = el;
                }}
                className={`-mx-2 rounded-lg px-2 py-3 transition-colors ${active ? "bg-ink/5" : ""}`}
              >
                <button
                  type="button"
                  onClick={() => onSelectItem(it.id)}
                  className="flex w-full items-start gap-3 text-start"
                >
                  <span className="nums mt-0.5 text-[11px] text-muted">{String(n + 1).padStart(2, "0")}</span>
                  <span className="flex-1">
                    <span className="block text-[13.5px] font-medium leading-snug">{it.label}</span>
                    <span className="block text-[11px] text-muted">{it.category}</span>
                  </span>
                  <span className="nums text-[13px] font-medium">{formatYER(prices[n].amount, locale)}</span>
                </button>

                <div className="mt-2 flex items-center gap-2 ps-6">
                  <div className="flex flex-1 flex-wrap gap-1.5" role="radiogroup" aria-label={`${isEn ? "Size" : "مقاس"} ${it.label}`}>
                    {it.sizes.map((s) => {
                      const on = sizes[it.id] === s;
                      return (
                        <button
                          key={s}
                          type="button"
                          role="radio"
                          aria-checked={on}
                          onClick={() => pick(it.id, s)}
                          className={`nums min-w-9 rounded-full border px-2.5 py-1 text-[11px] transition-colors ${
                            on ? "border-ink bg-ink text-on-ink" : lacks ? "border-brand text-ink" : "border-ink/15 hover:border-ink/50"
                          }`}
                        >
                          {s}
                        </button>
                      );
                    })}
                  </div>
                  <button
                    type="button"
                    onClick={() => addOne(it.id)}
                    className="rounded-full border border-dashed border-ink/40 px-3 py-1 text-[11px] font-medium hover:border-ink hover:bg-ink hover:text-on-ink"
                  >
                    {isEn ? "Add" : "أضف"}
                  </button>
                </div>
                {lacks && (
                  <p className="mt-1.5 ps-6 text-[11px] text-brand">
                    {isEn ? "Select a size first" : "اختر المقاس أولًا"}
                  </p>
                )}
              </li>
            );
          })}
        </ol>

        {look.items.length === 0 ? (
          <p className="py-6 text-center text-[12px] text-muted">
            {isEn ? "No items added to this look yet." : "لا توجد قطع مضافة لهذه الإطلالة بعد."}
          </p>
        ) : (
        <>
        <div className="mt-1 space-y-1.5 border-t border-dashed border-ink/25 pt-3 text-[12.5px]">
          <div className="flex justify-between">
            <span className="text-muted">{isEn ? "Subtotal" : "المجموع"}</span>
            <span className="nums">{formatYER(subtotal, locale)}</span>
          </div>
          {discount > 0 && (
            <div className="flex justify-between">
              <span className="text-muted">
                {isEn
                  ? `Full look discount ${look.wholeLookDiscount}%`
                  : `خصم الإطلالة كاملة ${look.wholeLookDiscount}%`}
              </span>
              <span className="nums text-brand">−{formatYER(discount, locale)}</span>
            </div>
          )}
          <div className="flex items-baseline justify-between pt-2">
            <span className="font-display text-xl font-semibold">{isEn ? "Total" : "الإجمالي"}</span>
            <span className="nums font-display text-2xl font-semibold">{formatYER(total, locale)}</span>
          </div>
        </div>

        <button
          type="button"
          onClick={addAll}
          className="mt-4 flex w-full items-center justify-center gap-2 rounded-full bg-brand px-5 py-3.5 text-[14px] font-semibold text-white transition-colors hover:bg-brand-deep"
        >
          {isEn ? "Add Full Look to Bag" : "أضف الإطلالة كاملة إلى السلة"}
        </button>
        </>
        )}

        <div
          aria-hidden
          className="mx-auto mt-5 h-9 w-full max-w-[260px] opacity-80"
          style={{
            background:
              "repeating-linear-gradient(90deg,var(--bh-ink) 0 2px,transparent 2px 4px,var(--bh-ink) 4px 5px,transparent 5px 8px,var(--bh-ink) 8px 11px,transparent 11px 12px)",
          }}
        />
        <p className="mt-2 text-center text-[11px] text-muted">
          {isEn ? "Thank you for choosing Bin Huwesh" : "شكرًا لاختياركم بن هويش"}
        </p>
      </div>
      <div aria-hidden className="zigzag" />
    </div>
  );
}
