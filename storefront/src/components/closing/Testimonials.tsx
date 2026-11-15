"use client";

import { useCallback, useEffect, useRef, useState, type CSSProperties, type KeyboardEvent } from "react";
import type { Testimonial } from "@/content/closing-types";
import { ArrowIcon } from "@/components/store/icons";
import { QuoteMark, StarIcon } from "./icons";
import s from "./closing.module.css";

/**
 * «قالوا عن بن هويش» cards. Three per row on desktop; when there are more
 * (or on a phone) the row becomes a native scroll-snap track: swipe with a
 * finger, or use the arrows / dots / ← → keys. Nothing moves on its own.
 * Cards rise in once when the section comes into view (skipped with
 * reduced motion).
 */
export default function Testimonials({ items }: { items: Testimonial[] }) {
  const trackRef = useRef<HTMLUListElement>(null);
  const rootRef = useRef<HTMLDivElement>(null);
  const [active, setActive] = useState(0);
  const [overflow, setOverflow] = useState(false);
  const [atStart, setAtStart] = useState(true);
  const [atEnd, setAtEnd] = useState(false);
  const n = items.length;

  const cards = () => [...(trackRef.current?.children ?? [])] as HTMLElement[];

  /** Which card sits at the start (right edge in RTL) of the track. */
  const measure = useCallback(() => {
    const track = trackRef.current;
    if (!track) return;
    const tr = track.getBoundingClientRect();
    let best = 0;
    let bestD = Infinity;
    cards().forEach((c, i) => {
      const d = Math.abs(c.getBoundingClientRect().right - tr.right);
      if (d < bestD) {
        bestD = d;
        best = i;
      }
    });
    const max = track.scrollWidth - track.clientWidth;
    const pos = Math.abs(track.scrollLeft); // RTL scrollLeft runs 0 → −max
    setActive(best);
    setOverflow(max > 2);
    setAtStart(pos < 4);
    setAtEnd(pos > max - 4);
  }, []);

  useEffect(() => {
    const track = trackRef.current;
    if (!track) return;
    measure();
    const ro = new ResizeObserver(measure);
    ro.observe(track);
    track.addEventListener("scroll", measure, { passive: true });
    return () => {
      ro.disconnect();
      track.removeEventListener("scroll", measure);
    };
  }, [measure]);

  const goTo = (i: number) => {
    const track = trackRef.current;
    const card = cards()[Math.max(0, Math.min(n - 1, i))];
    if (!track || !card) return;
    const reduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    const delta = card.getBoundingClientRect().right - track.getBoundingClientRect().right;
    track.scrollBy({ left: delta, behavior: reduced ? "auto" : "smooth" });
  };

  const onKey = (e: KeyboardEvent<HTMLUListElement>) => {
    const map: Record<string, number> = { ArrowLeft: active + 1, ArrowRight: active - 1, Home: 0, End: n - 1 };
    if (!(e.key in map) || !overflow) return;
    e.preventDefault();
    goTo(map[e.key]);
  };

  // Entry: cards rise in once when the section is reached.
  useEffect(() => {
    const root = rootRef.current;
    if (!root || window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;
    if (root.getBoundingClientRect().top < window.innerHeight * 0.85) return; // already on screen: no hide/show flash
    root.dataset.enter = "wait";
    const io = new IntersectionObserver(
      (entries) => {
        if (!entries.some((en) => en.isIntersecting)) return;
        io.disconnect();
        root.dataset.enter = "run";
      },
      { threshold: 0.25 },
    );
    io.observe(root);
    return () => io.disconnect();
  }, []);

  return (
    <div ref={rootRef} className={s.tWrap} data-overflow={overflow ? "" : undefined}>
      <ul
        ref={trackRef}
        className={s.track}
        role="region"
        aria-roledescription="شريط آراء"
        aria-label="آراء العملاء"
        tabIndex={overflow ? 0 : -1}
        onKeyDown={onKey}
      >
        {items.map((t, i) => (
          <li key={t.id} className={s.card} style={{ "--i": i } as CSSProperties} aria-roledescription="رأي" aria-label={`${i + 1} من ${n}`}>
            <QuoteMark className={s.quoteMark} />
            <blockquote className={s.quote}>
              <p>{t.quote}</p>
            </blockquote>
            <footer className={s.cardFoot}>
              <cite className={s.who}>{t.name}</cite>
              {t.verified ? (
                <span className={s.meta}>
                  {t.rating ? (
                    <span className={s.stars} role="img" aria-label={`${t.rating} من 5`}>
                      {[1, 2, 3, 4, 5].map((k) => (
                        <StarIcon key={k} width={14} height={14} filled={k <= (t.rating ?? 0)} />
                      ))}
                    </span>
                  ) : null}
                  {t.source && <span>{t.source}</span>}
                </span>
              ) : (
                <span className={s.previewTag}>مثال للمعاينة</span>
              )}
            </footer>
          </li>
        ))}
      </ul>

      <div className={s.tControls}>
        <button type="button" className={s.roundBtn} onClick={() => goTo(active - 1)} disabled={atStart} aria-label="الرأي السابق">
          <ArrowIcon width={18} height={18} style={{ transform: "scaleX(-1)" }} />
        </button>
        <div className={s.dots}>
          {items.map((t, i) => (
            <button
              key={t.id}
              type="button"
              className={s.dot}
              aria-label={`الرأي ${i + 1} من ${n}`}
              aria-current={i === active ? "true" : undefined}
              onClick={() => goTo(i)}
            />
          ))}
        </div>
        <button type="button" className={s.roundBtn} onClick={() => goTo(active + 1)} disabled={atEnd} aria-label="الرأي التالي">
          <ArrowIcon width={18} height={18} />
        </button>
      </div>
    </div>
  );
}
