"use client";

import Image from "next/image";
import { useState } from "react";
import { useBrandConfig } from "./BrandConfigContext";

/**
 * The full Bin Huwesh logo: uses the configured business logo from Laravel
 * when available and reachable, and gracefully falls back to the bundled
 * light/dark static brand logos otherwise.
 */
const W = 1935;
const H = 527;

export default function BrandLogo({
  height = 40,
  preload = false,
  /** Must include a display class (default inline-block). */
  className = "inline-block",
}: {
  height?: number;
  preload?: boolean;
  className?: string;
}) {
  const { logoUrl } = useBrandConfig();
  const [failed, setFailed] = useState(false);

  const width = Math.round((height * W) / H);
  const common = { width: W, height: H, draggable: false, preload, sizes: `${width}px` } as const;

  if (logoUrl && !failed) {
    return (
      <span className={`relative shrink-0 ${className}`} style={{ height }} role="img" aria-label="شركة بن هويش التجارية">
        {/* eslint-disable-next-line @next/next/no-img-element -- dynamic business_settings logo with graceful fallback */}
        <img
          src={logoUrl}
          alt="شركة بن هويش التجارية"
          draggable={false}
          onError={() => setFailed(true)}
          className="h-full w-auto object-contain"
        />
      </span>
    );
  }

  return (
    <span className={`relative shrink-0 ${className}`} style={{ width, height }} role="img" aria-label="شركة بن هويش التجارية">
      <Image {...common} alt="" src="/images/brand/logo-full-dark-text.webp" className="light-only h-full w-full" />
      <Image {...common} alt="" src="/images/brand/logo-full-light-text.webp" className="dark-only h-full w-full" />
    </span>
  );
}
