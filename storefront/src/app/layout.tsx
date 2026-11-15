import type { Metadata, Viewport } from "next";
import { Alexandria, IBM_Plex_Sans_Arabic, Kanit } from "next/font/google";
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

export const metadata: Metadata = {
  title: "بن هويش — أزياء رجالية وولادية في عدن",
  description:
    "شركة بن هويش التجارية: ملابس رجالية وشبابية وولادية، بدلات، معاوز، مقاسات جامبو، أحذية وإكسسوارات، مع التوصيل إلى المحافظات اليمنية.",
  // The shared review deployment (SITE_REVIEW=1) must stay out of search engines.
  ...(isReviewBuild && {
    robots: { index: false, follow: false, googleBot: { index: false, follow: false } },
  }),
};

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

export default function RootLayout({ children }: LayoutProps<"/">) {
  return (
    <html
      lang="ar"
      dir="rtl"
      // The bootstrap script sets data-theme / color-scheme before hydration.
      suppressHydrationWarning
      className={`${plex.variable} ${alexandria.variable} ${kanit.variable}`}
    >
      <head>
        <script dangerouslySetInnerHTML={{ __html: themeBootstrap }} />
      </head>
      <body className="bg-paper text-ink antialiased">
        {isReviewBuild && (
          <p role="note" className="bg-ink px-4 py-1.5 text-center text-[12px] leading-snug text-on-ink">
            نسخة مراجعة قيد التطوير — المنتجات والأسعار والشراء تجريبية
          </p>
        )}
        {children}
      </body>
    </html>
  );
}
