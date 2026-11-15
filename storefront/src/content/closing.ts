import data from "./closing.json";
import type { ClosingFile, Testimonial } from "./closing-types";

/**
 * End-of-page content — src/content/closing.json, edited with the local admin
 * at /admin/closing (dev only) or by hand. See closing-types.ts for the rules.
 */
export const closingData = data as ClosingFile;

export function visibleTestimonials(file: ClosingFile = closingData): Testimonial[] {
  return file.testimonials.items.filter((t) => t.visible);
}

/** Average and count from VERIFIED rated reviews only; null when there are none. */
export function verifiedRating(items: Testimonial[]): { average: number; count: number } | null {
  const rated = items.filter((t) => t.verified && typeof t.rating === "number");
  if (rated.length === 0) return null;
  const sum = rated.reduce((s, t) => s + (t.rating ?? 0), 0);
  return { average: Math.round((sum / rated.length) * 10) / 10, count: rated.length };
}
