"use client";

import Link from "next/link";
import { useCallback, useEffect, useState } from "react";
import { useCurrency } from "@/lib/currency";
import { cancelOrder, trackOrderById, type TrackedOrder, type TrackedOrderDetailItem } from "@/lib/customer-api";
import { useI18n } from "@/lib/i18n/context";
import { API_BASE_URL } from "@/lib/storefront-api";

const ORDER_STAGES = [
  "pending",
  "confirmed",
  "processing",
  "out_for_delivery",
  "delivered",
] as const;

type ParsedProductDetails = {
  name?: string;
  image?: string[] | string;
  image_fullpath?: string[];
};

function decodeUnicodeEscapes(text: string): string {
  return text.replace(/(?:\\)?u([0-9a-fA-F]{4})/g, (_, hex) =>
    String.fromCharCode(parseInt(hex, 16)),
  );
}

function parseProductDetails(
  raw: TrackedOrderDetailItem["product_details"],
): ParsedProductDetails | null {
  if (!raw) return null;
  if (typeof raw === "string") {
    try {
      const parsed = JSON.parse(raw) as unknown;
      if (typeof parsed === "string") {
        return JSON.parse(parsed) as ParsedProductDetails;
      }
      return parsed as ParsedProductDetails;
    } catch {
      return null;
    }
  }
  return raw;
}

function parseVariantLabel(raw?: string | null): string {
  if (!raw) return "";
  let val = raw.trim();
  try {
    const parsed = JSON.parse(val) as unknown;
    if (typeof parsed === "string") val = parsed;
  } catch {
    val = val.replace(/^"+|"+$/g, "");
  }
  return decodeUnicodeEscapes(val).replace(/^"+|"+$/g, "");
}

function resolveProductImage(productDetails: ParsedProductDetails | null): string | null {
  if (!productDetails) return null;
  const full = productDetails.image_fullpath?.[0];
  if (full) return full;

  let img: string | undefined;
  if (Array.isArray(productDetails.image)) {
    img = productDetails.image[0];
  } else if (typeof productDetails.image === "string") {
    const trimmed = productDetails.image.trim();
    if (trimmed.startsWith("[")) {
      try {
        const arr = JSON.parse(trimmed) as unknown;
        if (Array.isArray(arr) && typeof arr[0] === "string") img = arr[0];
      } catch {
        img = trimmed;
      }
    } else {
      img = trimmed;
    }
  }

  if (!img) return null;
  if (img.startsWith("http")) return img;
  return `${API_BASE_URL}/storage/app/public/product/${img}`;
}

export default function OrderDetailClient({ orderId }: { orderId: string }) {
  const { locale, dir, t } = useI18n();
  const { formatMoney } = useCurrency();

  const [order, setOrder] = useState<TrackedOrder | null>(null);
  const [loading, setLoading] = useState(true);
  const [canceling, setCanceling] = useState(false);

  const loadOrder = useCallback(async () => {
    setLoading(true);
    const data = await trackOrderById(orderId, locale);
    setOrder(data);
    setLoading(false);
  }, [orderId, locale]);

  useEffect(() => {
    const timer = setTimeout(() => {
      void loadOrder();
    }, 0);
    return () => clearTimeout(timer);
  }, [loadOrder]);

  const handleCancel = async () => {
    if (!order) return;
    setCanceling(true);
    const res = await cancelOrder(order.id, locale);
    setCanceling(false);
    if (res.ok) {
      await loadOrder();
    }
  };

  const stageLabel = (status: string) => {
    switch (status) {
      case "pending":
        return t.orderDetails.stagePending;
      case "confirmed":
        return t.orderDetails.stageConfirmed;
      case "processing":
        return t.orderDetails.stageProcessing;
      case "out_for_delivery":
        return t.orderDetails.stageOutForDelivery;
      case "delivered":
        return t.orderDetails.stageDelivered;
      case "canceled":
        return t.orderDetails.statusCanceled;
      case "returned":
        return t.orderDetails.statusReturned;
      case "failed":
        return t.orderDetails.statusFailed;
      default:
        return status;
    }
  };

  const paymentStatusLabel = (status: string) => {
    if (status === "paid") return t.orderDetails.paidLabel;
    if (status === "partially_paid") return t.orderDetails.partiallyPaidLabel;
    return t.orderDetails.unpaidLabel;
  };

  const paymentMethodLabel = (method: string) => {
    if (method === "cash_on_delivery") return t.orderDetails.paymentCod;
    if (method === "offline_payment") return t.orderDetails.paymentOffline;
    if (method === "digital_payment") return t.orderDetails.paymentDigital;
    return method.replaceAll("_", " ");
  };

  if (loading) {
    return (
      <div dir={dir} className="mx-auto max-w-5xl px-4 py-10 sm:px-6">
        <div className="rounded-3xl border border-line bg-card p-10 text-center text-sm text-muted">
          {t.modals.loggingIn}
        </div>
      </div>
    );
  }

  if (!order) {
    return (
      <div dir={dir} className="mx-auto max-w-5xl px-4 py-10 sm:px-6 space-y-4">
        <Link
          href="/account/orders"
          className="inline-flex h-9 items-center rounded-full border border-line bg-card px-4 text-xs font-medium text-ink hover:bg-stage"
        >
          {t.orderDetails.backToOrders}
        </Link>
        <div className="rounded-3xl border border-line bg-card p-10 text-center text-sm text-muted">
          {t.orderDetails.notFound}
        </div>
      </div>
    );
  }

  const currentStageIndex = ORDER_STAGES.indexOf(
    order.order_status as (typeof ORDER_STAGES)[number],
  );
  const isTerminalNegative =
    order.order_status === "canceled" ||
    order.order_status === "returned" ||
    order.order_status === "failed";

  const itemsSubtotal = (order.details ?? []).reduce(
    (sum, item) => sum + Number(item.price) * Number(item.quantity),
    0,
  );
  const productDiscount = (order.details ?? []).reduce(
    (sum, item) => sum + Number(item.discount_on_product ?? 0) * Number(item.quantity),
    0,
  );
  const couponDiscount = Number(order.coupon_discount_amount ?? 0);
  const extraDiscount = Number(order.extra_discount ?? 0);

  return (
    <div dir={dir} className="mx-auto max-w-5xl px-4 py-10 sm:px-6 space-y-6">
      {/* Top Bar */}
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div className="space-y-1">
          <Link
            href="/account/orders"
            className="inline-flex items-center text-xs font-medium text-muted hover:text-ink"
          >
            ← {t.orderDetails.backToOrders}
          </Link>
          <h1 className="font-display text-2xl font-bold text-ink sm:text-3xl nums">
            {t.orderDetails.pageTitle} #{order.id}
          </h1>
          <p className="text-xs text-muted" dir="ltr">
            {new Date(order.created_at).toLocaleString(locale === "en" ? "en-US" : "ar-YE")}
          </p>
        </div>

        <div className="flex flex-wrap items-center gap-2.5">
          <span className="rounded-full bg-brand/10 px-4 py-1.5 text-xs font-semibold text-brand">
            {stageLabel(order.order_status)}
          </span>
          <span className="rounded-full border border-line bg-card px-4 py-1.5 text-xs font-medium text-ink">
            {paymentStatusLabel(order.payment_status)}
          </span>
          {order.order_status === "pending" && (
            <button
              type="button"
              disabled={canceling}
              onClick={() => void handleCancel()}
              className="h-9 rounded-full border border-line px-4 text-xs font-medium text-brand transition hover:bg-brand/10 disabled:opacity-50"
            >
              {t.account.cancelOrderBtn}
            </button>
          )}
        </div>
      </div>

      {/* Order Tracking Stepper */}
      <section className="rounded-3xl border border-line bg-card p-6 space-y-5">
        <div className="flex flex-wrap items-center justify-between gap-2">
          <h2 className="font-display text-lg font-semibold text-ink">
            {t.orderDetails.trackingTitle}
          </h2>
          <span className="rounded-full bg-stage px-3 py-1 text-xs font-medium text-ink">
            {t.orderDetails.currentStageBadge}: {stageLabel(order.order_status)}
          </span>
        </div>

        {isTerminalNegative ? (
          <div className="rounded-2xl border border-brand/30 bg-brand/5 p-4 text-sm font-medium text-brand">
            {stageLabel(order.order_status)}
          </div>
        ) : (
          <div className="grid gap-3 sm:grid-cols-5">
            {ORDER_STAGES.map((stage, idx) => {
              const reached = currentStageIndex >= idx;
              const isCurrent = currentStageIndex === idx;
              return (
                <div
                  key={stage}
                  className={`relative rounded-2xl border p-3.5 transition ${
                    isCurrent
                      ? "border-brand bg-brand/5"
                      : reached
                        ? "border-emerald-500/40 bg-emerald-500/5"
                        : "border-line bg-background opacity-65"
                  }`}
                >
                  <div className="flex items-center gap-2">
                    <span
                      className={`grid size-6 shrink-0 place-items-center rounded-full text-[11px] font-bold ${
                        isCurrent
                          ? "bg-brand text-white"
                          : reached
                            ? "bg-emerald-600 text-white"
                            : "bg-stage text-muted"
                      }`}
                    >
                      {reached && !isCurrent ? "✓" : idx + 1}
                    </span>
                    <span className="text-xs font-semibold text-ink">{stageLabel(stage)}</span>
                  </div>
                  {isCurrent && (
                    <span className="mt-2 inline-block rounded-full bg-brand/15 px-2 py-0.5 text-[10px] font-semibold text-brand">
                      {t.orderDetails.currentStageBadge}
                    </span>
                  )}
                </div>
              );
            })}
          </div>
        )}
      </section>

      <div className="grid gap-6 lg:grid-cols-12">
        {/* Left: Ordered Items */}
        <section className="rounded-3xl border border-line bg-card p-6 space-y-4 lg:col-span-7">
          <h2 className="font-display text-lg font-semibold text-ink">
            {t.orderDetails.itemsTitle}
          </h2>
          <ul className="divide-y divide-line">
            {(order.details ?? []).map((item) => {
              const details = parseProductDetails(item.product_details);
              const imgUrl = resolveProductImage(details);
              const productName = details?.name
                ? decodeUnicodeEscapes(details.name)
                : `#${item.product_id}`;
              const variantText = parseVariantLabel(item.variant);
              return (
                <li key={item.id} className="flex items-center gap-3.5 py-3.5">
                  {imgUrl ? (
                    <img
                      src={imgUrl}
                      alt={productName}
                      className="size-16 shrink-0 rounded-2xl border border-line bg-stage object-cover"
                    />
                  ) : (
                    <div className="grid size-16 shrink-0 place-items-center rounded-2xl border border-line bg-stage text-xs font-semibold text-muted">
                      #{item.product_id}
                    </div>
                  )}
                  <div className="min-w-0 flex-1">
                    <p className="truncate font-medium text-ink">{productName}</p>
                    <p className="mt-0.5 text-xs text-muted">
                      {variantText ? `${variantText} · ` : ""}
                      <span className="nums">
                        {formatMoney(item.price)} × {item.quantity}
                      </span>
                    </p>
                  </div>
                  <span className="nums shrink-0 font-semibold text-ink">
                    {formatMoney(item.price * item.quantity)}
                  </span>
                </li>
              );
            })}
          </ul>
        </section>

        {/* Right: Summary + Delivery + Payment */}
        <div className="space-y-6 lg:col-span-5">
          {/* Financial Summary */}
          <section className="rounded-3xl border border-line bg-card p-6 space-y-3 text-sm">
            <h2 className="font-display text-lg font-semibold text-ink">
              {t.orderDetails.summaryTitle}
            </h2>
            <div className="flex justify-between">
              <span className="text-muted">{t.checkout.subtotal}</span>
              <span className="nums text-ink">{formatMoney(itemsSubtotal)}</span>
            </div>
            {productDiscount > 0 && (
              <div className="flex justify-between text-emerald-600">
                <span>{t.orderDetails.discountLabel}</span>
                <span className="nums">-{formatMoney(productDiscount)}</span>
              </div>
            )}
            {extraDiscount > 0 && (
              <div className="flex justify-between text-emerald-600">
                <span>{locale === "en" ? "Look / Bundle Discount" : "خصم كولكشن الإطلالة"}</span>
                <span className="nums">-{formatMoney(extraDiscount)}</span>
              </div>
            )}
            {couponDiscount > 0 && (
              <div className="flex justify-between text-emerald-600">
                <span>
                  {locale === "en" ? "Coupon Discount" : "خصم الكوبون"}
                  {order.coupon_code ? ` (${order.coupon_code})` : ""}
                </span>
                <span className="nums">-{formatMoney(couponDiscount)}</span>
              </div>
            )}
            {Number(order.total_tax_amount ?? 0) > 0 && (
              <div className="flex justify-between">
                <span className="text-muted">{t.orderDetails.taxLabel}</span>
                <span className="nums text-ink">{formatMoney(Number(order.total_tax_amount))}</span>
              </div>
            )}
            <div className="flex justify-between">
              <span className="text-muted">{t.checkout.deliveryChargeLabel}</span>
              <span className="nums text-ink">
                {Number(order.delivery_charge ?? 0) === 0
                  ? t.checkout.freeDeliveryLabel
                  : formatMoney(Number(order.delivery_charge ?? 0))}
              </span>
            </div>
            <div className="flex justify-between border-t border-line pt-3 text-base font-bold">
              <span className="text-ink">{t.checkout.total}</span>
              <span className="nums text-brand">{formatMoney(order.order_amount)}</span>
            </div>
          </section>

          {/* Delivery Info */}
          <section className="rounded-3xl border border-line bg-card p-6 space-y-2.5 text-sm">
            <h2 className="font-display text-lg font-semibold text-ink">
              {t.orderDetails.deliveryInfoTitle}
            </h2>
            {order.delivery_address && (
              <>
                <p className="text-xs text-muted">
                  {t.orderDetails.recipientLabel}:{" "}
                  <span className="font-medium text-ink">
                    {order.delivery_address.contact_person_name}
                  </span>
                </p>
                <p className="text-xs text-muted">
                  {t.orderDetails.phoneLabel}:{" "}
                  <span className="font-medium text-ink" dir="ltr">
                    {order.delivery_address.contact_person_number}
                  </span>
                </p>
                <p className="text-xs text-muted">
                  {t.orderDetails.addressLabel}:{" "}
                  <span className="font-medium text-ink">{order.delivery_address.address}</span>
                </p>
              </>
            )}
            {order.delivery_date && (
              <p className="text-xs text-muted">
                {t.orderDetails.deliveryDateLabel}:{" "}
                <span className="font-medium text-ink" dir="ltr">
                  {order.delivery_date}
                </span>
              </p>
            )}
            {order.order_note && (
              <p className="text-xs text-muted">
                {t.orderDetails.orderNoteLabel}:{" "}
                <span className="font-medium text-ink">{order.order_note}</span>
              </p>
            )}
          </section>

          {/* Delivery Man */}
          {order.delivery_man && (
            <section className="rounded-3xl border border-line bg-card p-6 space-y-2 text-sm">
              <h2 className="font-display text-lg font-semibold text-ink">
                {t.orderDetails.deliveryManTitle}
              </h2>
              <p className="font-medium text-ink">
                {order.delivery_man.f_name} {order.delivery_man.l_name}
              </p>
              {order.delivery_man.phone && (
                <p className="text-xs text-muted" dir="ltr">
                  {order.delivery_man.phone}
                </p>
              )}
            </section>
          )}

          {/* Payment Info */}
          <section className="rounded-3xl border border-line bg-card p-6 space-y-2.5 text-sm">
            <h2 className="font-display text-lg font-semibold text-ink">
              {t.orderDetails.paymentInfoTitle}
            </h2>
            <p className="text-xs text-muted">
              {t.orderDetails.paymentMethodLabel}:{" "}
              <span className="font-medium text-ink">{paymentMethodLabel(order.payment_method)}</span>
            </p>
            <p className="text-xs text-muted">
              {t.orderDetails.paymentStatusLabel}:{" "}
              <span className="font-medium text-ink">
                {paymentStatusLabel(order.payment_status)}
              </span>
            </p>

            {order.offline_payment_information?.payment_name && (
              <div className="mt-2 space-y-1.5 rounded-2xl bg-stage p-3 text-xs">
                <p className="font-semibold text-ink">
                  {t.orderDetails.transferMethodLabel}:{" "}
                  {order.offline_payment_information.payment_name}
                </p>
                {(order.offline_payment_information.method_information ?? []).map((entry, idx) =>
                  Object.entries(entry).map(([k, v]) => (
                    <p key={`${idx}-${k}`} className="text-muted">
                      {k}: <span className="font-medium text-ink">{v}</span>
                    </p>
                  )),
                )}
              </div>
            )}

            {order.offline_payment?.receipt_image_full_url && (
              <div className="pt-2 space-y-1.5">
                <p className="text-xs text-muted">{t.orderDetails.transferReceiptLabel}</p>
                <a
                  href={order.offline_payment.receipt_image_full_url}
                  target="_blank"
                  rel="noreferrer"
                >
                  <img
                    src={order.offline_payment.receipt_image_full_url}
                    alt={t.orderDetails.transferReceiptLabel}
                    className="max-h-44 rounded-2xl border border-line bg-stage object-contain p-1"
                  />
                </a>
              </div>
            )}
          </section>
        </div>
      </div>
    </div>
  );
}
