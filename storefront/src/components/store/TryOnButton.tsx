"use client";

import dynamic from "next/dynamic";
import { useState } from "react";

const TryOnModal = dynamic(() => import("./TryOnModal"), { ssr: false });

type TryOnButtonProps = {
  productId: number;
  productName: string;
  garmentImage: string;
  selectedColor?: string;
  locale?: "ar" | "en";
  variant?: "card" | "detail";
};

export function TryOnButton({
  productId,
  productName,
  garmentImage,
  selectedColor,
  locale = "ar",
  variant = "card",
}: TryOnButtonProps) {
  const [open, setOpen] = useState(false);
  const label = locale === "en" ? "Try It On" : "جرّبها عليك";

  return (
    <>
      <button
        type="button"
        onClick={(e) => {
          e.preventDefault();
          e.stopPropagation();
          setOpen(true);
        }}
        className={
          variant === "card"
            ? "inline-flex h-8 items-center gap-1.5 rounded-full border border-line bg-paper/90 px-3 text-[11px] font-semibold text-ink shadow-sm backdrop-blur-md transition-colors hover:border-brand hover:text-brand"
            : "inline-flex h-10 items-center gap-2 rounded-full border border-line bg-card px-4 text-xs font-semibold text-ink shadow-sm transition-colors hover:border-brand hover:text-brand"
        }
        aria-label={label}
      >
        <svg
          width="14"
          height="14"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          strokeWidth="2"
          strokeLinecap="round"
          strokeLinejoin="round"
          aria-hidden="true"
        >
          <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z" />
          <circle cx="12" cy="13" r="3" />
        </svg>
        <span>{label}</span>
      </button>

      {open && (
        <TryOnModal
          open={open}
          onClose={() => setOpen(false)}
          productId={productId}
          productName={productName}
          garmentImage={garmentImage}
          selectedColor={selectedColor}
          locale={locale}
        />
      )}
    </>
  );
}
