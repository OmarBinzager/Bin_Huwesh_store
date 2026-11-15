import Image from "next/image";

/**
 * The full Bin Huwesh logo exactly as supplied (assets/brand/*.webp, only
 * trimmed of empty margin): red circle + white mark, with the Arabic/English
 * name in black (light theme) or white (dark theme). Both files share the
 * same 1935×527 box, so swapping them never shifts the layout.
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
  const width = Math.round((height * W) / H);
  const common = { width: W, height: H, draggable: false, preload, sizes: `${width}px` } as const;
  return (
    <span className={`relative shrink-0 ${className}`} style={{ width, height }} role="img" aria-label="شركة بن هويش التجارية">
      <Image {...common} alt="" src="/images/brand/logo-full-dark-text.webp" className="light-only h-full w-full" />
      <Image {...common} alt="" src="/images/brand/logo-full-light-text.webp" className="dark-only h-full w-full" />
    </span>
  );
}
