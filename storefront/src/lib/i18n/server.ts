import { cookies } from "next/headers";
import { LOCALE_COOKIE, normalizeLocale, type Locale } from "./config";

/**
 * Reads the active storefront locale from the `bh-locale` cookie on the server.
 * Falls back to `"ar"` when not set.
 */
export async function getServerLocale(): Promise<Locale> {
  try {
    const store = await cookies();
    return normalizeLocale(store.get(LOCALE_COOKIE)?.value);
  } catch {
    return "ar";
  }
}
