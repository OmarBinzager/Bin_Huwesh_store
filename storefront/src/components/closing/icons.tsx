import type { SVGProps } from "react";
import type { SocialPlatform } from "@/content/closing-types";

/* Small line icons for the page ending — same 24px grid and stroke as store/icons. */
type P = SVGProps<SVGSVGElement>;
const base = {
  width: 20,
  height: 20,
  viewBox: "0 0 24 24",
  fill: "none",
  stroke: "currentColor",
  strokeWidth: 1.6,
  strokeLinecap: "round",
  strokeLinejoin: "round",
  "aria-hidden": true,
} as const;

export const PinIcon = (p: P) => (
  <svg {...base} {...p}>
    <path d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0 1 13 0c0 5.4-6.5 11-6.5 11z" />
    <circle cx="12" cy="10" r="2.4" />
  </svg>
);
export const PhoneIcon = (p: P) => (
  <svg {...base} {...p}>
    <path d="M5 4h3.2l1.6 4-2 1.3a10.5 10.5 0 0 0 5 5l1.3-2 4 1.6V17a2 2 0 0 1-2 2A15 15 0 0 1 3 6a2 2 0 0 1 2-2z" />
  </svg>
);
export const StoreIcon = (p: P) => (
  <svg {...base} {...p}>
    <path d="M4 9.5 5.5 4h13L20 9.5" />
    <path d="M4 9.5a2.7 2.7 0 0 0 5.3 0 2.7 2.7 0 0 0 5.4 0 2.7 2.7 0 0 0 5.3 0" />
    <path d="M5.5 12v8h13v-8M10 20v-4.5h4V20" />
  </svg>
);
export const MapIcon = (p: P) => (
  <svg {...base} {...p}>
    <path d="m9 5-5 2v13l5-2 6 2 5-2V5l-5 2z" />
    <path d="M9 5v13M15 7v13" />
  </svg>
);
/** WhatsApp-style chat bubble with a handset (generic glyph, not the logo file). */
export const WhatsAppIcon = (p: P) => (
  <svg {...base} {...p}>
    <path d="M4.5 19.5 5.6 16A8 8 0 1 1 8.4 18.6z" />
    <path d="M9.2 8.6c.3-.6.9-.6 1.1 0l.5 1.3c.1.3 0 .6-.2.8l-.4.4a5 5 0 0 0 2.7 2.7l.4-.4c.2-.2.5-.3.8-.2l1.3.5c.6.2.6.8 0 1.1-1.6 1-5.6-1.6-6.2-4.2-.1-.8-.2-1.5 0-2z" />
  </svg>
);
export const ArrowUpIcon = (p: P) => (
  <svg {...base} {...p}>
    <path d="M12 19V5M6 11l6-6 6 6" />
  </svg>
);
export const ChevronIcon = (p: P) => (
  <svg {...base} {...p}>
    <path d="m6 9 6 6 6-6" />
  </svg>
);
export const StarIcon = ({ filled, ...p }: P & { filled?: boolean }) => (
  <svg {...base} {...p} fill={filled ? "currentColor" : "none"}>
    <path d="m12 3.5 2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.9l-5.2 2.7 1-5.8L3.5 9.7l5.9-.9z" />
  </svg>
);
/** Opening quotation mark (filled, takes the brand red). */
export const QuoteMark = (p: P) => (
  <svg width="34" height="34" viewBox="0 0 32 32" aria-hidden {...p}>
    <path
      fill="currentColor"
      d="M13.5 7C8 8.6 4.5 12.8 4.5 18.4c0 4 2.3 6.6 5.6 6.6 2.8 0 4.8-2 4.8-4.7 0-2.6-1.9-4.4-4.4-4.4-.4 0-.8 0-1.1.1.5-3 2.7-5.5 5.6-6.6zm13 0C21 8.6 17.5 12.8 17.5 18.4c0 4 2.3 6.6 5.6 6.6 2.8 0 4.8-2 4.8-4.7 0-2.6-1.9-4.4-4.4-4.4-.4 0-.8 0-1.1.1.5-3 2.7-5.5 5.6-6.6z"
    />
  </svg>
);

/* Social glyphs: simple generic outlines, used only when the admin has entered the account link. */
export const SOCIAL_META: Record<SocialPlatform, { label: string; labelEn: string; icon: (p: P) => React.ReactElement }> = {
  instagram: {
    label: "إنستغرام",
    labelEn: "Instagram",
    icon: (p) => (
      <svg {...base} {...p}>
        <rect x="4" y="4" width="16" height="16" rx="4.5" />
        <circle cx="12" cy="12" r="3.6" />
        <circle cx="16.8" cy="7.2" r=".6" fill="currentColor" />
      </svg>
    ),
  },
  facebook: {
    label: "فيسبوك",
    labelEn: "Facebook",
    icon: (p) => (
      <svg {...base} {...p}>
        <path d="M14 8.5h2.5V5H14a3.5 3.5 0 0 0-3.5 3.5V11H8v3.5h2.5V20H14v-5.5h2.4l.6-3.5h-3V9a.5.5 0 0 1 .5-.5z" />
      </svg>
    ),
  },
  tiktok: {
    label: "تيك توك",
    labelEn: "TikTok",
    icon: (p) => (
      <svg {...base} {...p}>
        <path d="M14 4v10.5a3.5 3.5 0 1 1-3.5-3.5" />
        <path d="M14 4a4.5 4.5 0 0 0 4.5 4.5" />
      </svg>
    ),
  },
  snapchat: {
    label: "سناب شات",
    labelEn: "Snapchat",
    icon: (p) => (
      <svg {...base} {...p}>
        <path d="M12 4c2.8 0 4.5 2 4.5 4.6V11l1.8-.4c.5 0 .7.6.3.9l-1.9 1.2c.5 1.6 1.7 2.8 3.3 3.3-.3.6-1.4.9-2.6 1-.2.6-.3 1-.6 1.1-.8-.1-1.6-.3-2.4 0-.8.4-1.4 1-2.4 1s-1.6-.6-2.4-1c-.8-.3-1.6-.1-2.4 0-.3-.1-.4-.5-.6-1.1-1.2-.1-2.3-.4-2.6-1 1.6-.5 2.8-1.7 3.3-3.3l-1.9-1.2c-.4-.3-.2-.9.3-.9l1.8.4V8.6C7.5 6 9.2 4 12 4z" />
      </svg>
    ),
  },
  x: {
    label: "إكس",
    labelEn: "X",
    icon: (p) => (
      <svg {...base} {...p}>
        <path d="M5 5l14 14M19 5 5 19" />
      </svg>
    ),
  },
  youtube: {
    label: "يوتيوب",
    labelEn: "YouTube",
    icon: (p) => (
      <svg {...base} {...p}>
        <rect x="3.5" y="6" width="17" height="12" rx="3.5" />
        <path d="m10.5 9.5 4 2.5-4 2.5z" fill="currentColor" />
      </svg>
    ),
  },
};
