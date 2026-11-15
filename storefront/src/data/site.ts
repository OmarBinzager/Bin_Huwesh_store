/** Copy + nav for the Phase 1 visual reference. Swap wholesale in Phase 2. */

export const navLinks = [
  { label: "من نحن", href: "#about" },
  { label: "المنتجات", href: "#services" },
  { label: "التشكيلات", href: "#projects" },
  { label: "تواصل معنا", href: "#contact" },
] as const;

export const hero = {
  heading: "BIN HUWESH",
  /* Three authored lines — rendered with `whitespace-pre-line`. */
  tagline:
    "أناقة ننتقيها بعناية\nوجودة نصنع بها الفرق\nوتشكيلة تليق بكل حضور",
  ctaLabel: "اكتشف تشكيلتنا",
  /* Approved 3D Bin Huwesh sphere, transparent WebP. Web copy of
     brand/images/binhuwesh-logo-3d-sphere.png — geometry untouched. */
  portrait: "/images/hero/binhuwesh-sphere-3d.webp",
} as const;

export const about = {
  heading: "About me",
  body: "With more than five years of experience in design, i focus on branding, web design, and user experience, i truly enjoy working with businesses that aim to stand out and present their best image. Let’s build something incredible together!",
} as const;

const FIGMA_ASSETS =
  "https://shrug-person-78902957.figma.site/_components/v2/ebb2b8f25d8e24d5f0a5ca8af4c950de81aa2fd7";

/** Four decorative 3D props anchored to the corners of the About section. */
export const aboutDecor = [
  {
    key: "moon",
    src: `${FIGMA_ASSETS}/moon_icon.11395d36.png`,
    alt: "",
    className: "top-[4%] left-[1%] sm:left-[2%] md:left-[4%]",
    width: "w-[120px] sm:w-[160px] md:w-[210px]",
    x: -80,
    delay: 0.1,
  },
  {
    key: "p59",
    src: `${FIGMA_ASSETS}/p59_1.4659672e.png`,
    alt: "",
    className: "bottom-[8%] left-[3%] sm:left-[6%] md:left-[10%]",
    width: "w-[100px] sm:w-[140px] md:w-[180px]",
    x: -80,
    delay: 0.25,
  },
  {
    key: "lego",
    src: `${FIGMA_ASSETS}/lego_icon-1.703bb594.png`,
    alt: "",
    className: "top-[4%] right-[1%] sm:right-[2%] md:right-[4%]",
    width: "w-[120px] sm:w-[160px] md:w-[210px]",
    x: 80,
    delay: 0.15,
  },
  {
    key: "group134",
    src: `${FIGMA_ASSETS}/Group_134-1.2e04f3ce.png`,
    alt: "",
    className: "bottom-[8%] right-[3%] sm:right-[6%] md:right-[10%]",
    width: "w-[130px] sm:w-[170px] md:w-[220px]",
    x: 80,
    delay: 0.3,
  },
] as const;
