"use client";

import { useCallback, useEffect, useState } from "react";
import MapLocationPicker from "@/components/store/MapLocationPicker";
import { extractApiError, getAuthToken } from "@/lib/auth-api";
import {
  addAddress,
  deleteAddress,
  fetchAddresses,
  fetchStoreConfig,
  updateAddress,
  type CustomerAddress,
  type StoreConfig,
} from "@/lib/customer-api";
import { useI18n } from "@/lib/i18n/context";
import { AccountShell } from "./AccountShell";

export default function AddressesClient() {
  const { locale, t } = useI18n();
  const [config, setConfig] = useState<StoreConfig | null>(null);
  const [addresses, setAddresses] = useState<CustomerAddress[]>([]);
  const [loading, setLoading] = useState(true);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [showForm, setShowForm] = useState(false);

  const [contactName, setContactName] = useState("");
  const [contactPhone, setContactPhone] = useState("");
  const [addressType, setAddressType] = useState("home");
  const [addressText, setAddressText] = useState("");
  const [latitude, setLatitude] = useState("");
  const [longitude, setLongitude] = useState("");
  const [busy, setBusy] = useState(false);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  const loadAddresses = useCallback(async () => {
    if (!getAuthToken()) {
      setLoading(false);
      return;
    }
    setLoading(true);
    const list = await fetchAddresses(locale);
    setAddresses(list);
    setLoading(false);
  }, [locale]);

  useEffect(() => {
    fetchStoreConfig(locale).then(setConfig);
    const timer = setTimeout(() => {
      void loadAddresses();
    }, 0);
    return () => clearTimeout(timer);
  }, [loadAddresses, locale]);

  const startNew = () => {
    setEditingId(null);
    setContactName("");
    setContactPhone("");
    setAddressType("home");
    setAddressText("");
    setLatitude("");
    setLongitude("");
    setErrorMsg(null);
    setShowForm(true);
  };

  const startEdit = (addr: CustomerAddress) => {
    setEditingId(addr.id);
    setContactName(addr.contact_person_name);
    setContactPhone(addr.contact_person_number);
    setAddressType(addr.address_type || "home");
    setAddressText(addr.address);
    setLatitude(addr.latitude ?? "");
    setLongitude(addr.longitude ?? "");
    setErrorMsg(null);
    setShowForm(true);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setBusy(true);
    setErrorMsg(null);
    const payload = {
      contact_person_name: contactName.trim(),
      contact_person_number: contactPhone.trim(),
      address_type: addressType,
      address: addressText.trim(),
      ...(latitude ? { latitude } : {}),
      ...(longitude ? { longitude } : {}),
    };
    const res = editingId
      ? await updateAddress(editingId, payload, locale)
      : await addAddress(payload, locale);
    setBusy(false);

    if (!res.ok) {
      setErrorMsg(extractApiError(res.data, t.checkout.orderFailed));
      return;
    }
    setShowForm(false);
    await loadAddresses();
  };

  const handleDelete = async (id: number) => {
    await deleteAddress(id, locale);
    await loadAddresses();
  };

  return (
    <AccountShell
      current="addresses"
      title={t.account.addressesTitle}
      subtitle={t.account.addressesSubtitle}
    >
      <div className="space-y-6">
        <div className="flex justify-end">
          <button
            type="button"
            onClick={startNew}
            className="inline-flex h-10 items-center rounded-full bg-ink px-6 text-sm font-medium text-on-ink transition hover:bg-brand"
          >
            + {t.account.addAddressBtn}
          </button>
        </div>

        {showForm && (
          <form
            onSubmit={handleSubmit}
            className="rounded-3xl border border-line bg-card p-6 space-y-4 max-w-2xl"
          >
            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <label className="mb-1 block text-xs text-muted">{t.checkout.fullName}</label>
                <input
                  type="text"
                  required
                  value={contactName}
                  onChange={(e) => setContactName(e.target.value)}
                  className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-sm text-ink outline-none focus:border-ink"
                />
              </div>
              <div>
                <label className="mb-1 block text-xs text-muted">{t.checkout.phone}</label>
                <input
                  type="tel"
                  required
                  dir="ltr"
                  value={contactPhone}
                  onChange={(e) => setContactPhone(e.target.value)}
                  className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-sm text-ink outline-none focus:border-ink"
                />
              </div>
              <div>
                <label className="mb-1 block text-xs text-muted">{t.checkout.addressTypeLabel}</label>
                <select
                  value={addressType}
                  onChange={(e) => setAddressType(e.target.value)}
                  className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-sm text-ink outline-none focus:border-ink"
                >
                  <option value="home">{t.checkout.addressTypeHome}</option>
                  <option value="work">{t.checkout.addressTypeWork}</option>
                  <option value="other">{t.checkout.addressTypeOther}</option>
                </select>
              </div>
              <div className="sm:col-span-2">
                <label className="mb-1 block text-xs text-muted">{t.checkout.address}</label>
                <input
                  type="text"
                  required
                  value={addressText}
                  onChange={(e) => setAddressText(e.target.value)}
                  className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-sm text-ink outline-none focus:border-ink"
                />
              </div>
              <div className="sm:col-span-2">
                <MapLocationPicker
                  latitude={latitude}
                  longitude={longitude}
                  config={config}
                  onChange={(val) => {
                    setLatitude(val.latitude);
                    setLongitude(val.longitude);
                    if (val.address) setAddressText(val.address);
                  }}
                />
              </div>
            </div>

            {errorMsg && <p className="text-xs text-brand">{errorMsg}</p>}

            <div className="flex gap-3">
              <button
                type="submit"
                disabled={busy}
                className="h-10 rounded-full bg-ink px-6 text-sm font-medium text-on-ink transition hover:bg-brand disabled:opacity-50"
              >
                {busy ? t.modals.loggingIn : t.account.saveChangesBtn}
              </button>
              <button
                type="button"
                onClick={() => setShowForm(false)}
                className="h-10 rounded-full border border-line px-6 text-sm font-medium text-muted hover:text-ink"
              >
                {t.modals.close}
              </button>
            </div>
          </form>
        )}

        {loading ? (
          <div className="rounded-3xl border border-line bg-card p-8 text-center text-sm text-muted">
            {t.modals.loggingIn}
          </div>
        ) : addresses.length === 0 ? (
          <div className="rounded-3xl border border-line bg-card p-10 text-center text-sm text-muted">
            {t.account.noAddresses}
          </div>
        ) : (
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            {addresses.map((addr) => (
              <div
                key={addr.id}
                className="rounded-3xl border border-line bg-card p-5 flex flex-col justify-between gap-4"
              >
                <div className="space-y-1.5">
                  <div className="flex items-center justify-between gap-2">
                    <span className="rounded-full bg-stage px-3 py-1 text-xs font-medium text-ink">
                      {addr.address_type}
                    </span>
                    <span className="text-xs text-muted" dir="ltr">
                      {addr.contact_person_number}
                    </span>
                  </div>
                  <p className="font-display text-base font-semibold text-ink">
                    {addr.contact_person_name}
                  </p>
                  <p className="text-sm text-muted">{addr.address}</p>
                </div>
                <div className="flex gap-2 border-t border-line pt-3">
                  <button
                    type="button"
                    onClick={() => startEdit(addr)}
                    className="h-9 rounded-full border border-line px-4 text-xs font-medium text-ink hover:bg-stage"
                  >
                    {t.account.editAddressBtn}
                  </button>
                  <button
                    type="button"
                    onClick={() => void handleDelete(addr.id)}
                    className="h-9 rounded-full border border-line px-4 text-xs font-medium text-brand hover:bg-brand/10"
                  >
                    {t.account.deleteAddressBtn}
                  </button>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </AccountShell>
  );
}
