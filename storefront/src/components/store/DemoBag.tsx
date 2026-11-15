"use client";

import Link from "next/link";
import { createContext, useCallback, useContext, useEffect, useState, type ReactNode } from "react";
import { formatYER } from "@/lib/catalog";
import { useI18n } from "@/lib/i18n/context";

export type BagLine = {
  key: string;
  lookId: string;
  itemId: string;
  productId?: string | number | null;
  label: string;
  size: string;
  color?: string;
  price: number;
  qty: number;
  image?: string;
  lookDiscountPct?: number;
};

type AddInput = Omit<BagLine, "key" | "qty"> & { qty?: number };

type BagContext = {
  lines: BagLine[];
  count: number;
  subtotal: number;
  lookDiscount: number;
  isOpen: boolean;
  openBag: () => void;
  closeBag: () => void;
  add: (items: AddInput[]) => void;
  updateQty: (key: string, qty: number) => void;
  removeLine: (key: string) => void;
  clear: () => void;
};

const STORAGE_KEY = "bh-demo-bag";
const Ctx = createContext<BagContext>({
  lines: [],
  count: 0,
  subtotal: 0,
  lookDiscount: 0,
  isOpen: false,
  openBag: () => {},
  closeBag: () => {},
  add: () => {},
  updateQty: () => {},
  removeLine: () => {},
  clear: () => {},
});

export const useDemoBag = () => useContext(Ctx);

export function DemoBagProvider({ children }: { children: ReactNode }) {
  const { locale, dir, t } = useI18n();
  const [lines, setLines] = useState<BagLine[]>([]);
  const [isOpen, setIsOpen] = useState(false);
  const [toast, setToast] = useState<string | null>(null);

  useEffect(() => {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (raw) {
        const parsed = JSON.parse(raw) as BagLine[];
        setLines(parsed.map((l) => ({ ...l, qty: l.qty || 1 })));
      }
    } catch {
      /* ignore corrupt storage */
    }
  }, []);

  const persist = useCallback((next: BagLine[]) => {
    setLines(next);
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(next));
    } catch {
      /* ignore quota */
    }
  }, []);

  const add = useCallback(
    (incoming: AddInput[]) => {
      setLines((prev) => {
        const map = new Map(prev.map((l) => [l.key, { ...l }]));
        for (const item of incoming) {
          const key = `${item.lookId}:${item.itemId}:${item.size}:${item.color ?? ""}`;
          const existing = map.get(key);
          if (existing) {
            existing.qty += item.qty ?? 1;
          } else {
            map.set(key, {
              ...item,
              key,
              qty: item.qty ?? 1,
            });
          }
        }
        const next = Array.from(map.values());
        try {
          localStorage.setItem(STORAGE_KEY, JSON.stringify(next));
        } catch {
          /* ignore */
        }
        return next;
      });
      const msg = incoming.length === 1 ? t.bag.addedOne(incoming[0].label) : t.bag.addedMany(incoming.length);
      setToast(msg);
      setTimeout(() => setToast((cur) => (cur === msg ? null : cur)), 2800);
    },
    [t],
  );

  const updateQty = useCallback(
    (key: string, qty: number) => {
      if (qty <= 0) {
        persist(lines.filter((l) => l.key !== key));
      } else {
        persist(lines.map((l) => (l.key === key ? { ...l, qty } : l)));
      }
    },
    [lines, persist],
  );

  const removeLine = useCallback(
    (key: string) => {
      persist(lines.filter((l) => l.key !== key));
    },
    [lines, persist],
  );

  const clear = useCallback(() => {
    persist([]);
  }, [persist]);

  const count = lines.reduce((sum, l) => sum + (l.qty || 1), 0);
  const subtotal = lines.reduce((sum, l) => sum + l.price * (l.qty || 1), 0);
  const lookDiscount = lines.reduce(
    (sum, l) =>
      sum +
      (l.lookDiscountPct && l.lookDiscountPct > 0
        ? Math.round((l.price * (l.qty || 1) * l.lookDiscountPct) / 100)
        : 0),
    0,
  );

  return (
    <Ctx.Provider
      value={{
        lines,
        count,
        subtotal,
        lookDiscount,
        isOpen,
        openBag: () => setIsOpen(true),
        closeBag: () => setIsOpen(false),
        add,
        updateQty,
        removeLine,
        clear,
      }}
    >
      {children}

      {/* Toast Notification */}
      {toast && (
        <div
          role="status"
          className="fixed bottom-5 left-1/2 z-[1200] -translate-x-1/2 rounded-full bg-ink px-5 py-2.5 text-[13px] font-medium text-on-ink shadow-xl"
        >
          {toast}
        </div>
      )}

      {/* Production Cart Drawer */}
      {isOpen && (
        <div className="fixed inset-0 z-[1100] flex justify-end bg-black/50 backdrop-blur-xs" onClick={() => setIsOpen(false)}>
          <aside
            dir={dir}
            aria-label={t.bag.ariaLabel}
            onClick={(e) => e.stopPropagation()}
            className="flex h-full w-full max-w-md flex-col bg-card text-ink shadow-2xl"
          >
            <header className="flex items-center justify-between border-b border-line px-5 py-4">
              <h2 className="font-display text-lg font-semibold">{t.bag.title(count)}</h2>
              <button
                type="button"
                onClick={() => setIsOpen(false)}
                aria-label={t.bag.closeAria}
                className="grid size-9 place-items-center rounded-full border border-line hover:border-ink"
              >
                ✕
              </button>
            </header>

            <div className="flex-1 overflow-y-auto p-5">
              {lines.length === 0 ? (
                <div className="grid h-full place-items-center text-center">
                  <div>
                    <p className="text-[15px] text-muted">{t.bag.empty}</p>
                    <button
                      type="button"
                      onClick={() => setIsOpen(false)}
                      className="mt-4 rounded-full bg-ink px-5 py-2 text-[13px] font-medium text-on-ink"
                    >
                      {t.bag.continueShopping}
                    </button>
                  </div>
                </div>
              ) : (
                <ul className="divide-y divide-line">
                  {lines.map((l) => (
                    <li key={l.key} className="flex gap-3 py-4">
                      {l.image && (
                        <img src={l.image} alt={l.label} className="size-16 shrink-0 rounded-xl bg-stage object-contain p-1" />
                      )}
                      <div className="min-w-0 flex-1">
                        <p className="truncate text-[14px] font-medium">{l.label}</p>
                        <p className="mt-0.5 text-[12px] text-muted">
                          {t.bag.sizeLabel} <span dir="ltr">{l.size}</span>
                        </p>
                        <div className="mt-2 flex items-center justify-between">
                          <div className="inline-flex items-center rounded-full border border-line">
                            <button
                              type="button"
                              onClick={() => updateQty(l.key, (l.qty || 1) - 1)}
                              className="px-2.5 py-0.5 text-[14px] hover:text-brand"
                              aria-label={t.bag.decreaseQty}
                            >
                              −
                            </button>
                            <span className="nums px-2 text-[12px] font-medium">{l.qty || 1}</span>
                            <button
                              type="button"
                              onClick={() => updateQty(l.key, (l.qty || 1) + 1)}
                              className="px-2.5 py-0.5 text-[14px] hover:text-brand"
                              aria-label={t.bag.increaseQty}
                            >
                              +
                            </button>
                          </div>
                          <span className="nums text-[13px] font-semibold">{formatYER(l.price * (l.qty || 1), locale)}</span>
                        </div>
                      </div>
                      <button
                        type="button"
                        onClick={() => removeLine(l.key)}
                        aria-label={t.bag.removeAria(l.label)}
                        className="self-start text-[12px] text-muted hover:text-brand"
                      >
                        {t.bag.remove}
                      </button>
                    </li>
                  ))}
                </ul>
              )}
            </div>

            {lines.length > 0 && (
              <footer className="border-t border-line bg-background/60 p-5">
                <div className="mb-3 flex items-baseline justify-between">
                  <span className="text-[14px] text-muted">{t.bag.subtotal}</span>
                  <span className="nums font-display text-xl font-semibold">{formatYER(subtotal, locale)}</span>
                </div>
                <Link
                  href="/checkout"
                  onClick={() => setIsOpen(false)}
                  className="flex w-full items-center justify-center rounded-full bg-brand px-5 py-3.5 text-[14px] font-semibold text-white transition-colors hover:bg-brand-deep"
                >
                  {t.bag.checkout}
                </Link>
              </footer>
            )}
          </aside>
        </div>
      )}
    </Ctx.Provider>
  );
}
