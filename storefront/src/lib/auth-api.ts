import type { Locale } from "@/lib/i18n/config";
import { API_BASE_URL } from "@/lib/storefront-api";

export type SocialMedium = "google" | "facebook" | "apple";

export type AuthConfig = {
  otp_resend_time: number;
  customer_verification: {
    status: number;
    phone: number;
    email: number;
    firebase: number;
  };
  customer_login: {
    login_option: {
      manual_login: number;
      otp_login: number;
      social_media_login: number;
    };
    social_media_login_options: {
      google: number;
      facebook: number;
      apple: number;
    };
  };
  social_login: {
    google: number;
    facebook: number;
  };
  apple_login?: {
    status: number;
    client_id?: string | null;
  };
  forgot_password: {
    firebase: number;
    phone: number;
    email: number;
  };
};

export const DEFAULT_AUTH_CONFIG: AuthConfig = {
  otp_resend_time: 60,
  customer_verification: {
    status: 0,
    phone: 0,
    email: 0,
    firebase: 0,
  },
  customer_login: {
    login_option: {
      manual_login: 1,
      otp_login: 0,
      social_media_login: 0,
    },
    social_media_login_options: {
      google: 0,
      facebook: 0,
      apple: 0,
    },
  },
  social_login: {
    google: 0,
    facebook: 0,
  },
  forgot_password: {
    firebase: 0,
    phone: 1,
    email: 0,
  },
};

export type CustomerProfile = {
  id: number;
  f_name: string;
  l_name: string;
  email: string | null;
  phone: string | null;
  is_phone_verified?: number | boolean;
  email_verified_at?: string | null;
};

export async function fetchAuthConfig(locale: Locale = "ar"): Promise<AuthConfig> {
  try {
    const res = await fetch(`${API_BASE_URL}/api/v1/config?lang=${locale}`, {
      cache: "no-store",
      headers: { Accept: "application/json", "X-localization": locale },
    });
    if (!res.ok) return DEFAULT_AUTH_CONFIG;
    const raw = (await res.json()) as Partial<AuthConfig>;

    const loginOpt = raw.customer_login?.login_option;
    const socialOpt = raw.customer_login?.social_media_login_options;

    return {
      otp_resend_time: Number(raw.otp_resend_time ?? 60) || 60,
      customer_verification: {
        status: Number(raw.customer_verification?.status ?? 0),
        phone: Number(raw.customer_verification?.phone ?? 0),
        email: Number(raw.customer_verification?.email ?? 0),
        firebase: Number(raw.customer_verification?.firebase ?? 0),
      },
      customer_login: {
        login_option: {
          manual_login: Number(loginOpt?.manual_login ?? 1),
          otp_login: Number(loginOpt?.otp_login ?? 0),
          social_media_login: Number(loginOpt?.social_media_login ?? 0),
        },
        social_media_login_options: {
          google: Number(socialOpt?.google ?? raw.social_login?.google ?? 0),
          facebook: Number(socialOpt?.facebook ?? raw.social_login?.facebook ?? 0),
          apple: Number(socialOpt?.apple ?? raw.apple_login?.status ?? 0),
        },
      },
      social_login: {
        google: Number(raw.social_login?.google ?? 0),
        facebook: Number(raw.social_login?.facebook ?? 0),
      },
      apple_login: raw.apple_login,
      forgot_password: {
        firebase: Number(raw.forgot_password?.firebase ?? 0),
        phone: Number(raw.forgot_password?.phone ?? 0),
        email: Number(raw.forgot_password?.email ?? 0),
      },
    };
  } catch {
    return DEFAULT_AUTH_CONFIG;
  }
}

export function extractApiError(data: unknown, fallback: string): string {
  if (!data || typeof data !== "object") return fallback;
  const obj = data as {
    errors?: Array<{ message?: string }>;
    message?: string;
    error?: string;
  };
  if (Array.isArray(obj.errors) && obj.errors[0]?.message) {
    return obj.errors[0].message;
  }
  if (typeof obj.message === "string" && obj.message.trim()) {
    return obj.message;
  }
  if (typeof obj.error === "string" && obj.error.trim()) {
    return obj.error;
  }
  return fallback;
}

async function postJson<T>(
  path: string,
  body: Record<string, unknown>,
  locale: Locale,
  method: "POST" | "PUT" = "POST",
): Promise<{ ok: boolean; status: number; data: T }> {
  const res = await fetch(`${API_BASE_URL}${path}`, {
    method,
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      "X-localization": locale,
    },
    body: JSON.stringify(body),
  });
  const data = (await res.json().catch(() => ({}))) as T;
  return { ok: res.ok, status: res.status, data };
}

export async function loginWithPassword(
  payload: { email_or_phone: string; password: string; type: "phone" | "email" },
  locale: Locale,
) {
  return postJson<{ token?: string; temporary_token?: string; status?: boolean }>(
    "/api/v1/auth/login",
    payload,
    locale,
  );
}

export async function registerCustomer(
  payload: {
    f_name: string;
    l_name: string;
    phone: string;
    password: string;
    referral_code?: string;
  },
  locale: Locale,
) {
  return postJson<{ token?: string; temporary_token?: string }>(
    "/api/v1/auth/register",
    payload,
    locale,
  );
}

export async function checkPhone(phone: string, locale: Locale) {
  return postJson<{ message?: string; token?: "active" | "inactive" }>(
    "/api/v1/auth/check-phone",
    { phone },
    locale,
  );
}

export async function verifyPhone(payload: { phone: string; token: string }, locale: Locale) {
  return postJson<{ token?: string; message?: string }>(
    "/api/v1/auth/verify-phone",
    payload,
    locale,
  );
}

export async function checkEmail(email: string, locale: Locale) {
  return postJson<{ message?: string; token?: "active" | "inactive" }>(
    "/api/v1/auth/check-email",
    { email },
    locale,
  );
}

export async function verifyEmail(payload: { email: string; token: string }, locale: Locale) {
  return postJson<{ token?: string; message?: string }>(
    "/api/v1/auth/verify-email",
    payload,
    locale,
  );
}

export async function verifyOTP(payload: { phone: string; token: string }, locale: Locale) {
  return postJson<{ token?: string; temporary_token?: string; status?: boolean }>(
    "/api/v1/auth/verify-otp",
    payload,
    locale,
  );
}

export async function registrationWithOTP(
  payload: { name: string; phone: string; email?: string; referral_code?: string },
  locale: Locale,
) {
  return postJson<{ token?: string }>(
    "/api/v1/auth/registration-with-otp",
    payload,
    locale,
  );
}

export async function socialLogin(
  payload: {
    token: string;
    unique_id: string;
    email?: string;
    medium: "google" | "facebook" | "apple";
  },
  locale: Locale,
) {
  return postJson<{
    token?: string;
    temp_token?: string;
    status?: boolean;
    user?: CustomerProfile;
  }>("/api/v1/auth/social-customer-login", payload, locale);
}

export async function existingAccountCheck(
  payload: { email: string; user_response: 0 | 1; medium: "google" | "facebook" | "apple" },
  locale: Locale,
) {
  return postJson<{ token?: string; temp_token?: string; status?: boolean }>(
    "/api/v1/auth/existing-account-check",
    payload,
    locale,
  );
}

export async function registerWithSocialMedia(
  payload: { name: string; email: string; phone: string; referral_code?: string },
  locale: Locale,
) {
  return postJson<{ token?: string; temp_token?: string; status?: boolean }>(
    "/api/v1/auth/registration-with-social-media",
    payload,
    locale,
  );
}

export async function forgotPasswordRequest(
  payload: { email_or_phone: string; type: "phone" | "email" },
  locale: Locale,
) {
  return postJson<{ message?: string }>("/api/v1/auth/forgot-password", payload, locale);
}

export async function verifyResetToken(
  payload: { email_or_phone: string; reset_token: string },
  locale: Locale,
) {
  return postJson<{ message?: string }>("/api/v1/auth/verify-token", payload, locale);
}

export async function resetPasswordSubmit(
  payload: {
    email_or_phone: string;
    reset_token: string;
    type: "phone" | "email";
    password: string;
    confirm_password: string;
  },
  locale: Locale,
) {
  return postJson<{ message?: string }>("/api/v1/auth/reset-password", payload, locale, "PUT");
}

export async function fetchCustomerInfo(
  token: string,
  locale: Locale,
): Promise<CustomerProfile | null> {
  try {
    const res = await fetch(`${API_BASE_URL}/api/v1/customer/info`, {
      headers: {
        Accept: "application/json",
        "X-localization": locale,
        Authorization: `Bearer ${token}`,
      },
    });
    if (!res.ok) return null;
    return (await res.json()) as CustomerProfile;
  } catch {
    return null;
  }
}

/* ---------------- Unified Session Storage Helpers ---------------- */

const TOKEN_PRIMARY_KEY = "bh_auth_token";
const TOKEN_LEGACY_KEY = "bh-auth-token";
const USER_KEY = "bh_auth_user";

export function getAuthToken(): string | null {
  if (typeof window === "undefined") return null;
  try {
    return localStorage.getItem(TOKEN_PRIMARY_KEY) || localStorage.getItem(TOKEN_LEGACY_KEY);
  } catch {
    return null;
  }
}

export function setAuthToken(token: string) {
  if (typeof window === "undefined") return;
  try {
    localStorage.setItem(TOKEN_PRIMARY_KEY, token);
    localStorage.setItem(TOKEN_LEGACY_KEY, token);
    window.dispatchEvent(new Event("bh-auth-change"));
  } catch {
    /* ignore */
  }
}

export function getAuthUser(): CustomerProfile | null {
  if (typeof window === "undefined") return null;
  try {
    const raw = localStorage.getItem(USER_KEY);
    return raw ? (JSON.parse(raw) as CustomerProfile) : null;
  } catch {
    return null;
  }
}

export function setAuthUser(user: CustomerProfile) {
  if (typeof window === "undefined") return;
  try {
    localStorage.setItem(USER_KEY, JSON.stringify(user));
    window.dispatchEvent(new Event("bh-auth-change"));
  } catch {
    /* ignore */
  }
}

export function clearAuthSession() {
  if (typeof window === "undefined") return;
  try {
    localStorage.removeItem(TOKEN_PRIMARY_KEY);
    localStorage.removeItem(TOKEN_LEGACY_KEY);
    localStorage.removeItem(USER_KEY);
    window.dispatchEvent(new Event("bh-auth-change"));
  } catch {
    /* ignore */
  }
}
