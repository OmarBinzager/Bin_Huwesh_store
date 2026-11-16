import type { Metadata } from "next";
import { notFound } from "next/navigation";
import SiteFooter from "@/components/closing/SiteFooter";
import { DemoBagProvider } from "@/components/store/DemoBag";
import HtmlPolicyViewer from "@/components/store/HtmlPolicyViewer";
import StoreHeader from "@/components/store/StoreHeader";
import { getDictionary } from "@/lib/i18n/dictionaries";
import { getServerLocale } from "@/lib/i18n/server";
import { fetchPolicies, type PoliciesResponse } from "@/lib/storefront-api";

const POLICY_KEYS: Record<string, keyof PoliciesResponse> = {
  terms: "terms_and_conditions",
  privacy: "privacy_policy",
  return: "return_policy",
  refund: "refund_policy",
  cancellation: "cancellation_policy",
  about: "about_us",
  faq: "faq",
};

export function generateStaticParams() {
  return Object.keys(POLICY_KEYS).map((slug) => ({ slug }));
}

export async function generateMetadata({
  params,
}: {
  params: Promise<{ slug: string }>;
}): Promise<Metadata> {
  const { slug } = await params;
  const locale = await getServerLocale();
  const t = getDictionary(locale);
  const entry = t.policies[slug];
  if (!entry) return {};
  return {
    title: `${entry.title} — ${t.meta.brandSuffix}`,
    description: entry.fallback,
  };
}

export default async function PolicyPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const key = POLICY_KEYS[slug];
  if (!key) notFound();

  const locale = await getServerLocale();
  const t = getDictionary(locale);
  const entry = t.policies[slug];
  const policies = await fetchPolicies(locale);
  const content = policies?.[key]?.trim() || entry.fallback;

  return (
    <DemoBagProvider>
      <StoreHeader />
      <main className="min-h-screen bg-background">
        <HtmlPolicyViewer
          slug={slug}
          title={entry.title}
          contentHtml={content}
          locale={locale}
        />
      </main>
      <SiteFooter />
    </DemoBagProvider>
  );
}
