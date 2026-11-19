"use client";

import { useEffect, useState } from "react";
import {
  extractApiError,
  fetchCustomerInfo,
  getAuthToken,
  getAuthUser,
  setAuthUser,
} from "@/lib/auth-api";
import { updateCustomerProfile } from "@/lib/customer-api";
import { useI18n } from "@/lib/i18n/context";
import { AccountShell } from "./AccountShell";

export default function ProfileClient() {
  const { locale, t } = useI18n();

  const [firstName, setFirstName] = useState(() => getAuthUser()?.f_name ?? "");
  const [lastName, setLastName] = useState(() => getAuthUser()?.l_name ?? "");
  const [email, setEmail] = useState(() => getAuthUser()?.email ?? "");
  const [phone, setPhone] = useState(() => getAuthUser()?.phone ?? "");
  const [password, setPassword] = useState("");

  const [busy, setBusy] = useState(false);
  const [statusMsg, setStatusMsg] = useState<string | null>(null);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  useEffect(() => {
    const tk = getAuthToken();
    if (!tk) return;
    fetchCustomerInfo(tk, locale).then((p) => {
      if (!p) return;
      setAuthUser(p);
      setFirstName(p.f_name || "");
      setLastName(p.l_name || "");
      setEmail(p.email || "");
      setPhone(p.phone || "");
    });
  }, [locale]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setBusy(true);
    setStatusMsg(null);
    setErrorMsg(null);
    const res = await updateCustomerProfile(
      {
        f_name: firstName.trim(),
        l_name: lastName.trim(),
        email: email.trim(),
        phone: phone.trim(),
        ...(password.trim().length >= 6 ? { password: password.trim() } : {}),
      },
      locale,
    );
    setBusy(false);
    if (!res.ok) {
      setErrorMsg(extractApiError(res.data, t.checkout.orderFailed));
      return;
    }
    setPassword("");
    setStatusMsg(t.account.profileUpdated);
    const tk = getAuthToken();
    if (tk) {
      const updated = await fetchCustomerInfo(tk, locale);
      if (updated) {
        setAuthUser(updated);
      }
    }
  };

  return (
    <AccountShell
      current="profile"
      title={t.account.profileTitle}
      subtitle={t.account.profileSubtitle}
    >
      <div className="max-w-2xl">
        <form
          onSubmit={handleSubmit}
          className="rounded-3xl border border-line bg-card p-6 space-y-4"
        >
          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <label className="mb-1 block text-xs text-muted">{t.auth.firstNameLabel}</label>
              <input
                type="text"
                required
                value={firstName}
                onChange={(e) => setFirstName(e.target.value)}
                className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-sm text-ink outline-none focus:border-ink"
              />
            </div>
            <div>
              <label className="mb-1 block text-xs text-muted">{t.auth.lastNameLabel}</label>
              <input
                type="text"
                required
                value={lastName}
                onChange={(e) => setLastName(e.target.value)}
                className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-sm text-ink outline-none focus:border-ink"
              />
            </div>
            <div>
              <label className="mb-1 block text-xs text-muted">{t.auth.emailLabel}</label>
              <input
                type="email"
                required
                dir="ltr"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-sm text-ink outline-none focus:border-ink"
              />
            </div>
            <div>
              <label className="mb-1 block text-xs text-muted">{t.checkout.phone}</label>
              <input
                type="tel"
                required
                dir="ltr"
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
                className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-sm text-ink outline-none focus:border-ink"
              />
            </div>
            <div className="sm:col-span-2">
              <label className="mb-1 block text-xs text-muted">{t.account.newPasswordLabel}</label>
              <input
                type="password"
                minLength={6}
                dir="ltr"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-sm text-ink outline-none focus:border-ink"
              />
            </div>
          </div>

          {errorMsg && <p className="text-xs text-brand">{errorMsg}</p>}
          {statusMsg && <p className="text-xs text-emerald-600">{statusMsg}</p>}

          <button
            type="submit"
            disabled={busy}
            className="h-10 rounded-full bg-ink px-7 text-sm font-medium text-on-ink transition hover:bg-brand disabled:opacity-50"
          >
            {busy ? t.modals.loggingIn : t.account.saveChangesBtn}
          </button>
        </form>
      </div>
    </AccountShell>
  );
}
