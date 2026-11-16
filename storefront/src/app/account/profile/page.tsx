import type { Metadata } from "next";
import ProfileClient from "@/components/account/ProfileClient";
import SiteFooter from "@/components/closing/SiteFooter";
import { DemoBagProvider } from "@/components/store/DemoBag";
import StoreHeader from "@/components/store/StoreHeader";
import { getDictionary } from "@/lib/i18n/dictionaries";
import { getServerLocale } from "@/lib/i18n/server";

export async function generateMetadata(): Promise<Metadata> {
  const locale = await getServerLocale();
  const t = getDictionary(locale);
  return {
    title: `${t.account.profileTitle} — ${t.meta.brandSuffix}`,
    description: t.account.profileSubtitle,
  };
}

export default function ProfilePage() {
  return (
    <DemoBagProvider>
      <StoreHeader />
      <main className="min-h-screen bg-background">
        <ProfileClient />
      </main>
      <SiteFooter />
    </DemoBagProvider>
  );
}
