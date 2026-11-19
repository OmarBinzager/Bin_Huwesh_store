"use client";

import { useState } from "react";
import { useI18n } from "@/lib/i18n/context";
import {
  extractApiError,
  socialLogin,
  type AuthConfig,
  type SocialMedium,
} from "@/lib/auth-api";

declare global {
  interface Window {
    google?: {
      accounts?: {
        oauth2?: {
          initTokenClient: (config: {
            client_id: string;
            scope: string;
            callback: (resp: { access_token?: string; error?: string }) => void;
          }) => { requestAccessToken: () => void };
        };
      };
    };
    FB?: {
      init: (params: {
        appId: string;
        cookie?: boolean;
        xfbml?: boolean;
        version: string;
      }) => void;
      login: (
        cb: (resp: {
          authResponse?: { accessToken: string; userID: string };
        }) => void,
        opts?: { scope: string }
      ) => void;
    };
    AppleID?: {
      auth: {
        init: (params: {
          clientId: string;
          scope: string;
          redirectURI: string;
          usePopup: boolean;
        }) => void;
        signIn: () => Promise<{
          authorization?: { id_token?: string; code?: string };
          user?: { email?: string };
        }>;
      };
    };
  }
}

function loadScript(src: string, id: string): Promise<void> {
  return new Promise((resolve, reject) => {
    if (document.getElementById(id)) {
      resolve();
      return;
    }
    const script = document.createElement("script");
    script.id = id;
    script.src = src;
    script.async = true;
    script.defer = true;
    script.onload = () => resolve();
    script.onerror = () => reject(new Error(`Failed to load ${src}`));
    document.head.appendChild(script);
  });
}

export type SocialAuthButtonsProps = {
  config: AuthConfig;
  disabled?: boolean;
  onSuccess: (token: string) => Promise<void> | void;
  onNeedProfile: (info: {
    tempToken: string;
    email?: string;
    medium: SocialMedium;
  }) => void;
  onExistingAccount: (info: { email: string; medium: SocialMedium }) => void;
  onError: (message: string) => void;
};

export function SocialAuthButtons({
  config,
  disabled,
  onSuccess,
  onNeedProfile,
  onExistingAccount,
  onError,
}: SocialAuthButtonsProps) {
  const { locale, t } = useI18n();
  const [loadingProvider, setLoadingProvider] = useState<SocialMedium | null>(
    null
  );

  const socialEnabled =
    config.customer_login?.login_option?.social_media_login === 1;
  const opts = config.customer_login?.social_media_login_options;

  const googleOn = socialEnabled && opts?.google === 1;
  const facebookOn = socialEnabled && opts?.facebook === 1;
  const appleOn = socialEnabled && opts?.apple === 1;

  if (!googleOn && !facebookOn && !appleOn) {
    return null;
  }

  const submitSocialToken = async (
    medium: SocialMedium,
    token: string,
    uniqueId: string,
    email?: string
  ) => {
    try {
      const res = await socialLogin(
        { token, unique_id: uniqueId, email, medium },
        locale
      );
      if (res.ok && res.data.token) {
        await onSuccess(res.data.token);
        return;
      }
      if (res.data.temp_token) {
        onNeedProfile({
          tempToken: res.data.temp_token,
          email: email || res.data.user?.email || undefined,
          medium,
        });
        return;
      }
      if (res.data.user?.email) {
        onExistingAccount({ email: res.data.user.email, medium });
        return;
      }
      onError(extractApiError(res.data, t.modals.loginError));
    } catch (err) {
      onError((err as Error).message || t.modals.loginError);
    } finally {
      setLoadingProvider(null);
    }
  };

  const handleGoogle = async () => {
    const clientId = process.env.NEXT_PUBLIC_GOOGLE_CLIENT_ID;
    if (!clientId) {
      onError(t.auth.socialNotConfigured("Google"));
      return;
    }
    setLoadingProvider("google");
    try {
      await loadScript("https://accounts.google.com/gsi/client", "gsi-sdk");
      const oauth2 = window.google?.accounts?.oauth2;
      if (!oauth2) {
        throw new Error(t.auth.socialNotConfigured("Google"));
      }
      const client = oauth2.initTokenClient({
        client_id: clientId,
        scope: "email profile openid",
        callback: async (resp) => {
          if (resp.error || !resp.access_token) {
            setLoadingProvider(null);
            onError(resp.error || t.modals.loginError);
            return;
          }
          try {
            const infoRes = await fetch(
              "https://www.googleapis.com/oauth2/v3/userinfo",
              {
                headers: { Authorization: `Bearer ${resp.access_token}` },
              }
            );
            const info = (await infoRes.json()) as {
              sub?: string;
              email?: string;
            };
            await submitSocialToken(
              "google",
              resp.access_token,
              info.sub || resp.access_token.slice(0, 24),
              info.email
            );
          } catch (err) {
            setLoadingProvider(null);
            onError((err as Error).message || t.modals.loginError);
          }
        },
      });
      client.requestAccessToken();
    } catch (err) {
      setLoadingProvider(null);
      onError((err as Error).message || t.modals.loginError);
    }
  };

  const handleFacebook = async () => {
    const appId = process.env.NEXT_PUBLIC_FACEBOOK_APP_ID;
    if (!appId) {
      onError(t.auth.socialNotConfigured("Facebook"));
      return;
    }
    setLoadingProvider("facebook");
    try {
      await loadScript(
        "https://connect.facebook.net/en_US/sdk.js",
        "facebook-jssdk"
      );
      if (!window.FB) {
        throw new Error(t.auth.socialNotConfigured("Facebook"));
      }
      window.FB.init({ appId, cookie: true, xfbml: false, version: "v19.0" });
      window.FB.login(
        (resp) => {
          if (!resp.authResponse?.accessToken) {
            setLoadingProvider(null);
            onError(t.modals.loginError);
            return;
          }
          void submitSocialToken(
            "facebook",
            resp.authResponse.accessToken,
            resp.authResponse.userID
          );
        },
        { scope: "email,public_profile" }
      );
    } catch (err) {
      setLoadingProvider(null);
      onError((err as Error).message || t.modals.loginError);
    }
  };

  const handleApple = async () => {
    const clientId =
      config.apple_login?.client_id ||
      process.env.NEXT_PUBLIC_APPLE_CLIENT_ID ||
      "";
    if (!clientId) {
      onError(t.auth.socialNotConfigured("Apple"));
      return;
    }
    setLoadingProvider("apple");
    try {
      await loadScript(
        "https://appleid.cdn-apple.com/appleauth/static/jsapi/appleid/1/en_US/appleid.auth.js",
        "apple-signin-sdk"
      );
      if (!window.AppleID?.auth) {
        throw new Error(t.auth.socialNotConfigured("Apple"));
      }
      window.AppleID.auth.init({
        clientId,
        scope: "name email",
        redirectURI: window.location.origin + "/login",
        usePopup: true,
      });
      const resp = await window.AppleID.auth.signIn();
      const idToken = resp.authorization?.id_token;
      if (!idToken) {
        setLoadingProvider(null);
        onError(t.modals.loginError);
        return;
      }
      await submitSocialToken(
        "apple",
        idToken,
        idToken.slice(0, 32),
        resp.user?.email
      );
    } catch (err) {
      setLoadingProvider(null);
      onError((err as Error).message || t.modals.loginError);
    }
  };

  const isBusy = Boolean(disabled || loadingProvider);

  return (
    <div className="space-y-4">
      <div className="relative flex items-center justify-center">
        <div className="h-px w-full bg-line" />
        <span className="bg-card px-3 text-xs font-medium text-muted shrink-0">
          {t.auth.socialDivider}
        </span>
        <div className="h-px w-full bg-line" />
      </div>

      <div className="grid gap-2.5">
        {googleOn && (
          <button
            type="button"
            disabled={isBusy}
            onClick={handleGoogle}
            className="flex w-full items-center justify-center gap-3 rounded-full border border-line bg-card px-5 py-3 text-sm font-medium text-ink transition hover:bg-stage disabled:opacity-60"
          >
            <svg className="h-4 w-4" viewBox="0 0 24 24" aria-hidden="true">
              <path
                fill="#4285F4"
                d="M23.745 12.27c0-.79-.07-1.54-.19-2.27H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.665-5.17 3.665-8.97Z"
              />
              <path
                fill="#34A853"
                d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.11-6.72-4.96H1.29v3.14C3.26 21.3 7.31 24 12 24Z"
              />
              <path
                fill="#FBBC05"
                d="M5.28 14.24c-.24-.72-.38-1.49-.38-2.24s.14-1.52.38-2.24V6.62H1.29C.47 8.24 0 10.06 0 12s.47 3.76 1.29 5.38l3.99-3.14Z"
              />
              <path
                fill="#EA4335"
                d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.31 0 3.26 2.7 1.29 6.62l3.99 3.14c.95-2.85 3.6-4.96 6.72-4.96Z"
              />
            </svg>
            <span>
              {loadingProvider === "google"
                ? t.modals.loggingIn
                : t.auth.continueWithGoogle}
            </span>
          </button>
        )}

        {facebookOn && (
          <button
            type="button"
            disabled={isBusy}
            onClick={handleFacebook}
            className="flex w-full items-center justify-center gap-3 rounded-full border border-line bg-card px-5 py-3 text-sm font-medium text-ink transition hover:bg-stage disabled:opacity-60"
          >
            <svg
              className="h-4 w-4 text-[#1877F2]"
              viewBox="0 0 24 24"
              fill="currentColor"
              aria-hidden="true"
            >
              <path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.41c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.235 2.686.235v2.97h-1.514c-1.491 0-1.956.93-1.956 1.886v2.269h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073Z" />
            </svg>
            <span>
              {loadingProvider === "facebook"
                ? t.modals.loggingIn
                : t.auth.continueWithFacebook}
            </span>
          </button>
        )}

        {appleOn && (
          <button
            type="button"
            disabled={isBusy}
            onClick={handleApple}
            className="flex w-full items-center justify-center gap-3 rounded-full border border-line bg-card px-5 py-3 text-sm font-medium text-ink transition hover:bg-stage disabled:opacity-60"
          >
            <svg
              className="h-4 w-4"
              viewBox="0 0 24 24"
              fill="currentColor"
              aria-hidden="true"
            >
              <path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09l.01-.01ZM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25Z" />
            </svg>
            <span>
              {loadingProvider === "apple"
                ? t.modals.loggingIn
                : t.auth.continueWithApple}
            </span>
          </button>
        )}
      </div>
    </div>
  );
}
