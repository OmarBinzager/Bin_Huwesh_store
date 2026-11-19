"use client";

/* eslint-disable @next/next/no-img-element -- small static SVG logos, nothing for the optimiser to do */

import { useLayoutEffect, useRef, useState, type CSSProperties } from "react";
import type { Brand, BrandsFile } from "@/content/brand-types";
import { Marquee } from "@/components/ui/marquee";

/**
 * "ماركات عالمية" — the brand logo strip under the hero.
 * Content comes in through `data` (src/content/brands.json); motion is the
 * shared <Marquee>. This component only sizes the logos and keeps the loop
 * seamless at any screen width.
 */

/**
 * Optical sizing: every logo gets roughly the same visual AREA instead of the
 * same height, so a long wordmark (Tommy Hilfiger) and a compact mark (Puma)
 * read as equals. Aspect ratio is always preserved. Result is relative to
 * the base size, so it scales with --logo-base (smaller on phones).
 */
function opticalBox(aspect: number, scale: number) {
  const area = 2.5; // reference: a 2.5 : 1 logo is exactly base-high
  let h = Math.sqrt(area / aspect);
  h = Math.min(1.25, Math.max(0.42, h)) * (scale || 1);
  return { w: h * aspect, h };
}

export default function BrandStrip({ data }: { data: BrandsFile }) {
  const { settings } = data;
  const brands = data.brands.filter((b) => b.visible);
  const rootRef = useRef<HTMLDivElement>(null);
  const [copies, setCopies] = useState(1);
  const [duration, setDuration] = useState(40);

  /*
   * The marquee shows its children twice and slides by -50%, which is only
   * seamless when ONE set is at least as wide as the viewport. Repeat the
   * brand list until it is, and derive the cycle time from the configured
   * pixels-per-second so adding/removing logos never changes the pace.
   */
  useLayoutEffect(() => {
    const root = rootRef.current;
    if (!root || brands.length === 0) return;
    const measure = () => {
      const track = root.querySelector<HTMLElement>("[data-marquee-track]");
      const viewport = track?.parentElement;
      if (!track || !viewport) return;
      const setWidth = track.scrollWidth / 2 / copies;
      if (!setWidth) return;
      const need = Math.max(1, Math.ceil((viewport.clientWidth + 1) / setWidth));
      if (need !== copies) setCopies(need);
      setDuration((setWidth * need) / Math.max(5, settings.speed));
    };
    measure();
    const ro = new ResizeObserver(measure);
    ro.observe(root);
    return () => ro.disconnect();
  }, [copies, settings.speed, brands.length, data]);

  if (brands.length === 0) return null;

  const names = brands.map((b) => b.name).join("، ");
  const set = (k: number) =>
    brands.map((b) => <Logo key={`${k}-${b.id}`} brand={b} />);

  return (
    <section
      aria-labelledby={settings.showTitle ? "brand-strip-title" : undefined}
      aria-label={settings.showTitle ? undefined : settings.title}
      className="border-y border-line bg-paper text-ink"
      style={{ "--logo-base-lg": `${settings.logoSize}px` } as CSSProperties}
    >
      <div className="mx-auto flex max-w-[1440px] flex-col items-stretch gap-1 py-2 sm:flex-row sm:items-center sm:gap-6 sm:px-8 sm:py-0">
        {settings.showTitle && (
          <h2
            id="brand-strip-title"
            className="shrink-0 pt-3 text-center text-[12px] font-medium tracking-wide text-muted sm:border-e sm:border-line sm:py-6 sm:pe-6 sm:text-start sm:text-[13px]"
          >
            {settings.title}
          </h2>
        )}

        {/* Screen readers get the plain list; the moving copy is decorative. */}
        <ul className="sr-only">
          {brands.map((b) => (
            <li key={b.id}>{b.name}</li>
          ))}
        </ul>

        <div ref={rootRef} className="min-w-0 flex-1">
          <Marquee
            aria-label={`${settings.title}: ${names}`}
            role="marquee"
            tabIndex={0}
            pauseOnHover
            pauseOnFocus
            direction={settings.direction}
            speed={duration}
            className="mt-0 rounded-lg outline-none focus-visible:ring-2 focus-visible:ring-brand/50 sm:mt-0 [--logo-base:calc(var(--logo-base-lg)*0.78)] sm:[--logo-base:var(--logo-base-lg)]"
            viewportClassName="max-w-none py-[14px] sm:py-5 [mask-image:linear-gradient(to_right,transparent,#000_7%,#000_93%,transparent)] [-webkit-mask-image:linear-gradient(to_right,transparent,#000_7%,#000_93%,transparent)] motion-reduce:overflow-x-auto"
          >
            <ul aria-hidden className="flex items-center">
              {Array.from({ length: copies }, (_, k) => set(k))}
            </ul>
          </Marquee>
        </div>
      </div>
    </section>
  );
}

function Logo({ brand }: { brand: Brand }) {
  const box = opticalBox(brand.aspect, brand.scale);
  const size: CSSProperties = {
    width: `calc(var(--logo-base) * ${box.w.toFixed(4)})`,
    height: `calc(var(--logo-base) * ${box.h.toFixed(4)})`,
  };
  return (
    <li className="flex shrink-0 items-center px-[clamp(18px,3.4vw,46px)]" title={brand.name}>
      {brand.mode === "color" && brand.colorLogo ? (
        <img
          src={brand.colorLogo}
          alt=""
          draggable={false}
          className="object-contain transition-[filter] duration-300 invert dark:invert-0"
          style={size}
        />
      ) : (
        <span
          className="block bg-current text-ink/70 transition-colors duration-300 hover:text-ink"
          style={{
            ...size,
            WebkitMaskImage: `url("${brand.logo}")`,
            maskImage: `url("${brand.logo}")`,
            WebkitMaskSize: "contain",
            maskSize: "contain",
            WebkitMaskRepeat: "no-repeat",
            maskRepeat: "no-repeat",
            WebkitMaskPosition: "center",
            maskPosition: "center",
          }}
        />
      )}
    </li>
  );
}
