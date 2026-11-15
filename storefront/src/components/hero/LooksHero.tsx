"use client";

import {
  AnimatePresence,
  animate,
  motion,
  useMotionValue,
  useMotionValueEvent,
  useReducedMotion,
  type MotionValue,
} from "framer-motion";
import { useCallback, useEffect, useLayoutEffect, useRef, useState } from "react";
import type { Look } from "@/content/types";
import { formatYER, resolvePrice } from "@/lib/catalog";
import { ArrowIcon, CloseIcon, PauseIcon, PlayIcon, PlusIcon } from "@/components/store/icons";
import LookFigure, { type Focus } from "./LookFigure";
import LookReceipt from "./LookReceipt";
import { gridCell, metrics, mod, type Stage } from "./geometry";

type Mode = "ring" | "grid";

const EASE_IO = [0.65, 0, 0.25, 1] as const;
const AUTOPLAY_MS = 4800;

/**
 * Bin Huwesh hero: a ring of cut-out looks you can drag, a grid view of all
 * looks, and a detail view with garment hotspots and a receipt panel.
 * Content comes in through `looks`; this file holds only behaviour.
 */
export default function LooksHero({
  looks,
  className = "h-[calc(100svh-104px)] min-h-[600px] max-h-[1000px]",
}: {
  looks: Look[];
  /** Height classes; the admin preview passes its own. */
  className?: string;
}) {
  const n = looks.length;
  const reducedRaw = useReducedMotion();
  const [mounted, setMounted] = useState(false);
  useEffect(() => setMounted(true), []);
  const reduced = mounted ? (reducedRaw ?? false) : false;
  const stageRef = useRef<HTMLDivElement>(null);
  const [stage, setStage] = useState<Stage | null>(null);

  const rot = useMotionValue(0);
  const grid = useMotionValue(0);
  const detail = useMotionValue(0);

  const [active, setActive] = useState(0);
  const activeRef = useRef(0);
  const [mode, setMode] = useState<Mode>("ring");
  const [open, setOpen] = useState(false);
  const [paused, setPaused] = useState(false);
  const [selectedItem, setSelectedItem] = useState<string | null>(null);
  const [dragging, setDragging] = useState(false);
  /** Look under a mouse pointer (desktop hover focus); touch never sets it. */
  const [hovered, setHovered] = useState<number | null>(null);
  const lastInteraction = useRef(0);

  /* ── stage size ─────────────────────────────────────────────── */
  useLayoutEffect(() => {
    const el = stageRef.current;
    if (!el) return;
    const ro = new ResizeObserver(([e]) => {
      const { width, height } = e.contentRect;
      setStage({ w: width, h: height });
    });
    ro.observe(el);
    return () => ro.disconnect();
  }, []);

  /* Reduced motion: start on the calm grid, never autoplay. */
  useEffect(() => {
    if (!reduced) return;
    grid.set(1);
    // eslint-disable-next-line react-hooks/set-state-in-effect -- mirror a media preference once
    setMode("grid");
  }, [reduced, grid]);

  useMotionValueEvent(rot, "change", (r) => {
    if (!n) return;
    const i = mod(Math.round(r), n);
    if (i !== activeRef.current) {
      activeRef.current = i;
      setActive(i);
    }
  });

  const dur = (s: number) => (reduced ? 0 : s);

  const goTo = useCallback(
    (k: number) => {
      const r = rot.get();
      const base = Math.round(r);
      let delta = mod(k - base, n);
      if (delta > n / 2) delta -= n;
      animate(rot, base + delta, reduced ? { duration: 0 } : { type: "spring", stiffness: 90, damping: 20, mass: 1 });
    },
    [rot, n, reduced],
  );

  const openDetail = useCallback(
    (i: number, item?: string) => {
      if (i !== activeRef.current || Math.abs(rot.get() - Math.round(rot.get())) > 0.01) {
        const base = Math.round(rot.get());
        let delta = mod(i - base, n);
        if (delta > n / 2) delta -= n;
        rot.jump(base + delta);
      }
      setSelectedItem(item ?? null);
      setOpen(true);
      if (mode === "grid") {
        setMode("ring");
        animate(grid, 0, { duration: dur(0.8), ease: EASE_IO });
      }
      animate(detail, 1, { duration: dur(0.85), ease: EASE_IO });
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [mode, n, rot, grid, detail, reduced],
  );

  const closeDetail = useCallback(() => {
    setOpen(false);
    setSelectedItem(null);
    lastInteraction.current = Date.now();
    animate(detail, 0, { duration: dur(0.7), ease: EASE_IO });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [detail, reduced]);

  const switchMode = useCallback(
    (next: Mode) => {
      if (next === mode) return;
      if (open) closeDetail();
      setMode(next);
      animate(grid, next === "grid" ? 1 : 0, { duration: dur(1.05), ease: EASE_IO });
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [mode, open, closeDetail, grid, reduced],
  );

  /* ── autoplay ───────────────────────────────────────────────── */
  useEffect(() => {
    if (reduced || paused || open || mode !== "ring" || dragging) return;
    const id = setInterval(() => {
      if (document.hidden) return;
      if (Date.now() - lastInteraction.current < AUTOPLAY_MS * 1.5) return;
      goTo(activeRef.current + 1);
    }, AUTOPLAY_MS);
    return () => clearInterval(id);
  }, [reduced, paused, open, mode, dragging, goTo]);

  /* ── drag the ring ──────────────────────────────────────────── */
  const drag = useRef<{
    id: number;
    x0: number;
    r0: number;
    lastX: number;
    lastT: number;
    v: number;
    moved: boolean;
    index: number | null;
  } | null>(null);

  const onPointerDown = (e: React.PointerEvent<HTMLDivElement>) => {
    if (!stage || open) return;
    const target = (e.target as HTMLElement).closest<HTMLElement>("[data-look-index]");
    const index = target ? Number(target.dataset.lookIndex) : null;
    if (mode === "grid") {
      if (index !== null) openDetail(index);
      return;
    }
    if (e.pointerType === "mouse" && e.button !== 0) return;
    rot.stop();
    drag.current = { id: e.pointerId, x0: e.clientX, r0: rot.get(), lastX: e.clientX, lastT: performance.now(), v: 0, moved: false, index };
    lastInteraction.current = Date.now();
  };

  const onPointerMove = (e: React.PointerEvent<HTMLDivElement>) => {
    if (e.pointerType === "mouse" && !drag.current?.moved) {
      const t = (e.target as HTMLElement).closest<HTMLElement>("[data-look-index]");
      const i = t ? Number(t.dataset.lookIndex) : null;
      if (i !== hovered) setHovered(i);
    }
    const d = drag.current;
    if (!d || d.id !== e.pointerId || !stage) return;
    const dx = e.clientX - d.x0;
    if (!d.moved && Math.abs(dx) > 6) {
      d.moved = true;
      setDragging(true);
      (e.currentTarget as HTMLElement).setPointerCapture(e.pointerId);
    }
    if (!d.moved) return;
    const now = performance.now();
    const dt = Math.max(1, now - d.lastT);
    d.v = 0.8 * ((e.clientX - d.lastX) / dt) + 0.2 * d.v;
    d.lastX = e.clientX;
    d.lastT = now;
    // Dragging right brings the look on the left (the next one, in RTL) forward.
    rot.set(d.r0 + dx / metrics(stage).stepPx);
  };

  const endDrag = (e: React.PointerEvent<HTMLDivElement>) => {
    const d = drag.current;
    if (!d || d.id !== e.pointerId || !stage) return;
    drag.current = null;
    lastInteraction.current = Date.now();
    if (!d.moved) {
      if (d.index !== null) {
        if (d.index === activeRef.current) openDetail(d.index);
        else goTo(d.index);
      }
      return;
    }
    setDragging(false);
    // A flick carries on, but never more than two looks past where it was let go.
    const now = rot.get();
    const fling = Math.max(-2, Math.min(2, (d.v * 260) / metrics(stage).stepPx));
    animate(rot, Math.round(now + fling), { type: "spring", stiffness: 110, damping: 22 });
  };

  /* ── keyboard ───────────────────────────────────────────────── */
  const onKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === "Escape" && open) return closeDetail();
    if (open) return;
    if (e.key === "ArrowLeft") goTo(activeRef.current + 1);
    else if (e.key === "ArrowRight") goTo(activeRef.current - 1);
    else if (e.key === "Enter" && mode === "ring") openDetail(activeRef.current);
    else return;
    lastInteraction.current = Date.now();
    e.preventDefault();
  };

  const m = stage ? metrics(stage) : null;
  const safeActive = n ? mod(active, n) : 0;
  const look = looks[safeActive];
  const compact = m?.compact ?? false;

  /*
   * Who is sharp: in the ring the hovered model (desktop) or else the active
   * one, so every other model — sides included — softens. Touch has no hover,
   * so on phones focus simply follows the selected look. The grid is all
   * sharp at rest; hovering a tile there softens the rest the same way.
   */
  const focusIndex: number | null =
    open || dragging ? safeActive : hovered ?? (mode === "ring" ? safeActive : null);
  const focusFor = (i: number): Focus => (focusIndex === null ? "none" : i === focusIndex ? "self" : "other");

  if (!n || !look) {
    return (
      <section className={`grid place-items-center bg-stage text-muted ${className}`}>
        لا توجد إطلالات منشورة بعد.
      </section>
    );
  }

  return (
    <section
      aria-roledescription="carousel"
      aria-label="إطلالات بن هويش"
      onKeyDown={onKeyDown}
      className={`relative isolate overflow-hidden bg-stage text-ink ${className}`}
    >
      {/* Floor light — gives the figures a surface to stand on. */}
      <div
        aria-hidden
        className="pointer-events-none absolute inset-0 -z-10"
        style={{
          background:
            "radial-gradient(60% 45% at 50% 78%, var(--bh-floor-hi) 0%, color-mix(in srgb, var(--bh-floor-hi) 55%, transparent) 45%, transparent 100%), radial-gradient(90% 60% at 50% 0%, var(--bh-floor-top) 0%, transparent 70%)",
        }}
      />

      {/* ── stage ─────────────────────────────────────────────── */}
      <div
        ref={stageRef}
        tabIndex={0}
        aria-label="اسحب لتصفح الإطلالات، أو استخدم الأسهم"
        onPointerDown={onPointerDown}
        onPointerMove={onPointerMove}
        onPointerUp={endDrag}
        onPointerCancel={endDrag}
        onPointerLeave={() => setHovered(null)}
        className={`absolute inset-0 touch-pan-y outline-none ${
          open ? "" : mode === "grid" ? "cursor-pointer" : dragging ? "cursor-grabbing" : "cursor-grab"
        }`}
      >
        {stage &&
          m &&
          looks.map((l, i) => (
            <LookFigure
              key={l.id}
              look={l}
              index={i}
              count={n}
              stage={stage}
              m={m}
              rot={rot}
              grid={grid}
              detail={detail}
              isActive={i === safeActive}
              focus={focusFor(i)}
              showHotspots={open && i === safeActive}
              selectedItem={selectedItem}
              onSelectItem={setSelectedItem}
              preload={i < 2}
            />
          ))}
      </div>

      {/* Grid labels */}
      <AnimatePresence>
        {stage && m && mode === "grid" && !open && (
          <motion.ol
            className="pointer-events-none absolute inset-0 z-[600]"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1, transition: { delay: dur(0.55) } }}
            exit={{ opacity: 0, transition: { duration: 0.15 } }}
          >
            {looks.map((l, i) => {
              const c = gridCell(i, n, stage, m);
              return (
                <li
                  key={l.id}
                  className="nums absolute -translate-x-1/2 whitespace-nowrap text-center text-[11px] sm:text-[12px]"
                  style={{ left: stage.w / 2 + c.cx, top: c.labelY }}
                >
                  <span className="text-muted">{String(i + 1).padStart(2, "0")}</span>{" "}
                  <span className="font-medium">{l.title}</span>
                </li>
              );
            })}
          </motion.ol>
        )}
      </AnimatePresence>

      {/* ── top bar: eyebrow + preview notice ─────────────────── */}
      <div className="pointer-events-none absolute inset-x-0 top-0 z-[700] flex items-start justify-between gap-3 px-4 pt-4 sm:px-8 sm:pt-6">
        <AnimatePresence mode="wait">
          {open ? (
            <motion.div
              key={`t-${look.id}`}
              initial={{ opacity: 0, y: -8 }}
              animate={{ opacity: 1, y: 0, transition: { delay: dur(0.3) } }}
              exit={{ opacity: 0 }}
              className="pointer-events-auto"
            >
              <p className="nums text-[12px] text-muted">إطلالة {String(safeActive + 1).padStart(2, "0")}</p>
              <h2 className="font-display text-2xl font-semibold sm:text-4xl">{look.title}</h2>
              <p className="mt-1 hidden text-[13px] text-muted sm:block">{look.subtitle}</p>
            </motion.div>
          ) : (
            <motion.p
              key="eyebrow"
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              className="text-[12px] tracking-wide text-muted"
            >
              إطلالات بن هويش · مجموعة الموسم
            </motion.p>
          )}
        </AnimatePresence>
        <p className="hidden rounded-full border border-ink/10 bg-card/70 px-3 py-1 text-[11px] text-muted backdrop-blur sm:block">
          نموذج معاينة — الصور مولّدة والأسعار تجريبية
        </p>
      </div>

      {/* ── ring / grid toggle ─────────────────────────────────── */}
      <AnimatePresence>
        {!open && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className={`absolute z-[700] ${
              compact ? "left-4 top-12" : "left-6 top-1/2 -translate-y-1/2"
            }`}
          >
            <ModeToggle mode={mode} onChange={switchMode} vertical={!compact} />
          </motion.div>
        )}
      </AnimatePresence>

      {/* ── bottom bar (ring) ──────────────────────────────────── */}
      <AnimatePresence>
        {mode === "ring" && !open && (
          <motion.div
            initial={{ opacity: 0, y: 12 }}
            animate={{ opacity: 1, y: 0, transition: { delay: dur(0.2) } }}
            exit={{ opacity: 0, y: 12, transition: { duration: dur(0.2) } }}
            className="absolute inset-x-0 bottom-0 z-[700] px-4 pb-5 sm:px-8 sm:pb-7"
          >
            <div className="flex items-end justify-between gap-4">
              <div className="min-w-0">
                <div className="flex items-center gap-2 text-[12px] text-muted">
                  {/* ltr: keeps "01 / 08" from flipping to "08 / 01" in the RTL page */}
                  <span className="nums" dir="ltr">
                    {String(safeActive + 1).padStart(2, "0")} / {String(n).padStart(2, "0")}
                  </span>
                  {!reduced && (
                    <button
                      type="button"
                      onClick={() => setPaused((p) => !p)}
                      aria-label={paused ? "تشغيل العرض التلقائي" : "إيقاف العرض التلقائي"}
                      className="grid size-6 place-items-center rounded-full border border-ink/15 hover:border-ink/50"
                    >
                      {paused ? <PlayIcon width={11} height={11} /> : <PauseIcon width={11} height={11} />}
                    </button>
                  )}
                </div>
                <AnimatePresence mode="wait" initial={false}>
                  <motion.div
                    key={look.id}
                    initial={{ opacity: 0, y: 6 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0, y: -6 }}
                    transition={{ duration: dur(0.25) }}
                  >
                    <h2 className="mt-1 font-display text-[26px] leading-tight font-semibold sm:text-[40px]">
                      {look.title}
                    </h2>
                    <p className="text-[13px] text-muted">{look.subtitle}</p>
                  </motion.div>
                </AnimatePresence>
              </div>

              {!compact && (
                <AnimatePresence mode="wait" initial={false}>
                  <motion.ul
                    key={look.id}
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    transition={{ duration: dur(0.2) }}
                    className="mb-1 hidden min-w-0 flex-1 flex-nowrap justify-center gap-2 xl:flex"
                  >
                    {look.items.slice(0, 3).map((it) => (
                      <li key={it.id}>
                        <button
                          type="button"
                          onClick={() => openDetail(safeActive, it.id)}
                          className="flex items-center gap-2 rounded-full border border-dashed border-ink/25 bg-card/80 py-1.5 ps-1.5 pe-4 text-start backdrop-blur transition-colors hover:border-ink"
                        >
                          <span className="grid size-8 place-items-center rounded-full bg-ink/6 text-[10px] text-muted">
                            {it.category.slice(0, 5)}
                          </span>
                          <span>
                            <span className="block text-[12px] leading-tight font-medium">{it.label}</span>
                            <span className="nums block text-[11px] text-muted">{formatYER(resolvePrice(it).amount)}</span>
                          </span>
                        </button>
                      </li>
                    ))}
                  </motion.ul>
                </AnimatePresence>
              )}

              <button
                type="button"
                onClick={() => openDetail(safeActive)}
                className="group flex shrink-0 items-center gap-3"
              >
                <span className="hidden text-[13px] font-medium sm:inline">تسوّق الإطلالة</span>
                <span className="grid size-14 place-items-center rounded-full bg-brand text-white shadow-[0_10px_30px_-10px_rgba(208,16,31,0.8)] transition-transform group-hover:scale-105">
                  <PlusIcon />
                </span>
              </button>
            </div>
          </motion.div>
        )}
      </AnimatePresence>

      {/* ── detail: close + receipt + hint ─────────────────────── */}
      <AnimatePresence>
        {open && (
          <>
            <motion.button
              key="close"
              type="button"
              onClick={closeDetail}
              aria-label="إغلاق تفاصيل الإطلالة"
              initial={{ opacity: 0, scale: 0.8 }}
              animate={{ opacity: 1, scale: 1, transition: { delay: dur(0.35) } }}
              exit={{ opacity: 0, scale: 0.8 }}
              className={`absolute z-[800] grid size-11 place-items-center rounded-full border border-dashed border-ink/40 bg-card/80 backdrop-blur hover:border-ink ${
                compact ? "left-4 top-4" : "top-6"
              }`}
              style={compact || !m ? undefined : { left: m.panelW + 48 }}
            >
              <CloseIcon />
            </motion.button>

            <motion.aside
              key="receipt"
              aria-label="قطع الإطلالة"
              initial={compact ? { y: "100%" } : { x: -40, opacity: 0 }}
              animate={
                compact
                  ? { y: 0, transition: { duration: dur(0.5), ease: EASE_IO, delay: dur(0.2) } }
                  : { x: 0, opacity: 1, transition: { duration: dur(0.55), ease: EASE_IO, delay: dur(0.25) } }
              }
              exit={compact ? { y: "100%", transition: { duration: dur(0.3) } } : { x: -40, opacity: 0, transition: { duration: dur(0.25) } }}
              className={
                compact
                  ? "absolute inset-x-0 bottom-0 z-[800] h-[46%] overflow-hidden rounded-t-3xl"
                  : "absolute top-5 bottom-8 left-6 z-[800] flex flex-col"
              }
              style={compact || !m ? undefined : { width: m.panelW }}
            >
              {compact && <div aria-hidden className="mx-auto mt-0 h-1 w-10 rounded-full bg-ink/20" />}
              <LookReceipt look={look} index={safeActive} selectedItem={selectedItem} onSelectItem={setSelectedItem} />
            </motion.aside>

            {!compact && (
              <motion.p
                key="hint"
                initial={{ opacity: 0 }}
                animate={{ opacity: 1, transition: { delay: dur(0.8) } }}
                exit={{ opacity: 0 }}
                className="pointer-events-none absolute bottom-5 z-[700] -translate-x-1/2 text-[12px] text-muted"
                style={m && stage ? { left: (24 + m.panelW + stage.w) / 2 } : undefined}
              >
                اضغط على رقم لعرض القطعة وسعرها
              </motion.p>
            )}
          </>
        )}
      </AnimatePresence>

      <DragCursor stageRef={stageRef} enabled={mode === "ring" && !open && !compact} dragging={dragging} />
    </section>
  );
}

/* ── small parts ───────────────────────────────────────────────── */

function ModeToggle({
  mode,
  onChange,
  vertical,
}: {
  mode: Mode;
  onChange: (m: Mode) => void;
  vertical: boolean;
}) {
  const opts: { key: Mode; label: string }[] = [
    { key: "ring", label: "دائري" },
    { key: "grid", label: "شبكة" },
  ];
  return (
    <div
      role="radiogroup"
      aria-label="طريقة العرض"
      className={`flex rounded-full border border-dashed border-ink/30 bg-card/80 p-1 backdrop-blur ${
        vertical ? "flex-col" : ""
      }`}
    >
      {opts.map((o) => (
        <button
          key={o.key}
          type="button"
          role="radio"
          aria-checked={mode === o.key}
          onClick={() => onChange(o.key)}
          className={`relative rounded-full text-[11px] font-medium transition-colors ${
            vertical ? "px-2 py-3 [writing-mode:vertical-rl]" : "px-3.5 py-1.5"
          } ${mode === o.key ? "text-on-ink" : "text-ink hover:text-brand"}`}
        >
          {mode === o.key && (
            <motion.span layoutId="mode-pill" className="absolute inset-0 rounded-full bg-ink" transition={{ type: "spring", stiffness: 400, damping: 32 }} />
          )}
          <span className="relative">{o.label}</span>
        </button>
      ))}
    </div>
  );
}

/** The circular "اسحب" cursor from the reference, for fine pointers only. */
function DragCursor({
  stageRef,
  enabled,
  dragging,
}: {
  stageRef: React.RefObject<HTMLDivElement | null>;
  enabled: boolean;
  dragging: boolean;
}) {
  const x = useMotionValue(-200);
  const y = useMotionValue(-200);
  const [inside, setInside] = useState(false);

  useEffect(() => {
    const el = stageRef.current;
    if (!el || !window.matchMedia("(pointer: fine)").matches) return;
    const move = (e: PointerEvent) => {
      const r = el.getBoundingClientRect();
      x.set(e.clientX - r.left);
      y.set(e.clientY - r.top);
    };
    const enter = () => setInside(true);
    const leave = () => setInside(false);
    el.addEventListener("pointermove", move);
    el.addEventListener("pointerenter", enter);
    el.addEventListener("pointerleave", leave);
    return () => {
      el.removeEventListener("pointermove", move);
      el.removeEventListener("pointerenter", enter);
      el.removeEventListener("pointerleave", leave);
    };
  }, [stageRef, x, y]);

  return (
    <motion.div
      aria-hidden
      className="pointer-events-none absolute top-0 left-0 z-[750] grid size-16 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full border border-dashed border-ink/50 bg-white/60 text-[11px] font-medium backdrop-blur-sm"
      style={{ x, y } as { x: MotionValue<number>; y: MotionValue<number> }}
      animate={{ opacity: enabled && inside ? 1 : 0, scale: dragging ? 0.8 : 1 }}
      transition={{ duration: 0.18 }}
    >
      <span className="flex items-center gap-1">
        <ArrowIcon width={12} height={12} className="rotate-180" />
        اسحب
        <ArrowIcon width={12} height={12} />
      </span>
    </motion.div>
  );
}
