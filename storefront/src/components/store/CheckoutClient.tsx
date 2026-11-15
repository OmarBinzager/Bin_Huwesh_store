"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { useDemoBag } from "@/components/store/DemoBag";
import { formatYER as formatPrice } from "@/lib/catalog";
import { useI18n } from "@/lib/i18n/context";
import { API_BASE_URL } from "@/lib/storefront-api";

type OfflineMethod = {
  id: number;
  method_name: string;
  method_fields?: Array<{ input_name: string; input_data: string }>;
  payment_note?: string;
};

export default function CheckoutClient() {
  const { locale, t } = useI18n();
  const bag = useDemoBag();

  const [name, setName] = useState("");
  const [phone, setPhone] = useState("");
  const [city, setCity] = useState(locale === "en" ? "Hadramout - Al Mukalla" : "حضرموت - المكلا");
  const [address, setAddress] = useState("");
  const [orderNote, setOrderNote] = useState("");

  const [couponCode, setCouponCode] = useState("");
  const [appliedCoupon, setAppliedCoupon] = useState<string | null>(null);
  const [couponDiscount, setCouponDiscount] = useState(0);
  const [couponMsg, setCouponMsg] = useState<string | null>(null);

  const [paymentMethod, setPaymentMethod] = useState<"cash_on_delivery" | "offline_payment">(
    "cash_on_delivery",
  );
  const [offlineMethods, setOfflineMethods] = useState<OfflineMethod[]>([]);
  const [selectedOfflineName, setSelectedOfflineName] = useState("");
  const [paymentReference, setPaymentReference] = useState("");

  const [submitting, setSubmitting] = useState(false);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);
  const [orderSuccess, setOrderSuccess] = useState<{ orderId: string | number; total: number } | null>(
    null,
  );

  useEffect(() => {
    fetch(`${API_BASE_URL}/api/v1/offline-payment-method/list`, {
      headers: { Accept: "application/json", "X-localization": locale },
    })
      .then((r) => (r.ok ? r.json() : []))
      .then((data) => {
        const list = Array.isArray(data) ? data : data?.data ?? [];
        setOfflineMethods(list);
        if (list.length > 0) setSelectedOfflineName(list[0].method_name || "");
      })
      .catch(() => {});

    try {
      const savedUser = localStorage.getItem("bh_auth_user");
      if (savedUser) {
        const u = JSON.parse(savedUser);
        if (u?.f_name || u?.l_name) setName(`${u.f_name ?? ""} ${u.l_name ?? ""}`.trim());
        if (u?.phone) setPhone(u.phone);
      }
    } catch {}
  }, [locale]);

  const ensureAuthHeaders = async (): Promise<{
    headers: Record<string, string>;
    guestId?: string;
  }> => {
    const token = typeof window !== "undefined" ? localStorage.getItem("bh_auth_token") : null;
    if (token) {
      return {
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
          "X-localization": locale,
          Authorization: `Bearer ${token}`,
        },
      };
    }

    let guestId = typeof window !== "undefined" ? localStorage.getItem("bh_guest_id") : null;
    if (!guestId) {
      const res = await fetch(`${API_BASE_URL}/api/v1/guest/add`, {
        method: "POST",
        headers: { Accept: "application/json", "Content-Type": "application/json", "X-localization": locale },
        body: JSON.stringify({}),
      });
      if (res.ok) {
        const data = await res.json();
        guestId = String(data?.guest?.id ?? data?.guest_id ?? data?.id ?? "");
        if (guestId) localStorage.setItem("bh_guest_id", guestId);
      }
    }

    return {
      guestId: guestId || undefined,
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
        "X-localization": locale,
        ...(guestId ? { "guest-id": guestId } : {}),
      },
    };
  };

  const handleApplyCoupon = async () => {
    setCouponMsg(null);
    if (!couponCode.trim()) return;

    try {
      const { headers, guestId } = await ensureAuthHeaders();
      const qs = new URLSearchParams({ code: couponCode.trim() });
      if (guestId) qs.set("guest_id", guestId);
      const res = await fetch(`${API_BASE_URL}/api/v1/coupon/apply?${qs.toString()}`, {
        headers,
      });
      const data = await res.json();
      if (!res.ok) {
        setCouponMsg(data?.errors?.[0]?.message || data?.message || t.checkout.couponInvalid);
        return;
      }

      const discountVal = Number(data?.discount ?? 0);
      const computedDiscount =
        data?.discount_type === "percent"
          ? Math.round((bag.subtotal * discountVal) / 100)
          : discountVal;

      setAppliedCoupon(couponCode.trim());
      setCouponDiscount(Math.min(bag.subtotal, Math.max(0, computedDiscount)));
      setCouponMsg(t.checkout.couponApplied(formatPrice(computedDiscount, locale)));
    } catch {
      setCouponMsg(t.checkout.couponInvalid);
    }
  };

  const finalTotal = Math.max(0, bag.subtotal - bag.lookDiscount - couponDiscount);

  const handlePlaceOrder = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMsg(null);

    if (bag.lines.length === 0) {
      setErrorMsg(t.checkout.emptyTitle);
      return;
    }
    if (!name.trim() || !phone.trim() || !address.trim()) {
      setErrorMsg(t.checkout.fillRequired);
      return;
    }

    const purchasableLines = bag.lines.filter(
      (l) => l.productId !== null && l.productId !== undefined && Number(l.productId) > 0,
    );
    if (purchasableLines.length === 0) {
      setErrorMsg(
        locale === "en"
          ? "Selected items are preview-only and not yet linked to live inventory. Browse categories to add purchasable products."
          : "القطع المختارة حالياً للعرض التجريبي فقط ولم تُربط بمنتجات المخزون بعد. يمكنك تصفح الأقسام لاختيار منتجات متاحة للطلب.",
      );
      return;
    }

    setSubmitting(true);
    try {
      const { headers, guestId } = await ensureAuthHeaders();

      const cartPayload = purchasableLines.map((l) => ({
        product_id: Number(l.productId),
        price: l.price,
        quantity: l.qty,
        variant: l.size || "",
        variation: l.size ? [{ type: l.size }] : [],
        discount_amount: 0,
        tax_amount: 0,
      }));

      // 1. Check InfoSoft stock first
      const stockRes = await fetch(`${API_BASE_URL}/api/v1/products/check-stock`, {
        method: "POST",
        headers,
        body: JSON.stringify({ cart: cartPayload }),
      });
      if (!stockRes.ok) {
        const stockErr = await stockRes.json().catch(() => ({}));
        throw new Error(
          stockErr?.message ||
            stockErr?.errors?.[0]?.message ||
            t.checkout.orderFailed,
        );
      }

      // 2. Create delivery address
      const fullAddress = `${city} - ${address.trim()}`;
      const addrRes = await fetch(`${API_BASE_URL}/api/v1/customer/address/add`, {
        method: "POST",
        headers,
        body: JSON.stringify({
          contact_person_name: name.trim(),
          contact_person_number: phone.trim(),
          address_type: "Home",
          address: fullAddress,
        }),
      });
      if (!addrRes.ok) {
        const addrErr = await addrRes.json().catch(() => ({}));
        throw new Error(addrErr?.errors?.[0]?.message || t.checkout.orderFailed);
      }

      // Fetch created address id
      const listRes = await fetch(
        `${API_BASE_URL}/api/v1/customer/address/list${guestId ? `?guest_id=${guestId}` : ""}`,
        { headers },
      );
      const listData = listRes.ok ? await listRes.json() : [];
      const addresses = Array.isArray(listData) ? listData : listData?.addresses ?? [];
      const latestAddressId = addresses[0]?.id ?? addresses[addresses.length - 1]?.id;

      const lookIds = Array.from(
        new Set(
          purchasableLines
            .filter((l) => l.lookDiscountPct && l.lookDiscountPct > 0 && l.lookId && l.lookId !== "catalog")
            .map((l) => l.lookId),
        ),
      );

      // 3. Place order
      const placeRes = await fetch(`${API_BASE_URL}/api/v1/customer/order/place`, {
        method: "POST",
        headers,
        body: JSON.stringify({
          cart: cartPayload,
          order_amount: finalTotal,
          payment_method: paymentMethod,
          order_type: "delivery",
          branch_id: 1,
          delivery_address_id: latestAddressId,
          ...(lookIds.length > 0 ? { look_ids: lookIds } : {}),
          ...(guestId ? { guest_id: guestId } : {}),
          ...(appliedCoupon ? { coupon_code: appliedCoupon } : {}),
          ...(orderNote.trim() ? { order_note: orderNote.trim() } : {}),
          ...(paymentMethod === "offline_payment"
            ? {
                payment_by: selectedOfflineName || "تحويل بنكي",
                payment_note: paymentReference.trim() || "تحويل من المتجر الإلكتروني",
              }
            : {}),
        }),
      });

      const placeData = await placeRes.json().catch(() => ({}));
      if (!placeRes.ok) {
        throw new Error(
          placeData?.errors?.[0]?.message ||
            placeData?.message ||
            t.checkout.orderFailed,
        );
      }

      const createdOrderId = placeData?.order_id ?? placeData?.id ?? "—";
      bag.clear();
      setOrderSuccess({ orderId: createdOrderId, total: finalTotal });
    } catch (err) {
      setErrorMsg(err instanceof Error ? err.message : t.checkout.orderFailed);
    } finally {
      setSubmitting(false);
    }
  };

  if (orderSuccess) {
    return (
      <div className="mx-auto max-w-xl px-4 py-16 text-center">
        <div className="rounded-3xl border border-line bg-card p-8 shadow-sm">
          <span className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-500/15 text-2xl text-emerald-600">
            ✓
          </span>
          <h1 className="font-display text-2xl font-semibold text-ink">{t.checkout.successTitle}</h1>
          <p className="mt-2 text-[14px] text-muted">
            {t.checkout.orderNumber} <strong className="nums text-ink">#{orderSuccess.orderId}</strong>
          </p>
          <p className="mt-1 text-[14px] text-muted">
            {t.checkout.totalPaid} <strong className="nums text-ink">{formatPrice(orderSuccess.total, locale)}</strong>
          </p>
          <Link
            href="/"
            className="mt-6 inline-flex rounded-full bg-ink px-6 py-2.5 text-[13px] font-medium text-on-ink hover:bg-brand hover:text-white"
          >
            {t.checkout.continueShopping}
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-[1200px] px-4 py-8 sm:px-8 sm:py-12">
      <h1 className="mb-8 font-display text-3xl font-semibold text-ink">{t.checkout.title}</h1>

      {bag.lines.length === 0 ? (
        <div className="rounded-3xl border border-line bg-card p-8 text-center">
          <p className="text-[15px] text-muted">{t.checkout.emptyTitle}</p>
          <Link
            href="/#shop-by-category"
            className="mt-4 inline-flex rounded-full bg-ink px-6 py-2.5 text-[13px] font-medium text-on-ink hover:bg-brand hover:text-white"
          >
            {t.checkout.backToStore}
          </Link>
        </div>
      ) : (
        <div className="grid gap-8 lg:grid-cols-12">
          <form onSubmit={handlePlaceOrder} className="space-y-6 lg:col-span-7">
            <div className="rounded-3xl border border-line bg-card p-6">
              <h2 className="mb-4 font-display text-lg font-semibold text-ink">{t.checkout.customerSection}</h2>
              <div className="grid gap-4 sm:grid-cols-2">
                <div>
                  <label className="mb-1.5 block text-[13px] font-medium text-ink">
                    {t.checkout.fullName} *
                  </label>
                  <input
                    type="text"
                    required
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    placeholder={t.checkout.fullNamePlaceholder}
                    className="h-11 w-full rounded-xl border border-line bg-background px-3.5 text-[14px] text-ink outline-none focus:border-brand"
                  />
                </div>
                <div>
                  <label className="mb-1.5 block text-[13px] font-medium text-ink">
                    {t.checkout.phone} *
                  </label>
                  <input
                    type="tel"
                    required
                    dir="ltr"
                    value={phone}
                    onChange={(e) => setPhone(e.target.value)}
                    placeholder={t.checkout.phonePlaceholder}
                    className="h-11 w-full rounded-xl border border-line bg-background px-3.5 text-[14px] text-ink outline-none focus:border-brand"
                  />
                </div>
                <div>
                  <label className="mb-1.5 block text-[13px] font-medium text-ink">
                    {t.checkout.city} *
                  </label>
                  <input
                    type="text"
                    required
                    value={city}
                    onChange={(e) => setCity(e.target.value)}
                    className="h-11 w-full rounded-xl border border-line bg-background px-3.5 text-[14px] text-ink outline-none focus:border-brand"
                  />
                </div>
                <div>
                  <label className="mb-1.5 block text-[13px] font-medium text-ink">
                    {t.checkout.address} *
                  </label>
                  <input
                    type="text"
                    required
                    value={address}
                    onChange={(e) => setAddress(e.target.value)}
                    placeholder={t.checkout.addressPlaceholder}
                    className="h-11 w-full rounded-xl border border-line bg-background px-3.5 text-[14px] text-ink outline-none focus:border-brand"
                  />
                </div>
              </div>
              <div className="mt-4">
                <label className="mb-1.5 block text-[13px] font-medium text-ink">
                  {t.checkout.orderNote}
                </label>
                <textarea
                  rows={2}
                  value={orderNote}
                  onChange={(e) => setOrderNote(e.target.value)}
                  placeholder={t.checkout.orderNotePlaceholder}
                  className="w-full rounded-xl border border-line bg-background p-3 text-[14px] text-ink outline-none focus:border-brand"
                />
              </div>
            </div>

            <div className="rounded-3xl border border-line bg-card p-6">
              <h2 className="mb-4 font-display text-lg font-semibold text-ink">{t.checkout.paymentSection}</h2>
              <div className="space-y-3">
                <label className="flex cursor-pointer items-center gap-3 rounded-2xl border border-line p-4 transition-colors hover:border-brand">
                  <input
                    type="radio"
                    name="payment_method"
                    checked={paymentMethod === "cash_on_delivery"}
                    onChange={() => setPaymentMethod("cash_on_delivery")}
                  />
                  <div>
                    <p className="text-[14px] font-semibold text-ink">{t.checkout.cashOnDelivery}</p>
                    <p className="text-[12px] text-muted">
                      {locale === "en" ? "Pay cash when your order arrives" : "ادفع نقداً عند وصول الطلب"}
                    </p>
                  </div>
                </label>

                <label className="flex cursor-pointer items-center gap-3 rounded-2xl border border-line p-4 transition-colors hover:border-brand">
                  <input
                    type="radio"
                    name="payment_method"
                    checked={paymentMethod === "offline_payment"}
                    onChange={() => setPaymentMethod("offline_payment")}
                  />
                  <div>
                    <p className="text-[14px] font-semibold text-ink">{t.checkout.offlinePayment}</p>
                    <p className="text-[12px] text-muted">
                      {locale === "en"
                        ? "Transfer the amount and enter your reference number"
                        : "حوّل المبلغ وأدخل رقم الحوالة أو المرجع"}
                    </p>
                  </div>
                </label>

                {paymentMethod === "offline_payment" && (
                  <div className="space-y-3 rounded-2xl bg-stage p-4">
                    {offlineMethods.length > 0 && (
                      <div>
                        <label className="mb-1 block text-[12px] font-medium text-ink">
                          {locale === "en" ? "Select Account / Bank" : "اختر الحساب / البنك"}
                        </label>
                        <select
                          value={selectedOfflineName}
                          onChange={(e) => setSelectedOfflineName(e.target.value)}
                          className="h-10 w-full rounded-xl border border-line bg-card px-3 text-[13px] text-ink"
                        >
                          {offlineMethods.map((m) => (
                            <option key={m.id} value={m.method_name}>
                              {m.method_name}
                            </option>
                          ))}
                        </select>
                      </div>
                    )}
                    <div>
                      <label className="mb-1 block text-[12px] font-medium text-ink">
                        {t.checkout.paymentRef}
                      </label>
                      <input
                        type="text"
                        value={paymentReference}
                        onChange={(e) => setPaymentReference(e.target.value)}
                        placeholder={t.checkout.paymentRefPlaceholder}
                        className="h-10 w-full rounded-xl border border-line bg-card px-3 text-[13px] text-ink"
                      />
                    </div>
                  </div>
                )}
              </div>
            </div>

            {errorMsg && (
              <div className="rounded-2xl border border-red-500/30 bg-red-500/10 p-4 text-[13px] text-red-600">
                {errorMsg}
              </div>
            )}

            <button
              type="submit"
              disabled={submitting}
              className="h-12 w-full rounded-full bg-ink text-[15px] font-semibold text-on-ink transition-colors hover:bg-brand hover:text-white disabled:opacity-50"
            >
              {submitting ? t.checkout.submitting : `${t.checkout.submitOrder} (${formatPrice(finalTotal, locale)})`}
            </button>
          </form>

          {/* Order Summary */}
          <aside className="lg:col-span-5">
            <div className="sticky top-24 rounded-3xl border border-line bg-card p-6">
              <h2 className="mb-4 font-display text-lg font-semibold text-ink">{t.checkout.summarySection}</h2>
              <ul className="divide-y divide-line">
                {bag.lines.map((line) => (
                  <li key={line.key} className="flex items-center justify-between gap-3 py-3 text-[13px]">
                    <div className="min-w-0">
                      <p className="truncate font-medium text-ink">{line.label}</p>
                      <p className="text-[12px] text-muted">
                        {t.bag.sizeLabel} {line.size} · {t.product.quantity} {line.qty}
                      </p>
                    </div>
                    <span className="nums shrink-0 font-semibold text-ink">
                      {formatPrice(line.price * line.qty, locale)}
                    </span>
                  </li>
                ))}
              </ul>

              <div className="mt-4 flex gap-2 border-t border-line pt-4">
                <input
                  type="text"
                  value={couponCode}
                  onChange={(e) => setCouponCode(e.target.value)}
                  placeholder={t.checkout.couponPlaceholder}
                  className="h-10 flex-1 rounded-xl border border-line bg-background px-3 text-[13px] text-ink outline-none focus:border-brand"
                />
                <button
                  type="button"
                  onClick={handleApplyCoupon}
                  className="h-10 rounded-xl bg-stage px-4 text-[13px] font-medium text-ink hover:bg-brand hover:text-white"
                >
                  {t.checkout.applyCoupon}
                </button>
              </div>
              {couponMsg && <p className="mt-2 text-[12px] text-brand">{couponMsg}</p>}

              <div className="mt-5 space-y-2 border-t border-line pt-4 text-[14px]">
                <div className="flex justify-between text-muted">
                  <span>{t.checkout.subtotal}</span>
                  <span className="nums">{formatPrice(bag.subtotal, locale)}</span>
                </div>
                {bag.lookDiscount > 0 && (
                <div className="flex justify-between text-brand">
                  <span>{t.checkout.lookDiscount}</span>
                  <span className="nums">-{formatPrice(bag.lookDiscount, locale)}</span>
                </div>
              )}
              {couponDiscount > 0 && (
                  <div className="flex justify-between text-emerald-600">
                    <span>{t.checkout.couponDiscount}</span>
                    <span className="nums">-{formatPrice(couponDiscount, locale)}</span>
                  </div>
                )}
                <div className="flex justify-between border-t border-line pt-2 text-base font-bold text-ink">
                  <span>{t.checkout.total}</span>
                  <span className="nums">{formatPrice(finalTotal, locale)}</span>
                </div>
              </div>
            </div>
          </aside>
        </div>
      )}
    </div>
  );
}
