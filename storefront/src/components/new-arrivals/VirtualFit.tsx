"use client";

import { useEffect, useLayoutEffect, useMemo, useRef, useState } from "react";
import { useDemoBag } from "@/components/store/DemoBag";
import { categoriesInUse, visibleItems } from "@/content/new-arrivals";
import type { NaItem, NewArrivalsFile } from "@/content/new-arrivals-types";
import { useCurrency } from "@/lib/currency";
import { useI18n } from "@/lib/i18n/context";
import { approach, cardWidth, isNarrow, mod, offset, pose, slotPlan, xsTable } from "./arc";
import s from "./virtual-fit.module.css";

/**
 * «وصل حديثًا» — a VirtualFit-style try-on scene: frosted garment cards on a
 * 3D arc around a model; when the arc settles, a scan line reveals the model
 * wearing the chosen garment. These are prepared photos swapped with an
 * animation — not an AI try-on or a body measurement. Prices are DEMO data.
 *
 * Two engines, both imperative (no React render per frame):
 *  · model scan — mounted once; preloads the next figure, then sweeps it in;
 *    a newer choice cancels the older sweep without the model ever blanking.
 *  · arc — rebuilt when the tab filter changes; cards, drag/flick, tilt, bob,
 *    custom cursor. Paused off-screen and in hidden tabs; time-based so it
 *    runs the same at 60/120/144 Hz.
 */

const DIR = -1; // RTL: the next garment sits on the left
const LOOK_ID = "new-arrivals";

type ModelApi = { tryOn: (url: string) => void; preload: (urls: string[]) => void };
type ArcApi = { step: (d: number) => void; goToItem: (k: number) => void };

export default function VirtualFit({ data }: { data: NewArrivalsFile }) {
  const { locale } = useI18n();
  const { formatMoney } = useCurrency();
  const isEn = locale === "en";
  const all = useMemo(() => visibleItems(data), [data]);
  const cats = useMemo(() => categoriesInUse(data), [data]);
  const [cat, setCat] = useState("all");
  const items = useMemo(() => {
    const f = cat === "all" ? all : all.filter((i) => i.categoryId === cat);
    return f.length ? f : all;
  }, [all, cat]);
  const n = items.length;
  const { slots } = slotPlan(n);

  const [sel, setSel] = useState(0);
  const [colorId, setColorId] = useState<string | undefined>(items[0]?.colors[0]?.id);
  const [size, setSize] = useState<string | null>(null);
  const [needSize, setNeedSize] = useState(false);
  const [bagOpen, setBagOpen] = useState(false);
  const bag = useDemoBag();

  const item: NaItem | undefined = items[Math.min(sel, n - 1)];
  const color = item?.colors.find((c) => c.id === colorId) ?? item?.colors[0];

  const sceneRef = useRef<HTMLDivElement>(null);
  const roomRef = useRef<HTMLDivElement>(null);
  const ghostRef = useRef<HTMLDivElement>(null);
  const panelRef = useRef<HTMLDivElement>(null);
  const curRef = useRef<HTMLDivElement>(null);
  const mCurRef = useRef<HTMLImageElement>(null);
  const mNextRef = useRef<HTMLImageElement>(null);
  const gridRef = useRef<HTMLDivElement>(null);
  const scanRef = useRef<HTMLDivElement>(null);
  const cardRefs = useRef<(HTMLDivElement | null)[]>([]);
  const tabsRef = useRef<HTMLDivElement>(null);
  const pillRef = useRef<HTMLSpanElement>(null);
  const modelApi = useRef<ModelApi>({ tryOn: () => {}, preload: () => {} });
  const arcApi = useRef<ArcApi>({ step: () => {}, goToItem: () => {} });
  const onSelectRef = useRef<(k: number) => void>(() => {});
  const itemsRef = useRef(items);

  useEffect(() => {
    itemsRef.current = items;
    onSelectRef.current = (k: number) => {
      const it = itemsRef.current[k];
      setSel(k);
      setColorId(it?.colors[0]?.id);
      setSize(null);
      setNeedSize(false);
    };
  });

  /* ---------------- model scan engine (mounted once) ---------------- */
  useEffect(() => {
    const mCur = mCurRef.current!, mNext = mNextRef.current!, grid = gridRef.current!, scan = scanRef.current!;
    const reduced = window.matchMedia("(prefers-reduced-motion: reduce)");
    let token = 0;
    let anims: Animation[] = [];
    let scanning = false;
    let shown = "";
    let disposed = false;
    const decoded = new Map<string, Promise<void>>();
    const load = (url: string) => {
      let p = decoded.get(url);
      if (!p) {
        const im = new Image();
        im.src = url;
        p = im.decode().catch(() => {});
        decoded.set(url, p);
      }
      return p;
    };
    const stop = () => {
      anims.forEach((a) => a.cancel());
      anims = [];
      scanning = false;
    };
    modelApi.current.preload = (urls) => urls.forEach(load);
    modelApi.current.tryOn = async (url: string) => {
      if (url === shown && !scanning) return;
      const my = ++token;
      await load(url); // never start a sweep before the figure is ready
      if (disposed || my !== token) return;
      if (scanning) {
        // a newer choice: keep what was being revealed as the base, no blank frame
        mCur.src = mNext.src;
        await mCur.decode().catch(() => {});
        mCur.style.opacity = "1";
        if (my !== token) return;
      }
      stop();
      shown = url;
      if (reduced.matches) {
        mCur.src = url;
        await mCur.decode().catch(() => {});
        mCur.style.opacity = "1";
        return;
      }
      mNext.src = url;
      await mNext.decode().catch(() => {});
      if (my !== token) return;
      scanning = true;
      const ease = "cubic-bezier(.65,0,.35,1)";
      const reveal = mNext.animate([{ clipPath: "inset(0 0 100% 0)" }, { clipPath: "inset(0 0 0% 0)" }], { duration: 950, easing: ease, fill: "forwards" });
      anims = [
        reveal,
        grid.animate([{ clipPath: "inset(0 0 100% 0)" }, { clipPath: "inset(0 0 0 0)", offset: 0.5 }, { clipPath: "inset(100% 0 0 0)" }], { duration: 1092, easing: ease, fill: "forwards" }),
        scan.animate(
          [
            { top: "0%", transform: "scaleX(.2)", opacity: 0 },
            { top: "6%", transform: "scaleX(1)", opacity: 1, offset: 0.08 },
            { top: "94%", transform: "scaleX(1)", opacity: 1, offset: 0.92 },
            { top: "100%", transform: "scaleX(.2)", opacity: 0 },
          ],
          { duration: 950, easing: ease, fill: "forwards" },
        ),
        mNext.animate([{ transform: "scale(1.012)" }, { transform: "scale(1)" }], { duration: 1250, easing: "ease-out" }),
      ];
      reveal.finished
        .then(async () => {
          if (my !== token) return;
          mCur.src = url;
          await mCur.decode().catch(() => {});
          if (my !== token) return;
          mCur.style.opacity = "1";
          stop();
        })
        .catch(() => {});
    };
    return () => {
      disposed = true;
      stop();
      modelApi.current = { tryOn: () => {}, preload: () => {} };
    };
  }, []);

  /* ---------------- arc engine (per filtered list) ---------------- */
  const itemsKey = items.map((i) => i.id).join("|");
  useEffect(() => {
    const scene = sceneRef.current!, room = roomRef.current!, ghost = ghostRef.current!, panel = panelRef.current!, cur = curRef.current!;
    const list = itemsRef.current;
    const count = list.length;
    if (!count) return;
    const { slots: L, loop: looped } = slotPlan(count);
    const cards = cardRefs.current.slice(0, L);
    const letters = Array.from(ghost.querySelectorAll<HTMLElement>("span"));
    const reduced = window.matchMedia("(prefers-reduced-motion: reduce)");
    const seeds = cards.map((_, i) => i * 1.7 + 0.4);

    let W = 1, H = 1, xs = xsTable(1);
    let pos = 0, target = 0;
    let intro = reduced.matches ? 1 : 0;
    let t = 0, last = 0, raf = 0, visible = false, disposed = false;
    let sx = 0, sy = 0, tsx = 0, tsy = 0;
    let shownItem = -1;
    // pointer
    let pressed = false, dragging = false, moved = false, p0 = 0, downX = 0, downY = 0, lastX = 0, lastT = 0, flick = 0, downSlot = -1;
    // cursor
    let cx = 0, cy = 0, tcx = 0, tcy = 0, curMode = "";
    const fine = window.matchMedia("(pointer: fine)");

    const clampTarget = (v: number) => (looped ? v : Math.max(0, Math.min(count - 1, v)));
    const ie = () => 1 - Math.pow(1 - Math.min(1, intro), 3);

    const layout = () => {
      const r = scene.getBoundingClientRect();
      W = Math.max(1, r.width);
      H = Math.max(1, r.height);
      xs = xsTable(W);
      const narrow = isNarrow(W);
      const ph = panel.offsetHeight;
      const top = narrow ? 58 : 74;
      const modelBottom = narrow ? Math.max(0, ph + 12 - 22) : 0;
      const mh = Math.min(H - top - modelBottom, W * 1.2);
      const arcY = narrow ? (top + (H - ph - 12)) / 2 : H * 0.47;
      const ghostSize = Math.min(W * 0.1, mh * 0.26);
      const modelTop = H - modelBottom - mh;
      const st = scene.style;
      st.setProperty("--cw", `${cardWidth(W, H)}px`);
      st.setProperty("--mh", `${mh}px`);
      st.setProperty("--model-bottom", `${modelBottom}px`);
      st.setProperty("--arc-y", `${arcY}px`);
      st.setProperty("--panel-h", `${ph}px`);
      st.setProperty("--ghost-size", `${ghostSize}px`);
      st.setProperty("--ghost-top", `${Math.max(top - 8, modelTop - mh * 0.05)}px`);
      kick();
    };

    const settle = () => {
      if (dragging || pressed || Math.abs(target - pos) >= 0.02) return;
      const slot = looped ? mod(Math.round(target), L) : Math.round(target);
      const k = slot % count;
      if (k === shownItem) return;
      shownItem = k;
      onSelectRef.current(k);
      const c = list[k]?.colors[0];
      if (c) modelApi.current.tryOn(c.model);
    };

    const frame = (now: number) => {
      raf = 0;
      const dt = last ? Math.min(0.1, (now - last) / 1000) : 1 / 60; // time-based down to 10 fps
      last = now;
      const still = reduced.matches;
      if (intro < 1) intro = still ? 1 : Math.min(1, intro + 0.012 * dt * 60);
      if (!dragging) pos = still ? target : approach(pos, target, 0.11, dt);
      if (Math.abs(target - pos) < 0.0005) pos = target;
      sx = approach(sx, tsx, 0.06, dt);
      sy = approach(sy, tsy, 0.06, dt);
      if (!still) t += dt;
      const e = ie();
      const h = H;
      for (let i = 0; i < cards.length; i++) {
        const el = cards[i];
        if (!el) continue;
        const u = offset(i, pos, L, looped);
        const p = pose(u, { dir: DIR, xs, h, ie: e, bobT: still ? 0 : t, seed: seeds[i], sx: still ? 0 : sx, sy: still ? 0 : sy });
        el.style.transform = `translate(${p.x.toFixed(2)}px, ${p.y.toFixed(2)}px) perspective(1300px) translateZ(${p.z.toFixed(1)}px) rotateY(${p.ry.toFixed(2)}deg) rotateX(${p.rx.toFixed(2)}deg) scale(${p.scale.toFixed(4)})`;
        el.style.opacity = p.opacity.toFixed(3);
        el.style.zIndex = String(p.zIndex);
        el.style.visibility = p.hidden ? "hidden" : "visible";
        el.style.filter = p.blur > 0.05 ? `blur(${p.blur.toFixed(2)}px)` : "";
      }
      room.style.transform = `translate3d(${(-sx * 22).toFixed(2)}px, ${(-sy * 14).toFixed(2)}px, 0) scale(${(1.08 - 0.03 * e).toFixed(4)})`;
      ghost.style.transform = `translate(calc(-50% - ${(sx * 40).toFixed(2)}px), ${(-sy * 16).toFixed(2)}px)`;
      letters.forEach((l, i) => (l.style.transform = `translateY(${((1 - e) * (60 + 30 * i)).toFixed(1)}px)`));
      // cursor
      cx = approach(cx, tcx, 0.22, dt);
      cy = approach(cy, tcy, 0.22, dt);
      cur.style.transform = `translate(${cx.toFixed(1)}px, ${cy.toFixed(1)}px)`;
      settle();
      const busy = !still || intro < 1 || dragging || Math.abs(target - pos) > 0 || Math.abs(tcx - cx) > 0.5 || Math.abs(tcy - cy) > 0.5;
      if (busy && visible && !document.hidden && !disposed) raf = requestAnimationFrame(frame);
      else last = 0;
    };
    function kick() {
      if (!raf && visible && !document.hidden && !disposed) raf = requestAnimationFrame(frame);
    }

    const step = (d: number) => {
      if (count < 2) return;
      target = clampTarget(Math.round(target) + d);
      kick();
    };
    arcApi.current.step = step;
    arcApi.current.goToItem = (k: number) => {
      // nearest slot that shows item k
      let best = Math.round(target), bestD = Infinity;
      for (let i = 0; i < L; i++) {
        if (i % count !== k) continue;
        const d = offset(i, Math.round(target), L, looped);
        if (Math.abs(d) < Math.abs(bestD)) { bestD = d; best = Math.round(target) + d; }
      }
      target = clampTarget(best);
      kick();
    };

    // ----- pointer: drag / click a card / tilt / cursor
    const isUI = (el: EventTarget | null) => el instanceof Element && !!el.closest("[data-vf-ui]");
    const slotOf = (el: EventTarget | null) => {
      const c = el instanceof Element ? (el.closest("[data-slot]") as HTMLElement | null) : null;
      return c ? Number(c.dataset.slot) : -1;
    };
    const setMode = (m: string) => {
      if (m === curMode) return;
      curMode = m;
      cur.classList.toggle(s.curTry, m === "try");
      cur.classList.toggle(s.curDrag, m === "drag");
      cur.classList.toggle(s.curBtn, m === "btn");
    };
    const onMove = (e: PointerEvent) => {
      const r = scene.getBoundingClientRect();
      const lx = e.clientX - r.left, ly = e.clientY - r.top;
      tsx = Math.max(-0.5, Math.min(0.5, lx / r.width - 0.5));
      tsy = Math.max(-0.5, Math.min(0.5, ly / r.height - 0.5));
      if (e.pointerType === "mouse" && fine.matches) {
        if (!cur.classList.contains(s.curOn)) { cx = tcx = lx; cy = tcy = ly; cur.classList.add(s.curOn); }
        tcx = lx;
        tcy = ly;
        const el = e.target as Element | null;
        if (dragging) setMode("drag");
        else if (el?.closest?.("button, a, [role=radio]")) setMode("btn");
        else if (isUI(el)) setMode("");
        else if (slotOf(el) >= 0) setMode("try");
        else setMode("drag");
      }
      if (pressed) {
        const dx = e.clientX - downX;
        if (!moved && Math.hypot(dx, e.clientY - downY) >= 6) {
          moved = true;
          dragging = count > 1;
          if (dragging) try { scene.setPointerCapture(e.pointerId); } catch { /* gone */ }
        }
        if (dragging) {
          let p = p0 - (DIR * dx) / xs[1];
          if (!looped) p = p < 0 ? p * 0.35 : p > count - 1 ? count - 1 + (p - count + 1) * 0.35 : p;
          pos = p;
          const dts = Math.max(1, e.timeStamp - lastT);
          flick = flick * 0.6 + ((e.clientX - lastX) / dts) * 0.4;
          lastX = e.clientX;
          lastT = e.timeStamp;
        }
      }
      kick();
    };
    const onDown = (e: PointerEvent) => {
      if (e.button !== 0 || isUI(e.target)) return;
      pressed = true;
      moved = false;
      dragging = false;
      p0 = pos;
      downX = lastX = e.clientX;
      downY = e.clientY;
      lastT = e.timeStamp;
      flick = 0;
      downSlot = slotOf(e.target);
      cur.classList.add(s.curDown);
    };
    const onUp = (e: PointerEvent) => {
      cur.classList.remove(s.curDown);
      if (!pressed) return;
      pressed = false;
      if (dragging) {
        dragging = false;
        const throwBy = Math.max(-4, Math.min(4, -DIR * flick * 3.2));
        target = clampTarget(Math.round(pos + throwBy));
      } else if (e.type === "pointerup" && !moved && downSlot >= 0) {
        // click a card: bring it to the centre (it is then tried on)
        target = clampTarget(Math.round(pos) + Math.round(offset(downSlot, pos, L, looped)));
      } else target = clampTarget(Math.round(pos));
      kick();
    };
    const onLeave = () => {
      tsx = tsy = 0;
      cur.classList.remove(s.curOn);
      setMode("");
      kick();
    };
    let wheelAcc = 0, wheelAt = 0;
    const onWheel = (e: WheelEvent) => {
      // horizontal gestures only — vertical wheel always scrolls the page
      if (Math.abs(e.deltaX) <= Math.abs(e.deltaY)) return;
      e.preventDefault();
      wheelAcc += e.deltaX;
      if (Math.abs(wheelAcc) > 60 && e.timeStamp - wheelAt > 260) {
        // pushing content toward the start side brings the next garment in
        step(Math.sign(wheelAcc) * DIR);
        wheelAcc = 0;
        wheelAt = e.timeStamp;
      }
    };
    const onKey = (e: KeyboardEvent) => {
      if (e.target instanceof HTMLElement && e.target.closest("[role=radiogroup]")) return;
      if (e.key === "ArrowRight") step(DIR);
      else if (e.key === "ArrowLeft") step(-DIR);
      else return;
      e.preventDefault();
    };
    const sheen = (e: Event) => {
      const el = (e.currentTarget as HTMLElement).querySelector(`.${s.sheen}`);
      if (!el || reduced.matches) return;
      el.classList.remove(s.sheenRun);
      void (el as HTMLElement).offsetWidth;
      el.classList.add(s.sheenRun);
    };

    scene.addEventListener("pointermove", onMove);
    scene.addEventListener("pointerdown", onDown);
    scene.addEventListener("pointerup", onUp);
    scene.addEventListener("pointercancel", onUp);
    scene.addEventListener("pointerleave", onLeave);
    scene.addEventListener("wheel", onWheel, { passive: false });
    scene.addEventListener("keydown", onKey);
    cards.forEach((c) => c?.addEventListener("pointerenter", sheen));
    const onVis = () => kick();
    document.addEventListener("visibilitychange", onVis);
    reduced.addEventListener("change", onVis);

    const ro = new ResizeObserver(layout);
    ro.observe(scene);
    ro.observe(panel);
    const io = new IntersectionObserver(([en]) => {
      visible = en.isIntersecting;
      if (visible) {
        modelApi.current.preload(list.flatMap((i) => i.colors.map((c) => c.model)));
        kick();
      } else {
        cancelAnimationFrame(raf);
        raf = 0;
        last = 0;
      }
    }, { rootMargin: "120px 0px" });
    io.observe(scene);
    layout();

    return () => {
      disposed = true;
      cancelAnimationFrame(raf);
      ro.disconnect();
      io.disconnect();
      scene.removeEventListener("pointermove", onMove);
      scene.removeEventListener("pointerdown", onDown);
      scene.removeEventListener("pointerup", onUp);
      scene.removeEventListener("pointercancel", onUp);
      scene.removeEventListener("pointerleave", onLeave);
      scene.removeEventListener("wheel", onWheel);
      scene.removeEventListener("keydown", onKey);
      cards.forEach((c) => c?.removeEventListener("pointerenter", sheen));
      document.removeEventListener("visibilitychange", onVis);
      reduced.removeEventListener("change", onVis);
    };
  }, [itemsKey]);

  /* ---------------- tab pill ---------------- */
  useLayoutEffect(() => {
    const tabs = tabsRef.current, pill = pillRef.current;
    if (!tabs || !pill) return;
    const place = () => {
      const btn = tabs.querySelector<HTMLElement>('[aria-pressed="true"]');
      if (!btn) return;
      pill.style.width = `${btn.offsetWidth}px`;
      pill.style.transform = `translateX(${btn.offsetLeft}px)`;
    };
    place();
    const ro = new ResizeObserver(place);
    ro.observe(tabs);
    return () => ro.disconnect();
  }, [cat, cats.length]);

  if (!item || !color) return null;
  const lines = bag.lines;
  const total = lines.reduce((sum, l) => sum + l.price, 0);

  const chooseColor = (id: string) => {
    const c = item.colors.find((x) => x.id === id);
    if (!c) return;
    setColorId(id);
    modelApi.current.tryOn(c.model);
  };
  const addToBag = () => {
    if (!size) {
      setNeedSize(true);
      document.getElementById("vf-size-first")?.focus();
      return;
    }
    bag.add([{ lookId: LOOK_ID, itemId: `${item.id}:${color.id}`, productId: (item as { productId?: string | number | null }).productId ?? null, label: `${item.name} — ${color.name}`, size, price: item.price, image: color.garment || color.model }]);
  };
  const slotItems = Array.from({ length: slots }, (_, i) => items[i % n]);
  const firstModel = items[0].colors[0].model;

  return (
    <div
      ref={sceneRef}
      className={s.scene}
      dir={isEn ? "ltr" : "rtl"}
      tabIndex={0}
      role="region"
      aria-roledescription={isEn ? "virtual try-on gallery" : "معرض تجربة القطع"}
      aria-label={
        isEn
          ? "New arrivals — drag or use arrow keys to try pieces on the model"
          : "وصل حديثًا — اسحب أو استخدم الأسهم لتجربة القطع على المودل"
      }
    >
      <div ref={roomRef} className={s.room} style={{ backgroundImage: `url(${data.settings.room})` }} aria-hidden />
      <div ref={ghostRef} className={s.ghost} aria-hidden>
        {data.settings.watermark.split("").map((ch, i) => (
          <span key={i}>{ch === " " ? " " : ch}</span>
        ))}
      </div>

      {/* category tabs — real filters */}
      {cats.length > 1 && (
        <div ref={tabsRef} className={`${s.tabs} ${s.glass}`} data-vf-ui role="toolbar" aria-label={isEn ? "Filter items" : "تصفية القطع"}>
          <span ref={pillRef} className={s.pill} aria-hidden />
          {[{ id: "all", name: isEn ? "All" : "الكل" }, ...cats].map((c) => (
            <button key={c.id} type="button" className={s.tab} aria-pressed={cat === c.id} onClick={() => setCat(c.id)}>
              {c.name}
            </button>
          ))}
        </div>
      )}

      {/* side rail — only buttons that do something */}
      <div className={`${s.rail} ${s.glass}`} data-vf-ui>
        <button type="button" className={s.railBtn} aria-label={isEn ? `Preview bag: ${lines.length} items` : `السلة التجريبية: ${lines.length} قطع`} aria-expanded={bagOpen} onClick={() => setBagOpen((v) => !v)}>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
            <path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.6a2 2 0 0 0 2-1.5L20.5 8H6.2" />
            <circle cx="10" cy="20" r="1.3" />
            <circle cx="17" cy="20" r="1.3" />
          </svg>
          {lines.length > 0 && <span className={s.badge}>{lines.length}</span>}
        </button>
      </div>
      {bagOpen && (
        <div className={`${s.bag} ${s.glass}`} data-vf-ui role="dialog" aria-label={isEn ? "Preview bag" : "السلة التجريبية"}>
          <h3>{isEn ? "Preview Bag" : "السلة التجريبية"}</h3>
          {lines.length === 0 ? (
            <p className={s.bagNote}>{isEn ? "No items added yet." : "لم تُضف قطع بعد."}</p>
          ) : (
            <ul>
              {lines.map((l) => (
                <li key={l.key}>
                  <span>
                    {l.label} · <span dir="ltr">{l.size}</span>
                  </span>
                  <span dir="ltr">{formatMoney(l.price)}</span>
                </li>
              ))}
            </ul>
          )}
          {lines.length > 0 && (
            <p>
              {isEn ? "Total:" : "المجموع:"} <span dir="ltr">{formatMoney(total)}</span>
            </p>
          )}
          <p className={s.bagNote}>
            {isEn ? "Preview only — sample prices, no order is submitted." : "معاينة فقط — الأسعار تجريبية ولا يُرسل أي طلب شراء."}
          </p>
          <button type="button" className={s.railBtn} style={{ width: "auto", padding: "0 12px", height: 32, marginTop: 8 }} onClick={() => setBagOpen(false)}>
            {isEn ? "Close" : "إغلاق"}
          </button>
        </div>
      )}

      <button type="button" className={`${s.arrow} ${s.prev} ${s.glass}`} data-vf-ui aria-label={isEn ? "Previous item" : "القطعة السابقة"} disabled={n < 2} onClick={() => arcApi.current.step(-1)}>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden><path d="M15 5l-7 7 7 7" /></svg>
      </button>
      <button type="button" className={`${s.arrow} ${s.next} ${s.glass}`} data-vf-ui aria-label={isEn ? "Next item" : "القطعة التالية"} disabled={n < 2} onClick={() => arcApi.current.step(1)}>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden><path d="M9 5l7 7-7 7" /></svg>
      </button>

      {/* garment arc */}
      <div className={s.arc} aria-hidden>
        {slotItems.map((it, i) => (
          <div key={`${it.id}-${i}`} ref={(el) => { cardRefs.current[i] = el; }} className={`${s.card} ${s.glass}`} data-slot={i}>
            {/* eslint-disable-next-line @next/next/no-img-element -- transparent cut-out, sized by the card */}
            <img src={it.colors[0].garment} alt="" draggable={false} />
            <span className={s.cardName}>{it.name}</span>
            <span className={s.cardPrice}>{formatMoney(it.price)}</span>
            <span className={s.sheen} />
          </div>
        ))}
      </div>

      {/* model */}
      <div className={s.model} aria-hidden>
        {/* eslint-disable-next-line @next/next/no-img-element -- swapped imperatively by the scan */}
        <img ref={mCurRef} className={s.mCur} src={firstModel} alt="" />
        {/* eslint-disable-next-line @next/next/no-img-element -- swapped imperatively by the scan */}
        <img ref={mNextRef} className={s.mNext} src={firstModel} alt="" />
        <div ref={gridRef} className={s.grid} />
        <div ref={scanRef} className={s.scan} />
      </div>

      {/* detail panel */}
      <div ref={panelRef} className={`${s.panel} ${s.glass}`} data-vf-ui>
        <div className={s.thumb}>
          {/* eslint-disable-next-line @next/next/no-img-element -- transparent cut-out thumbnail */}
          <img key={color.garment} src={color.garment} alt={`${item.name} — ${color.name}`} />
        </div>
        <div className={s.info}>
          <Roll text={item.name} className={s.pName} />
          {item.description && <Roll text={item.description} className={s.pDesc} />}
          <Roll text={formatMoney(item.price)} className={s.pPrice} />
          <span className={s.demo}>{isEn ? "Sample price" : "سعر تجريبي"}</span>
        </div>
        <div className={`${s.group} ${s.sizeGroup}`}>
          <span className={s.label} id="vf-size-label">
            <span className={s.labelText}>{isEn ? "Size" : "المقاس"}</span>{" "}
            {needSize && <span className={s.need} role="alert">{isEn ? "Select size first" : "اختر المقاس أولًا"}</span>}
          </span>
          <div className={`${s.chips} ${needSize ? s.chipsNeed : ""}`} role="radiogroup" aria-labelledby="vf-size-label">
            {item.sizes.map((z, i) => (
              <button
                key={z}
                id={i === 0 ? "vf-size-first" : undefined}
                type="button"
                role="radio"
                aria-checked={size === z}
                className={s.chip}
                onClick={() => { setSize(z); setNeedSize(false); }}
              >
                {z}
              </button>
            ))}
          </div>
        </div>
        <div className={`${s.group} ${s.colorGroup}`}>
          <Roll text={`${isEn ? "Color" : "اللون"} · ${color.name}`} className={s.label} />
          <div className={s.dots} role="radiogroup" aria-label={isEn ? "Color" : "اللون"}>
            {item.colors.map((c) => (
              <button key={c.id} type="button" role="radio" aria-checked={c.id === color.id} aria-label={c.name} title={c.name}
                className={s.dot} style={{ background: c.swatch }} onClick={() => chooseColor(c.id)} />
            ))}
          </div>
        </div>
        <button type="button" className={s.add} onClick={addToBag}>
          {isEn ? "Add to Bag" : "أضف إلى السلة"}
        </button>
      </div>

      {/* custom cursor (pointer: fine, inside the scene only) */}
      <div ref={curRef} className={s.cur} aria-hidden>
        <span className={s.ring} />
        <span className={s.dotC} />
        <span className={`${s.curText} ${s.tTry}`}>{isEn ? "Try" : "جرّب"}</span>
        <span className={`${s.curText} ${s.tDrag}`}>{isEn ? "Drag" : "اسحب"}</span>
      </div>

      {/* accessible list + live announcement */}
      <div className={s.sr} data-vf-ui>
        <p aria-live="polite">{`${item.name}، ${item.description ? item.description + "، " : ""}${formatMoney(item.price)}، ${color.name}`}</p>
        <ul>
          {items.map((it, k) => (
            <li key={it.id}>
              <button type="button" tabIndex={-1} onClick={() => arcApi.current.goToItem(k)}>
                {isEn ? `Try ${it.name}` : `جرّب ${it.name}`}
              </button>
            </li>
          ))}
        </ul>
      </div>
    </div>
  );
}

/** Text that rolls up when it changes (old out to −110 %, new in from 110 %). */
function Roll({ text, className, ltr }: { text: string; className: string; ltr?: boolean }) {
  const [cur, setCur] = useState(text);
  const [prev, setPrev] = useState<string | null>(null);
  if (text !== cur) {
    setPrev(cur);
    setCur(text);
  }
  return (
    <span className={`${s.ln} ${className}`} dir={ltr ? "ltr" : undefined}>
      {prev !== null && (
        <span key={`o-${prev}`} className={s.rollOut} aria-hidden onAnimationEnd={() => setPrev(null)}>
          {prev}
        </span>
      )}
      <span key={`i-${cur}`} className={prev !== null ? s.rollIn : undefined}>
        {cur}
      </span>
    </span>
  );
}
