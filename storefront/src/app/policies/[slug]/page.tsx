import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { DemoBagProvider } from "@/components/store/DemoBag";
import StoreHeader from "@/components/store/StoreHeader";
import { fetchPolicies, type PoliciesResponse } from "@/lib/storefront-api";

const POLICY_MAP: Record<
  string,
  { title: string; key: keyof PoliciesResponse; fallback: string }
> = {
  terms: {
    title: "الشروط والأحكام",
    key: "terms_and_conditions",
    fallback: "تخضع جميع الطلبات والمشتريات عبر متجر بن هويش للشروط والأحكام المعتمدة.",
  },
  privacy: {
    title: "سياسة الخصوصية",
    key: "privacy_policy",
    fallback: "نحترم خصوصية عملائنا ونلتزم بحماية بياناتهم الشخصية وعدم مشاركتها إلا لإتمام خدمات التوصيل.",
  },
  return: {
    title: "سياسة الاستبدال والاسترجاع",
    key: "return_policy",
    fallback: "يمكن استبدال أو إرجاع القطع غير المستخدمة بحالتها الأصلية خلال المدة المحددة في الفاتورة.",
  },
  refund: {
    title: "سياسة الاسترداد المالي",
    key: "refund_policy",
    fallback: "يتم استرداد المبالغ وفق طريقة الدفع الأصلية بعد فحص المرتجع والموافقة عليه.",
  },
  cancellation: {
    title: "سياسة إلغاء الطلبات",
    key: "cancellation_policy",
    fallback: "يمكن إلغاء الطلب قبل شحنه عبر التواصل مع خدمة العملاء.",
  },
  about: {
    title: "من نحن",
    key: "about_us",
    fallback: "متجر بن هويش للأزياء الرجالية والنسائية والأطفال والأحذية المختارة بعناية.",
  },
  faq: {
    title: "الأسئلة الشائعة",
    key: "faq",
    fallback: "لأي استفسار حول المقاسات أو الشحن، يسعدنا تواصلكم معنا عبر واتساب خدمة العملاء.",
  },
};

export function generateStaticParams() {
  return Object.keys(POLICY_MAP).map((slug) => ({ slug }));
}

export async function generateMetadata({
  params,
}: {
  params: Promise<{ slug: string }>;
}): Promise<Metadata> {
  const { slug } = await params;
  const entry = POLICY_MAP[slug];
  if (!entry) return {};
  return { title: `${entry.title} — بن هويش` };
}

export default async function PolicyPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const entry = POLICY_MAP[slug];
  if (!entry) notFound();

  const policies = await fetchPolicies();
  const content = policies?.[entry.key]?.trim() || entry.fallback;

  return (
    <DemoBagProvider>
      <StoreHeader />
      <main className="min-h-screen bg-background">
        <div className="mx-auto max-w-3xl px-4 py-10 sm:px-8 sm:py-14">
          <nav aria-label="مسار التصفح" className="mb-6 text-[13px] text-muted">
            <ol className="flex items-center gap-1.5">
              <li>
                <Link href="/" className="hover:text-ink">
                  الرئيسية
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
            {Object.entries(POLICY_MAP).map(([key, item]) => (
              <Link
                key={key}
                href={`/policies/${key}`}
                className={`rounded-full px-4 py-1.5 text-[12px] font-medium transition-colors ${
                  key === slug
                    ? "bg-ink text-on-ink"
                    : "border border-line bg-card text-muted hover:border-brand hover:text-ink"
                }`}
              >
                {item.title}
              </Link>
            ))}
          </div>
        </div>
      </main>
    </DemoBagProvider>
  );
}
