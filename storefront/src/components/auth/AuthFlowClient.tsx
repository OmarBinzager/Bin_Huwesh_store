"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { useI18n } from "@/lib/i18n/context";
import {
  checkEmail,
  checkPhone,
  clearAuthSession,
  existingAccountCheck,
  extractApiError,
  fetchCustomerInfo,
  getAuthToken,
  getAuthUser,
  loginWithPassword,
  registerCustomer,
  registerWithSocialMedia,
  registrationWithOTP,
  setAuthToken,
  setAuthUser,
  verifyEmail,
  verifyOTP,
  verifyPhone,
  type AuthConfig,
  type CustomerProfile,
  type SocialMedium,
} from "@/lib/auth-api";
import { SocialAuthButtons } from "@/components/auth/SocialAuthButtons";

type AuthMode = "login" | "register";
type LoginMethod = "password" | "otp";
type FlowStage =
  | "auth"
  | "verify_otp"
  | "complete_otp_profile"
  | "complete_social_profile"
  | "existing_social_account";

type OtpPurpose = "otp_login" | "verify_phone" | "verify_email";

export function AuthFlowClient({
  initialMode,
  initialConfig,
}: {
  initialMode: AuthMode;
  initialConfig: AuthConfig;
}) {
  const { locale, dir, t } = useI18n();

  const manualEnabled =
    initialConfig.customer_login?.login_option?.manual_login !== 0;
  const otpEnabled = initialConfig.customer_login?.login_option?.otp_login === 1;
  const socialEnabled =
    initialConfig.customer_login?.login_option?.social_media_login === 1 &&
    Boolean(
      initialConfig.customer_login?.social_media_login_options?.google === 1 ||
        initialConfig.customer_login?.social_media_login_options?.facebook === 1 ||
        initialConfig.customer_login?.social_media_login_options?.apple === 1
    );
  const noMethodsEnabled = !manualEnabled && !otpEnabled && !socialEnabled;

  const [token, setToken] = useState<string | null>(() => getAuthToken());
  const [user, setUser] = useState<CustomerProfile | null>(() => getAuthUser());

  const [mode, setMode] = useState<AuthMode>(initialMode);
  const [loginMethod, setLoginMethod] = useState<LoginMethod>(
    manualEnabled ? "password" : "otp"
  );
  const [stage, setStage] = useState<FlowStage>("auth");

  // Form states
  const [identifier, setIdentifier] = useState("");
  const [password, setPassword] = useState("");
  const [firstName, setFirstName] = useState("");
  const [lastName, setLastName] = useState("");
  const [regPhone, setRegPhone] = useState("");
  const [regPassword, setRegPassword] = useState("");

  // OTP & Verification states
  const [otpPhone, setOtpPhone] = useState("");
  const [otpTarget, setOtpTarget] = useState("");
  const [otpPurpose, setOtpPurpose] = useState<OtpPurpose>("otp_login");
  const [otpCode, setOtpCode] = useState("");
  const [resendLeft, setResendLeft] = useState(0);

  // Complete profile / Social states
  const [fullName, setFullName] = useState("");
  const [socialEmail, setSocialEmail] = useState("");
  const [socialPhone, setSocialPhone] = useState("");
  const [socialMedium, setSocialMedium] = useState<SocialMedium>("google");
  const [pendingCreds, setPendingCreds] = useState<{
    email_or_phone: string;
    password: string;
    type: "phone" | "email";
  } | null>(null);

  const [busy, setBusy] = useState(false);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);
  const [infoMsg, setInfoMsg] = useState<string | null>(null);

  useEffect(() => {
    if (!token) return;
    let active = true;
    fetchCustomerInfo(token, locale)
      .then((profile) => {
        if (!active || !profile) return;
        setAuthUser(profile);
        setUser(profile);
      })
      .catch(() => {
        /* ignore stale profile fetch errors */
      });
    return () => {
      active = false;
    };
  }, [token, locale]);

  useEffect(() => {
    if (resendLeft <= 0) return;
    const timer = setInterval(() => {
      setResendLeft((prev) => (prev > 0 ? prev - 1 : 0));
    }, 1000);
    return () => clearInterval(timer);
  }, [resendLeft]);

  const finishLogin = async (newToken: string) => {
    setAuthToken(newToken);
    setToken(newToken);
    const profile = await fetchCustomerInfo(newToken, locale);
    if (profile) {
      setAuthUser(profile);
      setUser(profile);
    }
    setStage("auth");
    setErrorMsg(null);
    setInfoMsg(t.modals.loginSuccess);
  };

  const handleLogout = () => {
    clearAuthSession();
    setToken(null);
    setUser(null);
    setInfoMsg(t.modals.logoutSuccess);
    setErrorMsg(null);
  };

  const startOtpCountdown = () => {
    setResendLeft(initialConfig.otp_resend_time || 60);
  };

  const handlePasswordLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setBusy(true);
    setErrorMsg(null);
    setInfoMsg(null);
    const trimmed = identifier.trim();
    const type: "phone" | "email" = trimmed.includes("@") ? "email" : "phone";

    try {
      const res = await loginWithPassword(
        { email_or_phone: trimmed, password, type },
        locale
      );
      if (res.ok && res.data.token) {
        await finishLogin(res.data.token);
        return;
      }
      if (res.data.temporary_token) {
        setPendingCreds({ email_or_phone: trimmed, password, type });
        setOtpTarget(trimmed);
        setOtpCode("");
        if (type === "email") {
          setOtpPurpose("verify_email");
          await checkEmail(trimmed, locale);
        } else {
          setOtpPurpose("verify_phone");
          await checkPhone(trimmed, locale);
        }
        startOtpCountdown();
        setStage("verify_otp");
        return;
      }
      setErrorMsg(extractApiError(res.data, t.modals.loginError));
    } catch (err) {
      setErrorMsg((err as Error).message || t.modals.loginError);
    } finally {
      setBusy(false);
    }
  };

  const handleRegister = async (e: React.FormEvent) => {
    e.preventDefault();
    setBusy(true);
    setErrorMsg(null);
    setInfoMsg(null);
    const cleanPhone = regPhone.trim();

    try {
      const res = await registerCustomer(
        {
          f_name: firstName.trim(),
          l_name: lastName.trim(),
          phone: cleanPhone,
          password: regPassword,
        },
        locale
      );
      if (res.ok && res.data.token) {
        await finishLogin(res.data.token);
        return;
      }
      if (res.data.temporary_token) {
        setPendingCreds({
          email_or_phone: cleanPhone,
          password: regPassword,
          type: "phone",
        });
        setOtpTarget(cleanPhone);
        setOtpPurpose("verify_phone");
        setOtpCode("");
        startOtpCountdown();
        setStage("verify_otp");
        return;
      }
      setErrorMsg(extractApiError(res.data, t.modals.loginError));
    } catch (err) {
      setErrorMsg((err as Error).message || t.modals.loginError);
    } finally {
      setBusy(false);
    }
  };

  const handleSendLoginOtp = async (e: React.FormEvent) => {
    e.preventDefault();
    setBusy(true);
    setErrorMsg(null);
    setInfoMsg(null);
    const cleanPhone = otpPhone.trim();

    try {
      const res = await checkPhone(cleanPhone, locale);
      if (!res.ok) {
        setErrorMsg(extractApiError(res.data, t.modals.loginError));
        return;
      }
      setOtpTarget(cleanPhone);
      setOtpPurpose("otp_login");
      setOtpCode("");
      startOtpCountdown();
      setStage("verify_otp");
    } catch (err) {
      setErrorMsg((err as Error).message || t.modals.loginError);
    } finally {
      setBusy(false);
    }
  };

  const handleVerifyOtpSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setBusy(true);
    setErrorMsg(null);
    setInfoMsg(null);
    const cleanCode = otpCode.trim();

    try {
      if (otpPurpose === "otp_login") {
        const res = await verifyOTP(
          { phone: otpTarget, token: cleanCode },
          locale
        );
        if (res.ok && res.data.token) {
          await finishLogin(res.data.token);
          return;
        }
        if (res.ok && res.data.temporary_token) {
          setStage("complete_otp_profile");
          return;
        }
        setErrorMsg(extractApiError(res.data, t.modals.loginError));
        return;
      }

      if (otpPurpose === "verify_phone") {
        const res = await verifyPhone(
          { phone: otpTarget, token: cleanCode },
          locale
        );
        if (!res.ok) {
          setErrorMsg(extractApiError(res.data, t.modals.loginError));
          return;
        }
        if (res.data.token) {
          await finishLogin(res.data.token);
          return;
        }
        if (pendingCreds) {
          const loginRes = await loginWithPassword(pendingCreds, locale);
          if (loginRes.ok && loginRes.data.token) {
            await finishLogin(loginRes.data.token);
            return;
          }
        }
        setStage("auth");
        setMode("login");
        setInfoMsg(t.modals.loginSuccess);
        return;
      }

      const res = await verifyEmail(
        { email: otpTarget, token: cleanCode },
        locale
      );
      if (!res.ok) {
        setErrorMsg(extractApiError(res.data, t.modals.loginError));
        return;
      }
      if (res.data.token) {
        await finishLogin(res.data.token);
        return;
      }
      if (pendingCreds) {
        const loginRes = await loginWithPassword(pendingCreds, locale);
        if (loginRes.ok && loginRes.data.token) {
          await finishLogin(loginRes.data.token);
          return;
        }
      }
      setStage("auth");
      setMode("login");
      setInfoMsg(t.modals.loginSuccess);
    } catch (err) {
      setErrorMsg((err as Error).message || t.modals.loginError);
    } finally {
      setBusy(false);
    }
  };

  const handleResendOtp = async () => {
    if (resendLeft > 0 || busy) return;
    setBusy(true);
    setErrorMsg(null);
    try {
      const res =
        otpPurpose === "verify_email"
          ? await checkEmail(otpTarget, locale)
          : await checkPhone(otpTarget, locale);
      if (!res.ok) {
        setErrorMsg(extractApiError(res.data, t.modals.loginError));
        return;
      }
      startOtpCountdown();
    } catch (err) {
      setErrorMsg((err as Error).message || t.modals.loginError);
    } finally {
      setBusy(false);
    }
  };

  const handleCompleteOtpProfile = async (e: React.FormEvent) => {
    e.preventDefault();
    setBusy(true);
    setErrorMsg(null);
    try {
      const res = await registrationWithOTP(
        {
          name: fullName.trim(),
          phone: otpTarget,
          email: socialEmail.trim() || undefined,
        },
        locale
      );
      if (res.ok && res.data.token) {
        await finishLogin(res.data.token);
        return;
      }
      setErrorMsg(extractApiError(res.data, t.modals.loginError));
    } catch (err) {
      setErrorMsg((err as Error).message || t.modals.loginError);
    } finally {
      setBusy(false);
    }
  };

  const handleCompleteSocialProfile = async (e: React.FormEvent) => {
    e.preventDefault();
    setBusy(true);
    setErrorMsg(null);
    try {
      const res = await registerWithSocialMedia(
        {
          name: fullName.trim(),
          email: socialEmail.trim(),
          phone: socialPhone.trim(),
        },
        locale
      );
      if (res.ok && res.data.token) {
        await finishLogin(res.data.token);
        return;
      }
      setErrorMsg(extractApiError(res.data, t.modals.loginError));
    } catch (err) {
      setErrorMsg((err as Error).message || t.modals.loginError);
    } finally {
      setBusy(false);
    }
  };

  const handleExistingAccountChoice = async (accept: boolean) => {
    if (!accept) {
      setStage("auth");
      return;
    }
    setBusy(true);
    setErrorMsg(null);
    try {
      const res = await existingAccountCheck(
        {
          email: socialEmail,
          user_response: 1,
          medium: socialMedium,
        },
        locale
      );
      if (res.ok && res.data.token) {
        await finishLogin(res.data.token);
        return;
      }
      setErrorMsg(extractApiError(res.data, t.modals.loginError));
    } catch (err) {
      setErrorMsg((err as Error).message || t.modals.loginError);
    } finally {
      setBusy(false);
    }
  };

  // Logged-in state card
  if (token) {
    const displayName = user
      ? `${user.f_name || ""} ${user.l_name || ""}`.trim() ||
        user.phone ||
        user.email ||
        ""
      : "";

    return (
      <div
        dir={dir}
        className="mx-auto w-full max-w-lg rounded-3xl border border-line bg-card p-6 sm:p-8 shadow-sm space-y-6"
      >
        <div className="space-y-2 text-center">
          <span className="inline-flex rounded-full bg-stage px-3.5 py-1 text-xs font-medium text-muted">
            {t.auth.accountPageTitle}
          </span>
          <h1 className="font-display text-2xl sm:text-3xl font-bold text-ink">
            {t.auth.loggedInAs} {displayName}
          </h1>
          <p className="text-sm text-muted">
            {user?.phone || user?.email || t.modals.loggedInMsg}
          </p>
        </div>

        {infoMsg && (
          <p className="rounded-2xl bg-stage px-4 py-3 text-center text-sm text-ink">
            {infoMsg}
          </p>
        )}

        <div className="grid gap-3 sm:grid-cols-2">
          <Link
            href="/checkout"
            className="flex items-center justify-center rounded-full bg-ink px-5 py-3 text-sm font-medium text-on-ink transition hover:opacity-90"
          >
            {t.auth.goToCheckout}
          </Link>
          <Link
            href="/"
            className="flex items-center justify-center rounded-full border border-line bg-card px-5 py-3 text-sm font-medium text-ink transition hover:bg-stage"
          >
            {t.auth.backToHome}
          </Link>
        </div>

        <button
          type="button"
          onClick={handleLogout}
          className="w-full rounded-full border border-line px-5 py-2.5 text-sm font-medium text-muted transition hover:bg-stage hover:text-ink"
        >
          {t.modals.logout}
        </button>
      </div>
    );
  }

  return (
    <div
      dir={dir}
      className="mx-auto w-full max-w-lg rounded-3xl border border-line bg-card p-6 sm:p-8 shadow-sm space-y-6"
    >
      {/* Header */}
      <div className="space-y-1.5">
        <h1 className="font-display text-2xl sm:text-3xl font-bold text-ink">
          {stage === "verify_otp"
            ? t.auth.otpTitle
            : stage === "complete_otp_profile" ||
                stage === "complete_social_profile"
              ? t.auth.completeProfileTitle
              : stage === "existing_social_account"
                ? t.auth.existingAccountTitle
                : mode === "register"
                  ? t.auth.registerTitle
                  : t.auth.loginTitle}
        </h1>
        <p className="text-sm text-muted">
          {stage === "verify_otp"
            ? t.auth.otpSentTo(otpTarget)
            : stage === "complete_otp_profile" ||
                stage === "complete_social_profile"
              ? t.auth.completeProfileDesc
              : stage === "existing_social_account"
                ? t.auth.existingAccountDesc(socialEmail)
                : mode === "register"
                  ? t.auth.registerSubtitle
                  : t.auth.loginSubtitle}
        </p>
      </div>

      {/* Mode Switcher (Login / Register) when on main auth stage */}
      {stage === "auth" && (
        <div className="grid grid-cols-2 rounded-full bg-stage p-1">
          <button
            type="button"
            onClick={() => {
              setMode("login");
              setErrorMsg(null);
            }}
            className={`rounded-full py-2 text-sm font-medium transition ${
              mode === "login"
                ? "bg-card text-ink shadow-xs"
                : "text-muted hover:text-ink"
            }`}
          >
            {t.auth.loginTitle}
          </button>
          <button
            type="button"
            onClick={() => {
              setMode("register");
              setErrorMsg(null);
            }}
            className={`rounded-full py-2 text-sm font-medium transition ${
              mode === "register"
                ? "bg-card text-ink shadow-xs"
                : "text-muted hover:text-ink"
            }`}
          >
            {t.auth.registerTitle}
          </button>
        </div>
      )}

      {/* Alerts */}
      {errorMsg && (
        <div className="rounded-2xl border border-red-300/60 bg-red-500/10 px-4 py-3 text-sm text-red-600 dark:text-red-400">
          {errorMsg}
        </div>
      )}
      {infoMsg && (
        <div className="rounded-2xl bg-stage px-4 py-3 text-sm text-ink">
          {infoMsg}
        </div>
      )}

      {/* Stage: Verify OTP */}
      {stage === "verify_otp" && (
        <form onSubmit={handleVerifyOtpSubmit} className="space-y-4">
          <div className="space-y-1.5">
            <label className="block text-xs font-medium text-muted">
              {t.auth.otpCodeLabel}
            </label>
            <input
              type="text"
              inputMode="numeric"
              required
              dir="ltr"
              value={otpCode}
              onChange={(e) => setOtpCode(e.target.value)}
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
            {busy ? t.modals.loggingIn : t.auth.verifyOtpBtn}
          </button>

          <div className="flex items-center justify-between text-xs">
            <button
              type="button"
              disabled={resendLeft > 0 || busy}
              onClick={handleResendOtp}
              className="font-medium text-ink underline-offset-4 hover:underline disabled:text-muted disabled:no-underline"
            >
              {resendLeft > 0
                ? t.auth.resendIn(resendLeft)
                : t.auth.resendOtp}
            </button>

            <button
              type="button"
              onClick={() => {
                setStage("auth");
                setErrorMsg(null);
              }}
              className="text-muted hover:text-ink"
            >
              {t.modals.close}
            </button>
          </div>
        </form>
      )}

      {/* Stage: Complete OTP Profile */}
      {stage === "complete_otp_profile" && (
        <form onSubmit={handleCompleteOtpProfile} className="space-y-4">
          <div className="space-y-1.5">
            <label className="block text-xs font-medium text-muted">
              {t.auth.fullNameLabel}
            </label>
            <input
              type="text"
              required
              value={fullName}
              onChange={(e) => setFullName(e.target.value)}
              className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-sm text-ink outline-none focus:border-ink"
            />
          </div>
          <div className="space-y-1.5">
            <label className="block text-xs font-medium text-muted">
              {t.auth.emailLabel}
            </label>
            <input
              type="email"
              dir="ltr"
              value={socialEmail}
              onChange={(e) => setSocialEmail(e.target.value)}
              placeholder={t.auth.emailPlaceholder}
              className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-sm text-ink outline-none focus:border-ink"
            />
          </div>
          <button
            type="submit"
            disabled={busy}
            className="w-full rounded-full bg-ink px-5 py-3 text-sm font-medium text-on-ink transition hover:opacity-90 disabled:opacity-60"
          >
            {busy ? t.modals.loggingIn : t.auth.completeProfileBtn}
          </button>
        </form>
      )}

      {/* Stage: Complete Social Profile */}
      {stage === "complete_social_profile" && (
        <form onSubmit={handleCompleteSocialProfile} className="space-y-4">
          <div className="space-y-1.5">
            <label className="block text-xs font-medium text-muted">
              {t.auth.fullNameLabel}
            </label>
            <input
              type="text"
              required
              value={fullName}
              onChange={(e) => setFullName(e.target.value)}
              className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-sm text-ink outline-none focus:border-ink"
            />
          </div>
          <div className="space-y-1.5">
            <label className="block text-xs font-medium text-muted">
              {t.auth.emailLabel}
            </label>
            <input
              type="email"
              required
              dir="ltr"
              value={socialEmail}
              onChange={(e) => setSocialEmail(e.target.value)}
              placeholder={t.auth.emailPlaceholder}
              className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-sm text-ink outline-none focus:border-ink"
            />
          </div>
          <div className="space-y-1.5">
            <label className="block text-xs font-medium text-muted">
              {t.auth.phoneLabel}
            </label>
            <input
              type="tel"
              required
              dir="ltr"
              value={socialPhone}
              onChange={(e) => setSocialPhone(e.target.value)}
              placeholder={t.auth.phonePlaceholder}
              className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-sm text-ink outline-none focus:border-ink"
            />
          </div>
          <button
            type="submit"
            disabled={busy}
            className="w-full rounded-full bg-ink px-5 py-3 text-sm font-medium text-on-ink transition hover:opacity-90 disabled:opacity-60"
          >
            {busy ? t.modals.loggingIn : t.auth.completeProfileBtn}
          </button>
        </form>
      )}

      {/* Stage: Existing Social Account */}
      {stage === "existing_social_account" && (
        <div className="grid gap-3 sm:grid-cols-2">
          <button
            type="button"
            disabled={busy}
            onClick={() => handleExistingAccountChoice(true)}
            className="rounded-full bg-ink px-5 py-3 text-sm font-medium text-on-ink transition hover:opacity-90 disabled:opacity-60"
          >
            {busy ? t.modals.loggingIn : t.auth.linkAccountYes}
          </button>
          <button
            type="button"
            disabled={busy}
            onClick={() => handleExistingAccountChoice(false)}
            className="rounded-full border border-line bg-card px-5 py-3 text-sm font-medium text-ink transition hover:bg-stage"
          >
            {t.auth.linkAccountNo}
          </button>
        </div>
      )}

      {/* Stage: Auth (Login or Register) */}
      {stage === "auth" && noMethodsEnabled && (
        <div className="rounded-2xl bg-stage px-4 py-6 text-center text-sm text-muted">
          {t.auth.noMethodsEnabled}
        </div>
      )}

      {stage === "auth" && !noMethodsEnabled && mode === "login" && (
        <div className="space-y-5">
          {/* Method tabs if both manual & OTP are enabled */}
          {manualEnabled && otpEnabled && (
            <div className="flex gap-2 border-b border-line pb-3">
              <button
                type="button"
                onClick={() => {
                  setLoginMethod("password");
                  setErrorMsg(null);
                }}
                className={`rounded-full px-4 py-1.5 text-xs font-medium transition ${
                  loginMethod === "password"
                    ? "bg-ink text-on-ink"
                    : "bg-stage text-muted hover:text-ink"
                }`}
              >
                {t.auth.tabPassword}
              </button>
              <button
                type="button"
                onClick={() => {
                  setLoginMethod("otp");
                  setErrorMsg(null);
                }}
                className={`rounded-full px-4 py-1.5 text-xs font-medium transition ${
                  loginMethod === "otp"
                    ? "bg-ink text-on-ink"
                    : "bg-stage text-muted hover:text-ink"
                }`}
              >
                {t.auth.tabOtp}
              </button>
            </div>
          )}

          {manualEnabled && loginMethod === "password" && (
            <form onSubmit={handlePasswordLogin} className="space-y-4">
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
              <div className="space-y-1.5">
                <div className="flex items-center justify-between">
                  <label className="block text-xs font-medium text-muted">
                    {t.auth.passwordLabel}
                  </label>
                  <Link
                    href="/forgot-password"
                    className="text-xs font-medium text-muted hover:text-ink underline-offset-4 hover:underline"
                  >
                    {t.auth.forgotPasswordLink}
                  </Link>
                </div>
                <input
                  type="password"
                  required
                  dir="ltr"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-sm text-ink outline-none focus:border-ink"
                />
              </div>
              <button
                type="submit"
                disabled={busy}
                className="w-full rounded-full bg-ink px-5 py-3 text-sm font-medium text-on-ink transition hover:opacity-90 disabled:opacity-60"
              >
                {busy ? t.modals.loggingIn : t.auth.loginBtn}
              </button>
            </form>
          )}

          {otpEnabled && loginMethod === "otp" && (
            <form onSubmit={handleSendLoginOtp} className="space-y-4">
              <div className="space-y-1.5">
                <label className="block text-xs font-medium text-muted">
                  {t.auth.phoneLabel}
                </label>
                <input
                  type="tel"
                  required
                  dir="ltr"
                  value={otpPhone}
                  onChange={(e) => setOtpPhone(e.target.value)}
                  placeholder={t.auth.phonePlaceholder}
                  className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-sm text-ink outline-none focus:border-ink"
                />
              </div>
              <button
                type="submit"
                disabled={busy}
                className="w-full rounded-full bg-ink px-5 py-3 text-sm font-medium text-on-ink transition hover:opacity-90 disabled:opacity-60"
              >
                {busy ? t.modals.loggingIn : t.auth.sendOtpBtn}
              </button>
            </form>
          )}

          {socialEnabled && (
            <SocialAuthButtons
              config={initialConfig}
              disabled={busy}
              onSuccess={finishLogin}
              onNeedProfile={({ tempToken, email, medium }) => {
                void tempToken;
                setSocialEmail(email || "");
                setSocialMedium(medium);
                setStage("complete_social_profile");
              }}
              onExistingAccount={({ email, medium }) => {
                setSocialEmail(email);
                setSocialMedium(medium);
                setStage("existing_social_account");
              }}
              onError={(msg) => setErrorMsg(msg)}
            />
          )}
        </div>
      )}

      {stage === "auth" && !noMethodsEnabled && mode === "register" && (
        <div className="space-y-5">
          {manualEnabled ? (
            <form onSubmit={handleRegister} className="space-y-4">
              <div className="grid gap-3 sm:grid-cols-2">
                <div className="space-y-1.5">
                  <label className="block text-xs font-medium text-muted">
                    {t.auth.firstNameLabel}
                  </label>
                  <input
                    type="text"
                    required
                    value={firstName}
                    onChange={(e) => setFirstName(e.target.value)}
                    className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-sm text-ink outline-none focus:border-ink"
                  />
                </div>
                <div className="space-y-1.5">
                  <label className="block text-xs font-medium text-muted">
                    {t.auth.lastNameLabel}
                  </label>
                  <input
                    type="text"
                    required
                    value={lastName}
                    onChange={(e) => setLastName(e.target.value)}
                    className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-sm text-ink outline-none focus:border-ink"
                  />
                </div>
              </div>
              <div className="space-y-1.5">
                <label className="block text-xs font-medium text-muted">
                  {t.auth.phoneLabel}
                </label>
                <input
                  type="tel"
                  required
                  dir="ltr"
                  value={regPhone}
                  onChange={(e) => setRegPhone(e.target.value)}
                  placeholder={t.auth.phonePlaceholder}
                  className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-sm text-ink outline-none focus:border-ink"
                />
              </div>
              <div className="space-y-1.5">
                <label className="block text-xs font-medium text-muted">
                  {t.auth.passwordLabel}
                </label>
                <input
                  type="password"
                  required
                  minLength={6}
                  dir="ltr"
                  value={regPassword}
                  onChange={(e) => setRegPassword(e.target.value)}
                  className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-sm text-ink outline-none focus:border-ink"
                />
              </div>
              <button
                type="submit"
                disabled={busy}
                className="w-full rounded-full bg-ink px-5 py-3 text-sm font-medium text-on-ink transition hover:opacity-90 disabled:opacity-60"
              >
                {busy ? t.modals.loggingIn : t.auth.registerBtn}
              </button>
            </form>
          ) : (
            otpEnabled && (
              <form onSubmit={handleSendLoginOtp} className="space-y-4">
                <div className="space-y-1.5">
                  <label className="block text-xs font-medium text-muted">
                    {t.auth.phoneLabel}
                  </label>
                  <input
                    type="tel"
                    required
                    dir="ltr"
                    value={otpPhone}
                    onChange={(e) => setOtpPhone(e.target.value)}
                    placeholder={t.auth.phonePlaceholder}
                    className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-sm text-ink outline-none focus:border-ink"
                  />
                </div>
                <button
                  type="submit"
                  disabled={busy}
                  className="w-full rounded-full bg-ink px-5 py-3 text-sm font-medium text-on-ink transition hover:opacity-90 disabled:opacity-60"
                >
                  {busy ? t.modals.loggingIn : t.auth.sendOtpBtn}
                </button>
              </form>
            )
          )}

          {socialEnabled && (
            <SocialAuthButtons
              config={initialConfig}
              disabled={busy}
              onSuccess={finishLogin}
              onNeedProfile={({ tempToken, email, medium }) => {
                void tempToken;
                setSocialEmail(email || "");
                setSocialMedium(medium);
                setStage("complete_social_profile");
              }}
              onExistingAccount={({ email, medium }) => {
                setSocialEmail(email);
                setSocialMedium(medium);
                setStage("existing_social_account");
              }}
              onError={(msg) => setErrorMsg(msg)}
            />
          )}
        </div>
      )}
    </div>
  );
}
