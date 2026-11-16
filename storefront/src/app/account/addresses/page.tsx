import type { Metadata } from "next";
import AddressesClient from "@/components/account/AddressesClient";
import SiteFooter from "@/components/closing/SiteFooter";
import { DemoBagProvider } from "@/components/store/DemoBag";
import StoreHeader from "@/components/store/StoreHeader";
import { getDictionary } from "@/lib/i18n/dictionaries";
import { getServerLocale } from "@/lib/i18n/server";

export async function generateMetadata(): Promise<Metadata> {
  const locale = await getServerLocale();
  const t = getDictionary(locale);
  return {
    title: `${t.account.addressesTitle} — ${t.meta.brandSuffix}`,
    description: t.account.addressesSubtitle,
  };
}

export default function AddressesPage() {
  return (
    <DemoBagProvider>
      <StoreHeader />
      <main className="min-h-screen bg-background">
        <AddressesClient />
      </main>
      <SiteFooter />
    </DemoBagProvider>
  );
}
