export type Locale = "ar" | "en";

export const DEFAULT_LOCALE: Locale = "ar";
export const LOCALE_COOKIE = "bh-locale";
export const LOCALE_STORAGE_KEY = "bh-locale";

export function normalizeLocale(value?: string | null): Locale {
  return value === "en" ? "en" : "ar";
}

export function getDirection(locale: Locale): "rtl" | "ltr" {
  return locale === "ar" ? "rtl" : "ltr";
}
