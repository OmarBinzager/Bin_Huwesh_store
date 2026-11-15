import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { DemoBagProvider } from "@/components/store/DemoBag";
import ProductDetailClient from "@/components/store/ProductDetailClient";
import StoreHeader from "@/components/store/StoreHeader";
import { fetchProductDetail } from "@/lib/storefront-api";

export async function generateMetadata({
  params,
}: {
  params: Promise<{ id: string }>;
}): Promise<Metadata> {
  const { id } = await params;
  const data = await fetchProductDetail(id);
  if (!data) return { title: "المنتج — بن هويش" };
  return {
    title: `${data.product.name} — بن هويش`,
    description: data.product.brand?.name
      ? `${data.product.name} من ${data.product.brand.name}`
      : data.product.name,
  };
}

export default async function ProductDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  const data = await fetchProductDetail(id);
  if (!data) {
    notFound();
  }

  return (
    <DemoBagProvider>
      <StoreHeader />
      <main className="min-h-screen bg-background">
        <ProductDetailClient data={data} />
      </main>
    </DemoBagProvider>
  );
}
