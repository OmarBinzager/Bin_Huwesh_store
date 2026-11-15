import type { MetadataRoute } from "next";
import { STORE_CATEGORIES } from "@/content/categories";
import { API_BASE_URL } from "@/lib/storefront-api";

export const revalidate = 3600; // Cache sitemap for 1 hour

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const siteUrl = process.env.NEXT_PUBLIC_SITE_URL || "https://binhuwesh.com";
  const now = new Date();

  // 1. Static Core Pages
  const staticPages: MetadataRoute.Sitemap = [
    {
      url: `${siteUrl}/`,
      lastModified: now,
      changeFrequency: "daily",
      priority: 1.0,
    },
    {
      url: `${siteUrl}/checkout`,
      lastModified: now,
      changeFrequency: "monthly",
      priority: 0.3,
    },
    {
      url: `${siteUrl}/policies/terms`,
      lastModified: now,
      changeFrequency: "monthly",
      priority: 0.4,
    },
    {
      url: `${siteUrl}/policies/privacy`,
      lastModified: now,
      changeFrequency: "monthly",
      priority: 0.4,
    },
    {
      url: `${siteUrl}/policies/return`,
      lastModified: now,
      changeFrequency: "monthly",
      priority: 0.4,
    },
    {
      url: `${siteUrl}/policies/about`,
      lastModified: now,
      changeFrequency: "monthly",
      priority: 0.5,
    },
    {
      url: `${siteUrl}/policies/faq`,
      lastModified: now,
      changeFrequency: "monthly",
      priority: 0.5,
    },
  ];

  // 2. Categories
  const categoryPages: MetadataRoute.Sitemap = STORE_CATEGORIES.map((cat) => ({
    url: `${siteUrl}/categories/${cat.id}`,
    lastModified: now,
    changeFrequency: "daily",
    priority: 0.8,
  }));

  // 3. Dynamic Products from Backend
  let productPages: MetadataRoute.Sitemap = [];
  try {
    const res = await fetch(`${API_BASE_URL}/api/v1/products/latest?limit=100`, {
      next: { revalidate: 3600 },
      headers: { Accept: "application/json" },
    });
    if (res.ok) {
      const data = await res.json();
      const products = Array.isArray(data) ? data : data?.products ?? [];
      productPages = products.map((p: { id: number; updated_at?: string }) => ({
        url: `${siteUrl}/products/${p.id}`,
        lastModified: p.updated_at ? new Date(p.updated_at) : now,
        changeFrequency: "weekly",
        priority: 0.7,
      }));
    }
  } catch {
    // Graceful fallback if backend offline during sitemap build
  }

  return [...staticPages, ...categoryPages, ...productPages];
}
