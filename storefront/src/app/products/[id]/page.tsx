import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { DemoBagProvider } from "@/components/store/DemoBag";
import ProductDetailClient from "@/components/store/ProductDetailClient";
import StoreHeader from "@/components/store/StoreHeader";
import { getDictionary } from "@/lib/i18n/dictionaries";
import { getServerLocale } from "@/lib/i18n/server";
import { fetchProductDetail } from "@/lib/storefront-api";

export async function generateMetadata({
  params,
}: {
  params: Promise<{ id: string }>;
}): Promise<Metadata> {
  const { id } = await params;
  const locale = await getServerLocale();
  const t = getDictionary(locale);
  const data = await fetchProductDetail(id, locale);
  if (!data) return { title: `${t.product.fallbackTitle} — ${t.meta.brandSuffix}` };

  const siteUrl = process.env.NEXT_PUBLIC_SITE_URL || "https://binhuwesh.com";
  const desc = data.product.brand?.name
    ? `${data.product.name} — ${data.product.brand.name}. تسوق الآن من متجر بن هويش التجاري في اليمن.`
    : `${data.product.name}. تسوق الآن من متجر بن هويش التجاري في اليمن.`;
  const productImages = [
    ...(data.product.image ? [data.product.image] : []),
    ...(data.product.images ?? []),
  ];

  return {
    title: `${data.product.name} — ${t.meta.brandSuffix}`,
    description: desc,
    alternates: {
      canonical: `/products/${id}`,
    },
    openGraph: {
      type: "website",
      locale: locale === "ar" ? "ar_YE" : "en_US",
      url: `${siteUrl}/products/${id}`,
      title: `${data.product.name} — ${t.meta.brandSuffix}`,
      description: desc,
      images: productImages.map((img) => ({
        url: img,
        alt: data.product.name,
      })),
    },
    twitter: {
      card: "summary_large_image",
      title: `${data.product.name} — ${t.meta.brandSuffix}`,
      description: desc,
      images: productImages,
    },
  };
}

export default async function ProductDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  const locale = await getServerLocale();
  const data = await fetchProductDetail(id, locale);
  if (!data) {
    notFound();
  }

  const siteUrl = process.env.NEXT_PUBLIC_SITE_URL || "https://binhuwesh.com";
  const productSchema = {
    "@context": "https://schema.org",
    "@type": "Product",
    name: data.product.name,
    image: data.product.image ? [data.product.image] : [],
    description: data.product.description
      ? data.product.description.replace(/<[^>]*>?/gm, "").slice(0, 160)
      : data.product.name,
    sku: `BH-${data.product.id}`,
    brand: data.product.brand
      ? {
          "@type": "Brand",
          name: data.product.brand.name,
        }
      : undefined,
    offers: {
      "@type": "Offer",
      url: `${siteUrl}/products/${id}`,
      priceCurrency: "YER",
      price: data.product.final_price || data.product.price,
      availability:
        data.product.total_stock > 0
          ? "https://schema.org/InStock"
          : "https://schema.org/OutOfStock",
      itemCondition: "https://schema.org/NewCondition",
    },
  };

  return (
    <DemoBagProvider>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(productSchema) }}
      />
      <StoreHeader />
      <main className="min-h-screen bg-background">
        <ProductDetailClient data={data} />
      </main>
    </DemoBagProvider>
  );
}
