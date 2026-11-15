import type { LookItem } from "@/content/types";
import type { Locale } from "@/lib/i18n/config";

/**
 * Catalogue adapter — the single place the hero asks "what does this piece
 * cost?". Today there is no store API in this repo, so every piece falls back
 * to its preview price and is flagged `isDemo`. When the partner company's
 * API is connected, resolve `item.productId` here (price, stock, sizes, URL)
 * and the hero picks it up without any change to its motion code.
 */
export type ResolvedPrice = { amount: number; isDemo: boolean };

export function resolvePrice(item: LookItem): ResolvedPrice {
  if (typeof item.livePrice === "number" && item.productId !== null) {
    return { amount: item.livePrice, isDemo: Boolean(item.isDemo) };
  }
  return { amount: item.demoPrice, isDemo: true };
}

const yer = new Intl.NumberFormat("en-US");

/** "24,000 ر.ي" or "24,000 YER" — Latin digits, as used by the Bin Huwesh app. */
export function formatYER(amount: number, locale: Locale = "ar"): string {
  const unit = locale === "en" ? "YER" : "ر.ي";
  return `${yer.format(Math.round(amount))} ${unit}`;
}
