"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { useDemoBag } from "@/components/store/DemoBag";
import { formatYER as formatPrice } from "@/lib/catalog";
import { API_BASE_URL } from "@/lib/storefront-api";

type OfflineMethod = {
  id: number;
  method_name: string;
  method_fields?: Array<{ input_name: string; input_data: string }>;
  payment_note?: string;
};

export default function CheckoutClient() {
  const bag = useDemoBag();

  const [name, setName] = useState("");
  const [phone, setPhone] = useState("");
  const [city, setCity] = useState("حضرموت - المكلا");
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
      headers: { Accept: "application/json" },
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
  }, []);

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
          Authorization: `Bearer ${token}`,
        },
      };
    }

    let guestId = typeof window !== "undefined" ? localStorage.getItem("bh_guest_id") : null;
    if (!guestId) {
      const res = await fetch(`${API_BASE_URL}/api/v1/guest/add`, {
        method: "POST",
        headers: { Accept: "application/json", "Content-Type": "application/json" },
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
        setCouponMsg(data?.errors?.[0]?.message || data?.message || "الكوبون غير صالح");
        return;
      }

      const discountVal = Number(data?.discount ?? 0);
      const computedDiscount =
        data?.discount_type === "percent"
          ? Math.round((bag.subtotal * discountVal) / 100)
          : discountVal;

      setAppliedCoupon(couponCode.trim());
      setCouponDiscount(Math.min(bag.subtotal, Math.max(0, computedDiscount)));
      setCouponMsg("تم تطبيق كود الخصم بنجاح ✓");
    } catch {
      setCouponMsg("تعذر التحقق من الكوبون حالياً.");
    }
  };

  const finalTotal = Math.max(0, bag.subtotal - couponDiscount);

  const handlePlaceOrder = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMsg(null);

    if (bag.lines.length === 0) {
      setErrorMsg("حقيبة التسوق فارغة.");
      return;
    }
    if (!name.trim() || !phone.trim() || !address.trim()) {
      setErrorMsg("يرجى تعبئة الاسم ورقم الجوال وعنوان التوصيل.");
      return;
    }

    const purchasableLines = bag.lines.filter(
      (l) => l.productId !== null && l.productId !== undefined && Number(l.productId) > 0,
    );
    if (purchasableLines.length === 0) {
      setErrorMsg(
        "القطع المختارة حالياً للعرض التجريبي فقط ولم تُربط بمنتجات المخزون بعد. يمكنك تصفح الأقسام لاختيار منتجات متاحة للطلب.",
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
            "بعض المنتجات غير متوفرة حالياً في المخزون.",
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
        throw new Error(addrErr?.errors?.[0]?.message || "تعذر حفظ عنوان التوصيل.");
      }

      // Fetch created address id
      const listRes = await fetch(
        `${API_BASE_URL}/api/v1/customer/address/list${guestId ? `?guest_id=${guestId}` : ""}`,
        { headers },
      );
      const listData = listRes.ok ? await listRes.json() : [];
      const addresses = Array.isArray(listData) ? listData : listData?.addresses ?? [];
      const latestAddressId = addresses[0]?.id ?? addresses[addresses.length - 1]?.id;

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
            "تعذر إتمام الطلب، يرجى التحقق من البيانات.",
        );
      }

      const createdOrderId = placeData?.order_id ?? placeData?.id ?? "—";
      bag.clear();
      setOrderSuccess({ orderId: createdOrderId, total: finalTotal });
    } catch (err) {
      setErrorMsg(err instanceof Error ? err.message : "حدث خطأ غير متوقع.");
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
          <h1 className="font-display text-2xl font-semibold text-ink">تم استلام طلبك بنجاح!</h1>
          <p className="mt-2 text-[14px] text-muted">
            رقم الطلب: <strong className="text-ink">#{orderSuccess.orderId}</strong>
          </p>
          <p className="mt-1 text-[14px] text-muted">
            الإجمالي: <strong className="text-ink">{formatPrice(orderSuccess.total)}</strong>
          </p>
          <Link
            href="/"
            className="mt-6 inline-flex rounded-full bg-ink px-6 py-2.5 text-[13px] font-medium text-on-ink hover:bg-brand hover:text-white"
          >
            العودة إلى المتجر
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-[1200px] px-4 py-8 sm:px-8 sm:py-12">
      <h1 className="mb-8 font-display text-3xl font-semibold text-ink">إتمام الطلب</h1>

      {bag.lines.length === 0 ? (
        <div className="rounded-3xl border border-line bg-card p-8 text-center">
          <p className="text-[15px] text-muted">حقيبة التسوق فارغة حالياً.</p>
          <Link
            href="/#shop-by-category"
            className="mt-4 inline-flex rounded-full bg-ink px-6 py-2.5 text-[13px] font-medium text-on-ink hover:bg-brand hover:text-white"
          >
            تصفح الأقسام
          </Link>
        </div>
      ) : (
        <div className="grid gap-8 lg:grid-cols-12">
          <form onSubmit={handlePlaceOrder} className="space-y-6 lg:col-span-7">
            <div className="rounded-3xl border border-line bg-card p-6">
              <h2 className="mb-4 font-display text-lg font-semibold text-ink">بيانات التوصيل</h2>
              <div className="grid gap-4 sm:grid-cols-2">
                <div>
                  <label className="mb-1.5 block text-[13px] font-medium text-ink">
                    الاسم الكامل *
                  </label>
                  <input
                    type="text"
                    required
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    placeholder="الاسم الثلاثي"
                    className="h-11 w-full rounded-xl border border-line bg-background px-3.5 text-[14px] text-ink outline-none focus:border-brand"
                  />
                </div>
                <div>
                  <label className="mb-1.5 block text-[13px] font-medium text-ink">
                    رقم الجوال *
                  </label>
                  <input
                    type="tel"
                    required
                    value={phone}
                    onChange={(e) => setPhone(e.target.value)}
                    placeholder="7xxxxxxxx"
                    className="h-11 w-full rounded-xl border border-line bg-background px-3.5 text-[14px] text-ink outline-none focus:border-brand"
                  />
                </div>
                <div>
                  <label className="mb-1.5 block text-[13px] font-medium text-ink">
                    المحافظة / المدينة *
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
                    العنوان التفصيلي *
                  </label>
                  <input
                    type="text"
                    required
                    value={address}
                    onChange={(e) => setAddress(e.target.value)}
                    placeholder="الحي، الشارع، أقرب معلم"
                    className="h-11 w-full rounded-xl border border-line bg-background px-3.5 text-[14px] text-ink outline-none focus:border-brand"
                  />
                </div>
              </div>
              <div className="mt-4">
                <label className="mb-1.5 block text-[13px] font-medium text-ink">
                  ملاحظات إضافية (اختياري)
                </label>
                <textarea
                  rows={2}
                  value={orderNote}
                  onChange={(e) => setOrderNote(e.target.value)}
                  placeholder="ملاحظات للمندوب أو التجهيز..."
                  className="w-full rounded-xl border border-line bg-background p-3 text-[14px] text-ink outline-none focus:border-brand"
                />
              </div>
            </div>

            <div className="rounded-3xl border border-line bg-card p-6">
              <h2 className="mb-4 font-display text-lg font-semibold text-ink">طريقة الدفع</h2>
              <div className="space-y-3">
                <label className="flex cursor-pointer items-center gap-3 rounded-2xl border border-line p-4 transition-colors hover:border-brand">
                  <input
                    type="radio"
                    name="payment_method"
                    checked={paymentMethod === "cash_on_delivery"}
                    onChange={() => setPaymentMethod("cash_on_delivery")}
                  />
                  <div>
                    <p className="text-[14px] font-semibold text-ink">الدفع عند الاستلام</p>
                    <p className="text-[12px] text-muted">ادفع نقداً عند وصول الطلب</p>
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
                    <p className="text-[14px] font-semibold text-ink">تحويل بنكي / محفظة إلكترونية</p>
                    <p className="text-[12px] text-muted">حوّل المبلغ وأدخل رقم الحوالة أو المرجع</p>
                  </div>
                </label>

                {paymentMethod === "offline_payment" && (
                  <div className="space-y-3 rounded-2xl bg-stage p-4">
                    {offlineMethods.length > 0 && (
                      <div>
                        <label className="mb-1 block text-[12px] font-medium text-ink">
                          اختر الحساب / البنك
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
                        رقم الحوالة / المرجع
                      </label>
                      <input
                        type="text"
                        value={paymentReference}
                        onChange={(e) => setPaymentReference(e.target.value)}
                        placeholder="أدخل رقم الإشعار أو اسم المحوّل"
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
              {submitting ? "جاري تأكيد الطلب..." : `تأكيد الطلب (${formatPrice(finalTotal)})`}
            </button>
          </form>

          {/* Order Summary */}
          <aside className="lg:col-span-5">
            <div className="sticky top-24 rounded-3xl border border-line bg-card p-6">
              <h2 className="mb-4 font-display text-lg font-semibold text-ink">ملخص الحقيبة</h2>
              <ul className="divide-y divide-line">
                {bag.lines.map((line) => (
                  <li key={line.key} className="flex items-center justify-between gap-3 py-3 text-[13px]">
                    <div className="min-w-0">
                      <p className="truncate font-medium text-ink">{line.label}</p>
                      <p className="text-[12px] text-muted">
                        المقاس: {line.size} · الكمية: {line.qty}
                      </p>
                    </div>
                    <span className="shrink-0 font-semibold text-ink">
                      {formatPrice(line.price * line.qty)}
                    </span>
                  </li>
                ))}
              </ul>

              <div className="mt-4 flex gap-2 border-t border-line pt-4">
                <input
                  type="text"
                  value={couponCode}
                  onChange={(e) => setCouponCode(e.target.value)}
                  placeholder="كود الخصم"
                  className="h-10 flex-1 rounded-xl border border-line bg-background px-3 text-[13px] text-ink outline-none focus:border-brand"
                />
                <button
                  type="button"
                  onClick={handleApplyCoupon}
                  className="h-10 rounded-xl bg-stage px-4 text-[13px] font-medium text-ink hover:bg-brand hover:text-white"
                >
                  تطبيق
                </button>
              </div>
              {couponMsg && <p className="mt-2 text-[12px] text-brand">{couponMsg}</p>}

              <div className="mt-5 space-y-2 border-t border-line pt-4 text-[14px]">
                <div className="flex justify-between text-muted">
                  <span>المجموع الفرعي</span>
                  <span>{formatPrice(bag.subtotal)}</span>
                </div>
                {couponDiscount > 0 && (
                  <div className="flex justify-between text-emerald-600">
                    <span>الخصم</span>
                    <span>-{formatPrice(couponDiscount)}</span>
                  </div>
                )}
                <div className="flex justify-between border-t border-line pt-2 text-base font-bold text-ink">
                  <span>الإجمالي</span>
                  <span>{formatPrice(finalTotal)}</span>
                </div>
              </div>
            </div>
          </aside>
        </div>
      )}
    </div>
  );
}
