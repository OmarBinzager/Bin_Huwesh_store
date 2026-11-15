"use client";

import Link from "next/link";
import { useState } from "react";
import { STORE_CATEGORIES, categoryHref } from "@/content/categories";
import { useI18n } from "@/lib/i18n/context";
import BrandLogo from "./BrandLogo";
import { useDemoBag } from "./DemoBag";
import LanguageToggle from "./LanguageToggle";
import { AuthModal, MobileMenuDrawer, SearchModal } from "./StoreModals";
import ThemeToggle from "./ThemeToggle";
import { BagIcon, HeartIcon, MenuIcon, SearchIcon, UserIcon } from "./icons";

export default function StoreHeader({ current }: { current?: string } = {}) {
  const { t } = useI18n();
  const { count, openBag } = useDemoBag();
  const [searchOpen, setSearchOpen] = useState(false);
  const [authOpen, setAuthOpen] = useState(false);
  const [menuOpen, setMenuOpen] = useState(false);

  return (
    <>
      <header className="relative z-50 bg-card text-ink">
        <div className="bg-brand px-4 py-2 text-center text-[12px] text-white">
          {t.header.shippingBanner}
        </div>
        <div className="mx-auto flex h-[68px] max-w-[1440px] items-center gap-2 border-b border-line px-3 sm:gap-4 sm:px-8">
          <button
            type="button"
            aria-label={t.header.menuAria}
            onClick={() => setMenuOpen(true)}
            className="grid size-10 place-items-center rounded-full hover:bg-ink/6 lg:hidden"
          >
            <MenuIcon />
          </button>

          <Link href="/" className="flex items-center" aria-label={t.meta.brandHomeAria}>
            <BrandLogo height={36} preload className="inline-block sm:hidden" />
            <BrandLogo height={48} preload className="hidden sm:inline-block" />
          </Link>

          <nav aria-label={t.header.navAria} className="mx-auto hidden lg:block">
            <ul className="flex items-center gap-6 text-[14px]">
              {STORE_CATEGORIES.map((c) => (
                <li key={c.id}>
                  <Link
                    href={categoryHref(c.id)}
                    aria-current={current === c.id ? "page" : undefined}
                    className={`relative py-2 transition-colors hover:text-brand ${current === c.id ? "text-brand" : ""}`}
                  >
                    {t.categories[c.id]?.name ?? c.name}
                  </Link>
                </li>
              ))}
            </ul>
          </nav>

          <div className="ms-auto flex items-center gap-0.5 sm:gap-1 lg:ms-0">
            <LanguageToggle />
            <ThemeToggle />
            <button
              type="button"
              aria-label={t.header.searchAria}
              onClick={() => setSearchOpen(true)}
              className="grid size-10 place-items-center rounded-full hover:bg-ink/6"
            >
              <SearchIcon />
            </button>
            <button
              type="button"
              aria-label={t.header.accountAria}
              onClick={() => setAuthOpen(true)}
              className="hidden size-10 place-items-center rounded-full hover:bg-ink/6 sm:grid"
            >
              <UserIcon />
            </button>
            <button
              type="button"
              aria-label={t.header.favoritesAria}
              onClick={() => setAuthOpen(true)}
              className="hidden size-10 place-items-center rounded-full hover:bg-ink/6 sm:grid"
            >
              <HeartIcon />
            </button>
            <button
              type="button"
              aria-label={t.header.bagAria(count)}
              onClick={openBag}
              className="relative grid size-10 place-items-center rounded-full hover:bg-ink/6"
            >
              <BagIcon />
              {count > 0 && (
                <span className="nums absolute -top-0.5 -left-0.5 grid min-w-5 place-items-center rounded-full bg-brand px-1 text-[10px] leading-5 font-semibold text-white">
                  {count}
                </span>
              )}
            </button>
          </div>
        </div>
      </header>

      <SearchModal open={searchOpen} onClose={() => setSearchOpen(false)} />
      <AuthModal open={authOpen} onClose={() => setAuthOpen(false)} />
      <MobileMenuDrawer open={menuOpen} onClose={() => setMenuOpen(false)} />
    </>
  );
}
