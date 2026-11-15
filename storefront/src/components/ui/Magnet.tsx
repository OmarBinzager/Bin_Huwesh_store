"use client";

import { useReducedMotion } from "framer-motion";
import { type ReactNode, useEffect, useRef, useState } from "react";

type MagnetProps = {
  children: ReactNode;
  /** Distance in px beyond the element's edge where the pull begins. */
  padding?: number;
  /** Higher divides the offset down, so a larger number pulls less. */
  strength?: number;
  activeTransition?: string;
  inactiveTransition?: string;
  className?: string;
  innerClassName?: string;
};

export default function Magnet({
  children,
  padding = 100,
  strength = 2,
  activeTransition = "transform 0.3s ease-out",
  inactiveTransition = "transform 0.6s ease-in-out",
  className,
  innerClassName,
}: MagnetProps) {
  const ref = useRef<HTMLDivElement>(null);
  const [active, setActive] = useState(false);
  const [offset, setOffset] = useState({ x: 0, y: 0 });
  const reduceMotion = useReducedMotion();

  useEffect(() => {
    if (reduceMotion) return;

    const onMove = (event: MouseEvent) => {
      const node = ref.current;
      if (!node) return;

      const { left, top, width, height } = node.getBoundingClientRect();
      const centerX = left + width / 2;
      const centerY = top + height / 2;
      const distX = Math.abs(event.clientX - centerX) - width / 2;
      const distY = Math.abs(event.clientY - centerY) - height / 2;

      if (distX < padding && distY < padding) {
        setActive(true);
        setOffset({
          x: (event.clientX - centerX) / strength,
          y: (event.clientY - centerY) / strength,
        });
      } else {
        setActive(false);
        setOffset({ x: 0, y: 0 });
      }
    };

    window.addEventListener("mousemove", onMove, { passive: true });
    return () => window.removeEventListener("mousemove", onMove);
  }, [padding, strength, reduceMotion]);

  return (
    <div ref={ref} className={className}>
      <div
        className={innerClassName}
        style={{
          transform: `translate3d(${offset.x}px, ${offset.y}px, 0)`,
          transition: active ? activeTransition : inactiveTransition,
          willChange: "transform",
        }}
      >
        {children}
      </div>
    </div>
  );
}
