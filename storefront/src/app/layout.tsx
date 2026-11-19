import type { Metadata, Viewport } from "next";
import { Alexandria, IBM_Plex_Sans_Arabic, Kanit } from "next/font/google";
import Script from "next/script";
import { BrandConfigProvider } from "@/components/store/BrandConfigContext";
import { CurrencyProvider } from "@/lib/currency";
import { getDirection } from "@/lib/i18n/config";
import { I18nProvider } from "@/lib/i18n/context";
import { getDictionary } from "@/lib/i18n/dictionaries";
import { getServerLocale } from "@/lib/i18n/server";
import { fetchStoreBrandingConfig } from "@/lib/storefront-api";
import "./globals.css";

/* Store UI + body copy. */
const plex = IBM_Plex_Sans_Arabic({
  variable: "--font-plex",
  subsets: ["arabic", "latin"],
  weight: ["300", "400", "500", "600", "700"],
  display: "swap",
});

/* Display headings (look titles, section titles). */
const alexandria = Alexandria({
  variable: "--font-alexandria",
  subsets: ["arabic", "latin"],
  weight: ["400", "500", "600", "700"],
  display: "swap",
});

/* Only used by the archived phase-1 prototype at /prototype-v1. */
const kanit = Kanit({
  variable: "--font-kanit",
  subsets: ["latin"],
  weight: ["300", "400", "500", "600", "700", "800", "900"],
  display: "swap",
  preload: false,
});

/** Shared review deployment — see SITE_REVIEW in next.config.ts. */
const isReviewBuild = process.env.SITE_REVIEW === "1";

export async function generateMetadata(): Promise<Metadata> {
  const [locale, branding] = await Promise.all([
    getServerLocale(),
    fetchStoreBrandingConfig(),
  ]);
  const t = getDictionary(locale);
  const siteUrl = process.env.NEXT_PUBLIC_SITE_URL || "https://binhuwesh.com";

  return {
    metadataBase: new URL(siteUrl),
    ...(branding.favIconUrl
      ? {
          icons: {
            icon: branding.favIconUrl,
            shortcut: branding.favIconUrl,
            apple: branding.favIconUrl,
          },
        }
      : {}),
    title: {
      default: t.meta.siteTitle,
      template: `%s — ${t.meta.brandSuffix}`,
    },
    description: t.meta.siteDescription,
    applicationName: "شركة بن هويش التجارية",
    authors: [{ name: "Bin Huwesh" }],
    keywords: [
      "بن هويش",
      "أزياء رجالية",
      "ملابس شبابية",
      "ملابس ولادية",
      "معاوز يمنية",
      "بدلات رسمية",
      "مقاسات جامبو",
      "أحذية",
      "عدن",
      "اليمن",
      "تسوق ملابس اليمن",
    ],
    creator: "Bin Huwesh Commercial Co.",
    publisher: "Bin Huwesh Commercial Co.",
    formatDetection: {
      email: true,
      address: true,
      telephone: true,
    },
    alternates: {
      canonical: "/",
      languages: {
        ar: "/?lang=ar",
        en: "/?lang=en",
      },
    },
    openGraph: {
      type: "website",
      locale: locale === "ar" ? "ar_YE" : "en_US",
      url: siteUrl,
      siteName: "شركة بن هويش التجارية",
      title: t.meta.siteTitle,
      description: t.meta.siteDescription,
      images: [
        {
          url: "/images/brand/logo-full-light-text.webp",
          width: 1200,
          height: 630,
          alt: "شركة بن هويش التجارية",
        },
      ],
    },
    twitter: {
      card: "summary_large_image",
      title: t.meta.siteTitle,
      description: t.meta.siteDescription,
      images: ["/images/brand/logo-full-light-text.webp"],
    },
    // The shared review deployment (SITE_REVIEW=1) must stay out of search engines.
    ...(isReviewBuild
      ? {
          robots: { index: false, follow: false, googleBot: { index: false, follow: false } },
        }
      : {
          robots: {
            index: true,
            follow: true,
            googleBot: {
              index: true,
              follow: true,
              "max-video-preview": -1,
              "max-image-preview": "large",
              "max-snippet": -1,
            },
          },
        }),
  };
}

export const viewport: Viewport = {
  themeColor: [
    { media: "(prefers-color-scheme: light)", color: "#ffffff" },
    { media: "(prefers-color-scheme: dark)", color: "#0a0a0a" },
  ],
};

/**
 * Runs before first paint: applies the visitor's saved theme (or, the first
 * time, their system preference) so the wrong palette never flashes.
 * Keep in sync with THEME_KEY in components/store/ThemeToggle.tsx.
 */
const themeBootstrap = `(function(){var d=document.documentElement,t;try{t=localStorage.getItem("bh-theme")}catch(e){}if(t!=="light"&&t!=="dark"){t=window.matchMedia&&matchMedia("(prefers-color-scheme: dark)").matches?"dark":"light"}d.dataset.theme=t;d.style.colorScheme=t})();`;

export default async function RootLayout({ children }: LayoutProps<"/">) {
  const [locale, branding] = await Promise.all([
    getServerLocale(),
    fetchStoreBrandingConfig(),
  ]);
  const dir = getDirection(locale);
  const t = getDictionary(locale);
  const siteUrl = process.env.NEXT_PUBLIC_SITE_URL || "https://binhuwesh.com";

  const organizationSchema = {
    "@context": "https://schema.org",
    "@type": "ClothingStore",
    name: "شركة بن هويش التجارية",
    alternateName: "Bin Huwesh",
    url: siteUrl,
    logo: `${siteUrl}/images/brand/bh-mark.png`,
    image: `${siteUrl}/images/brand/logo-full-light-text.webp`,
    description: t.meta.siteDescription,
    telephone: "+967770000000",
    address: {
      "@type": "PostalAddress",
      addressLocality: "Aden",
      addressCountry: "YE",
    },
    priceRange: "$$",
    currenciesAccepted: "YER",
  };

  return (
    <html
      lang={locale}
      dir={dir}
      // The bootstrap script sets data-theme / color-scheme before hydration.
      suppressHydrationWarning
      className={`${plex.variable} ${alexandria.variable} ${kanit.variable}`}
    >
      <head>
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: JSON.stringify(organizationSchema) }}
        />
      </head>
      <body className="bg-paper text-ink antialiased">
        <Script id="theme-bootstrap" strategy="afterInteractive">
          {themeBootstrap}
        </Script>
        {isReviewBuild && (
          <p role="note" className="bg-ink px-4 py-1.5 text-center text-[12px] leading-snug text-on-ink">
            {t.meta.reviewNote}
          </p>
        )}
        <BrandConfigProvider logoUrl={branding.logoUrl}>
          <I18nProvider initialLocale={locale}>
            <CurrencyProvider>{children}</CurrencyProvider>
          </I18nProvider>
        </BrandConfigProvider>
      </body>
    </html>
  );
}
