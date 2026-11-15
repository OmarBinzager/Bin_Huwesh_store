"use client";

import { useI18n } from "@/lib/i18n/context";

export default function LanguageToggle({ className = "" }: { className?: string }) {
  const { locale, t, setLocale } = useI18n();
  const next = locale === "ar" ? "en" : "ar";

  return (
    <button
      type="button"
      onClick={() => setLocale(next)}
      aria-label={t.header.switchLangAria}
      title={t.header.switchLangAria}
      className={`grid h-10 min-w-10 place-items-center rounded-full px-2.5 text-[12px] font-semibold tracking-wide transition-colors hover:bg-ink/6 ${className}`}
    >
      {t.header.switchLangLabel}
    </button>
  );
}
