"use client";

import { Suspense } from "react";
import { Canvas } from "@react-three/fiber";
import { OrbitControls } from "@react-three/drei";
import { ParticleSphere } from "@/components/ui/cosmos-3d-orbit-gallery";

/**
 * The 16 approved Bin Huwesh looks, square-cropped for the gallery's square
 * planes (`planeGeometry args={[1.5, 1.5]}`) so nothing is stretched.
 * Sourced from brand/images — the originals there are never served directly.
 */
const binHuweshImages = Array.from(
  { length: 16 },
  (_, i) => `/images/cosmos/cosmos-${String(i + 1).padStart(2, "0")}.jpg`,
);

export default function CosmosGallerySection() {
  return (
    <section className="w-full h-screen bg-black relative">
      {/*
       * `absolute` rather than the demo's `fixed`: the demo is a standalone
       * full-page hero, so a fixed heading was harmless there. In this page it
       * would pin the text over every section below for the whole scroll.
       */}
      <div className="absolute top-20 left-0 right-0 z-10 p-6">
        <div className="max-w-[750px] mx-auto px-6 text-center">
          <p className="font-mono text-xs sm:text-sm uppercase tracking-[0.35em] text-white/70">
            BIN HUWESH • 2026
          </p>
          <h2
            dir="rtl"
            lang="ar"
            className="mt-5 text-white text-4xl md:text-6xl leading-[1.3] tracking-tight font-normal text-balance"
          >
            وجهتك الأولى لعالم الأناقة
          </h2>
          <p className="mt-5 text-xs sm:text-sm md:text-base uppercase tracking-[0.2em] text-white/60">
            MEN&apos;S CURATED COLLECTION (16)
          </p>
        </div>
      </div>

      <Canvas camera={{ position: [-10, 1.5, 10], fov: 50 }}>
        <ambientLight intensity={0.5} />
        <pointLight position={[10, 10, 10]} intensity={1} />
        {/* useTexture suspends while the 16 textures load. */}
        <Suspense fallback={null}>
          <ParticleSphere images={binHuweshImages} />
        </Suspense>
        <OrbitControls enablePan={true} enableZoom={true} enableRotate={true} />
      </Canvas>
    </section>
  );
}
