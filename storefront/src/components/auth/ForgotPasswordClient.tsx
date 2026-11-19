"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { useI18n } from "@/lib/i18n/context";
import {
  extractApiError,
  forgotPasswordRequest,
  resetPasswordSubmit,
  verifyResetToken,
  type AuthConfig,
} from "@/lib/auth-api";

type ResetStep = "request" | "verify" | "reset" | "done";

export function ForgotPasswordClient({
  initialConfig,
}: {
  initialConfig: AuthConfig;
}) {
  const { locale, dir, t } = useI18n();

  const [step, setStep] = useState<ResetStep>("request");
  const [identifier, setIdentifier] = useState("");
  const [resetType, setResetType] = useState<"phone" | "email">("phone");
  const [resetToken, setResetToken] = useState("");
  const [password, setPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [resendLeft, setResendLeft] = useState(0);

  const [busy, setBusy] = useState(false);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  useEffect(() => {
    if (resendLeft <= 0) return;
    const timer = setInterval(() => {
      setResendLeft((prev) => (prev > 0 ? prev - 1 : 0));
    }, 1000);
    return () => clearInterval(timer);
  }, [resendLeft]);

  const startCountdown = () => {
    setResendLeft(initialConfig.otp_resend_time || 60);
  };

  const handleRequest = async (e: React.FormEvent) => {
    e.preventDefault();
    setBusy(true);
    setErrorMsg(null);
    const trimmed = identifier.trim();
    const detectedType: "phone" | "email" = trimmed.includes("@")
      ? "email"
      : "phone";
    setResetType(detectedType);

    try {
      const res = await forgotPasswordRequest(
        { email_or_phone: trimmed, type: detectedType },
        locale
      );
      if (!res.ok) {
        setErrorMsg(extractApiError(res.data, t.modals.loginError));
        return;
      }
      startCountdown();
      setStep("verify");
    } catch (err) {
      setErrorMsg((err as Error).message || t.modals.loginError);
    } finally {
      setBusy(false);
    }
  };

  const handleVerify = async (e: React.FormEvent) => {
    e.preventDefault();
    setBusy(true);
    setErrorMsg(null);
    try {
      const res = await verifyResetToken(
        {
          email_or_phone: identifier.trim(),
          reset_token: resetToken.trim(),
        },
        locale
      );
      if (!res.ok) {
        setErrorMsg(extractApiError(res.data, t.modals.loginError));
        return;
      }
      setStep("reset");
    } catch (err) {
      setErrorMsg((err as Error).message || t.modals.loginError);
    } finally {
      setBusy(false);
    }
  };

  const handleReset = async (e: React.FormEvent) => {
    e.preventDefault();
    if (password !== confirmPassword) {
      setErrorMsg(t.auth.passwordMismatch);
      return;
    }
    setBusy(true);
    setErrorMsg(null);
    try {
      const res = await resetPasswordSubmit(
        {
          email_or_phone: identifier.trim(),
          reset_token: resetToken.trim(),
          type: resetType,
          password,
          confirm_password: confirmPassword,
        },
        locale
      );
      if (!res.ok) {
        setErrorMsg(extractApiError(res.data, t.modals.loginError));
        return;
      }
      setStep("done");
    } catch (err) {
      setErrorMsg((err as Error).message || t.modals.loginError);
    } finally {
      setBusy(false);
    }
  };

  const handleResend = async () => {
    if (resendLeft > 0 || busy) return;
    setBusy(true);
    setErrorMsg(null);
    try {
      const res = await forgotPasswordRequest(
        { email_or_phone: identifier.trim(), type: resetType },
        locale
      );
      if (!res.ok) {
        setErrorMsg(extractApiError(res.data, t.modals.loginError));
        return;
      }
      startCountdown();
    } catch (err) {
      setErrorMsg((err as Error).message || t.modals.loginError);
    } finally {
      setBusy(false);
    }
  };

  return (
    <div
      dir={dir}
      className="mx-auto w-full max-w-lg rounded-3xl border border-line bg-card p-6 sm:p-8 shadow-sm space-y-6"
    >
      <div className="space-y-1.5">
        <h1 className="font-display text-2xl sm:text-3xl font-bold text-ink">
          {step === "done" ? t.auth.resetSuccessTitle : t.auth.forgotTitle}
        </h1>
        <p className="text-sm text-muted">
          {step === "done"
            ? t.auth.resetSuccessDesc
            : step === "verify"
              ? t.auth.otpSentTo(identifier)
              : t.auth.forgotSubtitle}
        </p>
      </div>

      {errorMsg && (
        <div className="rounded-2xl border border-red-300/60 bg-red-500/10 px-4 py-3 text-sm text-red-600 dark:text-red-400">
          {errorMsg}
        </div>
      )}

      {step === "request" && (
        <form onSubmit={handleRequest} className="space-y-4">
          <div className="space-y-1.5">
            <label className="block text-xs font-medium text-muted">
              {t.auth.phoneOrEmail}
            </label>
            <input
              type="text"
              required
              dir="ltr"
              value={identifier}
              onChange={(e) => setIdentifier(e.target.value)}
              placeholder={t.auth.phonePlaceholder}
              className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-sm text-ink outline-none focus:border-ink"
            />
          </div>
          <button
            type="submit"
            disabled={busy}
            className="w-full rounded-full bg-ink px-5 py-3 text-sm font-medium text-on-ink transition hover:opacity-90 disabled:opacity-60"
          >
            {busy ? t.modals.loggingIn : t.auth.resetStep1Btn}
          </button>
          <div className="text-center">
            <Link
              href="/login"
              className="text-xs font-medium text-muted hover:text-ink underline-offset-4 hover:underline"
            >
              {t.auth.loginTitle}
            </Link>
          </div>
        </form>
      )}

      {step === "verify" && (
        <form onSubmit={handleVerify} className="space-y-4">
          <div className="space-y-1.5">
            <label className="block text-xs font-medium text-muted">
              {t.auth.otpCodeLabel}
            </label>
            <input
              type="text"
              inputMode="numeric"
              required
              dir="ltr"
              value={resetToken}
              onChange={(e) => setResetToken(e.target.value)}
              placeholder={t.auth.otpCodePlaceholder}
              className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-center text-lg tracking-widest text-ink outline-none focus:border-ink"
            />
          </div>
          <p className="text-xs text-muted">{t.auth.devOtpHint}</p>
          <button
            type="submit"
            disabled={busy}
            className="w-full rounded-full bg-ink px-5 py-3 text-sm font-medium text-on-ink transition hover:opacity-90 disabled:opacity-60"
          >
            {busy ? t.modals.loggingIn : t.auth.resetStep2Btn}
          </button>
          <div className="flex items-center justify-between text-xs">
            <button
              type="button"
              disabled={resendLeft > 0 || busy}
              onClick={handleResend}
              className="font-medium text-ink underline-offset-4 hover:underline disabled:text-muted disabled:no-underline"
            >
              {resendLeft > 0
                ? t.auth.resendIn(resendLeft)
                : t.auth.resendOtp}
            </button>
            <button
              type="button"
              onClick={() => {
                setStep("request");
                setErrorMsg(null);
              }}
              className="text-muted hover:text-ink"
            >
              {t.modals.close}
            </button>
          </div>
        </form>
      )}

      {step === "reset" && (
        <form onSubmit={handleReset} className="space-y-4">
          <div className="space-y-1.5">
            <label className="block text-xs font-medium text-muted">
              {t.auth.passwordLabel}
            </label>
            <input
              type="password"
              required
              minLength={6}
              dir="ltr"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-sm text-ink outline-none focus:border-ink"
            />
          </div>
          <div className="space-y-1.5">
            <label className="block text-xs font-medium text-muted">
              {t.auth.confirmPasswordLabel}
            </label>
            <input
              type="password"
              required
              minLength={6}
              dir="ltr"
              value={confirmPassword}
              onChange={(e) => setConfirmPassword(e.target.value)}
              className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-sm text-ink outline-none focus:border-ink"
            />
          </div>
          <button
            type="submit"
            disabled={busy}
            className="w-full rounded-full bg-ink px-5 py-3 text-sm font-medium text-on-ink transition hover:opacity-90 disabled:opacity-60"
          >
            {busy ? t.modals.loggingIn : t.auth.resetStep3Btn}
          </button>
        </form>
      )}

      {step === "done" && (
        <Link
          href="/login"
          className="flex w-full items-center justify-center rounded-full bg-ink px-5 py-3 text-sm font-medium text-on-ink transition hover:opacity-90"
        >
          {t.auth.loginTitle}
        </Link>
      )}
    </div>
  );
}
