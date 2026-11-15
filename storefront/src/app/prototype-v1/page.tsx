import AboutSection from "@/components/sections/AboutSection";
import CosmosGallerySection from "@/components/sections/CosmosGallerySection";
import HeroSection from "@/components/sections/HeroSection";
import MarqueeSection from "@/components/sections/MarqueeSection";
import ServicesSection from "@/components/sections/ServicesSection";
import AtmosphericBackground from "@/components/ui/AtmosphericBackground";

import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "النموذج الأول (أرشيف) — بن هويش",
  robots: { index: false },
};

/**
 * Phase-1 dark visual prototype, kept for reference while the store design
 * replaces it at "/". It keeps its own font, colours and direction so the
 * new store theme does not leak into it.
 */
export default function PrototypeV1() {
  return (
    <main
      dir="ltr"
      // Pin the light palette: this archived page uses `ink` as its dark backdrop.
      data-theme="light"
      className="relative bg-ink text-mist overflow-x-clip font-proto"
    >
      {/*
       * The atmospheric background now scopes to the Hero alone: the Cosmos
       * gallery below brings its own black backdrop, as supplied. The Hero's
       * own background is unchanged.
       */}
      <div className="relative">
        <AtmosphericBackground />
        <HeroSection />
      </div>

      <CosmosGallerySection />

      <MarqueeSection />
      <AboutSection />
      <ServicesSection />
    </main>
  );
}
