/**
 * Content model for the end of the home page: «قالوا عن بن هويش»
 * (testimonials), «ننتظرك في بن هويش» (visit / contact) and the footer.
 * Data: src/content/closing.json — edited at /admin/closing (dev only).
 *
 * Honesty rules (enforced by the admin store):
 *  - A testimonial is either a VERIFIED customer review (with its source) or
 *    a clearly labelled preview example. Only verified reviews may carry a
 *    star rating, and any average / count is computed from verified ones.
 *  - No phone numbers or e-mail addresses inside quotes or names.
 *  - The map button exists only when a real map link is set — never a guessed pin.
 *  - Footer links point only at pages/anchors that exist, the company's
 *    phone/WhatsApp, or https links entered by the admin.
 */

export type Testimonial = {
  /** Stable id. */
  id: string;
  /** The customer's words (no private data). */
  quote: string;
  /** Display name the customer agreed to (e.g. «أبو محمد», «سالم ع.»). */
  name: string;
  /** true = a real review with a known source; false = preview example. */
  verified: boolean;
  /** Where a verified review comes from (e.g. «تقييم Google», «رسالة واتساب»). */
  source?: string;
  /** 1–5, only for verified reviews whose source has a star rating. */
  rating?: number;
  visible: boolean;
};

export type FooterLink = { label: string; href: string };
export type FooterGroup = { id: string; title: string; links: FooterLink[] };

export const SOCIAL_PLATFORMS = ["instagram", "facebook", "tiktok", "snapchat", "x", "youtube"] as const;
export type SocialPlatform = (typeof SOCIAL_PLATFORMS)[number];
export type SocialLink = { platform: SocialPlatform; url: string };

export type ClosingFile = {
  version: 1;
  testimonials: {
    title: string;
    subtitle: string;
    items: Testimonial[];
  };
  contact: {
    title: string;
    subtitle: string;
    company: string;
    address: string;
    /** As shown on the site, e.g. «780024406». */
    phoneDisplay: string;
    /** International digits without +, e.g. «967780024406» (tel: and wa.me are built from it). */
    phoneIntl: string;
    /** Real map link of the showroom; empty = no map button. */
    mapUrl: string;
  };
  footer: {
    tagline: string;
    groups: FooterGroup[];
    social: SocialLink[];
  };
};

/** Anchors on the home page a footer link may point at. */
export const HOME_ANCHORS = ["shop-by-category", "new-arrivals", "shoes", "testimonials", "visit"] as const;

export const telHref = (intl: string) => `tel:+${intl}`;
export const whatsappHref = (intl: string) => `https://wa.me/${intl}`;
