"use client";

import Link from "next/link";
import { useEffect, useState, type ReactNode } from "react";
import { getAuthToken } from "@/lib/auth-api";
import { useI18n } from "@/lib/i18n/context";

export function AccountShell({
  current,
  title,
  subtitle,
  children,
}: {
  current: "addresses" | "orders" | "profile" | "notifications";
  title: string;
  subtitle: string;
  children: ReactNode;
}) {
  const { dir, t } = useI18n();
  const [token, setToken] = useState<string | null>(() => getAuthToken());

  useEffect(() => {
    const sync = () => setToken(getAuthToken());
    window.addEventListener("bh-auth-change", sync);
    window.addEventListener("storage", sync);
    return () => {
      window.removeEventListener("bh-auth-change", sync);
      window.removeEventListener("storage", sync);
    };
  }, []);

  const tabs = [
    { key: "addresses", href: "/account/addresses", label: t.userMenu.addresses },
    { key: "orders", href: "/account/orders", label: t.userMenu.orders },
    { key: "profile", href: "/account/profile", label: t.userMenu.profile },
    { key: "notifications", href: "/account/notifications", label: t.userMenu.notifications },
  ] as const;

  return (
    <div dir={dir} className="mx-auto max-w-[1440px] px-4 py-8 sm:px-8 sm:py-10 space-y-6">
      <nav aria-label={t.breadcrumbs.ariaLabel} className="flex items-center gap-2 text-xs text-muted">
        <Link href="/" className="hover:text-ink transition">
          {t.breadcrumbs.home}
        </Link>
        <span>/</span>
        <span className="font-medium text-ink">{title}</span>
      </nav>

      <header className="space-y-1.5">
        <h1 className="font-display text-2xl sm:text-3xl font-bold text-ink">{title}</h1>
        <p className="text-sm text-muted">{subtitle}</p>
      </header>

      <div className="flex flex-wrap gap-2 border-b border-line pb-4">
        {tabs.map((tab) => {
          const active = tab.key === current;
          return (
            <Link
              key={tab.key}
              href={tab.href}
              className={`inline-flex h-10 items-center rounded-full px-5 text-sm font-medium transition ${
                active
                  ? "bg-ink text-on-ink"
                  : "border border-line bg-card text-muted hover:bg-stage hover:text-ink"
              }`}
            >
              {tab.label}
            </Link>
          );
        })}
      </div>

      {!token ? (
        <div className="mx-auto max-w-lg rounded-3xl border border-line bg-card p-8 text-center space-y-4">
          <h2 className="font-display text-xl font-semibold text-ink">
            {t.account.loginRequiredTitle}
          </h2>
          <p className="text-sm text-muted">{t.account.loginRequiredDesc}</p>
          <Link
            href="/login"
            className="inline-flex h-10 items-center justify-center rounded-full bg-ink px-7 text-sm font-medium text-on-ink transition hover:bg-brand"
          >
            {t.account.goToLogin}
          </Link>
        </div>
      ) : (
        children
      )}
    </div>
  );
}
