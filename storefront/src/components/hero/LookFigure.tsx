"use client";

import Image from "next/image";
import {
  AnimatePresence,
  animate,
  motion,
  useMotionValue,
  useTransform,
  type MotionValue,
} from "framer-motion";
import { useEffect } from "react";
import type { Look, LookItem } from "@/content/types";
import { formatYER, resolvePrice } from "@/lib/catalog";
import { useI18n } from "@/lib/i18n/context";
import {
  detailPose,
  gridPose,
  mixPose,
  ringPose,
  stagger,
  type Metrics,
  type Stage,
} from "./geometry";

/**
 * Focus state, decided by the hero:
 *  "self"  — this model is the one in focus (hovered, or the active look);
 *  "other" — another model is in focus, so this one softens;
 *  "none"  — nothing is singled out (grid at rest): depth rules alone.
 */
export type Focus = "self" | "other" | "none";

/** Extra softness (px) for models that are not in focus. */
const FOCUS_BLUR = { desktop: 3.2, compact: 2.2 };

type Props = {
  look: Look;
  index: number;
  count: number;
  stage: Stage;
  m: Metrics;
  rot: MotionValue<number>;
  grid: MotionValue<number>;
  detail: MotionValue<number>;
  isActive: boolean;
  focus: Focus;
  showHotspots: boolean;
  selectedItem: string | null;
  onSelectItem: (id: string) => void;
  preload: boolean;
};

/**
 * One cut-out model. Position, size, depth blur and fade are all derived from
 * shared motion values, so React never re-renders during drag/animation.
 * The blur is applied to the photo (and its ground shadow) only — never to
 * hotspots, labels or buttons.
 */
export default function LookFigure({
  look,
  index,
  count,
  stage,
  m,
  rot,
  grid,
  detail,
  isActive,
  focus,
  showHotspots,
  selectedItem,
  onSelectItem,
  preload,
}: Props) {
  const scaleInScene = look.image.scale || 1;
  const boxH = m.baseH * scaleInScene;
  const boxW = boxH * (look.image.width / look.image.height);
  const offX = ((look.image.offsetX ?? 0) / 100) * boxH;
  const offY = ((look.image.offsetY ?? 0) / 100) * boxH;

  const pose = useTransform(() => {
    const ring = ringPose(index, rot.get(), count, m);
    const g = stagger(grid.get(), index, count);
    let p = mixPose(ring, gridPose(index, count, stage, m), g);
    const d = detail.get();
    if (isActive) {
      p = mixPose(p, detailPose(stage, m, scaleInScene), d);
    } else if (d > 0) {
      p = { ...p, opacity: p.opacity * (1 - d), scale: p.scale * (1 - 0.12 * d) };
    }
    return p;
  });

  /* 1 = sharp (in focus), 0 = softened. Animated so focus changes glide. */
  const sharp = useMotionValue(focus === "other" ? 0 : 1);
  useEffect(() => {
    const c = animate(sharp, focus === "other" ? 0 : 1, { duration: 0.45, ease: [0.33, 0, 0.2, 1] });
    return () => c.stop();
  }, [focus, sharp]);

  const focusPx = m.compact ? FOCUS_BLUR.compact : FOCUS_BLUR.desktop;
  const filter = useTransform(() => {
    const p = pose.get();
    const s = sharp.get();
    // In focus: perfectly sharp, even from the back of the ring.
    // Out of focus: at least `focusPx`, more if depth already blurs it.
    const blur = focus === "none" ? p.blur : (1 - s) * Math.max(p.blur, focusPx);
    return blur > 0.15 ? `blur(${blur.toFixed(2)}px)` : "none";
  });

  const x = useTransform(pose, (p) => p.x);
  const y = useTransform(pose, (p) => p.y - boxH);
  const scale = useTransform(pose, (p) => p.scale);
  const opacity = useTransform(pose, (p) => p.opacity);
  const zIndex = useTransform(pose, (p) => p.z);
  const pointerEvents = useTransform(pose, (p) => (p.opacity < 0.05 ? "none" : "auto"));

  return (
    <motion.div
      data-look-index={index}
      className="absolute top-0 left-1/2 select-none"
      style={{
        width: boxW,
        height: boxH,
        marginLeft: -boxW / 2,
        x,
        y,
        scale,
        opacity,
        zIndex,
        pointerEvents,
        transformOrigin: "50% 100%",
        /* No `will-change: transform`: Chrome would freeze the raster at the
           small back-of-ring size and the model would look soft when it grows. */
      }}
    >
      {/* Admin-tunable nudge; hotspots ride along with the photo. */}
      <div className="absolute inset-0" style={{ transform: `translate(${offX}px, ${offY}px)` }}>
        <motion.div className="absolute inset-0" style={{ filter }}>
          {/* Ground shadow: drawn in code so it travels and scales with the model. */}
          <div
            aria-hidden
            className="absolute left-[8%] right-[8%] bottom-[-3.5%] h-[9%] rounded-[50%]"
            style={{
              background:
                "radial-gradient(closest-side, rgb(var(--bh-ground) / var(--bh-ground-a)), rgb(var(--bh-ground) / calc(var(--bh-ground-a) * 0.4)) 55%, rgb(var(--bh-ground) / 0) 100%)",
            }}
          />
          <Image
            src={look.image.src}
            alt={look.image.alt}
            width={look.image.width}
            height={look.image.height}
            sizes={m.compact ? "60vw" : "(max-width: 1400px) 30vw, 420px"}
            preload={preload}
            draggable={false}
            className="relative h-full w-full object-contain"
          />
        </motion.div>

        <AnimatePresence>
          {showHotspots &&
            look.items.map((item, n) => (
              <Hotspot
                key={item.id}
                item={item}
                n={n}
                selected={selectedItem === item.id}
                onSelect={onSelectItem}
                compact={m.compact}
              />
            ))}
        </AnimatePresence>
      </div>
    </motion.div>
  );
}

function Hotspot({
  item,
  n,
  selected,
  onSelect,
  compact,
}: {
  item: LookItem;
  n: number;
  selected: boolean;
  onSelect: (id: string) => void;
  compact: boolean;
}) {
  const { locale } = useI18n();
  const price = resolvePrice(item);
  // Desktop: pills open towards the roomier side of the figure.
  // Phone: the figure is centred on a narrow screen, so a sideways pill
  // runs off the edge — open it below the dot, centred, instead.
  const opensLeft = item.hotspot.x < 55;
  const pillPos = compact
    ? "top-[calc(100%+2px)] left-1/2 -translate-x-1/2"
    : `top-1/2 -translate-y-1/2 ${opensLeft ? "right-[calc(100%+2px)]" : "left-[calc(100%+2px)]"}`;
  return (
    <motion.div
      className="absolute z-10"
      style={{ left: `${item.hotspot.x}%`, top: `${item.hotspot.y}%` }}
      initial={{ opacity: 0, scale: 0.4 }}
      animate={{ opacity: 1, scale: 1, transition: { delay: 0.35 + n * 0.08, type: "spring", stiffness: 380, damping: 24 } }}
      exit={{ opacity: 0, scale: 0.4, transition: { duration: 0.15 } }}
    >
      <div className="group relative -translate-x-1/2 -translate-y-1/2">
        <button
          type="button"
          onClick={(e) => {
            e.stopPropagation();
            onSelect(item.id);
          }}
          aria-label={`${item.label} — ${formatYER(price.amount, locale)}`}
          aria-pressed={selected}
          className="nums relative grid size-11 place-items-center rounded-full outline-none"
        >
          {/* Dots sit on the photo, so they keep fixed colours in both themes. */}
          <span
            aria-hidden
            className={`absolute inset-1.5 rounded-full ${selected ? "bg-brand" : "bg-white/70"} animate-ping opacity-60`}
            style={{ animationDuration: "2.2s" }}
          />
          <span
            className={`relative grid size-7 place-items-center rounded-full text-[11px] font-semibold text-white shadow-[0_2px_10px_rgba(0,0,0,0.25)] ring-2 ring-white transition-colors ${
              selected ? "bg-brand" : "bg-onyx group-hover:bg-brand"
            }`}
          >
            {String(n + 1).padStart(2, "0")}
          </span>
        </button>
        <span
          className={`pointer-events-none absolute z-10 whitespace-nowrap rounded-full border border-ink/10 bg-card/95 px-3 py-1.5 text-[12px] text-ink shadow-md backdrop-blur transition-all duration-200 ${pillPos} ${selected ? "opacity-100" : "opacity-0 group-hover:opacity-100 group-focus-within:opacity-100"}`}
        >
          <span className="font-medium">{item.label}</span>
          <span className="nums ms-2 text-muted">{formatYER(price.amount, locale)}</span>
        </span>
      </div>
    </motion.div>
  );
}
