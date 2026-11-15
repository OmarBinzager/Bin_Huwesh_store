import type { MetadataRoute } from "next";

/**
 * The shared review deployment (SITE_REVIEW=1, see next.config.ts) asks every
 * crawler to stay out; every other build keeps the default open rules.
 */
export default function robots(): MetadataRoute.Robots {
  if (process.env.SITE_REVIEW === "1") {
    return { rules: { userAgent: "*", disallow: "/" } };
  }
  return { rules: { userAgent: "*", allow: "/" } };
}
