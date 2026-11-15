"use client";

import { useEffect, useRef, useState } from "react";
import { marqueeRowOne, marqueeRowTwo } from "@/data/marquee";

/** Tripled so the strip never runs out of tiles while it translates. */
const triple = (images: string[]) => [...images, ...images, ...images];

const rowOne = triple(marqueeRowOne);
const rowTwo = triple(marqueeRowTwo);

export default function MarqueeSection() {
  const sectionRef = useRef<HTMLElement>(null);
  const [offset, setOffset] = useState(0);

  useEffect(() => {
    let frame = 0;

    const update = () => {
      frame = 0;
      const node = sectionRef.current;
      if (!node) return;
      const sectionTop = node.getBoundingClientRect().top + window.scrollY;
      setOffset((window.scrollY - sectionTop + window.innerHeight) * 0.3);
    };

    // rAF-coalesced so a fast scroll never queues more work than it can paint.
    const onScroll = () => {
      if (!frame) frame = requestAnimationFrame(update);
    };

    update();
    window.addEventListener("scroll", onScroll, { passive: true });
    window.addEventListener("resize", onScroll, { passive: true });

    return () => {
      window.removeEventListener("scroll", onScroll);
      window.removeEventListener("resize", onScroll);
      if (frame) cancelAnimationFrame(frame);
    };
  }, []);

  const shift = offset - 200;

  return (
    <section
      ref={sectionRef}
      className="bg-ink overflow-x-clip pt-24 pb-10 sm:pt-32 md:pt-40"
      aria-label="Selected work previews"
    >
      <div className="flex flex-col gap-3">
        <Row images={rowOne} translateX={shift} />
        <Row images={rowTwo} translateX={-shift} />
      </div>
    </section>
  );
}

function Row({ images, translateX }: { images: string[]; translateX: number }) {
  return (
    <div
      className="flex gap-3"
      style={{
        transform: `translateX(${translateX}px)`,
        willChange: "transform",
      }}
    >
      {images.map((src, index) => (
        <div
          key={`${src}-${index}`}
          className="marquee-tile shrink-0 overflow-hidden rounded-2xl"
          style={{ width: 420, height: 270 }}
        >
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img
            src={src}
            alt=""
            aria-hidden="true"
            width={420}
            height={270}
            loading="lazy"
            decoding="async"
            className="h-full w-full rounded-2xl object-cover"
          />
        </div>
      ))}
    </div>
  );
}
