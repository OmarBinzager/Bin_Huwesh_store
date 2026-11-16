import type { Metadata } from "next";
import OrderDetailClient from "@/components/account/OrderDetailClient";
import SiteFooter from "@/components/closing/SiteFooter";
import { DemoBagProvider } from "@/components/store/DemoBag";
import StoreHeader from "@/components/store/StoreHeader";
import { getDictionary } from "@/lib/i18n/dictionaries";
import { getServerLocale } from "@/lib/i18n/server";

export async function generateMetadata({
  params,
}: {
  params: Promise<{ id: string }>;
}): Promise<Metadata> {
  const { id } = await params;
  const locale = await getServerLocale();
  const t = getDictionary(locale);
  return {
    title: `${t.orderDetails.pageTitle} #${id} — ${t.meta.brandSuffix}`,
  };
}

export default async function OrderDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return (
    <DemoBagProvider>
      <StoreHeader />
      <main className="min-h-screen bg-background">
        <OrderDetailClient orderId={id} />
      </main>
      <SiteFooter />
    </DemoBagProvider>
  );
}
