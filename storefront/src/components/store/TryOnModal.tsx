"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import { createDecartClient, models, type RealTimeClient } from "@decartai/sdk";
import { fetchTryOnToken } from "@/lib/storefront-api";

export type TryOnModalProps = {
  open: boolean;
  onClose: () => void;
  productId: number;
  productName: string;
  garmentImage: string;
  selectedColor?: string;
  locale?: "ar" | "en";
  embedded?: boolean;
};

type Stage = "consent" | "connecting" | "live" | "error";

export default function TryOnModal({
  open,
  onClose,
  productId,
  productName,
  garmentImage,
  selectedColor,
  locale = "ar",
  embedded = false,
}: TryOnModalProps) {
  const isEn = locale === "en";
  const [stage, setStage] = useState<Stage>("consent");
  const [errorMsg, setErrorMsg] = useState<string>("");
  const [facingMode, setFacingMode] = useState<"user" | "environment">("user");
  const [secondsLeft, setSecondsLeft] = useState<number>(45);
  const [disclaimer, setDisclaimer] = useState<string>(
    isEn
      ? "Approximate visual preview only, not a size recommendation."
      : "معاينة تقريبية للشكل، وليست توصية بالمقاس."
  );

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

  const handleClose = useCallback(() => {
    stopEverything();
    setStage("consent");
    setErrorMsg("");
    onClose();
  }, [onClose, stopEverything]);

  useEffect(() => {
    if (!open) {
      stopEverything();
    }
    return () => {
      stopEverything();
    };
  }, [open, stopEverything]);

  const startSession = useCallback(
    async (mode: "user" | "environment") => {
      stopEverything();
      setStage("connecting");
      setErrorMsg("");

      try {
        if (!navigator?.mediaDevices?.getUserMedia) {
          throw new Error(
            isEn
              ? "Camera access is not supported in this browser or requires HTTPS."
              : "الكاميرا غير مدعومة في هذا المتصفح أو تتطلب اتصالاً آمناً (HTTPS)."
          );
        }

        const tokenData = await fetchTryOnToken(productId, selectedColor, locale);
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

        const imageSource = tokenData.garment_image_url || garmentImage;
        const proxiedUrl = `/api/try-on/garment-image?url=${encodeURIComponent(imageSource)}`;
        const imgRes = await fetch(proxiedUrl);
        if (!imgRes.ok) {
          throw new Error(
            isEn
              ? "Could not load the selected garment image."
              : "تعذر تحميل صورة القطعة المختارة."
          );
        }
        const imgBlob = await imgRes.blob();
        const garmentFile = new File([imgBlob], "garment.jpg", {
          type: imgBlob.type || "image/jpeg",
        });

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
              text: selectedColor
                ? `Fit this ${selectedColor} garment naturally on the person while preserving natural body movement and lighting`
                : "Fit this garment naturally on the person while preserving natural body movement and lighting",
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
        if (rawMsg.includes("Permission") || rawMsg.includes("NotAllowed")) {
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
    [garmentImage, isEn, locale, productId, selectedColor, stopEverything]
  );

  const toggleCamera = () => {
    const next = facingMode === "user" ? "environment" : "user";
    setFacingMode(next);
    if (stage === "live" || stage === "connecting") {
      startSession(next);
    }
  };

  if (!open) return null;

  const formattedTime = `00:${String(secondsLeft).padStart(2, "0")}`;

  return (
    <div
      className={
        embedded
          ? "fixed inset-0 z-50 flex flex-col bg-paper text-ink"
          : "fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-3 backdrop-blur-sm sm:p-6"
      }
      role="dialog"
      aria-modal="true"
      aria-label={isEn ? "Virtual Try-On" : "جرّبها عليك"}
      onClick={(e) => {
        if (!embedded && e.target === e.currentTarget) handleClose();
      }}
    >
      <div
        className={
          embedded
            ? "flex h-full w-full flex-col overflow-hidden bg-paper"
            : "relative flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-3xl border border-line bg-paper shadow-2xl"
        }
      >
        {/* Top Bar */}
        <div className="flex items-center justify-between gap-3 border-b border-line bg-card px-4 py-3 sm:px-6">
          <div className="flex items-center gap-3 min-w-0">
            <img
              src={garmentImage}
              alt={productName}
              className="h-11 w-9 shrink-0 rounded-xl border border-line bg-stage object-cover"
            />
            <div className="min-w-0">
              <p className="font-display text-sm font-semibold text-ink truncate">
                {isEn ? "Virtual Try-On" : "جرّبها عليك"}
              </p>
              <div className="flex items-center gap-2 text-xs text-muted truncate">
                <span className="truncate">{productName}</span>
                {selectedColor && (
                  <span className="shrink-0 rounded-full border border-line bg-stage px-2 py-0.5 text-[11px] text-ink">
                    {selectedColor}
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
        <div className="flex-1 overflow-y-auto p-4 sm:p-6">
          {stage === "consent" && (
            <div className="flex flex-col items-center text-center">
              <div className="relative mb-5 h-44 w-36 overflow-hidden rounded-2xl border border-line bg-stage">
                <img
                  src={garmentImage}
                  alt={productName}
                  className="h-full w-full object-contain p-2"
                />
              </div>

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
            <div className="flex flex-col items-center gap-3">
              <div className="relative aspect-[3/4] w-full max-w-sm overflow-hidden rounded-3xl border border-line bg-stage">
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

                {stage === "connecting" && (
                  <div className="absolute inset-0 flex flex-col items-center justify-center bg-paper/80 backdrop-blur-sm">
                    <div className="h-9 w-9 animate-spin rounded-full border-2 border-brand border-t-transparent" />
                    <p className="mt-3 text-sm font-medium text-ink">
                      {isEn ? "Preparing live try-on..." : "جاري تجهيز المعاينة المباشرة..."}
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
              </div>

              <p className="text-center text-xs text-muted">{disclaimer}</p>
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
    </div>
  );
}
