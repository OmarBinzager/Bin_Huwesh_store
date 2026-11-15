"use client";

import { useEffect } from "react";
import { MoonIcon, SunIcon } from "./icons";

/** Same key the bootstrap script in app/layout.tsx reads. */
export const THEME_KEY = "bh-theme";

/**
 * Sun/moon switch. The current theme is read from <html data-theme>, which
 * the layout's inline script sets before paint, so this component needs no
 * state of its own and renders identically on server and client (the icons
 * swap via the .light-only / .dark-only CSS helpers).
 */
export default function ThemeToggle({ className = "" }: { className?: string }) {
  useEffect(() => {
    // Enable colour transitions only after first paint.
    const id = requestAnimationFrame(() => document.documentElement.classList.add("theme-ready"));
    return () => cancelAnimationFrame(id);
  }, []);

  const toggle = () => {
    const root = document.documentElement;
    const next = root.dataset.theme === "dark" ? "light" : "dark";
    root.dataset.theme = next;
    root.style.colorScheme = next;
    try {
      localStorage.setItem(THEME_KEY, next);
    } catch {
      /* private mode — the choice simply isn't remembered */
    }
  };

  return (
    <button
      type="button"
      onClick={toggle}
      aria-label="تبديل الوضع الليلي والنهاري"
      title="الوضع الليلي / النهاري"
      className={`grid size-10 place-items-center rounded-full transition-colors hover:bg-ink/6 ${className}`}
    >
      {/* In light mode offer the moon (go dark); in dark mode offer the sun. */}
      <MoonIcon className="light-only" />
      <SunIcon className="dark-only" />
    </button>
  );
}
