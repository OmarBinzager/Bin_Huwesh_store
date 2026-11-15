"use client";

import { useRouter } from "next/navigation";
import { createContext, useCallback, useContext, useEffect, useState, type ReactNode } from "react";
import {
  getDirection,
  LOCALE_COOKIE,
  LOCALE_STORAGE_KEY,
  normalizeLocale,
  type Locale,
} from "./config";
import { getDictionary, type Dictionary } from "./dictionaries";

type I18nContextValue = {
  locale: Locale;
  dir: "rtl" | "ltr";
  t: Dictionary;
  setLocale: (next: Locale) => void;
};

const defaultDict = getDictionary("ar");

const I18nContext = createContext<I18nContextValue>({
  locale: "ar",
  dir: "rtl",
  t: defaultDict,
  setLocale: () => {},
});

export function useI18n() {
  return useContext(I18nContext);
}

export function I18nProvider({
  initialLocale,
  children,
}: {
  initialLocale: Locale;
  children: ReactNode;
}) {
  const router = useRouter();
  const [override, setOverride] = useState<Locale | null>(null);
  const locale = override ?? initialLocale;

  useEffect(() => {
    const root = document.documentElement;
    root.lang = locale;
    root.dir = getDirection(locale);
  }, [locale]);

  const setLocale = useCallback(
    (nextRaw: Locale) => {
      const next = normalizeLocale(nextRaw);
      if (next === locale) return;

      document.cookie = `${LOCALE_COOKIE}=${next}; path=/; max-age=31536000; SameSite=Lax`;
      try {
        localStorage.setItem(LOCALE_STORAGE_KEY, next);
      } catch {
        /* ignore private mode */
      }

      const root = document.documentElement;
      root.lang = next;
      root.dir = getDirection(next);
      setOverride(next);
      router.refresh();
    },
    [locale, router],
  );

  return (
    <I18nContext.Provider
      value={{
        locale,
        dir: getDirection(locale),
        t: getDictionary(locale),
        setLocale,
      }}
    >
      {children}
    </I18nContext.Provider>
  );
}
