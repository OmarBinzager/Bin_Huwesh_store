"use client";

import Link from "next/link";
import { useEffect, useMemo, useState } from "react";
import { useDemoBag } from "@/components/store/DemoBag";
import MapLocationPicker from "@/components/store/MapLocationPicker";
import { getAuthToken, getAuthUser } from "@/lib/auth-api";
import { useCurrency } from "@/lib/currency";
import {
  addAddress,
  fetchAddresses,
  fetchCoupons,
  fetchDeliveryFeeSetup,
  fetchStoreConfig,
  fetchTimeSlots,
  type BranchDeliveryFeeInfo,
  type CouponItem,
  type CustomerAddress,
  type StoreConfig,
  type TimeSlotItem,
} from "@/lib/customer-api";
import { useI18n } from "@/lib/i18n/context";
import { API_BASE_URL } from "@/lib/storefront-api";

type OfflineMethod = {
  id: number;
  method_name: string;
  image?: string | null;
  image_full_url?: string | null;
  method_fields?: Array<{
    field_name?: string;
    field_data?: string;
    input_name?: string;
    input_data?: string;
  }>;
  method_informations?: Array<{
    information_name: string;
    information_placeholder?: string;
    information_required?: number | boolean;
  }>;
  payment_note?: string | null;
};

type PaymentChoice = "cash_on_delivery" | "offline_payment" | "digital_payment";

export default function CheckoutClient() {
  const { locale, dir, t } = useI18n();
  const { activeCurrency, formatMoney } = useCurrency();
  const bag = useDemoBag();

  const [config, setConfig] = useState<StoreConfig | null>(null);
  const [token, setToken] = useState<string | null>(() => getAuthToken());

  const [branchId, setBranchId] = useState<number>(1);
  const [deliveryBranches, setDeliveryBranches] = useState<BranchDeliveryFeeInfo[]>([]);
  const [selectedAreaId, setSelectedAreaId] = useState<number | "">("");
  const [timeSlots, setTimeSlots] = useState<TimeSlotItem[]>([]);
  const [timeSlotId, setTimeSlotId] = useState<number | "">("");
  const [deliveryDate, setDeliveryDate] = useState<string>(() => {
    const d = new Date();
    return d.toISOString().slice(0, 10);
  });

  const [addresses, setAddresses] = useState<CustomerAddress[]>([]);
  const [selectedAddressId, setSelectedAddressId] = useState<number | "new">("new");
  const [addressType, setAddressType] = useState<"home" | "work" | "other">("home");
  const [saveNewAddress, setSaveNewAddress] = useState(true);

  const [name, setName] = useState(() => {
    const u = getAuthUser();
    return u ? `${u.f_name ?? ""} ${u.l_name ?? ""}`.trim() : "";
  });
  const [phone, setPhone] = useState(() => getAuthUser()?.phone ?? "");
  const [city, setCity] = useState(locale === "en" ? "Hadramout - Al Mukalla" : "حضرموت - المكلا");
  const [address, setAddress] = useState("");
  const [latitude, setLatitude] = useState("");
  const [longitude, setLongitude] = useState("");
  const [orderNote, setOrderNote] = useState("");

  const [coupons, setCoupons] = useState<CouponItem[]>([]);
  const [couponCode, setCouponCode] = useState("");
  const [appliedCoupon, setAppliedCoupon] = useState<string | null>(null);
  const [couponDiscount, setCouponDiscount] = useState(0);
  const [couponMsg, setCouponMsg] = useState<string | null>(null);

  const [paymentChoice, setPaymentChoice] = useState<PaymentChoice>("cash_on_delivery");
  const [offlineMethods, setOfflineMethods] = useState<OfflineMethod[]>([]);
  const [selectedOfflineName, setSelectedOfflineName] = useState("");
  const [offlineFieldValues, setOfflineFieldValues] = useState<Record<string, string>>({});
  const [receiptFile, setReceiptFile] = useState<File | null>(null);
  const [receiptPreviewUrl, setReceiptPreviewUrl] = useState<string | null>(null);
  const [paymentReference, setPaymentReference] = useState("");
  const [selectedGateway, setSelectedGateway] = useState("");
  const [bringChangeAmount, setBringChangeAmount] = useState("");

  const [submitting, setSubmitting] = useState(false);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);
  const [orderSuccess, setOrderSuccess] = useState<{ orderId: string | number; total: number } | null>(
    null,
  );

  useEffect(() => {
    let active = true;
    fetchStoreConfig(locale).then((cfg) => {
      if (!active || !cfg) return;
      setConfig(cfg);
      if (cfg.branches?.length) {
        setBranchId(cfg.branches[0].id);
      }
      if (cfg.active_payment_method_list?.length) {
        setSelectedGateway(cfg.active_payment_method_list[0].gateway);
      }
    });

    fetchTimeSlots(locale).then((slots) => {
      if (!active) return;
      setTimeSlots(slots);
      if (slots.length > 0) setTimeSlotId(slots[0].id);
    });

    fetchCoupons(locale).then((list) => {
      if (active) setCoupons(list);
    });

    fetchDeliveryFeeSetup(locale).then((branches) => {
      if (!active) return;
      setDeliveryBranches(branches);
      const firstBranch = branches[0];
      if (
        firstBranch?.delivery_charge_setup?.delivery_charge_type === "area" &&
        firstBranch.delivery_charge_by_area?.length
      ) {
        setSelectedAreaId(firstBranch.delivery_charge_by_area[0].id);
      }
    });

    fetch(`${API_BASE_URL}/api/v1/offline-payment-method/list`, {
      headers: { Accept: "application/json", "X-localization": locale },
    })
      .then((r) => (r.ok ? r.json() : []))
      .then((data) => {
        if (!active) return;
        const list = Array.isArray(data) ? data : data?.data ?? [];
        setOfflineMethods(list);
        if (list.length > 0) setSelectedOfflineName(list[0].method_name || "");
      })
      .catch(() => {});

    return () => {
      active = false;
    };
  }, [locale]);

  useEffect(() => {
    const syncCustomer = () => {
      const tk = getAuthToken();
      setToken(tk);
      const guestId = typeof window !== "undefined" ? localStorage.getItem("bh_guest_id") || undefined : undefined;
      if (tk || guestId) {
        fetchAddresses(locale, guestId).then((list) => {
          setAddresses(list);
          if (list.length > 0) {
            setSelectedAddressId(list[0].id);
          }
        });
      }
    };
    syncCustomer();
    window.addEventListener("bh-auth-change", syncCustomer);
    return () => window.removeEventListener("bh-auth-change", syncCustomer);
  }, [locale]);

  const ensureAuthHeaders = async (): Promise<{
    headers: Record<string, string>;
    guestId?: string;
  }> => {
    const tk = getAuthToken();
    if (tk) {
      return {
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
          "X-localization": locale,
          "X-Currency-Code": activeCurrency.currency_code,
          Authorization: `Bearer ${tk}`,
        },
      };
    }

    let guestId = typeof window !== "undefined" ? localStorage.getItem("bh_guest_id") : null;
    if (!guestId) {
      const res = await fetch(`${API_BASE_URL}/api/v1/guest/add`, {
        method: "POST",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
          "X-localization": locale,
        },
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
        "X-Currency-Code": activeCurrency.currency_code,
        ...(guestId ? { "guest-id": guestId } : {}),
      },
    };
  };

  const applyCouponCode = async (codeToApply: string) => {
    setCouponMsg(null);
    const clean = codeToApply.trim();
    if (!clean) return;
    setCouponCode(clean);

    try {
      const { headers, guestId } = await ensureAuthHeaders();
      const qs = new URLSearchParams({ code: clean });
      if (guestId) qs.set("guest_id", guestId);
      const res = await fetch(`${API_BASE_URL}/api/v1/coupon/apply?${qs.toString()}`, {
        headers,
      });
      const data = await res.json();
      if (!res.ok) {
        setAppliedCoupon(null);
        setCouponDiscount(0);
        setCouponMsg(data?.errors?.[0]?.message || data?.message || t.checkout.couponInvalid);
        return;
      }

      const subtotalAfterLooks = Math.max(0, bag.subtotal - bag.lookDiscount);
      let disc = 0;
      if (data?.discount_type === "percent") {
        disc = Math.round((subtotalAfterLooks * Number(data.discount || 0)) / 100);
        if (data?.max_discount && disc > Number(data.max_discount)) {
          disc = Number(data.max_discount);
        }
      } else {
        disc = Number(data?.discount || 0);
      }
      setAppliedCoupon(clean);
      setCouponDiscount(disc);
      setCouponMsg(t.checkout.couponApplied(formatMoney(disc)));
    } catch {
      setCouponMsg(t.checkout.couponInvalid);
    }
  };

  const activeBranchDelivery = useMemo(
    () => deliveryBranches.find((b) => b.id === branchId) ?? deliveryBranches[0],
    [deliveryBranches, branchId],
  );
  const deliveryChargeType = activeBranchDelivery?.delivery_charge_setup?.delivery_charge_type ?? "fixed";
  const deliveryAreas = useMemo(
    () => activeBranchDelivery?.delivery_charge_by_area ?? [],
    [activeBranchDelivery],
  );
  const selectedArea = useMemo(
    () => deliveryAreas.find((a) => a.id === Number(selectedAreaId)) ?? null,
    [deliveryAreas, selectedAreaId],
  );

  const deliveryDistanceKm = useMemo(() => {
    if (deliveryChargeType !== "distance") return null;
    const bLat = Number(activeBranchDelivery?.latitude ?? config?.branches?.[0]?.latitude);
    const bLng = Number(activeBranchDelivery?.longitude ?? config?.branches?.[0]?.longitude);
    const cLat = Number(latitude);
    const cLng = Number(longitude);
    if (!Number.isFinite(bLat) || !Number.isFinite(bLng) || !Number.isFinite(cLat) || !Number.isFinite(cLng) || !latitude || !longitude) {
      return null;
    }
    const toRad = (deg: number) => (deg * Math.PI) / 180;
    const R = 6371;
    const dLat = toRad(cLat - bLat);
    const dLon = toRad(cLng - bLng);
    const a =
      Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos(toRad(bLat)) * Math.cos(toRad(cLat)) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
    return 2 * R * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  }, [deliveryChargeType, activeBranchDelivery, config, latitude, longitude]);

  const deliveryFee = useMemo(() => {
    if (
      config?.free_delivery_over_amount_status === 1 &&
      config.free_delivery_over_amount &&
      bag.subtotal >= Number(config.free_delivery_over_amount)
    ) {
      return 0;
    }

    if (deliveryChargeType === "area") {
      return Number(selectedArea?.delivery_charge ?? 0);
    }

    if (deliveryChargeType === "distance") {
      const setup = activeBranchDelivery?.delivery_charge_setup;
      const minCharge = Number(setup?.minimum_delivery_charge ?? config?.delivery_management?.min_shipping_charge ?? 0);
      const perKm = Number(setup?.delivery_charge_per_kilometer ?? config?.delivery_management?.shipping_per_km ?? 0);
      const minFreeKm = Number(setup?.minimum_distance_for_free_delivery ?? 0);
      if (deliveryDistanceKm === null) return minCharge;
      if (minFreeKm > 0 && deliveryDistanceKm < minFreeKm) return 0;
      return deliveryDistanceKm > 0 ? Math.max(deliveryDistanceKm * perKm, minCharge) : minCharge;
    }

    const fixedSetup = activeBranchDelivery?.delivery_charge_setup?.fixed_delivery_charge;
    return Number(fixedSetup ?? config?.delivery_charge ?? 0);
  }, [config, bag.subtotal, deliveryChargeType, selectedArea, activeBranchDelivery, deliveryDistanceKm]);

  const finalTotal = Math.max(0, bag.subtotal - bag.lookDiscount - couponDiscount + deliveryFee);

  const codEnabled = config?.cash_on_delivery !== "false";
  const offlineEnabled = config?.offline_payment !== "false" && offlineMethods.length > 0;
  const digitalEnabled =
    config?.digital_payment !== "false" && Boolean(config?.active_payment_method_list?.length);
  const guestBlocked = config?.guest_checkout === 0 && !token;

  const selectedOfflineObj = offlineMethods.find((m) => m.method_name === selectedOfflineName);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMsg(null);

    if (guestBlocked) {
      setErrorMsg(t.checkout.guestDisabledMsg);
      return;
    }

    if (
      paymentChoice === "cash_on_delivery" &&
      config?.maximum_amount_for_cod_order_status === 1 &&
      config.maximum_amount_for_cod_order &&
      finalTotal > Number(config.maximum_amount_for_cod_order)
    ) {
      setErrorMsg(t.checkout.codLimitMsg(formatMoney(Number(config.maximum_amount_for_cod_order))));
      return;
    }

    const usingSavedAddress = selectedAddressId !== "new";
    if (!usingSavedAddress) {
      if (!name.trim() || !phone.trim() || !address.trim()) {
        setErrorMsg(t.checkout.fillRequired);
        return;
      }
    }

    if (deliveryChargeType === "area" && deliveryAreas.length > 0 && !selectedAreaId) {
      setErrorMsg(t.checkout.deliveryAreaRequired);
      return;
    }

    if (paymentChoice === "offline_payment" && selectedOfflineObj?.method_informations?.length) {
      for (const info of selectedOfflineObj.method_informations) {
        const isReq = info.information_required === 1 || info.information_required === true;
        if (isReq && !(offlineFieldValues[info.information_name] ?? "").trim()) {
          setErrorMsg(t.checkout.requiredFieldMsg(info.information_name));
          return;
        }
      }
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
        quantity: l.qty,
        price: l.price,
        variation: l.size && l.size !== "Standard" ? [{ type: l.size, price: l.price }] : [],
        variant: l.size && l.size !== "Standard" ? l.size : "",
        discount_amount: 0,
        tax_amount: 0,
      }));

      let deliveryAddressId: number | undefined =
        usingSavedAddress && typeof selectedAddressId === "number" ? selectedAddressId : undefined;

      if (!deliveryAddressId && (saveNewAddress || token || guestId)) {
        const addrRes = await addAddress(
          {
            contact_person_name: name.trim(),
            contact_person_number: phone.trim(),
            address_type: addressType,
            address: `${city} — ${address.trim()}`,
            ...(latitude ? { latitude } : {}),
            ...(longitude ? { longitude } : {}),
          },
          locale,
          guestId,
        );
        if (addrRes.ok) {
          const list = await fetchAddresses(locale, guestId);
          if (list.length > 0) {
            deliveryAddressId = list[list.length - 1]?.id ?? list[0]?.id;
          }
        }
      }

      const lookIds = Array.from(
        new Set(
          purchasableLines
            .map((l) => l.lookId)
            .filter((id): id is string => Boolean(id)),
        ),
      );

      const resolvedPaymentMethod =
        paymentChoice === "digital_payment" && selectedGateway ? selectedGateway : paymentChoice;

      const placeRes = await fetch(`${API_BASE_URL}/api/v1/customer/order/place`, {
        method: "POST",
        headers,
        body: JSON.stringify({
          cart: cartPayload,
          order_amount: finalTotal,
          payment_method: resolvedPaymentMethod,
          order_type: "delivery",
          branch_id: branchId || 1,
          delivery_date: deliveryDate,
          ...(timeSlotId ? { time_slot_id: Number(timeSlotId) } : {}),
          ...(deliveryChargeType === "area" && selectedAreaId
            ? { selected_delivery_area: Number(selectedAreaId) }
            : {}),
          ...(deliveryChargeType === "distance" && deliveryDistanceKm !== null
            ? { distance: Number(deliveryDistanceKm.toFixed(2)) }
            : {}),
          ...(deliveryAddressId ? { delivery_address_id: deliveryAddressId } : {}),
          ...(lookIds.length > 0 ? { look_ids: lookIds } : {}),
          is_guest: token ? 0 : 1,
          ...(token && getAuthUser()?.id ? { customer_id: getAuthUser()!.id } : {}),
          ...(guestId ? { guest_id: guestId } : {}),
          ...(appliedCoupon ? { coupon_code: appliedCoupon } : {}),
          ...(orderNote.trim() ? { order_note: orderNote.trim() } : {}),
          ...(paymentChoice === "cash_on_delivery" && bringChangeAmount.trim()
            ? { bring_change_amount: Number(bringChangeAmount) }
            : {}),
          currency_code: activeCurrency.currency_code,
          ...(paymentChoice === "offline_payment"
            ? {
                payment_by: selectedOfflineName || (locale === "en" ? "Bank Transfer" : "تحويل بنكي"),
                payment_note: paymentReference.trim() || (locale === "en" ? "Online store transfer" : "تحويل من المتجر الإلكتروني"),
              }
            : {}),
        }),
      });

      const placeData = await placeRes.json().catch(() => ({}));
      if (!placeRes.ok) {
        throw new Error(
          placeData?.errors?.[0]?.message || placeData?.message || t.checkout.orderFailed,
        );
      }

      const createdOrderId = placeData?.order_id ?? placeData?.id ?? "—";

      if (paymentChoice === "offline_payment" && createdOrderId !== "—") {
        const paymentInfoPayload = {
          payment_id: selectedOfflineObj?.id,
          payment_name: selectedOfflineObj?.method_name || selectedOfflineName,
          payment_note: selectedOfflineObj?.payment_note || "",
          method_fields: (selectedOfflineObj?.method_fields ?? []).map((f) => ({
            [f.field_name ?? f.input_name ?? "field"]: f.field_data ?? f.input_data ?? "",
          })),
          method_information: [
            ...(selectedOfflineObj?.method_informations ?? []).map((info) => ({
              [info.information_name]: (offlineFieldValues[info.information_name] ?? "").trim(),
            })),
            ...(paymentReference.trim()
              ? [{ [t.checkout.paymentRef]: paymentReference.trim() }]
              : []),
          ],
        };

        const formData = new FormData();
        formData.append("order_id", String(createdOrderId));
        formData.append("is_partial", "0");
        formData.append("payment_info", JSON.stringify(paymentInfoPayload));
        if (receiptFile) {
          formData.append("receipt_image", receiptFile);
        }

        const offlineHeaders: Record<string, string> = {
          Accept: "application/json",
          "X-localization": locale,
        };
        if (token) offlineHeaders.Authorization = `Bearer ${token}`;
        else if (guestId) offlineHeaders["guest-id"] = guestId;

        await fetch(`${API_BASE_URL}/api/v1/customer/order/store-offline-data`, {
          method: "POST",
          headers: offlineHeaders,
          body: formData,
        });
      }

      setOrderSuccess({ orderId: createdOrderId, total: finalTotal });
      bag.clear();
    } catch (err) {
      setErrorMsg((err as Error).message || t.checkout.orderFailed);
    } finally {
      setSubmitting(false);
    }
  };

  if (orderSuccess) {
    return (
      <div dir={dir} className="mx-auto max-w-xl px-4 py-16 text-center">
        <div className="rounded-3xl border border-line bg-card p-8 shadow-sm">
          <div className="mx-auto mb-4 grid size-14 place-items-center rounded-full bg-emerald-500/15 text-emerald-600">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5">
              <path d="M5 13l4 4L19 7" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
          </div>
          <h1 className="font-display text-2xl font-bold text-ink">{t.checkout.successTitle}</h1>
          <p className="mt-2 text-sm text-muted">{t.checkout.successBody}</p>
          <div className="mt-6 rounded-2xl bg-stage p-4 text-sm">
            <div className="flex justify-between py-1">
              <span className="text-muted">{t.checkout.orderNumber}</span>
              <span className="nums font-bold text-ink">#{orderSuccess.orderId}</span>
            </div>
            <div className="flex justify-between py-1">
              <span className="text-muted">{t.checkout.totalPaid}</span>
              <span className="nums font-bold text-brand">{formatMoney(orderSuccess.total)}</span>
            </div>
          </div>
          <div className="mt-6 flex flex-wrap justify-center gap-3">
            {orderSuccess.orderId !== "—" && (
              <Link
                href={`/account/orders/${orderSuccess.orderId}`}
                className="inline-flex rounded-full bg-brand px-6 py-3 text-sm font-semibold text-white transition hover:opacity-90"
              >
                {t.account.orderDetailsBtn}
              </Link>
            )}
            {token && (
              <Link
                href="/account/orders"
                className="inline-flex rounded-full border border-line bg-card px-6 py-3 text-sm font-semibold text-ink hover:bg-stage"
              >
                {t.userMenu.orders}
              </Link>
            )}
            <Link
              href="/"
              className="inline-flex rounded-full bg-ink px-7 py-3 text-sm font-semibold text-on-ink hover:bg-brand"
            >
              {t.checkout.continueShopping}
            </Link>
          </div>
        </div>
      </div>
    );
  }

  if (bag.lines.length === 0) {
    return (
      <div dir={dir} className="mx-auto max-w-xl px-4 py-20 text-center">
        <h1 className="font-display text-2xl font-bold text-ink">{t.checkout.emptyTitle}</h1>
        <p className="mt-2 text-sm text-muted">{t.checkout.emptyBody}</p>
        <Link
          href="/"
          className="mt-6 inline-flex rounded-full bg-ink px-7 py-3 text-sm font-semibold text-on-ink hover:bg-brand"
        >
          {t.checkout.backToStore}
        </Link>
      </div>
    );
  }

  return (
    <div dir={dir} className="mx-auto max-w-[1440px] px-4 py-8 sm:px-8">
      <nav aria-label={t.breadcrumbs.ariaLabel} className="mb-4 flex items-center gap-2 text-xs text-muted">
        <Link href="/" className="hover:text-ink transition">
          {t.breadcrumbs.home}
        </Link>
        <span>/</span>
        <span className="font-medium text-ink">{t.checkout.title}</span>
      </nav>

      <h1 className="mb-8 font-display text-3xl font-bold text-ink">{t.checkout.title}</h1>

      {guestBlocked && (
        <div className="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-3xl border border-brand/30 bg-brand/5 p-5">
          <p className="text-sm font-medium text-ink">{t.checkout.guestDisabledMsg}</p>
          <Link
            href="/login"
            className="rounded-full bg-brand px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-deep"
          >
            {t.account.goToLogin}
          </Link>
        </div>
      )}

      <form onSubmit={handleSubmit} className="grid gap-8 lg:grid-cols-12">
        {/* Left: Fulfillment, Delivery Details, TimeSlot & Payment */}
        <div className="space-y-6 lg:col-span-7">
          {/* Delivery Schedule */}
          <div className="rounded-3xl border border-line bg-card p-6 space-y-4">
            <h2 className="font-display text-lg font-semibold text-ink">{t.checkout.deliveryScheduleTitle}</h2>

            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <label className="mb-1 block text-xs text-muted">{t.checkout.deliveryDateLabel}</label>
                <input
                  type="date"
                  dir="ltr"
                  value={deliveryDate}
                  onChange={(e) => setDeliveryDate(e.target.value)}
                  className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-sm text-ink outline-none focus:border-brand"
                />
              </div>
              {timeSlots.length > 0 && (
                <div>
                  <label className="mb-1 block text-xs text-muted">{t.checkout.timeSlotLabel}</label>
                  <select
                    value={timeSlotId}
                    onChange={(e) => setTimeSlotId(Number(e.target.value))}
                    className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-sm text-ink outline-none focus:border-brand"
                  >
                    {timeSlots.map((slot) => (
                      <option key={slot.id} value={slot.id}>
                        {slot.start_time} - {slot.end_time}
                      </option>
                    ))}
                  </select>
                </div>
              )}
            </div>

            {deliveryChargeType === "area" && deliveryAreas.length > 0 && (
              <div>
                <label className="mb-1 block text-xs text-muted">
                  {t.checkout.deliveryAreaLabel} *
                </label>
                <select
                  required
                  value={selectedAreaId}
                  onChange={(e) => setSelectedAreaId(e.target.value ? Number(e.target.value) : "")}
                  className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-sm text-ink outline-none focus:border-brand"
                >
                  <option value="">{t.checkout.selectDeliveryArea}</option>
                  {deliveryAreas.map((area) => (
                    <option key={area.id} value={area.id}>
                      {area.area_name} — {formatMoney(area.delivery_charge)}
                    </option>
                  ))}
                </select>
              </div>
            )}
          </div>

          {/* Delivery Address */}
          <div className="rounded-3xl border border-line bg-card p-6 space-y-4">
              <h2 className="font-display text-lg font-semibold text-ink">{t.checkout.customerSection}</h2>

              {addresses.length > 0 && (
                <div className="space-y-2">
                  <p className="text-xs font-medium text-muted">{t.checkout.savedAddressesLabel}</p>
                  <div className="grid gap-2.5 sm:grid-cols-2">
                    {addresses.map((addr) => (
                      <button
                        key={addr.id}
                        type="button"
                        onClick={() => setSelectedAddressId(addr.id)}
                        className={`rounded-2xl border p-3.5 text-start transition ${
                          selectedAddressId === addr.id
                            ? "border-brand bg-brand/5"
                            : "border-line bg-background hover:bg-stage"
                        }`}
                      >
                        <div className="flex items-center justify-between gap-2">
                          <span className="rounded-full bg-stage px-2.5 py-0.5 text-[11px] font-medium text-ink">
                            {addr.address_type}
                          </span>
                          <span className="text-xs text-muted" dir="ltr">
                            {addr.contact_person_number}
                          </span>
                        </div>
                        <p className="mt-1.5 text-sm font-medium text-ink">{addr.contact_person_name}</p>
                        <p className="mt-0.5 line-clamp-2 text-xs text-muted">{addr.address}</p>
                      </button>
                    ))}
                    <button
                      type="button"
                      onClick={() => setSelectedAddressId("new")}
                      className={`flex items-center justify-center rounded-2xl border border-dashed p-3.5 text-sm font-medium transition ${
                        selectedAddressId === "new"
                          ? "border-brand bg-brand/5 text-brand"
                          : "border-line text-muted hover:text-ink"
                      }`}
                    >
                      + {t.checkout.useNewAddress}
                    </button>
                  </div>
                </div>
              )}

              {selectedAddressId === "new" && (
                <div className="grid gap-4 sm:grid-cols-2">
                  <div>
                    <label className="mb-1 block text-xs text-muted">{t.checkout.fullName} *</label>
                    <input
                      type="text"
                      required
                      value={name}
                      onChange={(e) => setName(e.target.value)}
                      placeholder={t.checkout.fullNamePlaceholder}
                      className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-sm text-ink outline-none focus:border-brand"
                    />
                  </div>
                  <div>
                    <label className="mb-1 block text-xs text-muted">{t.checkout.phone} *</label>
                    <input
                      type="tel"
                      required
                      dir="ltr"
                      value={phone}
                      onChange={(e) => setPhone(e.target.value)}
                      placeholder={t.checkout.phonePlaceholder}
                      className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-sm text-ink outline-none focus:border-brand"
                    />
                  </div>
                  <div>
                    <label className="mb-1 block text-xs text-muted">{t.checkout.addressTypeLabel}</label>
                    <select
                      value={addressType}
                      onChange={(e) => setAddressType(e.target.value as "home" | "work" | "other")}
                      className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-sm text-ink outline-none focus:border-brand"
                    >
                      <option value="home">{t.checkout.addressTypeHome}</option>
                      <option value="work">{t.checkout.addressTypeWork}</option>
                      <option value="other">{t.checkout.addressTypeOther}</option>
                    </select>
                  </div>
                  <div>
                    <label className="mb-1 block text-xs text-muted">{t.checkout.city} *</label>
                    <input
                      type="text"
                      required
                      value={city}
                      onChange={(e) => setCity(e.target.value)}
                      className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-sm text-ink outline-none focus:border-brand"
                    />
                  </div>
                  <div className="sm:col-span-2">
                    <label className="mb-1 block text-xs text-muted">{t.checkout.address} *</label>
                    <input
                      type="text"
                      required
                      value={address}
                      onChange={(e) => setAddress(e.target.value)}
                      placeholder={t.checkout.addressPlaceholder}
                      className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-sm text-ink outline-none focus:border-brand"
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
                        if (val.address) setAddress(val.address);
                      }}
                    />
                  </div>
                  {token && (
                    <label className="sm:col-span-2 flex items-center gap-2 text-xs text-muted cursor-pointer">
                      <input
                        type="checkbox"
                        checked={saveNewAddress}
                        onChange={(e) => setSaveNewAddress(e.target.checked)}
                        className="accent-brand"
                      />
                      <span>{t.checkout.saveAddressBtn}</span>
                    </label>
                  )}
                </div>
              )}

              <div>
                <label className="mb-1 block text-xs text-muted">{t.checkout.orderNote}</label>
                <textarea
                  rows={2}
                  value={orderNote}
                  onChange={(e) => setOrderNote(e.target.value)}
                  placeholder={t.checkout.orderNotePlaceholder}
                  className="w-full rounded-2xl border border-line bg-background px-4 py-3 text-sm text-ink outline-none focus:border-brand"
                />
              </div>
            </div>

          {/* Coupon */}
          <div className="rounded-3xl border border-line bg-card p-6 space-y-3">
            <h2 className="font-display text-lg font-semibold text-ink">{t.checkout.couponTitle}</h2>
            <div className="flex gap-2">
              <input
                type="text"
                dir="ltr"
                value={couponCode}
                onChange={(e) => setCouponCode(e.target.value)}
                placeholder={t.checkout.couponPlaceholder}
                className="flex-1 rounded-full border border-line bg-background px-4 py-2.5 text-sm text-ink outline-none focus:border-brand"
              />
              <button
                type="button"
                onClick={() => void applyCouponCode(couponCode)}
                className="rounded-full bg-ink px-5 py-2.5 text-sm font-medium text-on-ink hover:bg-brand"
              >
                {t.checkout.applyCoupon}
              </button>
            </div>
            {coupons.length > 0 && (
              <div className="pt-1">
                <p className="mb-1.5 text-xs text-muted">{t.checkout.availableCoupons}</p>
                <div className="flex flex-wrap gap-2">
                  {coupons.slice(0, 6).map((c) => (
                    <button
                      key={c.id}
                      type="button"
                      onClick={() => void applyCouponCode(c.code)}
                      className="rounded-full border border-dashed border-brand/50 bg-brand/5 px-3 py-1 text-xs font-medium text-brand hover:bg-brand/10"
                    >
                      {c.code}
                    </button>
                  ))}
                </div>
              </div>
            )}
            {couponMsg && (
              <p className={`text-xs ${appliedCoupon ? "text-emerald-600" : "text-brand"}`}>
                {couponMsg}
              </p>
            )}
          </div>

          {/* Payment Method */}
          <div className="rounded-3xl border border-line bg-card p-6 space-y-4">
            <h2 className="font-display text-lg font-semibold text-ink">{t.checkout.paymentSection}</h2>

            <div className="space-y-3">
              {codEnabled && (
                <label className="flex cursor-pointer items-center gap-3 rounded-2xl border border-line p-4 transition hover:border-ink">
                  <input
                    type="radio"
                    name="payment"
                    checked={paymentChoice === "cash_on_delivery"}
                    onChange={() => setPaymentChoice("cash_on_delivery")}
                    className="accent-brand"
                  />
                  <div>
                    <p className="text-sm font-semibold text-ink">{t.checkout.cashOnDelivery}</p>
                    <p className="text-xs text-muted">
                      {locale === "en" ? "Pay cash when your order arrives" : "ادفع نقداً عند وصول الطلب"}
                    </p>
                  </div>
                </label>
              )}

              {paymentChoice === "cash_on_delivery" && (
                <div className="rounded-2xl bg-stage p-4">
                  <label className="mb-1 block text-xs text-muted">{t.checkout.bringChangeLabel}</label>
                  <input
                    type="number"
                    min={0}
                    dir="ltr"
                    value={bringChangeAmount}
                    onChange={(e) => setBringChangeAmount(e.target.value)}
                    placeholder={t.checkout.bringChangePlaceholder}
                    className="w-full rounded-xl border border-line bg-background px-3.5 py-2 text-sm text-ink outline-none focus:border-brand"
                  />
                </div>
              )}

              {offlineEnabled && (
                <label className="flex cursor-pointer items-center gap-3 rounded-2xl border border-line p-4 transition hover:border-ink">
                  <input
                    type="radio"
                    name="payment"
                    checked={paymentChoice === "offline_payment"}
                    onChange={() => setPaymentChoice("offline_payment")}
                    className="accent-brand"
                  />
                  <div>
                    <p className="text-sm font-semibold text-ink">{t.checkout.offlinePayment}</p>
                    <p className="text-xs text-muted">
                      {locale === "en"
                        ? "Transfer the amount and enter the transfer reference"
                        : "حوّل المبلغ وأدخل رقم الحوالة أو المرجع"}
                    </p>
                  </div>
                </label>
              )}

              {paymentChoice === "offline_payment" && offlineEnabled && (
                <div className="space-y-4 rounded-2xl bg-stage p-4">
                  <div>
                    <label className="mb-2 block text-xs font-medium text-muted">
                      {locale === "en" ? "Select Account / Bank" : "اختر الحساب / البنك أو خدمة التحويل"}
                    </label>
                    <div className="grid gap-2.5 sm:grid-cols-2">
                      {offlineMethods.map((m) => {
                        const selected = selectedOfflineName === m.method_name;
                        return (
                          <button
                            key={m.id}
                            type="button"
                            onClick={() => {
                              setSelectedOfflineName(m.method_name);
                              setOfflineFieldValues({});
                            }}
                            className={`flex items-center gap-3 rounded-xl border p-3 text-start transition ${
                              selected
                                ? "border-brand bg-brand/5 text-ink"
                                : "border-line bg-background text-muted hover:text-ink"
                            }`}
                          >
                            {m.image_full_url ? (
                              <img
                                src={m.image_full_url}
                                alt={m.method_name}
                                className="size-10 shrink-0 rounded-lg border border-line bg-white object-contain p-1"
                              />
                            ) : (
                              <span className="grid size-10 shrink-0 place-items-center rounded-lg border border-line bg-card text-xs font-bold text-muted">
                                {m.method_name.slice(0, 2)}
                              </span>
                            )}
                            <span className="truncate text-sm font-medium text-ink">{m.method_name}</span>
                          </button>
                        );
                      })}
                    </div>
                  </div>

                  {selectedOfflineObj?.method_fields && selectedOfflineObj.method_fields.length > 0 && (
                    <div className="rounded-xl border border-line bg-card p-3.5 text-xs space-y-1.5">
                      {selectedOfflineObj.method_fields.map((f, idx) => (
                        <div key={idx} className="flex justify-between gap-2">
                          <span className="text-muted">{f.field_name ?? f.input_name}:</span>
                          <span className="font-medium text-ink" dir="ltr">
                            {f.field_data ?? f.input_data}
                          </span>
                        </div>
                      ))}
                      {selectedOfflineObj.payment_note && (
                        <p className="border-t border-line pt-2 text-[11px] text-muted">
                          {selectedOfflineObj.payment_note}
                        </p>
                      )}
                    </div>
                  )}

                  {selectedOfflineObj?.method_informations &&
                    selectedOfflineObj.method_informations.length > 0 && (
                      <div className="space-y-3">
                        {selectedOfflineObj.method_informations.map((info, idx) => {
                          const isReq =
                            info.information_required === 1 || info.information_required === true;
                          return (
                            <div key={`${selectedOfflineObj.id}-${idx}`}>
                              <label className="mb-1 block text-xs text-muted">
                                {info.information_name} {isReq ? <span className="text-brand">*</span> : ""}
                              </label>
                              <input
                                type="text"
                                required={isReq}
                                value={offlineFieldValues[info.information_name] ?? ""}
                                onChange={(e) =>
                                  setOfflineFieldValues((prev) => ({
                                    ...prev,
                                    [info.information_name]: e.target.value,
                                  }))
                                }
                                placeholder={info.information_placeholder || info.information_name}
                                className="w-full rounded-xl border border-line bg-background px-3.5 py-2.5 text-sm text-ink outline-none focus:border-brand"
                              />
                            </div>
                          );
                        })}
                      </div>
                    )}

                  {(!selectedOfflineObj?.method_informations ||
                    selectedOfflineObj.method_informations.length === 0) && (
                    <div>
                      <label className="mb-1 block text-xs text-muted">{t.checkout.paymentRef}</label>
                      <input
                        type="text"
                        value={paymentReference}
                        onChange={(e) => setPaymentReference(e.target.value)}
                        placeholder={t.checkout.paymentRefPlaceholder}
                        className="w-full rounded-xl border border-line bg-background px-3.5 py-2.5 text-sm text-ink outline-none focus:border-brand"
                      />
                    </div>
                  )}

                  {/* Transfer receipt / bond image upload */}
                  <div>
                    <label className="mb-1.5 block text-xs text-muted">
                      {t.checkout.attachReceiptLabel}
                    </label>
                    <div className="flex flex-wrap items-center gap-3">
                      <label className="inline-flex h-9 cursor-pointer items-center rounded-xl border border-line bg-card px-4 text-xs font-medium text-ink transition hover:border-brand">
                        <span>{t.checkout.chooseReceiptBtn}</span>
                        <input
                          type="file"
                          accept="image/*"
                          className="sr-only"
                          onChange={(e) => {
                            const file = e.target.files?.[0] ?? null;
                            if (receiptPreviewUrl) URL.revokeObjectURL(receiptPreviewUrl);
                            setReceiptFile(file);
                            setReceiptPreviewUrl(file ? URL.createObjectURL(file) : null);
                          }}
                        />
                      </label>
                      {receiptFile && (
                        <button
                          type="button"
                          onClick={() => {
                            if (receiptPreviewUrl) URL.revokeObjectURL(receiptPreviewUrl);
                            setReceiptFile(null);
                            setReceiptPreviewUrl(null);
                          }}
                          className="h-9 rounded-xl border border-line px-3 text-xs text-brand hover:bg-brand/10"
                        >
                          {t.checkout.removeReceiptBtn}
                        </button>
                      )}
                    </div>
                    {receiptPreviewUrl && (
                      <div className="mt-2.5">
                        <img
                          src={receiptPreviewUrl}
                          alt="Receipt preview"
                          className="max-h-40 rounded-xl border border-line object-contain bg-card p-1"
                        />
                      </div>
                    )}
                  </div>
                </div>
              )}

              {digitalEnabled && (
                <label className="flex cursor-pointer items-center gap-3 rounded-2xl border border-line p-4 transition hover:border-ink">
                  <input
                    type="radio"
                    name="payment"
                    checked={paymentChoice === "digital_payment"}
                    onChange={() => setPaymentChoice("digital_payment")}
                    className="accent-brand"
                  />
                  <div>
                    <p className="text-sm font-semibold text-ink">{t.checkout.paymentDigital}</p>
                  </div>
                </label>
              )}

              {paymentChoice === "digital_payment" && digitalEnabled && (
                <div className="rounded-2xl bg-stage p-4">
                  <label className="mb-1 block text-xs text-muted">{t.checkout.selectGateway}</label>
                  <select
                    value={selectedGateway}
                    onChange={(e) => setSelectedGateway(e.target.value)}
                    className="w-full rounded-xl border border-line bg-background px-3.5 py-2.5 text-sm text-ink outline-none"
                  >
                    {config?.active_payment_method_list?.map((gw) => (
                      <option key={gw.gateway} value={gw.gateway}>
                        {gw.gateway_title || gw.gateway}
                      </option>
                    ))}
                  </select>
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Right: Order Summary */}
        <div className="lg:col-span-5">
          <div className="sticky top-6 rounded-3xl border border-line bg-card p-6 space-y-5">
            <h2 className="font-display text-lg font-semibold text-ink">{t.checkout.summarySection}</h2>

            <ul className="divide-y divide-line">
              {bag.lines.map((line) => (
                <li key={line.key} className="flex items-start gap-3 py-3 text-sm">
                  {line.image ? (
                    <img
                      src={line.image}
                      alt={line.label}
                      className="size-14 shrink-0 rounded-xl border border-line bg-stage object-cover"
                    />
                  ) : (
                    <div className="grid size-14 shrink-0 place-items-center rounded-xl border border-line bg-stage text-[11px] font-medium text-muted">
                      {line.label.slice(0, 2)}
                    </div>
                  )}
                  <div className="min-w-0 flex-1">
                    <p className="truncate font-medium text-ink">{line.label}</p>
                    <p className="text-xs text-muted">{line.size}</p>
                    <div className="mt-2 flex items-center gap-2">
                      <div className="inline-flex items-center rounded-full border border-line bg-background">
                        <button
                          type="button"
                          aria-label={t.checkout.decreaseQtyAria}
                          onClick={() => bag.updateQty(line.key, line.qty - 1)}
                          className="grid size-6 place-items-center text-xs text-ink hover:text-brand"
                        >
                          −
                        </button>
                        <span className="nums px-2 text-xs font-medium text-ink">{line.qty}</span>
                        <button
                          type="button"
                          aria-label={t.checkout.increaseQtyAria}
                          onClick={() => bag.updateQty(line.key, line.qty + 1)}
                          className="grid size-6 place-items-center text-xs text-ink hover:text-brand"
                        >
                          +
                        </button>
                      </div>
                      <button
                        type="button"
                        aria-label={t.checkout.removeItemAria}
                        onClick={() => bag.removeLine(line.key)}
                        className="text-[11px] font-medium text-brand hover:underline"
                      >
                        {t.account.deleteAddressBtn}
                      </button>
                    </div>
                  </div>
                  <span className="nums shrink-0 font-semibold text-ink">
                    {formatMoney(line.price * line.qty)}
                  </span>
                </li>
              ))}
            </ul>

            <div className="space-y-2 border-t border-line pt-4 text-sm">
              <div className="flex justify-between">
                <span className="text-muted">{t.checkout.subtotal}</span>
                <span className="nums text-ink">{formatMoney(bag.subtotal)}</span>
              </div>
              {bag.lookDiscount > 0 && (
                <div className="flex justify-between text-emerald-600">
                  <span>{t.checkout.lookDiscount}</span>
                  <span className="nums">-{formatMoney(bag.lookDiscount)}</span>
                </div>
              )}
              {couponDiscount > 0 && (
                <div className="flex justify-between text-emerald-600">
                  <span>{t.checkout.couponDiscount}</span>
                  <span className="nums">-{formatMoney(couponDiscount)}</span>
                </div>
              )}
              <div className="flex justify-between">
                <span className="text-muted">{t.checkout.deliveryChargeLabel}</span>
                <span className="nums text-ink">
                  {deliveryFee === 0 ? t.checkout.freeDeliveryLabel : formatMoney(deliveryFee)}
                </span>
              </div>
              {config?.free_delivery_over_amount_status === 1 &&
                Boolean(config.free_delivery_over_amount) &&
                deliveryFee > 0 && (
                  <p className="text-[11px] text-muted">
                    {t.checkout.freeDeliveryOverNote(formatMoney(Number(config.free_delivery_over_amount)))}
                  </p>
                )}
              <div className="flex justify-between border-t border-line pt-3 text-base font-bold">
                <span className="text-ink">{t.checkout.total}</span>
                <span className="nums text-brand">{formatMoney(finalTotal)}</span>
              </div>
            </div>

            {errorMsg && (
              <p className="rounded-2xl bg-brand/10 px-4 py-3 text-xs text-brand">{errorMsg}</p>
            )}

            <button
              type="submit"
              disabled={submitting || guestBlocked}
              className="w-full rounded-full bg-brand py-3.5 text-sm font-semibold text-white transition hover:bg-brand-deep disabled:opacity-50"
            >
              {submitting ? t.checkout.submitting : t.checkout.submitOrder}
            </button>
          </div>
        </div>
      </form>
    </div>
  );
}
