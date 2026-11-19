"use client";

import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from "react";
import { useI18n } from "@/lib/i18n/context";
import { fetchStoreConfig, type CurrencyItem } from "@/lib/customer-api";

const STORAGE_KEY = "bh_currency_code";

const DEFAULT_CURRENCY: CurrencyItem = {
  id: 0,
  country: "YE",
  currency_code: "YER",
  currency_symbol: "ر.ي",
  symbol_position: "right",
  decimal_digits: 0,
  exchange_rate: 1,
  is_base: true,
};

type CurrencyContextValue = {
  currencies: CurrencyItem[];
  activeCurrency: CurrencyItem;
  setCurrencyCode: (code: string) => void;
  formatMoney: (amountInBase: number) => string;
};

const CurrencyContext = createContext<CurrencyContextValue | null>(null);

export function CurrencyProvider({ children }: { children: ReactNode }) {
  const { locale } = useI18n();
  const [currencies, setCurrencies] = useState<CurrencyItem[]>([DEFAULT_CURRENCY]);
  const [selectedCode, setSelectedCode] = useState<string>(() => {
    if (typeof window === "undefined") return "YER";
    try {
      return localStorage.getItem(STORAGE_KEY) || "YER";
    } catch {
      return "YER";
    }
  });

  useEffect(() => {
    let active = true;
    fetchStoreConfig(locale).then((cfg) => {
      if (!active || !cfg?.active_currencies?.length) return;
      setCurrencies(cfg.active_currencies);
    });
    return () => {
      active = false;
    };
  }, [locale]);

  const activeCurrency = useMemo(() => {
    return (
      currencies.find((c) => c.currency_code === selectedCode) ??
      currencies.find((c) => Boolean(c.is_base)) ??
      currencies[0] ??
      DEFAULT_CURRENCY
    );
  }, [currencies, selectedCode]);

  const setCurrencyCode = useCallback((code: string) => {
    setSelectedCode(code);
    try {
      localStorage.setItem(STORAGE_KEY, code);
    } catch {
      /* ignore */
    }
  }, []);

  const formatMoney = useCallback(
    (amountInBase: number) => {
      const rate = activeCurrency.exchange_rate > 0 ? activeCurrency.exchange_rate : 1;
      const converted = amountInBase / rate;
      const digits = Number.isFinite(activeCurrency.decimal_digits)
        ? Math.min(4, Math.max(0, activeCurrency.decimal_digits))
        : 0;
      const formatted = new Intl.NumberFormat("en-US", {
        minimumFractionDigits: activeCurrency.currency_code === "YER" ? 0 : digits,
        maximumFractionDigits: activeCurrency.currency_code === "YER" ? 0 : digits,
      }).format(activeCurrency.currency_code === "YER" ? Math.round(converted) : converted);

      const sym =
        activeCurrency.currency_code === "YER" && locale === "en"
          ? "YER"
          : activeCurrency.currency_symbol;

      return activeCurrency.symbol_position === "left"
        ? `${sym} ${formatted}`
        : `${formatted} ${sym}`;
    },
    [activeCurrency, locale],
  );

  const value = useMemo(
    () => ({ currencies, activeCurrency, setCurrencyCode, formatMoney }),
    [currencies, activeCurrency, setCurrencyCode, formatMoney],
  );

  return <CurrencyContext.Provider value={value}>{children}</CurrencyContext.Provider>;
}

export function useCurrency(): CurrencyContextValue {
  const ctx = useContext(CurrencyContext);
  if (!ctx) {
    throw new Error("useCurrency must be used inside CurrencyProvider");
  }
  return ctx;
}
