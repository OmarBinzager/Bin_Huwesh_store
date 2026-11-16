import type { Metadata } from "next";
import NotificationsClient from "@/components/account/NotificationsClient";
import SiteFooter from "@/components/closing/SiteFooter";
import { DemoBagProvider } from "@/components/store/DemoBag";
import StoreHeader from "@/components/store/StoreHeader";
import { getDictionary } from "@/lib/i18n/dictionaries";
import { getServerLocale } from "@/lib/i18n/server";

export async function generateMetadata(): Promise<Metadata> {
  const locale = await getServerLocale();
  const t = getDictionary(locale);
  return {
    title: `${t.account.notificationsTitle} — ${t.meta.brandSuffix}`,
    description: t.account.notificationsSubtitle,
  };
}

export default function NotificationsPage() {
  return (
    <DemoBagProvider>
      <StoreHeader />
      <main className="min-h-screen bg-background">
        <NotificationsClient />
      </main>
      <SiteFooter />
    </DemoBagProvider>
  );
}
