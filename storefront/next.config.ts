import { networkInterfaces } from "node:os";
import type { NextConfig } from "next";

/**
 * Let a phone on the same Wi-Fi open the dev server (http://<LAN-IP>:3000)
 * for touch testing. Next 16 blocks dev assets for non-localhost origins by
 * default; this allows only this machine's own LAN addresses, detected at
 * start-up, so nothing machine-specific is committed. Dev-only setting.
 */
const lanHosts = Object.values(networkInterfaces())
  .flat()
  .filter((a) => a && a.family === "IPv4" && !a.internal)
  .map((a) => a!.address);

/**
 * SITE_REVIEW=1 marks the shared review deployment (set at build time on the
 * hosting side only). It must never be indexed by search engines.
 */
const isReviewBuild = process.env.SITE_REVIEW === "1";

const nextConfig: NextConfig = {
  allowedDevOrigins: lanHosts,
  images: {
    remotePatterns: [
      { protocol: "http", hostname: "127.0.0.1", port: "8000", pathname: "/**" },
      { protocol: "http", hostname: "localhost", port: "8000", pathname: "/**" },
      { protocol: "https", hostname: "jebnah.com", pathname: "/**" },
      { protocol: "https", hostname: "*.jebnah.com", pathname: "/**" },
    ],
  },
  // The dev "N" badge sits bottom-left — exactly over the hero's
  // "تسوّق الإطلالة" button in RTL — and swallows taps during review.
  devIndicators: false,
  async headers() {
    if (!isReviewBuild) return [];
    return [
      {
        source: "/:path*",
        headers: [{ key: "X-Robots-Tag", value: "noindex, nofollow, noarchive" }],
      },
    ];
  },
};

export default nextConfig;
