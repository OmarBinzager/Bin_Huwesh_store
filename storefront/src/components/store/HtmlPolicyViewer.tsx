"use client";

import Link from "next/link";
import { useState } from "react";
import type { Locale } from "@/lib/i18n/config";
import s from "./HtmlPolicyViewer.module.css";

export type PolicyNavItem = {
  slug: string;
  title: string;
  icon: string;
  badge?: string;
};

const POLICY_ITEMS: PolicyNavItem[] = [
  { slug: "terms", title: "الشروط والأحكام", icon: "📜" },
  { slug: "privacy", title: "سياسة الخصوصية", icon: "🔒" },
  { slug: "return", title: "الاستبدال والاسترجاع", icon: "🔄" },
  { slug: "refund", title: "الاسترداد المالي", icon: "💰" },
  { slug: "cancellation", title: "سياسة الإلغاء", icon: "❌" },
  { slug: "about", title: "من نحن", icon: "🏛️" },
  { slug: "faq", title: "الأسئلة الشائعة", icon: "❓" },
];

const POLICY_ITEMS_EN: PolicyNavItem[] = [
  { slug: "terms", title: "Terms & Conditions", icon: "📜" },
  { slug: "privacy", title: "Privacy Policy", icon: "🔒" },
  { slug: "return", title: "Return & Exchange", icon: "🔄" },
  { slug: "refund", title: "Refund Policy", icon: "💰" },
  { slug: "cancellation", title: "Cancellation Policy", icon: "❌" },
  { slug: "about", title: "About Us", icon: "🏛️" },
  { slug: "faq", title: "FAQ", icon: "❓" },
];

interface HtmlPolicyViewerProps {
  slug: string;
  title: string;
  contentHtml: string;
  locale?: Locale;
}

export default function HtmlPolicyViewer({
  slug,
  title,
  contentHtml,
  locale = "ar",
}: HtmlPolicyViewerProps) {
  const [copied, setCopied] = useState(false);
  const isEn = locale === "en";
  const navItems = isEn ? POLICY_ITEMS_EN : POLICY_ITEMS;
  const currentItem = navItems.find((item) => item.slug === slug);

  const handleCopyLink = async () => {
    try {
      if (typeof window !== "undefined") {
        await navigator.clipboard.writeText(window.location.href);
        setCopied(true);
        setTimeout(() => setCopied(false), 2500);
      }
    } catch {
      // Ignore clipboard error
    }
  };

  const handlePrint = () => {
    if (typeof window !== "undefined") {
      window.print();
    }
  };

  return (
    <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
      {/* Breadcrumb Navigation */}
      <nav
        aria-label="Breadcrumb"
        className={`${s.noPrint} mb-6 flex items-center gap-2 text-xs font-medium text-muted`}
      >
        <Link href="/" className="transition-colors hover:text-ink">
          {isEn ? "Home" : "الرئيسية"}
        </Link>
        <span aria-hidden="true" className="text-line">
          /
        </span>
        <span className="text-muted">{isEn ? "Policies & Information" : "السياسات والمعلومات"}</span>
        <span aria-hidden="true" className="text-line">
          /
        </span>
        <span className="text-ink font-semibold">{title}</span>
      </nav>

      {/* Main Grid Layout: Sidebar Navigation + Unified HTML Document Card */}
      <div className="grid grid-cols-1 gap-8 lg:grid-cols-12">
        {/* Sidebar Nav (Desktop) / Horizontal Tabs (Mobile) */}
        <aside className={`${s.noPrint} lg:col-span-4 xl:col-span-3`}>
          <div className="sticky top-24 space-y-4">
            {/* Header Box */}
            <div className="rounded-3xl border border-line bg-card p-5 shadow-xs">
              <div className="flex items-center gap-3">
                <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-stage text-xl">
                  {currentItem?.icon || "📄"}
                </div>
                <div>
                  <h2 className="font-display text-base font-bold text-ink">
                    {isEn ? "Legal & Policies" : "اللوائح والسياسات"}
                  </h2>
                  <p className="text-[11px] text-muted">
                    {isEn ? "Bin Huwesh Official Guidelines" : "وثائق وإرشادات متجر بن هويش الرسمية"}
                  </p>
                </div>
              </div>

              {/* Navigation list */}
              <nav className="mt-5 space-y-1.5" aria-label="Policies Navigation">
                {navItems.map((item) => {
                  const isActive = item.slug === slug;
                  return (
                    <Link
                      key={item.slug}
                      href={`/policies/${item.slug}`}
                      className={`group flex items-center justify-between rounded-xl px-3.5 py-2.5 text-xs font-medium transition-all ${
                        isActive
                          ? "bg-ink text-on-ink shadow-sm"
                          : "text-muted hover:bg-stage hover:text-ink"
                      }`}
                    >
                      <span className="flex items-center gap-2.5">
                        <span className="text-sm">{item.icon}</span>
                        <span>{item.title}</span>
                      </span>
                      {isActive && (
                        <span className="h-1.5 w-1.5 rounded-full bg-brand" />
                      )}
                    </Link>
                  );
                })}
              </nav>
            </div>

            {/* Quick Support Card */}
            <div className="rounded-3xl border border-line bg-card/60 p-5 backdrop-blur-xs">
              <h3 className="text-xs font-bold text-ink">
                {isEn ? "Need clarification?" : "هل لديك أي استفسار؟"}
              </h3>
              <p className="mt-1 text-[11px] leading-relaxed text-muted">
                {isEn
                  ? "Our customer care team in Aden is here to assist with any policy questions."
                  : "فريق خدمة عملاء بن هويش في عدن متواجد للرد على أسئلتكم ومساعدتكم."}
              </p>
              <a
                href={`https://wa.me/967780024406?text=${encodeURIComponent(
                  `السلام عليكم، لدي استفسار بخصوص: ${title}`
                )}`}
                target="_blank"
                rel="noopener noreferrer"
                className="mt-3.5 flex w-full items-center justify-center gap-2 rounded-xl bg-stage px-4 py-2.5 text-xs font-semibold text-ink transition-colors hover:bg-line"
              >
                <span>💬</span>
                <span>{isEn ? "Contact Support" : "محادثة خدمة العملاء"}</span>
              </a>
            </div>
          </div>
        </aside>

        {/* Unified Document Content Card */}
        <div className="lg:col-span-8 xl:col-span-9">
          <article
            className={`${s.htmlCard} rounded-3xl border border-line bg-card p-6 shadow-xs sm:p-10`}
          >
            {/* Document Header */}
            <header className="border-b border-line pb-6">
              <div className="flex flex-wrap items-center justify-between gap-3">
                <div className="inline-flex items-center gap-2 rounded-full border border-line bg-stage px-3 py-1 text-[11px] font-medium text-muted">
                  <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                  <span>{isEn ? "Official Store Document" : "وثيقة رسمية معتمدة"}</span>
                </div>

                {/* Top Quick Actions */}
                <div className={`${s.noPrint} flex items-center gap-2`}>
                  <button
                    type="button"
                    onClick={handleCopyLink}
                    className="inline-flex items-center gap-1.5 rounded-xl border border-line bg-card px-3 py-1.5 text-xs font-medium text-muted transition-colors hover:border-ink hover:text-ink"
                    title={isEn ? "Copy link" : "نسخ الرابط"}
                  >
                    <span>{copied ? "✓" : "🔗"}</span>
                    <span>
                      {copied
                        ? isEn
                          ? "Copied!"
                          : "تم النسخ!"
                        : isEn
                        ? "Share"
                        : "مشاركة"}
                    </span>
                  </button>

                  <button
                    type="button"
                    onClick={handlePrint}
                    className="inline-flex items-center gap-1.5 rounded-xl border border-line bg-card px-3 py-1.5 text-xs font-medium text-muted transition-colors hover:border-ink hover:text-ink"
                    title={isEn ? "Print document" : "طباعة الوثيقة"}
                  >
                    <span>🖨️</span>
                    <span>{isEn ? "Print" : "طباعة"}</span>
                  </button>
                </div>
              </div>

              <h1 className="mt-4 font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">
                {title}
              </h1>
              <p className="mt-2 text-xs text-muted">
                {isEn
                  ? "Bin Huwesh Fashion & Footwear — Aden, Yemen"
                  : "شركة بن هويش التجارية للأزياء والأحذية — عدن، اليمن"}
              </p>
            </header>

            {/* Rendered HTML Content */}
            <div className="pt-8">
              {contentHtml ? (
                <div
                  className={s.htmlContent}
                  dangerouslySetInnerHTML={{ __html: contentHtml }}
                />
              ) : (
                <div className="py-12 text-center text-muted">
                  <p className="text-base font-medium">
                    {isEn ? "No content published yet." : "لا يوجد محتوى متاح حالياً."}
                  </p>
                </div>
              )}
            </div>

            {/* Document Footer Notice */}
            <footer
              className={`${s.noPrint} mt-12 rounded-2xl border border-line bg-stage/50 p-5 text-xs text-muted`}
            >
              <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                  <span className="font-semibold text-ink">
                    {isEn ? "Bin Huwesh Store Guidelines" : "معايير متجر بن هويش للأزياء"}
                  </span>
                  <p className="mt-0.5 text-[11px]">
                    {isEn
                      ? "For further inquiries, please visit our showroom in Aden (Al Mansoura - Al Hijaz) or call 780024406."
                      : "لأي استفسار إضافي، يسعدنا استقبالكم في صالة عرضنا في عدن (المنصورة – خط الحجاز) أو الاتصال على 780024406."}
                  </p>
                </div>
                <a
                  href="https://wa.me/967780024406"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-xl bg-ink px-4 py-2 text-xs font-semibold text-on-ink transition-opacity hover:opacity-90"
                >
                  <span>واتساب بن هويش</span>
                </a>
              </div>
            </footer>
          </article>
        </div>
      </div>
    </div>
  );
}
