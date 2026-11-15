import * as React from "react"
import { cn } from "@/lib/utils"

/**
 * Marquee — supplied component, kept API-compatible. Small additions for
 * this site (all optional, defaults unchanged):
 *  - `pauseOnFocus`: also pause while keyboard focus is inside the marquee;
 *    hover/focus pausing applies to the whole marquee, not just the track.
 *  - `viewportClassName`: extra classes for the clipping viewport
 *    (e.g. remove the default max-w-[90vw], add edge fades).
 *  - The viewport is `dir="ltr"`: the motion is physical, and inside an RTL
 *    page a right-anchored track would leave a gap while it slides.
 *  - `motion-reduce:animate-none` for visitors who prefer reduced motion.
 * The animation itself (`animate-marquee`, `animate-marquee-reverse`,
 * `--duration`) is defined in app/globals.css (Tailwind v4 has no JS config).
 */
interface MarqueeProps extends React.HTMLAttributes<HTMLDivElement> {
  children: React.ReactNode
  pauseOnHover?: boolean
  pauseOnFocus?: boolean
  direction?: "left" | "right"
  /** Seconds for one full cycle. */
  speed?: number
  viewportClassName?: string
}

export function Marquee({
  children,
  pauseOnHover = false,
  pauseOnFocus = false,
  direction = "left",
  speed = 30,
  className,
  viewportClassName,
  ...props
}: MarqueeProps) {
  return (
    <div
      className={cn(
        "group/marquee w-full overflow-hidden sm:mt-24 mt-10 z-10",
        className
      )}
      {...props}
    >
      {/* ltr on the VIEWPORT: in an RTL page a flex container anchors its
          overflowing track to the right, so sliding left would open a gap. */}
      <div dir="ltr" className={cn("relative flex max-w-[90vw] overflow-hidden py-5", viewportClassName)}>
        <div
          data-marquee-track=""
          className={cn(
            "flex w-max animate-marquee motion-reduce:animate-none",
            pauseOnHover && "group-hover/marquee:[animation-play-state:paused]",
            pauseOnFocus && "group-focus-within/marquee:[animation-play-state:paused]",
            direction === "right" && "animate-marquee-reverse"
          )}
          style={{ "--duration": `${speed}s` } as React.CSSProperties}
        >
          {children}
          {children}
        </div>
      </div>
    </div>
  )
}
