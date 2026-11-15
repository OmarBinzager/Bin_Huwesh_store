"use client";

import { useEffect, useRef } from "react";
import Hls from "hls.js";

const videoSrc =
  "https://stream.mux.com/T6oQJQ02cQ6N01TR6iHwZkKFkbepS34dkkIc9iukgy400g.m3u8";

const posterSrc =
  "https://images.unsplash.com/photo-1647356191320-d7a1f80ca777?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxhYnN0cmFjdCUyMGRhcmslMjB0ZWNobm9sb2d5JTIwbmV1cmFsJTIwbmV0d29ya3xlbnwxfHx8fDE3Njg5NzIyNTV8MA&ixlib=rb-4.1.0&q=80&w=1080";

/**
 * Crimson colour treatment for the reference clip.
 *
 * The source is blue/indigo. Rather than masking it, this collapses it to
 * luminance and rebuilds the hue: `sepia` lands the whole frame on a single
 * warm hue (~39deg), `hue-rotate(-43deg)` carries that to crimson (~356deg,
 * the hue of #8B111B), and saturate/brightness settle it into the dark end of
 * the brand palette. Luminance — every detail and every frame of motion — is
 * untouched, so the clip still reads exactly as the reference does.
 */
const crimsonTreatment =
  "grayscale(1) sepia(1) hue-rotate(-43deg) saturate(2.6) brightness(0.85)";

/**
 * The single atmospheric background shared by Hero + CollectionSurfer.
 *
 * The outer element spans both sections; the inner `sticky` layer pins one
 * viewport-sized copy for that whole range, so the video plays continuously
 * and never restarts at the section boundary. `overflow-hidden` belongs on the
 * sticky layer — putting it on the outer element would make that element the
 * sticky child's scroll container and defeat the pinning.
 */
export default function AtmosphericBackground() {
  const videoRef = useRef<HTMLVideoElement>(null);

  useEffect(() => {
    const video = videoRef.current;
    if (!video) return;

    const tryPlay = () => {
      video.play().catch((e) => console.log("Auto-play prevented:", e));
    };

    /*
     * The reference calls play() exactly once, when the manifest parses. If
     * the tab happens to be backgrounded at that moment Chrome aborts it
     * ("video-only background media was paused to save power", AbortError 20)
     * and nothing ever retries, so the background stays frozen for the rest of
     * the session. Re-arming on visibilitychange is the whole fix; playback
     * behaviour is otherwise the reference's.
     */
    const onVisibilityChange = () => {
      if (!document.hidden && video.paused) tryPlay();
    };
    document.addEventListener("visibilitychange", onVisibilityChange);

    if (Hls.isSupported()) {
      const hls = new Hls();
      hls.loadSource(videoSrc);
      hls.attachMedia(video);
      hls.on(Hls.Events.MANIFEST_PARSED, tryPlay);
      return () => {
        document.removeEventListener("visibilitychange", onVisibilityChange);
        hls.destroy();
      };
    }

    if (video.canPlayType("application/vnd.apple.mpegurl")) {
      video.src = videoSrc;
      video.addEventListener("loadedmetadata", tryPlay);
    }

    return () => {
      document.removeEventListener("visibilitychange", onVisibilityChange);
      video.removeEventListener("loadedmetadata", tryPlay);
    };
  }, []);

  return (
    <div
      aria-hidden
      className="pointer-events-none absolute inset-0 select-none"
    >
      <div className="sticky top-0 h-screen w-full overflow-hidden bg-black">
        {/* Background video layer */}
        <video
          ref={videoRef}
          poster={posterSrc}
          autoPlay
          muted
          loop
          playsInline
          className="absolute inset-0 h-full w-full object-cover opacity-60"
          style={{ filter: crimsonTreatment }}
        />

        {/* Video overlay */}
        <div className="absolute inset-0 bg-black/60 backdrop-blur-[2px]" />

        {/* Decorative gradients — reference geometry, crimson palette. */}
        <div className="absolute top-[-20%] left-[20%] h-[600px] w-[600px] bg-[#5C0A12]/20 blur-[120px] mix-blend-screen" />
        <div className="absolute bottom-[-10%] right-[20%] h-[500px] w-[500px] bg-[#8B111B]/20 blur-[120px] mix-blend-screen" />
      </div>
    </div>
  );
}
