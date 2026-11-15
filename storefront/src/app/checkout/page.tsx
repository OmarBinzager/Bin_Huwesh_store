import type { Metadata } from "next";
import CheckoutClient from "@/components/store/CheckoutClient";
import { DemoBagProvider } from "@/components/store/DemoBag";
import StoreHeader from "@/components/store/StoreHeader";
import { getDictionary } from "@/lib/i18n/dictionaries";
import { getServerLocale } from "@/lib/i18n/server";

export async function generateMetadata(): Promise<Metadata> {
  const locale = await getServerLocale();
  const t = getDictionary(locale);
  return {
    title: t.checkout.metaTitle,
    description: t.checkout.metaDescription,
  };
}

export default function CheckoutPage() {
  return (
    <DemoBagProvider>
      <StoreHeader />
      <main className="min-h-screen bg-background">
        <CheckoutClient />
      </main>
    </DemoBagProvider>
  );
}
