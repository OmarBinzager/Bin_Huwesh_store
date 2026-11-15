import SiteFooter from "@/components/closing/SiteFooter";
import TestimonialsSection from "@/components/closing/TestimonialsSection";
import VisitSection from "@/components/closing/VisitSection";
import LooksHero from "@/components/hero/LooksHero";
import BrandStrip from "@/components/sections/BrandStrip";
import RailGallerySection from "@/components/sections/RailGallerySection";
import NewArrivalsSection from "@/components/sections/NewArrivalsSection";
import ShopByCategorySection from "@/components/sections/ShopByCategorySection";
import ShoesSection from "@/components/sections/ShoesSection";
import { DemoBagProvider } from "@/components/store/DemoBag";
import StoreHeader from "@/components/store/StoreHeader";
import { getServerLocale } from "@/lib/i18n/server";
import { fetchStorefrontBootstrap } from "@/lib/storefront-api";

export const revalidate = 300;

export default async function Home() {
  const locale = await getServerLocale();
  const data = await fetchStorefrontBootstrap(locale);
  return (
    <DemoBagProvider>
      <StoreHeader />
      <main>
        <LooksHero looks={data.looks} />
        <BrandStrip data={data.brands} />
        <RailGallerySection items={data.rail} />
        <ShopByCategorySection data={data.categories} locale={locale} />
        <NewArrivalsSection data={data.virtualFit} />
        <ShoesSection data={data.shoes} />
        <TestimonialsSection data={data.closing} />
        <VisitSection data={data.closing} />
      </main>
      <SiteFooter data={data.closing} />
    </DemoBagProvider>
  );
}
