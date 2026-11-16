import type { Metadata } from "next";
import Link from "next/link";
import { AuthFlowClient } from "@/components/auth/AuthFlowClient";
import { DemoBagProvider } from "@/components/store/DemoBag";
import StoreHeader from "@/components/store/StoreHeader";
import { fetchAuthConfig } from "@/lib/auth-api";
import { getDictionary } from "@/lib/i18n/dictionaries";
import { getServerLocale } from "@/lib/i18n/server";

export const dynamic = "force-dynamic";

export async function generateMetadata(): Promise<Metadata> {
  const locale = await getServerLocale();
  const t = getDictionary(locale);
  return {
    title: `${t.auth.registerTitle} — ${t.meta.brandSuffix}`,
    description: t.auth.registerSubtitle,
  };
}

export default async function RegisterPage() {
  const locale = await getServerLocale();
  const t = getDictionary(locale);
  const config = await fetchAuthConfig(locale);

  return (
    <DemoBagProvider>
      <StoreHeader />
      <main className="min-h-screen bg-background">
        <div className="mx-auto max-w-[1440px] px-4 py-8 sm:px-6 lg:px-10 space-y-8">
          <nav
            aria-label={t.breadcrumbs.ariaLabel}
            className="flex items-center gap-2 text-xs text-muted"
          >
            <Link href="/" className="hover:text-ink transition">
              {t.breadcrumbs.home}
            </Link>
            <span>/</span>
            <span className="text-ink font-medium">{t.auth.registerTitle}</span>
          </nav>

          <AuthFlowClient initialMode="register" initialConfig={config} />
        </div>
      </main>
    </DemoBagProvider>
  );
}
