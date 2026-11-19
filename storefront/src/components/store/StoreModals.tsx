"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { STORE_CATEGORIES, categoryHref } from "@/content/categories";
import {
  clearAuthSession,
  extractApiError,
  fetchCustomerInfo,
  getAuthToken,
  loginWithPassword,
  setAuthToken,
  setAuthUser,
} from "@/lib/auth-api";
import { useCurrency } from "@/lib/currency";
import { useI18n } from "@/lib/i18n/context";
import { productHref } from "@/lib/product-url";
import { API_BASE_URL, type CatalogProduct } from "@/lib/storefront-api";
import LanguageToggle from "./LanguageToggle";
import ThemeToggle from "./ThemeToggle";

export function SearchModal({ open, onClose }: { open: boolean; onClose: () => void }) {
  const router = useRouter();
  const { locale, dir, t } = useI18n();
  const { formatMoney } = useCurrency();
  const [query, setQuery] = useState("");
  const [results, setResults] = useState<CatalogProduct[]>([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (!open) return;
    const trimmed = query.trim();
    if (trimmed.length < 2) {
      return;
    }
    const timer = setTimeout(async () => {
      setLoading(true);
      try {
        const res = await fetch(
          `${API_BASE_URL}/api/v1/storefront/products?q=${encodeURIComponent(trimmed)}&limit=8&lang=${locale}`,
          { headers: { Accept: "application/json", "X-localization": locale } },
        );
        if (res.ok) {
          const data = await res.json();
          setResults(Array.isArray(data?.products) ? data.products : []);
        }
      } catch {
        setResults([]);
      } finally {
        setLoading(false);
      }
    }, 300);
    return () => clearTimeout(timer);
  }, [query, open, locale]);

  if (!open) return null;

  const handleSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const trimmed = query.trim();
    if (!trimmed) return;
    onClose();
    router.push(`/products?q=${encodeURIComponent(trimmed)}`);
  };

  return (
    <div className="fixed inset-0 z-[1100] flex items-start justify-center bg-black/50 p-4 pt-20 backdrop-blur-xs" onClick={onClose}>
      <div
        dir={dir}
        onClick={(e) => e.stopPropagation()}
        className="w-full max-w-xl overflow-hidden rounded-3xl border border-line bg-card text-ink shadow-2xl"
      >
        <form onSubmit={handleSearchSubmit} className="flex items-center gap-3 border-b border-line px-5 py-4">
          <input
            type="search"
            enterKeyHint="search"
            autoFocus
            value={query}
            onChange={(e) => {
              const val = e.target.value;
              setQuery(val);
              if (val.trim().length < 2) setResults([]);
            }}
            placeholder={t.modals.searchPlaceholder}
            className="w-full bg-transparent text-[15px] outline-none placeholder:text-muted"
          />
          <button
            type="submit"
            className="shrink-0 rounded-full bg-ink px-4 py-1.5 text-[12px] font-medium text-on-ink transition hover:bg-brand"
          >
            {t.catalog.searchBtn}
          </button>
          <button type="button" onClick={onClose} className="shrink-0 text-[13px] text-muted hover:text-ink">
            {t.modals.close}
          </button>
        </form>
        <div className="max-h-[60vh] overflow-y-auto p-4">
          {loading ? (
            <p className="py-8 text-center text-[13px] text-muted">{t.modals.searching}</p>
          ) : query.trim().length < 2 ? (
            <p className="py-8 text-center text-[13px] text-muted">{t.modals.searchMinChars}</p>
          ) : results.length === 0 ? (
            <p className="py-8 text-center text-[13px] text-muted">{t.modals.noSearchResults}</p>
          ) : (
            <div className="space-y-3">
              <ul className="divide-y divide-line">
                {results.map((p) => (
                  <li key={p.id}>
                    <Link
                      href={productHref(p.id, p.name)}
                      onClick={onClose}
                      className="flex items-center gap-3 rounded-xl p-2.5 transition-colors hover:bg-ink/5"
                    >
                      <img
                        src={Array.isArray(p.images) && p.images[0] ? p.images[0] : p.image}
                        alt={p.name}
                        className="size-14 rounded-lg bg-stage object-cover"
                      />
                      <div className="min-w-0 flex-1">
                        <p className="truncate text-[14px] font-medium">{p.name}</p>
                        <p className="nums text-[12px] text-brand">{formatMoney(p.final_price ?? p.price)}</p>
                      </div>
                    </Link>
                  </li>
                ))}
              </ul>
              <div className="border-t border-line pt-3 text-center">
                <Link
                  href={`/products?q=${encodeURIComponent(query.trim())}`}
                  onClick={onClose}
                  className="inline-flex h-9 items-center rounded-full bg-ink px-5 text-xs font-medium text-on-ink transition hover:bg-brand"
                >
                  {t.catalog.viewAllSearchResults}
                </Link>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

export function AuthModal({ open, onClose }: { open: boolean; onClose: () => void }) {
  const { locale, dir, t } = useI18n();
  const [phone, setPhone] = useState("");
  const [password, setPassword] = useState("");
  const [status, setStatus] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const [userToken, setUserToken] = useState<string | null>(() => getAuthToken());
  const activeToken = open ? (userToken ?? getAuthToken()) : userToken;

  if (!open) return null;

  const login = async (e: React.FormEvent) => {
    e.preventDefault();
    setBusy(true);
    setStatus(null);
    const trimmed = phone.trim();
    const type: "phone" | "email" = trimmed.includes("@") ? "email" : "phone";

    try {
      const res = await loginWithPassword(
        { email_or_phone: trimmed, password, type },
        locale,
      );
      if (!res.ok || !res.data.token) {
        throw new Error(extractApiError(res.data, t.modals.loginError));
      }
      setAuthToken(res.data.token);
      setUserToken(res.data.token);
      const profile = await fetchCustomerInfo(res.data.token, locale);
      if (profile) {
        setAuthUser(profile);
      }
      setStatus(t.modals.loginSuccess);
    } catch (err) {
      setStatus((err as Error).message);
    } finally {
      setBusy(false);
    }
  };

  const logout = () => {
    clearAuthSession();
    setUserToken(null);
    setStatus(t.modals.logoutSuccess);
  };

  return (
    <div className="fixed inset-0 z-[1100] flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs" onClick={onClose}>
      <div
        dir={dir}
        onClick={(e) => e.stopPropagation()}
        className="w-full max-w-md rounded-3xl border border-line bg-card p-6 text-ink shadow-2xl"
      >
        <div className="mb-4 flex items-center justify-between">
          <h2 className="font-display text-xl font-semibold">{t.modals.accountTitle}</h2>
          <button type="button" onClick={onClose} className="text-[13px] text-muted hover:text-ink">
            {t.modals.close}
          </button>
        </div>

        {activeToken ? (
          <div className="space-y-4">
            <p className="rounded-xl bg-emerald-500/10 px-4 py-3 text-[13px] text-emerald-700 dark:text-emerald-400">
              {t.modals.loggedInMsg}
            </p>
            <div className="flex gap-2">
              <Link
                href="/checkout"
                onClick={onClose}
                className="flex-1 rounded-full bg-brand py-2.5 text-center text-[13px] font-semibold text-white"
              >
                {t.modals.checkout}
              </Link>
              <button
                type="button"
                onClick={logout}
                className="rounded-full border border-line px-4 py-2.5 text-[13px] hover:border-ink"
              >
                {t.modals.logout}
              </button>
            </div>
          </div>
        ) : (
          <form onSubmit={login} className="space-y-3">
            <div>
              <label className="mb-1 block text-[12px] text-muted">{t.modals.phoneOrEmail}</label>
              <input
                type="text"
                required
                dir="ltr"
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
                placeholder="77XXXXXXX"
                className="w-full rounded-xl border border-line bg-background px-3.5 py-2.5 text-[14px] outline-none focus:border-brand"
              />
            </div>
            <div>
              <label className="mb-1 block text-[12px] text-muted">{t.modals.password}</label>
              <input
                type="password"
                required
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="w-full rounded-xl border border-line bg-background px-3.5 py-2.5 text-[14px] outline-none focus:border-brand"
              />
            </div>
            {status && <p className="text-[12px] text-brand">{status}</p>}
            <button
              type="submit"
              disabled={busy}
              className="w-full rounded-full bg-brand py-3 text-[14px] font-semibold text-white hover:bg-brand-deep disabled:opacity-50"
            >
              {busy ? t.modals.loggingIn : t.modals.login}
            </button>
            <div className="flex flex-wrap items-center justify-between gap-2 pt-2 text-[12px] text-muted">
              <Link href="/forgot-password" onClick={onClose} className="hover:text-ink underline-offset-4 hover:underline">
                {t.modals.forgotPassword}
              </Link>
              <div className="flex items-center gap-3">
                <Link href="/register" onClick={onClose} className="font-medium text-ink hover:underline underline-offset-4">
                  {t.modals.createAccount}
                </Link>
                <Link href="/login" onClick={onClose} className="hover:text-ink underline-offset-4 hover:underline">
                  {t.modals.fullAuthOptions}
                </Link>
              </div>
            </div>
          </form>
        )}
      </div>
    </div>
  );
}

export function MobileMenuDrawer({ open, onClose }: { open: boolean; onClose: () => void }) {
  const { dir, t } = useI18n();
  if (!open) return null;

  return (
    <div className="fixed inset-0 z-[1100] flex bg-black/50 backdrop-blur-xs" onClick={onClose}>
      <aside
        dir={dir}
        onClick={(e) => e.stopPropagation()}
        className="flex h-full w-72 flex-col bg-card p-5 text-ink shadow-2xl"
      >
        <div className="mb-5 flex items-center justify-between border-b border-line pb-3">
          <span className="font-display text-lg font-semibold">{t.header.navAria}</span>
          <button type="button" onClick={onClose} className="text-[14px] text-muted hover:text-ink">
            ✕
          </button>
        </div>
        <ul className="flex-1 space-y-2 overflow-y-auto">
          {STORE_CATEGORIES.map((c) => (
            <li key={c.id}>
              <Link
                href={categoryHref(c.id)}
                onClick={onClose}
                className="block rounded-xl px-3 py-2.5 text-[15px] transition-colors hover:bg-ink/5 hover:text-brand"
              >
                {t.categories[c.id]?.name ?? c.name}
              </Link>
            </li>
          ))}
        </ul>
        <div className="mt-4 flex items-center justify-between border-t border-line pt-4">
          <span className="text-[13px] text-muted">{t.header.languageLabel}</span>
          <LanguageToggle />
        </div>
        <div className="mt-2 flex items-center justify-between border-t border-line pt-3">
          <span className="text-[13px] text-muted">{t.header.appearanceLabel}</span>
          <ThemeToggle />
        </div>
      </aside>
    </div>
  );
}
