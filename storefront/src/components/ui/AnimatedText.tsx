"use client";

import {
  motion,
  useScroll,
  useTransform,
  type MotionValue,
} from "framer-motion";
import { type CSSProperties, useMemo, useRef } from "react";

/** Prefix-sum of character offsets, kept out of render so it can use a cursor. */
function buildCharIndex(text: string) {
  const words = text.split(" ");
  const wordStarts: number[] = [];
  let cursor = 0;
  for (const word of words) {
    wordStarts.push(cursor);
    cursor += word.length;
  }
  return { words, wordStarts, totalChars: cursor };
}

type AnimatedTextProps = {
  text: string;
  className?: string;
  style?: CSSProperties;
};

/**
 * Character-by-character scroll reveal. Words stay as unbreakable inline-block
 * units so the paragraph still wraps naturally at every breakpoint.
 */
export default function AnimatedText({
  text,
  className,
  style,
}: AnimatedTextProps) {
  const ref = useRef<HTMLParagraphElement>(null);
  const { scrollYProgress } = useScroll({
    target: ref,
    offset: ["start 0.8", "end 0.2"],
  });

  const { words, wordStarts, totalChars } = useMemo(
    () => buildCharIndex(text),
    [text],
  );

  return (
    <p ref={ref} className={className} style={style}>
      {words.map((word, wordIndex) => (
        <span
          key={`${word}-${wordIndex}`}
          className="inline-block whitespace-nowrap"
        >
          {word.split("").map((char, charIndex) => {
            const position = wordStarts[wordIndex] + charIndex;
            return (
              <Char
                key={charIndex}
                char={char}
                progress={scrollYProgress}
                start={position / totalChars}
                end={(position + 1) / totalChars}
              />
            );
          })}
          {wordIndex < words.length - 1 ? " " : null}
        </span>
      ))}
    </p>
  );
}

function Char({
  char,
  progress,
  start,
  end,
}: {
  char: string;
  progress: MotionValue<number>;
  start: number;
  end: number;
}) {
  const opacity = useTransform(progress, [start, end], [0.2, 1]);

  return (
    <span className="relative inline-block">
      {/* Invisible copy holds the layout box; the animated copy sits on top. */}
      <span className="opacity-0">{char}</span>
      <motion.span className="absolute left-0 top-0" style={{ opacity }}>
        {char}
      </motion.span>
    </span>
  );
}
