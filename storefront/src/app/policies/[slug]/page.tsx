import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { DemoBagProvider } from "@/components/store/DemoBag";
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
  return { title: `${entry.title} — ${t.meta.brandSuffix}` };
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
        <div className="mx-auto max-w-3xl px-4 py-10 sm:px-8 sm:py-14">
          <nav aria-label={t.breadcrumbs.ariaLabel} className="mb-6 text-[13px] text-muted">
            <ol className="flex items-center gap-1.5">
              <li>
                <Link href="/" className="hover:text-ink">
                  {t.breadcrumbs.home}
                </Link>
              </li>
              <li aria-hidden>/</li>
              <li className="text-ink">{entry.title}</li>
            </ol>
          </nav>

          <article className="rounded-3xl border border-line bg-card p-6 sm:p-10">
            <h1 className="font-display text-3xl font-semibold text-ink">{entry.title}</h1>
            <div
              className="prose prose-sm mt-6 max-w-none leading-relaxed text-muted"
              dangerouslySetInnerHTML={{ __html: content }}
            />
          </article>

          <div className="mt-8 flex flex-wrap gap-2">
            {Object.keys(POLICY_KEYS).map((pKey) => (
              <Link
                key={pKey}
                href={`/policies/${pKey}`}
                className={`rounded-full px-4 py-1.5 text-[12px] font-medium transition-colors ${
                  pKey === slug
                    ? "bg-ink text-on-ink"
                    : "border border-line bg-card text-muted hover:border-brand hover:text-ink"
                }`}
              >
                {t.policies[pKey]?.title ?? pKey}
              </Link>
            ))}
          </div>
        </div>
      </main>
    </DemoBagProvider>
  );
}
