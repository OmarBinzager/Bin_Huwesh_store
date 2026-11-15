"use client";

import {
  motion,
  useReducedMotion,
  type HTMLMotionProps,
} from "framer-motion";
import type { ComponentType, CSSProperties, ReactNode } from "react";

type MotionComponent = ComponentType<HTMLMotionProps<"div">>;

/**
 * Built once at module scope. Calling motion.create() during render would make
 * React remount (and reset) the element on every pass.
 */
const motionComponents = {
  div: motion.create("div"),
  nav: motion.create("nav"),
  ul: motion.create("ul"),
  li: motion.create("li"),
  section: motion.create("section"),
  h1: motion.create("h1"),
  h2: motion.create("h2"),
  h3: motion.create("h3"),
  p: motion.create("p"),
  span: motion.create("span"),
} as unknown as Record<string, MotionComponent>;

export type FadeInTag = keyof typeof motionComponents;

type FadeInProps = {
  children: ReactNode;
  as?: FadeInTag;
  className?: string;
  style?: CSSProperties;
  delay?: number;
  duration?: number;
  x?: number;
  y?: number;
};

export default function FadeIn({
  children,
  as = "div",
  className,
  style,
  delay = 0,
  duration = 0.7,
  x = 0,
  y = 30,
}: FadeInProps) {
  const Component = motionComponents[as] ?? motionComponents.div;
  const reduceMotion = useReducedMotion();

  return (
    <Component
      className={className}
      style={style}
      initial={reduceMotion ? { opacity: 0 } : { opacity: 0, x, y }}
      whileInView={{ opacity: 1, x: 0, y: 0 }}
      viewport={{ once: true, margin: "50px", amount: 0 }}
      transition={{
        duration: reduceMotion ? 0.2 : duration,
        delay: reduceMotion ? 0 : delay,
        ease: [0.25, 0.1, 0.25, 1],
      }}
    >
      {children}
    </Component>
  );
}
