"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import { createPortal } from "react-dom";
import { createDecartClient, models, type RealTimeClient } from "@decartai/sdk";
import { fetchTryOnToken } from "@/lib/storefront-api";

export type TryOnVariant = {
  label: string;
  image: string;
  swatch?: string;
};

export type TryOnModalProps = {
  open: boolean;
  onClose: () => void;
  productId: number;
  productName: string;
  garmentImage: string;
  selectedColor?: string;
  variants?: TryOnVariant[];
  locale?: "ar" | "en";
  embedded?: boolean;
  autoStart?: boolean;
};

type Stage = "consent" | "connecting" | "live" | "error";

async function loadGarmentFile(imageUrl: string): Promise<File> {
  const proxiedUrl = `/api/try-on/garment-image?url=${encodeURIComponent(imageUrl)}`;
  const imgRes = await fetch(proxiedUrl, { cache: "no-store" });
  if (!imgRes.ok) {
    throw new Error("GARMENT_IMAGE_LOAD_FAILED");
  }
  const imgBlob = await imgRes.blob();
  return new File([imgBlob], "garment.jpg", {
    type: imgBlob.type || "image/jpeg",
  });
}

function buildGarmentPrompt(color?: string) {
  return color
    ? `Fit this ${color} garment naturally on the person while preserving natural body movement and lighting`
    : "Fit this garment naturally on the person while preserving natural body movement and lighting";
}

export default function TryOnModal({
  open,
  onClose,
  productId,
  productName,
  garmentImage,
  selectedColor,
  variants = [],
  locale = "ar",
  embedded = false,
  autoStart = false,
}: TryOnModalProps) {
  const isEn = locale === "en";
  const [stage, setStage] = useState<Stage>("consent");
  const [errorMsg, setErrorMsg] = useState<string>("");
  const [facingMode, setFacingMode] = useState<"user" | "environment">("user");
  const [secondsLeft, setSecondsLeft] = useState<number>(45);
  const [isFullscreen, setIsFullscreen] = useState<boolean>(embedded);
  const [switchingVariant, setSwitchingVariant] = useState<boolean>(false);
  const [activeColor, setActiveColor] = useState<string | undefined>(selectedColor);
  const [activeImage, setActiveImage] = useState<string>(garmentImage);
  const [disclaimer, setDisclaimer] = useState<string>(
    isEn
      ? "Approximate visual preview only, not a size recommendation."
      : "معاينة تقريبية للشكل، وليست توصية بالمقاس."
  );

  const modalContainerRef = useRef<HTMLDivElement | null>(null);
  const remoteVideoRef = useRef<HTMLVideoElement | null>(null);
  const localVideoRef = useRef<HTMLVideoElement | null>(null);
  const streamRef = useRef<MediaStream | null>(null);
  const realtimeRef = useRef<RealTimeClient | null>(null);
  const timerRef = useRef<ReturnType<typeof setInterval> | null>(null);

  const stopEverything = useCallback(() => {
    if (timerRef.current) {
      clearInterval(timerRef.current);
      timerRef.current = null;
    }
    if (realtimeRef.current) {
      try {
        realtimeRef.current.disconnect();
      } catch {
        // Ignore disconnect errors on teardown
      }
      realtimeRef.current = null;
    }
    if (streamRef.current) {
      streamRef.current.getTracks().forEach((t) => {
        try {
          t.stop();
        } catch {
          // Ignore track stop errors
        }
      });
      streamRef.current = null;
    }
    if (remoteVideoRef.current) {
      remoteVideoRef.current.srcObject = null;
    }
    if (localVideoRef.current) {
      localVideoRef.current.srcObject = null;
    }
  }, []);

  const exitNativeFullscreen = useCallback(() => {
    if (typeof document !== "undefined" && document.fullscreenElement) {
      document.exitFullscreen().catch(() => {});
    }
  }, []);

  const handleClose = useCallback(() => {
    stopEverything();
    exitNativeFullscreen();
    setIsFullscreen(embedded);
    setStage("consent");
    setErrorMsg("");
    onClose();
  }, [embedded, exitNativeFullscreen, onClose, stopEverything]);

  const autoStartedRef = useRef<boolean>(false);

  useEffect(() => {
    if (!open) {
      autoStartedRef.current = false;
      stopEverything();
    }
    return () => {
      stopEverything();
    };
  }, [open, stopEverything]);

  const toggleFullscreen = useCallback(() => {
    const next = !isFullscreen;
    setIsFullscreen(next);
    if (typeof document !== "undefined") {
      if (next && modalContainerRef.current?.requestFullscreen) {
        modalContainerRef.current.requestFullscreen().catch(() => {});
      } else if (!next && document.fullscreenElement) {
        document.exitFullscreen().catch(() => {});
      }
    }
  }, [isFullscreen]);

  const startSession = useCallback(
    async (mode: "user" | "environment", overrideColor?: string, overrideImage?: string) => {
      stopEverything();
      setStage("connecting");
      setErrorMsg("");

      const targetColor = overrideColor ?? activeColor;
      const targetImage = overrideImage ?? activeImage;

      try {
        if (!navigator?.mediaDevices?.getUserMedia) {
          throw new Error(
            isEn
              ? "Camera access is not supported in this browser or requires HTTPS."
              : "الكاميرا غير مدعومة في هذا المتصفح أو تتطلب اتصالاً آمناً (HTTPS)."
          );
        }

        const tokenData = await fetchTryOnToken(productId, targetColor, locale);
        if (tokenData.disclaimer) {
          setDisclaimer(tokenData.disclaimer);
        }

        const maxSec = tokenData.max_session_seconds || 45;
        setSecondsLeft(maxSec);

        const modelName = (tokenData.model || "lucy-vton-latest") as Parameters<
          typeof models.realtime
        >[0];
        const model = models.realtime(modelName);

        const mediaStream = await navigator.mediaDevices.getUserMedia({
          audio: false,
          video: {
            facingMode: { ideal: mode },
            width: { ideal: model.width },
            height: { ideal: model.height },
            frameRate: model.fps,
          },
        });
        streamRef.current = mediaStream;

        if (localVideoRef.current) {
          localVideoRef.current.srcObject = mediaStream;
          localVideoRef.current.play().catch(() => {});
        }

        const imageSource = targetImage || tokenData.garment_image_url;
        const garmentFile = await loadGarmentFile(imageSource);

        const client = createDecartClient({ apiKey: tokenData.api_key });
        const rt = await client.realtime.connect(mediaStream, {
          model,
          mirror: mode === "user" ? "auto" : false,
          onRemoteStream: (remoteStream) => {
            if (remoteVideoRef.current) {
              remoteVideoRef.current.srcObject = remoteStream;
              remoteVideoRef.current.play().catch(() => {});
            }
          },
          initialState: {
            prompt: {
              text: buildGarmentPrompt(targetColor),
              enhance: true,
            },
            image: garmentFile,
          },
        });

        realtimeRef.current = rt;
        setStage("live");

        timerRef.current = setInterval(() => {
          setSecondsLeft((prev) => {
            if (prev <= 1) {
              stopEverything();
              setStage("consent");
              return 0;
            }
            return prev - 1;
          });
        }, 1000);
      } catch (err) {
        stopEverything();
        const rawMsg = err instanceof Error ? err.message : "";
        let friendly = rawMsg;
        if (rawMsg === "GARMENT_IMAGE_LOAD_FAILED") {
          friendly = isEn
            ? "Could not load the selected garment image."
            : "تعذر تحميل صورة القطعة المختارة.";
        } else if (rawMsg.includes("Permission") || rawMsg.includes("NotAllowed")) {
          friendly = isEn
            ? "Camera permission was denied. Please allow camera access and try again."
            : "تم رفض إذن الكاميرا. يرجى السماح بالوصول إلى الكاميرا والمحاولة مجدداً.";
        } else if (rawMsg.includes("1013") || rawMsg.toLowerCase().includes("session limit")) {
          friendly = isEn
            ? "Virtual try-on is busy right now. Please try again in a moment."
            : "خدمة التجربة الافتراضية مشغولة حالياً. يرجى المحاولة بعد لحظات.";
        } else if (!friendly) {
          friendly = isEn
            ? "Could not start the virtual try-on session."
            : "تعذر تشغيل التجربة الافتراضية حالياً.";
        }
        setErrorMsg(friendly);
        setStage("error");
      }
    },
    [activeColor, activeImage, isEn, locale, productId, stopEverything]
  );

  useEffect(() => {
    if (open && autoStart && !autoStartedRef.current) {
      autoStartedRef.current = true;
      queueMicrotask(() => {
        startSession(facingMode);
      });
    }
  }, [autoStart, facingMode, open, startSession]);

  const handleSelectVariant = useCallback(
    async (variant: TryOnVariant) => {
      setActiveColor(variant.label);
      setActiveImage(variant.image);

      if (stage === "live" && realtimeRef.current) {
        setSwitchingVariant(true);
        try {
          const newFile = await loadGarmentFile(variant.image);
          await realtimeRef.current.set({
            prompt: buildGarmentPrompt(variant.label),
            image: newFile,
          });
        } catch {
          // If live swap fails, reconnect cleanly with the new color/image
          startSession(facingMode, variant.label, variant.image);
        } finally {
          setSwitchingVariant(false);
        }
      }
    },
    [facingMode, stage, startSession]
  );

  const toggleCamera = () => {
    const next = facingMode === "user" ? "environment" : "user";
    setFacingMode(next);
    if (stage === "live" || stage === "connecting") {
      startSession(next);
    }
  };

  if (!open || typeof document === "undefined") return null;

  const formattedTime = `00:${String(secondsLeft).padStart(2, "0")}`;
  const fullScreenMode = embedded || isFullscreen;

  return createPortal(
    <div
      ref={modalContainerRef}
      className={
        fullScreenMode
          ? "fixed inset-0 z-[9999] flex flex-col bg-paper text-ink"
          : "fixed inset-0 z-[9999] flex items-center justify-center bg-black/70 p-3 backdrop-blur-sm sm:p-6"
      }
      role="dialog"
      aria-modal="true"
      aria-label={isEn ? "Virtual Try-On" : "جرّبها عليك"}
      onClick={(e) => {
        if (!fullScreenMode && e.target === e.currentTarget) handleClose();
      }}
    >
      <div
        className={
          fullScreenMode
            ? "flex h-full w-full flex-col overflow-hidden bg-paper"
            : "relative flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-3xl border border-line bg-paper shadow-2xl"
        }
      >
        {/* Top Bar */}
        <div className="flex items-center justify-between gap-3 border-b border-line bg-card px-4 py-3 sm:px-6">
          <div className="flex items-center gap-3 min-w-0">
            <img
              src={activeImage}
              alt={productName}
              className="h-11 w-9 shrink-0 rounded-xl border border-line bg-stage object-cover"
            />
            <div className="min-w-0">
              <p className="font-display text-sm font-semibold text-ink truncate">
                {isEn ? "Virtual Try-On" : "جرّبها عليك"}
              </p>
              <div className="flex items-center gap-2 text-xs text-muted truncate">
                <span className="truncate">{productName}</span>
                {activeColor && (
                  <span className="shrink-0 rounded-full border border-line bg-stage px-2 py-0.5 text-[11px] text-ink">
                    {activeColor}
                  </span>
                )}
              </div>
            </div>
          </div>

          <div className="flex items-center gap-2 shrink-0">
            {stage === "live" && (
              <span
                dir="ltr"
                className="nums rounded-full bg-brand/10 px-3 py-1 text-xs font-semibold text-brand"
              >
                {formattedTime}
              </span>
            )}
            {!embedded && (
              <button
                type="button"
                onClick={toggleFullscreen}
                aria-label={
                  isFullscreen
                    ? isEn
                      ? "Exit Fullscreen"
                      : "تصغير الشاشة"
                    : isEn
                    ? "Fullscreen"
                    : "ملء الشاشة"
                }
                title={
                  isFullscreen
                    ? isEn
                      ? "Exit Fullscreen"
                      : "تصغير الشاشة"
                    : isEn
                    ? "Fullscreen"
                    : "ملء الشاشة"
                }
                className="flex h-10 w-10 items-center justify-center rounded-full border border-line bg-stage text-ink transition-colors hover:border-brand hover:text-brand"
              >
                {isFullscreen ? (
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3" />
                  </svg>
                ) : (
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7" />
                  </svg>
                )}
              </button>
            )}
            <button
              type="button"
              onClick={handleClose}
              aria-label={isEn ? "Close" : "إغلاق"}
              className="flex h-10 w-10 items-center justify-center rounded-full border border-line bg-stage text-ink transition-colors hover:border-brand hover:text-brand"
            >
              ✕
            </button>
          </div>
        </div>

        {/* Body */}
        <div
          className={
            fullScreenMode && (stage === "connecting" || stage === "live")
              ? "relative flex-1 overflow-hidden bg-black"
              : "flex-1 overflow-y-auto p-4 sm:p-6"
          }
        >
          {stage === "consent" && (
            <div className="flex flex-col items-center text-center">
              <div className="relative mb-4 h-44 w-36 overflow-hidden rounded-2xl border border-line bg-stage">
                <img
                  src={activeImage}
                  alt={productName}
                  className="h-full w-full object-contain p-2"
                />
              </div>

              {variants.length > 1 && (
                <div className="mb-4 flex max-w-md flex-wrap items-center justify-center gap-2">
                  {variants.map((v) => {
                    const isSelected = activeColor === v.label || activeImage === v.image;
                    return (
                      <button
                        key={`${v.label}-${v.image}`}
                        type="button"
                        onClick={() => handleSelectVariant(v)}
                        className={`inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-medium transition-colors ${
                          isSelected
                            ? "border-brand bg-brand/10 text-ink"
                            : "border-line bg-stage text-muted hover:text-ink"
                        }`}
                      >
                        {v.swatch && (
                          <span
                            className="h-3 w-3 rounded-full border border-line"
                            style={{ backgroundColor: v.swatch }}
                          />
                        )}
                        <span>{v.label}</span>
                      </button>
                    );
                  })}
                </div>
              )}

              <h3 className="font-display text-lg font-semibold text-ink sm:text-xl">
                {isEn
                  ? "Preview this piece live on your camera"
                  : "معاينة القطعة عليك مباشرة بالكاميرا"}
              </h3>

              <p className="mt-2.5 max-w-md text-[13px] leading-relaxed text-muted">
                {isEn
                  ? "By continuing, you consent to streaming your camera feed securely to Decart AI solely to render this garment in real time. Both camera and connection stop immediately when you close this window."
                  : "بالمتابعة، فإنك توافق على بث فيديو الكاميرا بشكل مشفّر إلى خدمة Decart لعرض القطعة عليك لحظياً فقط. تتوقف الكاميرا والاتصال بالخدمة فور إغلاق هذه النافذة."}
              </p>

              <div className="mt-4 w-full max-w-md rounded-2xl border border-brand/30 bg-brand/5 px-4 py-3 text-[13px] font-medium text-ink">
                {disclaimer}
              </div>

              <div className="mt-6 flex w-full max-w-md flex-col gap-2.5 sm:flex-row">
                <button
                  type="button"
                  onClick={() => startSession(facingMode)}
                  className="flex h-11 flex-1 items-center justify-center rounded-full bg-brand px-6 text-sm font-semibold text-white transition-opacity hover:opacity-90"
                >
                  {isEn ? "I Agree, Start Try-On" : "موافق، ابدأ التجربة"}
                </button>
                <button
                  type="button"
                  onClick={handleClose}
                  className="flex h-11 items-center justify-center rounded-full border border-line bg-stage px-5 text-sm font-medium text-ink transition-colors hover:border-ink"
                >
                  {isEn ? "Cancel" : "إلغاء"}
                </button>
              </div>
            </div>
          )}

          {(stage === "connecting" || stage === "live") && (
            <div
              className={
                fullScreenMode
                  ? "relative flex h-full w-full flex-col items-center justify-center"
                  : "flex flex-col items-center gap-3"
              }
            >
              <div
                className={
                  fullScreenMode
                    ? "relative h-full w-full overflow-hidden bg-black"
                    : "relative aspect-[3/4] w-full max-w-sm overflow-hidden rounded-3xl border border-line bg-stage"
                }
              >
                <video
                  ref={remoteVideoRef}
                  autoPlay
                  playsInline
                  muted
                  className="h-full w-full object-cover"
                />
                <video
                  ref={localVideoRef}
                  autoPlay
                  playsInline
                  muted
                  className={`absolute bottom-3 left-3 h-24 w-18 rounded-xl border border-line bg-black object-cover shadow-md ${
                    stage === "live" ? "opacity-90" : "opacity-100"
                  }`}
                />

                {(stage === "connecting" || switchingVariant) && (
                  <div className="absolute inset-0 flex flex-col items-center justify-center bg-paper/75 backdrop-blur-sm">
                    <div className="h-9 w-9 animate-spin rounded-full border-2 border-brand border-t-transparent" />
                    <p className="mt-3 text-sm font-medium text-ink">
                      {switchingVariant
                        ? isEn
                          ? "Switching garment..."
                          : "جاري تبديل اللون / الشكل..."
                        : isEn
                        ? "Preparing live try-on..."
                        : "جاري تجهيز المعاينة المباشرة..."}
                    </p>
                  </div>
                )}

                <button
                  type="button"
                  onClick={toggleCamera}
                  className="absolute top-3 left-3 flex h-10 items-center gap-1.5 rounded-full border border-line bg-paper/85 px-3 text-xs font-medium text-ink backdrop-blur-md transition-colors hover:border-brand"
                >
                  <span>{isEn ? "Flip Camera" : "تبديل الكاميرا"}</span>
                </button>

                {variants.length > 1 && (
                  <div className="absolute inset-x-3 bottom-3 flex justify-center">
                    <div className="flex max-w-full gap-1.5 overflow-x-auto rounded-full border border-line bg-paper/85 px-2.5 py-1.5 backdrop-blur-md">
                      {variants.map((v) => {
                        const isSelected = activeColor === v.label || activeImage === v.image;
                        return (
                          <button
                            key={`live-${v.label}-${v.image}`}
                            type="button"
                            disabled={switchingVariant}
                            onClick={() => handleSelectVariant(v)}
                            className={`inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold transition-colors ${
                              isSelected
                                ? "bg-brand text-white"
                                : "bg-stage text-ink hover:bg-card"
                            }`}
                          >
                            {v.swatch && (
                              <span
                                className="h-2.5 w-2.5 rounded-full border border-white/50"
                                style={{ backgroundColor: v.swatch }}
                              />
                            )}
                            <span>{v.label}</span>
                          </button>
                        );
                      })}
                    </div>
                  </div>
                )}
              </div>

              {!fullScreenMode && (
                <p className="text-center text-xs text-muted">{disclaimer}</p>
              )}
            </div>
          )}

          {stage === "error" && (
            <div className="flex flex-col items-center py-6 text-center">
              <p className="max-w-md text-sm font-medium text-brand">{errorMsg}</p>
              <div className="mt-5 flex gap-3">
                <button
                  type="button"
                  onClick={() => startSession(facingMode)}
                  className="flex h-10 items-center rounded-full bg-ink px-5 text-xs font-semibold text-on-ink hover:bg-brand hover:text-white"
                >
                  {isEn ? "Try Again" : "إعادة المحاولة"}
                </button>
                <button
                  type="button"
                  onClick={handleClose}
                  className="flex h-10 items-center rounded-full border border-line bg-stage px-5 text-xs font-medium text-ink"
                >
                  {isEn ? "Close" : "إغلاق"}
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>,
    document.body
  );
}
