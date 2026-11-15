"use client";

import React, { useRef } from "react";
import {
  motion,
  useScroll,
  useTransform,
  useSpring,
  useMotionValue,
  MotionValue,
} from "framer-motion";

export interface CollectionItem {
  id: number;
  image: string;
  title: string;
}

export type CollectionSurferVariant = "magnetic" | "uplift" | "simple";

// Default items for the component in case none are provided
const ITEMS: CollectionItem[] = [
  {
    id: 1,
    image: "/images/collection/collection-01.jpg",
    title: "HERITAGE 01",
  },
  {
    id: 2,
    image: "/images/collection/collection-02.jpg",
    title: "HERITAGE 02",
  },
  {
    id: 3,
    image: "/images/collection/collection-03.jpg",
    title: "HERITAGE 03",
  },
  {
    id: 4,
    image: "/images/collection/collection-04.jpg",
    title: "HERITAGE 04",
  },
  {
    id: 5,
    image: "/images/collection/collection-05.jpg",
    title: "HERITAGE 05",
  },
  {
    id: 6,
    image: "/images/collection/collection-06.jpg",
    title: "HERITAGE 06",
  },
  {
    id: 7,
    image: "/images/collection/collection-07.jpg",
    title: "HERITAGE 07",
  },
  {
    id: 8,
    image: "/images/collection/collection-08.jpg",
    title: "HERITAGE 08",
  },
  {
    id: 9,
    image: "/images/collection/collection-09.jpg",
    title: "HERITAGE 09",
  },
  {
    id: 10,
    image: "/images/collection/collection-10.jpg",
    title: "HERITAGE 10",
  },
  {
    id: 11,
    image: "/images/collection/collection-11.jpg",
    title: "HERITAGE 11",
  },
  {
    id: 12,
    image: "/images/collection/collection-12.jpg",
    title: "HERITAGE 12",
  },
  {
    id: 13,
    image: "/images/collection/collection-13.jpg",
    title: "HERITAGE 13",
  },
  {
    id: 14,
    image: "/images/collection/collection-14.jpg",
    title: "HERITAGE 14",
  },
  {
    id: 15,
    image: "/images/collection/collection-15.jpg",
    title: "HERITAGE 15",
  },
  {
    id: 16,
    image: "/images/collection/collection-16.jpg",
    title: "HERITAGE 16",
  },
];

interface CollectionSurferProps {
  items?: CollectionItem[];
  variant?: CollectionSurferVariant;
  /**
   * Scroll sensitivity: px of scrolling per item. The reference value is 600,
   * which sets the pace of the travel. The total pinned distance is derived
   * from it, so lowering this speeds the sequence up rather than truncating it.
   */
  scrollPerItem?: number;
}

export function CollectionSurfer({
  items = ITEMS,
  variant = "magnetic",
  scrollPerItem = 600,
}: CollectionSurferProps) {
  // 1. Loop Setup: Duplicate items to create a buffer
  // We render the list twice: [Original Set, Duplicate Set]
  // When we scroll past the Original Set, we snap back to the start.
  const duplicatedItems = [...items, ...items];

  /*
   * The exact scroll distance for the ORIGINAL set to travel one full
   * set-length — i.e. every one of the items passes the viewer exactly once,
   * ending on the frame that visually matches the start. This is the shortest
   * distance that completes the sequence cleanly, so it is also the length of
   * the pinned section: the stage releases the moment the last item lands,
   * with no empty scrolling and no second lap.
   */
  const loopDistance = items.length * scrollPerItem;

  /*
   * INTEGRATION NOTE (the only change to the reference's motion source).
   *
   * The reference is a standalone page: it reads window scrollY directly and
   * pins its stage with `position: fixed`. Dropped into the middle of this
   * site that would (a) start the sequence already part-way through, because
   * scrollY is non-zero by the time the hero has been passed, and (b) leave a
   * fixed stage covering Marquee/About/Services forever.
   *
   * So the scroll is read relative to this section instead and re-expressed in
   * the same pixel scale the loop maths expects. Every constant below —
   * scrollPerItem, loopDistance, the step vector, the modulo — is untouched.
   */
  const sectionRef = useRef<HTMLElement>(null);
  const { scrollYProgress } = useScroll({
    target: sectionRef,
    offset: ["start start", "end end"],
  });
  const sectionScroll = useTransform(scrollYProgress, [0, 1], [0, loopDistance]);

  const smoothScroll = useSpring(sectionScroll, {
    mass: 0.1,
    stiffness: 100,
    damping: 20,
  });

  /*
   * 2. Single-pass progress.
   *
   * The reference took `value % loopDistance` so the scene could lap forever
   * on an endless spacer. Now that the section is exactly one sequence long,
   * the modulo would only ever fire at the very end — wrapping 9600 back to 0
   * and snapping the whole track to its start on the last frame before
   * release (the smoothing spring overshoots slightly past the end, which
   * would trigger it). Clamping instead is identical across the whole range
   * and simply holds the final frame. Same path, no lap, no snap.
   */
  const loopedProgress = useTransform(smoothScroll, (value) =>
    Math.min(Math.max(value, 0), loopDistance),
  );

  // Step vector
  const stepX = 240;
  const stepY = -84;
  const stepZ = -288;

  // We only move the scene backwards by the length of ONE set of items
  const x = useTransform(
    loopedProgress,
    [0, loopDistance],
    [0, -items.length * stepX],
  );
  const y = useTransform(
    loopedProgress,
    [0, loopDistance],
    [0, -items.length * stepY],
  );
  const z = useTransform(
    loopedProgress,
    [0, loopDistance],
    [0, -items.length * stepZ],
  );

  // Mouse position for magnetic effect
  // Initialize off-screen so no card is scaled by default
  const mouseX = useMotionValue(-10000);
  const mouseY = useMotionValue(-10000);

  const handleMouseMove = (e: React.MouseEvent) => {
    if (variant === "simple") return;
    mouseX.set(e.clientX);
    mouseY.set(e.clientY);
  };

  const handleMouseLeave = () => {
    if (variant === "simple") return;
    mouseX.set(-10000);
    mouseY.set(-10000);
  };

  return (
    /*
     * The section IS the spacer. Its height is one viewport (the pinned stage
     * itself) plus exactly one sequence, so the scrolled distance while pinned
     * is `loopDistance` 1:1 in real pixels — preserving the authored
     * 600px-per-item pace — and the stage unpins the instant the sequence
     * ends. `sticky` rather than `fixed` is what lets it release into Marquee.
     */
    <section
      ref={sectionRef}
      className="relative text-white w-full"
      style={{ height: `calc(100vh + ${loopDistance}px)` }}
    >
      {/* Pinned viewport */}
      <div
        className="sticky top-0 w-full h-screen overflow-hidden flex items-center justify-center perspective-container"
        onMouseMove={handleMouseMove}
        onMouseLeave={handleMouseLeave}
      >
        {/* UI Overlays */}
        <div className="absolute top-[3vw] left-[3vw] z-50 pointer-events-none mix-blend-difference">
          <p className="font-mono font-medium text-[clamp(0.7rem,1.4vw,1.05rem)] uppercase tracking-[0.35em] leading-none ml-[4vw] opacity-80">
            BIN HUWESH • 2026
          </p>
          {/*
           * Arabic is the dominant line. `tracking` is deliberately left at its
           * default — letter-spacing pulls apart the cursive joins — and the
           * looser leading gives the ascenders/dots room the Latin lines don't
           * need. `text-left` keeps the RTL line flush with the block's left
           * edge instead of drifting to the container's right.
           */}
          <h1
            dir="rtl"
            lang="ar"
            className="font-heading font-bold text-[clamp(2.5rem,7.5vw,6.5rem)] leading-[1.15] text-left"
          >
            وجهتك الأولى لعالم الأناقة
          </h1>
          <p className="font-heading font-bold text-[clamp(1rem,2.4vw,2rem)] leading-[0.9] tracking-[0.12em] uppercase">
            MEN&apos;S CURATED COLLECTION
            <span className="text-[0.4em] align-top relative top-[0.6em] ml-2 font-mono tabular-nums">
              ({items.length})
            </span>
          </p>
        </div>

        {/* Same corner, same weight — `uppercase`/`tracking` dropped because
            neither does anything useful to Arabic, and the extra step in size
            keeps it as legible as the Latin original was at this scale. */}
        <div
          dir="rtl"
          lang="ar"
          className="absolute bottom-[3vw] right-[3vw] z-50 text-sm opacity-70"
        >
          مرّر لاكتشاف المجموعة
        </div>

        {/* 3D Scene */}
        <div
          className="absolute inset-0 flex items-center justify-center"
          style={{
            perspective: "2000px",
            perspectiveOrigin: "10% 10%",
          }}
        >
          {/* Animated Track */}
          <motion.div
            className="relative w-0 h-0"
            style={{
              x,
              y,
              z,
              transformStyle: "preserve-3d",
            }}
          >
            {duplicatedItems.map((item, i) => (
              <Card
                key={`${item.id}-${i}`}
                item={item}
                i={i}
                stepX={stepX}
                stepY={stepY}
                stepZ={stepZ}
                mouseX={mouseX}
                mouseY={mouseY}
                scrollSpring={smoothScroll}
                variant={variant}
              />
            ))}
          </motion.div>
        </div>
      </div>
    </section>
  );
}

function Card({
  item,
  i,
  stepX,
  stepY,
  stepZ,
  mouseX,
  mouseY,
  scrollSpring,
  variant,
}: {
  item: CollectionItem;
  i: number;
  stepX: number;
  stepY: number;
  stepZ: number;
  mouseX: MotionValue<number>;
  mouseY: MotionValue<number>;
  scrollSpring: MotionValue<number>;
  variant: CollectionSurferVariant;
}) {
  const ref = useRef<HTMLDivElement>(null);

  // Calculate distance from mouse to center of card
  // The `: number[]` annotation is only for TS — framer-motion 13 types the
  // multi-value transform input as unknown[]. Behaviour is unchanged.
  const distance = useTransform([mouseX, mouseY, scrollSpring], ([x, y]: number[]) => {
    if (!ref.current || variant === "simple") return 200; // Default large distance
    const rect = ref.current.getBoundingClientRect();
    const centerX = rect.left + rect.width / 2;
    const centerY = rect.top + rect.height / 2;
    const dist = Math.sqrt(Math.pow(x - centerX, 2) + Math.pow(y - centerY, 2));
    return dist;
  });

  // --- Magnetic Variant ---
  // Map distance to scale: Closer = larger
  const targetScale = useTransform(distance, [0, 400], [1.5, 1]);
  const springScale = useSpring(targetScale, {
    mass: 0.5,
    stiffness: 300,
    damping: 20,
  });

  // --- Uplift Variant ---
  // Map distance to Y uplift: Closer = move up (negative Y)
  const targetUplift = useTransform(distance, [0, 400], [-100, 0]);
  const springUplift = useSpring(targetUplift, {
    mass: 0.5,
    stiffness: 300,
    damping: 20,
  });

  // Combine transforms based on variant
  const transform = useTransform([springScale, springUplift], ([s, u]) => {
    let scaleValue = 1;
    let upliftValue = 0;

    if (variant === "magnetic") {
      scaleValue = Number(s);
    } else if (variant === "uplift") {
      upliftValue = Number(u);
    }

    const baseX = i * stepX;
    const baseY = i * stepY;
    const baseZ = i * stepZ;

    return `translate3d(${baseX}px, ${baseY + upliftValue}px, ${baseZ}px) rotateY(-50deg) scale(${scaleValue})`;
  });

  return (
    <motion.div
      ref={ref}
      className="absolute w-[300px] h-[400px] bg-neutral-900 overflow-hidden shadow-2xl transition-colors duration-500 ease-out group"
      style={{
        transform,
        transformStyle: "preserve-3d",
      }}
    >
      {/* Index number: Using i % 16 + 1 so the duplicate cards show correct numbers (01-16) */}
      <div className="absolute -top-6 -left-4 text-white font-mono text-xs opacity-50 transition-opacity group-hover:opacity-100">
        {String((i % 16) + 1).padStart(2, "0")}
      </div>

      {/* Image */}
      <div className="relative w-full h-full brightness-75 group-hover:brightness-100 transition-all duration-300">
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img
          src={item.image}
          alt={item.title}
          className="w-full h-full object-cover"
        />
      </div>

      <div className="absolute inset-0 bg-gradient-to-tr from-black/20 to-transparent pointer-events-none" />
    </motion.div>
  );
}

export default CollectionSurfer;
