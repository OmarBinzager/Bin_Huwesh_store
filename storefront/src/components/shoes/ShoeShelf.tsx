"use client";

import { useCallback, useEffect, useRef, useState, type CSSProperties, type KeyboardEvent, type PointerEvent } from "react";
import Link from "next/link";
import { useDemoBag } from "@/components/store/DemoBag";
import type { Shoe } from "@/content/shoes-types";
import { useCurrency } from "@/lib/currency";
import { useI18n } from "@/lib/i18n/context";
import { productHref } from "@/lib/product-url";
import { Camera } from "./camera";
import s from "./shoe-shelf.module.css";

/**
 * «أحذية تكمل إطلالتك» — lit shoe shelves.
 *
 *  1. Lights on: the room starts dark and each shoe is lit in turn (once,
 *     when the section first comes into view).
 *  2. Hover / focus: one spotlight glides to the shoe, the others dim and the
 *     name and price appear under its shelf.
 *  3. Open: the whole wall is zoomed by a camera transform until the chosen
 *     shoe is large on its own shelf (neighbours stay partly visible) and the
 *     detail panel slides in beside it. The big shoe IS the small one — same
 *     element, same image — so there is no swap and nothing can mismatch.
 *  4. Previous / next pan the camera along the wall; close reverses the zoom
 *     and gives focus back to the shoe on the wall.
 *
 * Data lives in src/content/shoes.json (admin: /admin/shoes); nothing in the
 * data knows about the motion.
 */
const LOOK_ID = "shoes";
/** Spring stiffness (rad/s) per move — roughly 0.8–0.9 s to settle. */
const OMEGA = { open: 6.4, pan: 7.2, close: 7.6 } as const;

type Props = { shoes: Shoe[] };

export default function ShoeShelf({ shoes }: Props) {
  const { locale } = useI18n();
  const { formatMoney } = useCurrency();
  const isEn = locale === "en";
  const bag = useDemoBag();
  const stageRef = useRef<HTMLDivElement>(null);
  const worldRef = useRef<HTMLDivElement>(null);
  const spotRef = useRef<HTMLSpanElement>(null);
  const panelRef = useRef<HTMLElement>(null);
  const headingRef = useRef<HTMLHeadingElement>(null);
  const cellRefs = useRef<(HTMLButtonElement | null)[]>([]);
  const picRefs = useRef<(HTMLImageElement | null)[]>([]);
  const camRef = useRef<Camera | null>(null);
  const tokenRef = useRef(0);
  const openRef = useRef(false);
  const currentRef = useRef(0);
  /** Shoe the camera actually shows (currentRef may already point at one still loading). */
  const shownRef = useRef(0);
  const reducedRef = useRef(false);
  const scrollBackRef = useRef<number | null>(null);
  /** Where focus goes once React has rendered the new state (the title remounts per shoe). */
  const focusNext = useRef<"title" | "cell" | null>(null);

  /** Detail panel open? (the wall is zoomed or zooming) */
  const [open, setOpen] = useState(false);
  /** Shoe shown in the panel — kept after closing so the panel slides out with its content. */
  const [current, setCurrent] = useState(0);
  /** Wall back at rest after a close (labels and hover come back). */
  const [settled, setSettled] = useState(true);
  const [colorOf, setColorOf] = useState<Record<string, number>>({});
  const [size, setSize] = useState<string | null>(null);
  const [needSize, setNeedSize] = useState(false);
  const [added, setAdded] = useState<{ id: string; label: string; size: string; image: string } | null>(null);
  const addedTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  const n = shoes.length;
  const shoe = shoes[Math.min(current, n - 1)];
  const colorIdx = (sh: Shoe) => Math.min(colorOf[sh.id] ?? 0, sh.colors.length - 1);
  const color = shoe.colors[colorIdx(shoe)];

  // ── camera ────────────────────────────────────────────────────────────────
  useEffect(() => {
    const world = worldRef.current;
    if (!world) return;
    const cam = new Camera(world, (atRest) => {
      if (atRest && !openRef.current) setSettled(true);
    });
    camRef.current = cam;
    const reduced = window.matchMedia("(prefers-reduced-motion: reduce)");
    const sync = () => (reducedRef.current = reduced.matches);
    sync();
    reduced.addEventListener("change", sync);
    // A hidden tab never shows a half-finished move: jump to the end.
    const onVis = () => document.hidden && cam.finish();
    document.addEventListener("visibilitychange", onVis);
    return () => {
      cam.stop();
      reduced.removeEventListener("change", sync);
      document.removeEventListener("visibilitychange", onVis);
    };
  }, []);

  /** Centre of shoe i's picture in world (= unzoomed stage) coordinates, and its width. */
  const measure = useCallback((i: number) => {
    const cell = cellRefs.current[i];
    const pic = picRefs.current[i];
    if (!cell || !pic) return null;
    const box = pic.parentElement as HTMLElement; // .pic wrapper, positioned inside the cell
    return {
      x: cell.offsetLeft + box.offsetLeft + box.offsetWidth / 2,
      y: cell.offsetTop + box.offsetTop + box.offsetHeight / 2,
      w: box.offsetWidth,
      h: box.offsetHeight,
    };
  }, []);

  /** Where the open shoe goes: the free part of the stage beside (desktop) or above (phone) the panel. */
  const detailTarget = useCallback((i: number) => {
    const stage = stageRef.current;
    const panel = panelRef.current;
    const m = measure(i);
    if (!stage || !panel || !m) return null;
    const W = stage.clientWidth;
    const H = stage.clientHeight;
    const side = panel.offsetHeight >= H * 0.9; // full-height side panel vs. sheet under the shoe
    let cx: number, cy: number, maxW: number, maxH: number;
    if (side) {
      const left = panel.offsetLeft < W / 2; // RTL: the panel sits on the left
      const freeW = W - panel.offsetWidth;
      cx = left ? panel.offsetWidth + freeW / 2 : freeW / 2;
      cy = H * 0.47;
      maxW = freeW * 0.68;
      maxH = H * 0.66;
    } else {
      const freeH = panel.offsetTop || 200;
      cx = W / 2;
      cy = freeH * 0.52;
      maxW = W * 0.9;
      maxH = freeH * 0.86;
    }
    const scale = Math.max(1, Math.min(maxW / m.w, maxH / m.h));
    return { p: { x: cx, y: cy }, scale, at: { x: m.x, y: m.y } };
  }, [measure]);

  // ── one gliding spotlight (in the world layer, so it zooms with the wall) ──
  const moveSpot = useCallback((i: number) => {
    const spot = spotRef.current;
    const cell = cellRefs.current[i];
    if (!spot || !cell) return;
    spot.style.transform = `translate3d(${cell.offsetLeft}px, ${cell.offsetTop}px, 0)`;
    spot.style.width = `${cell.offsetWidth}px`;
    spot.style.height = `${cell.offsetHeight}px`;
    spot.dataset.on = "1";
    spot.dataset.i = String(i);
  }, []);
  const hideSpot = () => {
    if (openRef.current || !spotRef.current) return;
    delete spotRef.current.dataset.on;
  };

  /** The shoe image must be decoded before the camera moves (no blank or half-loaded zoom). */
  const ready = async (i: number) => {
    const img = picRefs.current[i];
    if (!img) return;
    if (img.loading === "lazy") img.loading = "eager";
    try {
      await img.decode();
    } catch {
      /* broken image — still move, the alt text is there */
    }
  };

  /** Keep the composition on screen: bring the stage into view if part of it is hidden. */
  const frameStage = () => {
    const stage = stageRef.current;
    if (!stage) return;
    const r = stage.getBoundingClientRect();
    const header = 72; // sticky store header
    const room = window.innerHeight - header;
    // The open composition (shoe + panel) needs the top `need` px of the stage.
    const need = Math.min(r.height, room);
    if (r.top >= header - 2 && r.top + need <= window.innerHeight + 2) return;
    if (scrollBackRef.current === null) scrollBackRef.current = window.scrollY;
    const top = r.top + window.scrollY - header - Math.max(0, (room - r.height) / 2);
    window.scrollTo({ top, behavior: reducedRef.current ? "auto" : "smooth" });
  };

  const openShoe = async (i: number) => {
    const cam = camRef.current;
    if (!cam) return;
    const token = ++tokenRef.current;
    await ready(i);
    if (token !== tokenRef.current) return;
    const t = detailTarget(i);
    if (!t) return;
    openRef.current = true;
    currentRef.current = i;
    shownRef.current = i;
    setCurrent(i);
    setOpen(true);
    setSettled(false);
    setSize(null);
    setNeedSize(false);
    setAdded(null);
    moveSpot(i);
    cam.setSubject(t.at);
    cam.aim(t.p, t.scale, OMEGA.open, reducedRef.current);
    frameStage();
    focusNext.current = "title";
  };

  const go = async (dir: 1 | -1) => {
    const cam = camRef.current;
    if (!cam || !openRef.current) return;
    // Rapid presses step from the newest target, not from what is on screen.
    const i = (currentRef.current + dir + n) % n;
    currentRef.current = i;
    const token = ++tokenRef.current;
    await ready(i);
    if (token !== tokenRef.current || !openRef.current) return;
    const t = detailTarget(i);
    if (!t) return;
    // The panel body is keyed per shoe: focus inside it would be dropped on the new shoe.
    if (document.activeElement?.closest(`.${s.panelBody}`) || document.activeElement === document.body) focusNext.current = "title";
    shownRef.current = i;
    setCurrent(i);
    setSize(null);
    setNeedSize(false);
    setAdded(null);
    moveSpot(i);
    cam.setSubject(t.at);
    cam.aim(t.p, t.scale, OMEGA.pan, reducedRef.current);
  };

  const close = useCallback(() => {
    const cam = camRef.current;
    if (!cam || !openRef.current) return;
    tokenRef.current++;
    openRef.current = false;
    currentRef.current = shownRef.current; // a still-loading next/previous is dropped
    setOpen(false);
    cam.rest(OMEGA.close, reducedRef.current);
    if (scrollBackRef.current !== null) {
      window.scrollTo({ top: scrollBackRef.current, behavior: reducedRef.current ? "auto" : "smooth" });
      scrollBackRef.current = null;
    }
    // Focus returns to the shoe the visitor ended on, once the wall is no longer inert.
    focusNext.current = "cell";
  }, []);

  useEffect(() => {
    const want = focusNext.current;
    if (want) {
      focusNext.current = null;
      if (want === "title" && open) headingRef.current?.focus({ preventScroll: true });
      if (want === "cell" && !open) cellRefs.current[currentRef.current]?.focus({ preventScroll: true });
    }
    if (open) {
      const raf = requestAnimationFrame(() => frameStage());
      return () => cancelAnimationFrame(raf);
    }
  }, [open, current]);

  // Resize while open: re-aim instantly (no drifting animation on rotate).
  useEffect(() => {
    const stage = stageRef.current;
    if (!stage) return;
    const ro = new ResizeObserver(() => {
      const cam = camRef.current;
      if (!cam) return;
      if (openRef.current) {
        const t = detailTarget(currentRef.current);
        if (t) {
          cam.setSubject(t.at);
          cam.aim(t.p, t.scale, OMEGA.pan, true);
        }
      } else {
        cam.finish();
      }
      if (spotRef.current?.dataset.on) moveSpot(Number(spotRef.current.dataset.i ?? 0));
    });
    ro.observe(stage);
    return () => ro.disconnect();
  }, [detailTarget, moveSpot]);

  // ── lights-on intro (once, first time the section is seen) ────────────────
  useEffect(() => {
    const stage = stageRef.current;
    if (!stage || reducedRef.current) return;
    stage.dataset.intro = "wait";
    let timer: ReturnType<typeof setTimeout> | undefined;
    const io = new IntersectionObserver(
      (entries) => {
        if (!entries.some((e) => e.isIntersecting)) return;
        io.disconnect();
        requestAnimationFrame(() =>
          requestAnimationFrame(() => {
            stage.dataset.intro = "run";
            timer = setTimeout(() => (stage.dataset.intro = "done"), 2600);
          }),
        );
      },
      { threshold: 0.3 },
    );
    io.observe(stage);
    return () => {
      io.disconnect();
      if (timer) clearTimeout(timer);
      stage.dataset.intro = "done";
    };
  }, []);

  // ── input ─────────────────────────────────────────────────────────────────
  const onKeyDown = (e: KeyboardEvent<HTMLDivElement>) => {
    if (!openRef.current) return;
    if (e.key === "Escape") {
      e.preventDefault();
      close();
      return;
    }
    if ((e.target as HTMLElement).closest("[role=radiogroup]")) return;
    // RTL: left is "forward".
    if (e.key === "ArrowLeft") {
      e.preventDefault();
      void go(1);
    } else if (e.key === "ArrowRight") {
      e.preventDefault();
      void go(-1);
    }
  };

  // Horizontal swipe on the open stage (phones): previous / next. Vertical scroll is untouched.
  const swipe = useRef<{ x: number; y: number; id: number } | null>(null);
  const onPointerDown = (e: PointerEvent<HTMLDivElement>) => {
    if (!openRef.current || e.pointerType === "mouse") return;
    if ((e.target as HTMLElement).closest("button, [role=radiogroup]")) return;
    swipe.current = { x: e.clientX, y: e.clientY, id: e.pointerId };
  };
  const onPointerUp = (e: PointerEvent<HTMLDivElement>) => {
    const st = swipe.current;
    swipe.current = null;
    if (!st || st.id !== e.pointerId) return;
    const dx = e.clientX - st.x;
    const dy = e.clientY - st.y;
    if (Math.abs(dx) > 48 && Math.abs(dx) > Math.abs(dy) * 1.4) void go(dx < 0 ? 1 : -1);
  };

  // ── colour (photographed variants only) ───────────────────────────────────
  const pickColor = async (j: number) => {
    const sh = shoes[currentRef.current];
    const url = sh.colors[j]?.image;
    if (!url) return;
    const pre = new Image();
    pre.src = url;
    try {
      await pre.decode();
    } catch {
      /* fall through — the img element shows its alt */
    }
    setColorOf((m) => ({ ...m, [sh.id]: j }));
  };

  // ── bag ───────────────────────────────────────────────────────────────────
  const addToBag = () => {
    if (!size) {
      setNeedSize(true);
      return;
    }
    const label = shoe.colors.length > 1 ? `${shoe.name} — ${color.name}` : shoe.name;
    bag.add([{ lookId: LOOK_ID, itemId: `${shoe.id}:${color.id}`, productId: (shoe as { productId?: string | number | null }).productId ?? null, label, size, price: shoe.price, image: color.image }]);
    setAdded({ id: shoe.id, label, size, image: color.image });
    if (addedTimer.current) clearTimeout(addedTimer.current);
    addedTimer.current = setTimeout(() => setAdded(null), 3200);
  };
  useEffect(() => () => {
    if (addedTimer.current) clearTimeout(addedTimer.current);
  }, []);

  const two = (k: number) => String(k).padStart(2, "0");
  const justAdded = added?.id === shoe.id;

  return (
    <div
      ref={stageRef}
      className={s.stage}
      data-open={open ? "" : undefined}
      data-settled={settled ? "" : undefined}
      onKeyDown={onKeyDown}
      onPointerDown={onPointerDown}
      onPointerUp={onPointerUp}
      onPointerCancel={() => (swipe.current = null)}
    >
      {/* Room: wall, curved light, floor glow. Purely decorative, never catches clicks. */}
      <div className={s.room} aria-hidden />
      <div className={s.arc} aria-hidden />
      <div className={s.floorGlow} aria-hidden />
      <div className={s.dusk} aria-hidden />

      <div ref={worldRef} className={s.world} inert={open || undefined} onPointerLeave={hideSpot}>
        <span ref={spotRef} className={s.spot} aria-hidden>
          <span className={s.cone} />
          <span className={s.pool} />
        </span>
        <ul className={s.grid} aria-label={isEn ? "Shoes" : "الأحذية"}>
          {shoes.map((sh, i) => {
            const c = sh.colors[colorIdx(sh)];
            return (
              <li
                key={sh.id}
                className={s.item}
                // Lights-on order: column by column from the right (two rows of five), or one by one on phones.
                style={{ "--dl": `${((i % 5) * 2 + Math.floor(i / 5)) * 105}ms`, "--ds": `${i * 90}ms` } as CSSProperties}
              >
                <button
                  ref={(el) => {
                    cellRefs.current[i] = el;
                  }}
                  type="button"
                  className={s.cell}
                  data-current={open && i === current ? "" : undefined}
                  aria-label={`${sh.name} — ${formatMoney(sh.price)}. ${isEn ? "View details" : "عرض التفاصيل"}`}
                  onPointerEnter={(e) => e.pointerType === "mouse" && moveSpot(i)}
                  onFocus={() => moveSpot(i)}
                  onBlur={hideSpot}
                  onClick={() => void openShoe(i)}
                >
                  <span className={s.flash} aria-hidden />
                  <span className={s.pic}>
                    {/* eslint-disable-next-line @next/next/no-img-element -- transparent cut-outs, exact size control for the zoom */}
                    <img
                      ref={(el) => {
                        picRefs.current[i] = el;
                      }}
                      src={c.image}
                      alt={sh.alt}
                      width={1400}
                      height={800}
                      loading="lazy"
                      decoding="async"
                      draggable={false}
                    />
                    <span className={s.contact} aria-hidden />
                  </span>
                  <span className={s.shelf} aria-hidden />
                  <span className={s.label} aria-hidden>
                    <span className={s.labelName}>{sh.name}</span>
                    <span className={s.labelPrice} dir="ltr">
                      {formatMoney(sh.price)}
                    </span>
                  </span>
                </button>
              </li>
            );
          })}
        </ul>
      </div>

      <p className={s.hint} aria-hidden={open}>
        <span className={s.hintHover}>
          {isEn ? "Hover to illuminate a shoe, click for a closer look" : "مرّر المؤشر لإضاءة الحذاء، واضغط لرؤيته عن قرب"}
        </span>
        <span className={s.hintTouch}>
          {isEn ? "Tap any shoe for a closer look" : "اضغط على أي حذاء لرؤيته عن قرب"}
        </span>
      </p>

      <aside ref={panelRef} className={s.panel} aria-label={isEn ? "Shoe details" : "تفاصيل الحذاء"} inert={!open || undefined} aria-hidden={!open}>
        <div className={s.panelHead}>
          <span className={s.count} dir="ltr">
            {two(current + 1)} / {two(n)}
          </span>
          <button type="button" className={s.close} onClick={close} aria-label={isEn ? "Close details and return to shelves" : "إغلاق التفاصيل والعودة إلى الرفوف"}>
            <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden>
              <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" />
            </svg>
          </button>
        </div>

        <p className={s.srOnly} aria-live="polite">
          {open ? `${shoe.name} — ${formatMoney(shoe.price)} (${current + 1} / ${n})` : ""}
        </p>
        <div key={shoe.id} className={s.panelBody}>
          <h3 ref={headingRef} tabIndex={-1} className={s.name}>
            {(shoe as { productId?: string | number | null }).productId ? (
              <Link
                href={productHref((shoe as { productId?: string | number | null }).productId!, shoe.name)}
                className="hover:underline underline-offset-4"
              >
                {shoe.name}
              </Link>
            ) : (
              shoe.name
            )}
          </h3>
          {shoe.description && <p className={s.desc}>{shoe.description}</p>}
          <p className={s.price} dir="ltr">
            {formatMoney(shoe.price)}
          </p>

          {shoe.colors.length > 1 ? (
            <div className={s.block}>
              <p className={s.blockLabel}>
                {isEn ? "Color" : "اللون"} · <span>{color.name}</span>
              </p>
              <div role="radiogroup" aria-label={isEn ? "Color" : "اللون"} className={s.swatches}>
                {shoe.colors.map((c, j) => (
                  <button
                    key={c.id}
                    type="button"
                    role="radio"
                    aria-checked={j === colorIdx(shoe)}
                    aria-label={c.name}
                    tabIndex={j === colorIdx(shoe) ? 0 : -1}
                    className={s.swatch}
                    style={{ "--sw": c.swatch } as CSSProperties}
                    onClick={() => void pickColor(j)}
                  />
                ))}
              </div>
            </div>
          ) : (
            <p className={s.blockLabel}>
              {isEn ? "Color" : "اللون"} · <span>{color.name}</span>
            </p>
          )}

          <div className={s.block}>
            <p className={s.blockLabel} id="shoe-size-label">
              {isEn ? "Size (EU)" : "المقاس (أوروبي)"}
              {needSize && !size && <span className={s.warn}> — {isEn ? "Select size first" : "اختر مقاسك أولًا"}</span>}
            </p>
            <SizeGroup sizes={shoe.sizes} value={size} onChange={(z) => (setSize(z), setNeedSize(false))} warn={needSize && !size} />
          </div>

          <button
            type="button"
            className={s.add}
            data-ready={size ? "" : undefined}
            data-done={justAdded ? "" : undefined}
            aria-disabled={!size}
            onClick={addToBag}
          >
            {justAdded ? (isEn ? "Added to Bag ✓" : "أُضيف إلى السلة ✓") : size ? (
              <>
                {isEn ? "Add to Bag" : "أضف إلى السلة"} · <span dir="ltr">{formatMoney(shoe.price)}</span>
              </>
            ) : (
              isEn ? "Select Size to Add" : "اختر المقاس للإضافة"
            )}
          </button>

          <div aria-live="polite">
            {justAdded && added && (
              <p className={s.added}>
                {/* eslint-disable-next-line @next/next/no-img-element -- tiny thumbnail of the added shoe */}
                <img src={added.image} alt="" width={56} height={32} />
                <span>
                  {isEn ? (
                    <>Added &ldquo;{added.label}&rdquo; size <span dir="ltr">{added.size}</span> to your bag.</>
                  ) : (
                    <>أُضيف «{added.label}» مقاس <span dir="ltr">{added.size}</span> إلى السلة التجريبية.</>
                  )}
                </span>
              </p>
            )}
          </div>

        </div>

        <div className={s.panelNav}>
          <button type="button" className={s.navBtn} onClick={() => void go(-1)} aria-label={isEn ? "Previous shoe" : "الحذاء السابق"}>
            <span aria-hidden>→</span> {isEn ? "Previous" : "السابق"}
          </button>
          <button type="button" className={s.navBtn} onClick={() => void go(1)} aria-label={isEn ? "Next shoe" : "الحذاء التالي"}>
            {isEn ? "Next" : "التالي"} <span aria-hidden>←</span>
          </button>
        </div>
      </aside>
    </div>
  );
}

/** Radio group of the sizes this shoe actually lists (roving tabindex, arrow keys). */
function SizeGroup({ sizes, value, onChange, warn }: { sizes: string[]; value: string | null; onChange: (z: string) => void; warn: boolean }) {
  const refs = useRef<(HTMLButtonElement | null)[]>([]);
  const active = value ? sizes.indexOf(value) : 0;
  const onKey = (e: KeyboardEvent<HTMLDivElement>) => {
    const k = e.key;
    if (!["ArrowLeft", "ArrowRight", "ArrowUp", "ArrowDown", "Home", "End"].includes(k)) return;
    e.preventDefault();
    const cur = refs.current.findIndex((b) => b === document.activeElement);
    const from = cur < 0 ? active : cur;
    // RTL: left = next.
    const next =
      k === "Home" ? 0 : k === "End" ? sizes.length - 1 : k === "ArrowLeft" || k === "ArrowDown" ? (from + 1) % sizes.length : (from - 1 + sizes.length) % sizes.length;
    refs.current[next]?.focus();
    onChange(sizes[next]);
  };
  return (
    <div role="radiogroup" aria-labelledby="shoe-size-label" aria-invalid={warn || undefined} className={s.sizes} data-warn={warn ? "" : undefined} onKeyDown={onKey}>
      {sizes.map((z, j) => (
        <button
          key={z}
          ref={(el) => {
            refs.current[j] = el;
          }}
          type="button"
          role="radio"
          aria-checked={value === z}
          tabIndex={j === active ? 0 : -1}
          className={s.size}
          onClick={() => onChange(z)}
        >
          {z}
        </button>
      ))}
    </div>
  );
}
