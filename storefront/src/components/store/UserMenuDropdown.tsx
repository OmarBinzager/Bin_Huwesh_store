"use client";

import Link from "next/link";
import { useEffect, useRef, useState } from "react";
import {
  clearAuthSession,
  fetchCustomerInfo,
  getAuthToken,
  getAuthUser,
  type CustomerProfile,
} from "@/lib/auth-api";
import { useCurrency } from "@/lib/currency";
import { useI18n } from "@/lib/i18n/context";
import { UserIcon } from "./icons";

export default function UserMenuDropdown({
  onOpenAuthModal,
}: {
  onOpenAuthModal: () => void;
}) {
  const { locale, dir, t } = useI18n();
  const { currencies, activeCurrency, setCurrencyCode } = useCurrency();

  const [token, setToken] = useState<string | null>(null);
  const [user, setUser] = useState<CustomerProfile | null>(null);
  const [open, setOpen] = useState(false);
  const rootRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const syncAuth = () => {
      const tk = getAuthToken();
      setToken(tk);
      setUser(getAuthUser());
      if (tk) {
        fetchCustomerInfo(tk, locale).then((p) => {
          if (p) setUser(p);
        });
      }
    };
    syncAuth();
    window.addEventListener("bh-auth-change", syncAuth);
    window.addEventListener("storage", syncAuth);
    return () => {
      window.removeEventListener("bh-auth-change", syncAuth);
      window.removeEventListener("storage", syncAuth);
    };
  }, [locale]);

  useEffect(() => {
    if (!open) return;
    const onDocClick = (e: MouseEvent) => {
      if (rootRef.current && !rootRef.current.contains(e.target as Node)) {
        setOpen(false);
      }
    };
    const onKey = (e: KeyboardEvent) => {
      if (e.key === "Escape") setOpen(false);
    };
    document.addEventListener("mousedown", onDocClick);
    document.addEventListener("keydown", onKey);
    return () => {
      document.removeEventListener("mousedown", onDocClick);
      document.removeEventListener("keydown", onKey);
    };
  }, [open]);

  const handleButtonClick = () => {
    if (!token) {
      onOpenAuthModal();
      return;
    }
    setOpen((prev) => !prev);
  };

  const handleLogout = () => {
    clearAuthSession();
    setOpen(false);
  };

  const fullName = user
    ? `${user.f_name || ""} ${user.l_name || ""}`.trim() ||
      user.phone ||
      user.email ||
      ""
    : "";

  return (
    <div ref={rootRef} className="relative">
      <button
        type="button"
        aria-label={token ? t.userMenu.accountAria : t.header.accountAria}
        aria-expanded={token ? open : undefined}
        onClick={handleButtonClick}
        className="relative grid size-10 place-items-center rounded-full hover:bg-ink/6"
      >
        <UserIcon />
        {token && (
          <span
            aria-hidden
            className="absolute top-1.5 end-1.5 size-2 rounded-full bg-brand ring-2 ring-card"
          />
        )}
      </button>

      {token && open && (
        <div
          dir={dir}
          role="menu"
          aria-label={t.userMenu.accountAria}
          className="absolute end-0 top-12 z-[1050] w-72 rounded-3xl border border-line bg-card p-3 text-ink shadow-2xl"
        >
          {/* Profile header */}
          <div className="mb-2 rounded-2xl bg-stage px-3.5 py-3">
            <p className="truncate font-display text-sm font-semibold text-ink">
              {fullName || t.modals.accountTitle}
            </p>
            {(user?.phone || user?.email) && (
              <p className="mt-0.5 truncate text-xs text-muted" dir="ltr">
                {user.phone || user.email}
              </p>
            )}
          </div>

          {/* Navigation links */}
          <nav className="space-y-0.5">
            <Link
              href="/account/addresses"
              onClick={() => setOpen(false)}
              className="flex min-h-10 items-center justify-between rounded-2xl px-3.5 text-sm font-medium text-ink transition hover:bg-stage"
            >
              <span>{t.userMenu.addresses}</span>
            </Link>
            <Link
              href="/account/orders"
              onClick={() => setOpen(false)}
              className="flex min-h-10 items-center justify-between rounded-2xl px-3.5 text-sm font-medium text-ink transition hover:bg-stage"
            >
              <span>{t.userMenu.orders}</span>
            </Link>
            <Link
              href="/account/profile"
              onClick={() => setOpen(false)}
              className="flex min-h-10 items-center justify-between rounded-2xl px-3.5 text-sm font-medium text-ink transition hover:bg-stage"
            >
              <span>{t.userMenu.profile}</span>
            </Link>
            <Link
              href="/account/notifications"
              onClick={() => setOpen(false)}
              className="flex min-h-10 items-center justify-between rounded-2xl px-3.5 text-sm font-medium text-ink transition hover:bg-stage"
            >
              <span>{t.userMenu.notifications}</span>
            </Link>
            <Link
              href="/#visit"
              onClick={() => setOpen(false)}
              className="flex min-h-10 items-center justify-between rounded-2xl px-3.5 text-sm font-medium text-ink transition hover:bg-stage"
            >
              <span>{t.userMenu.contactUs}</span>
            </Link>
          </nav>

          {/* Currency switcher */}
          {currencies.length > 0 && (
            <div className="my-2 border-t border-line pt-2.5 px-2">
              <p className="mb-1.5 px-1.5 text-[11px] font-medium text-muted">
                {t.userMenu.currency}
              </p>
              <div className="flex flex-wrap gap-1.5">
                {currencies.map((cur) => {
                  const active = cur.currency_code === activeCurrency.currency_code;
                  return (
                    <button
                      key={cur.currency_code}
                      type="button"
                      onClick={() => setCurrencyCode(cur.currency_code)}
                      className={`rounded-full px-3 py-1 text-xs font-medium transition ${
                        active
                          ? "bg-ink text-on-ink"
                          : "border border-line bg-background text-muted hover:text-ink"
                      }`}
                    >
                      {cur.currency_code} ({cur.currency_symbol})
                    </button>
                  );
                })}
              </div>
            </div>
          )}

          {/* Logout */}
          <div className="mt-1 border-t border-line pt-2">
            <button
              type="button"
              onClick={handleLogout}
              className="flex min-h-10 w-full items-center justify-center rounded-full border border-line text-sm font-medium text-brand transition hover:bg-brand/10"
            >
              {t.userMenu.logout}
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
