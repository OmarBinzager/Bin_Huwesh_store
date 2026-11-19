"use client";

import Link from "next/link";
import { useCallback, useEffect, useState } from "react";
import { getAuthToken } from "@/lib/auth-api";
import { useCurrency } from "@/lib/currency";
import {
  cancelOrder,
  fetchOrderDetails,
  fetchOrders,
  type CustomerOrder,
  type OrderDetailItem,
} from "@/lib/customer-api";
import { useI18n } from "@/lib/i18n/context";
import { AccountShell } from "./AccountShell";

export default function OrdersClient() {
  const { locale, t } = useI18n();
  const { formatMoney } = useCurrency();
  const [orders, setOrders] = useState<CustomerOrder[]>([]);
  const [loading, setLoading] = useState(true);
  const [expandedId, setExpandedId] = useState<number | null>(null);
  const [detailsMap, setDetailsMap] = useState<Record<number, OrderDetailItem[]>>({});

  const loadOrders = useCallback(async () => {
    if (!getAuthToken()) {
      setLoading(false);
      return;
    }
    setLoading(true);
    const list = await fetchOrders(locale);
    setOrders(list);
    setLoading(false);
  }, [locale]);

  useEffect(() => {
    const timer = setTimeout(() => {
      void loadOrders();
    }, 0);
    return () => clearTimeout(timer);
  }, [loadOrders]);

  const toggleDetails = async (orderId: number) => {
    if (expandedId === orderId) {
      setExpandedId(null);
      return;
    }
    setExpandedId(orderId);
    if (!detailsMap[orderId]) {
      const items = await fetchOrderDetails(orderId, locale);
      setDetailsMap((prev) => ({ ...prev, [orderId]: items }));
    }
  };

  const handleCancel = async (orderId: number) => {
    const res = await cancelOrder(orderId, locale);
    if (res.ok) {
      await loadOrders();
    }
  };

  const orderStatusLabel = (status: string) => {
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

  return (
    <AccountShell
      current="orders"
      title={t.account.ordersTitle}
      subtitle={t.account.ordersSubtitle}
    >
      {loading ? (
        <div className="rounded-3xl border border-line bg-card p-8 text-center text-sm text-muted">
          {t.modals.loggingIn}
        </div>
      ) : orders.length === 0 ? (
        <div className="rounded-3xl border border-line bg-card p-10 text-center text-sm text-muted">
          {t.account.noOrders}
        </div>
      ) : (
        <div className="space-y-4">
          {orders.map((order) => {
            const isExpanded = expandedId === order.id;
            const details = detailsMap[order.id] ?? [];
            const canCancel = order.order_status === "pending";
            return (
              <div
                key={order.id}
                className="rounded-3xl border border-line bg-card p-5 sm:p-6 space-y-4"
              >
                <div className="flex flex-wrap items-center justify-between gap-3">
                  <div className="space-y-1">
                    <p className="font-display text-lg font-bold text-ink nums">
                      {t.account.orderIdLabel}
                      {order.id}
                    </p>
                    <p className="text-xs text-muted" dir="ltr">
                      {new Date(order.created_at).toLocaleString(locale === "en" ? "en-US" : "ar-YE")}
                    </p>
                  </div>

                  <div className="flex flex-wrap items-center gap-2">
                    <span className="rounded-full bg-stage px-3 py-1 text-xs font-medium text-ink">
                      {orderStatusLabel(order.order_status)}
                    </span>
                    <span className="rounded-full border border-line px-3 py-1 text-xs font-medium text-muted">
                      {paymentStatusLabel(order.payment_status)}
                    </span>
                    <span className="nums font-display text-base font-bold text-brand">
                      {formatMoney(order.order_amount)}
                    </span>
                  </div>
                </div>

                <div className="flex flex-wrap gap-2 border-t border-line pt-3">
                  <Link
                    href={`/account/orders/${order.id}`}
                    className="inline-flex h-9 items-center rounded-full bg-ink px-4 text-xs font-medium text-on-ink transition hover:bg-brand"
                  >
                    {t.account.orderDetailsBtn}
                  </Link>
                  <button
                    type="button"
                    onClick={() => void toggleDetails(order.id)}
                    className="h-9 rounded-full border border-line px-4 text-xs font-medium text-ink hover:bg-stage"
                  >
                    {isExpanded ? t.account.hideDetailsBtn : t.account.viewDetailsBtn}
                  </button>
                  {canCancel && (
                    <button
                      type="button"
                      onClick={() => void handleCancel(order.id)}
                      className="h-9 rounded-full border border-line px-4 text-xs font-medium text-brand hover:bg-brand/10"
                    >
                      {t.account.cancelOrderBtn}
                    </button>
                  )}
                </div>

                {isExpanded && (
                  <div className="rounded-2xl bg-stage p-4 space-y-2">
                    {details.map((item) => {
                      let pName = `#${item.product_id}`;
                      const rawDetails = item.product_details as unknown;
                      if (typeof rawDetails === "string") {
                        try {
                          const parsed = JSON.parse(rawDetails) as { name?: string };
                          if (parsed?.name) pName = parsed.name;
                        } catch {
                          // ignore
                        }
                      } else if (
                        rawDetails &&
                        typeof rawDetails === "object" &&
                        "name" in rawDetails &&
                        typeof (rawDetails as { name?: unknown }).name === "string"
                      ) {
                        pName = (rawDetails as { name: string }).name;
                      }
                      return (
                        <div
                          key={item.id}
                          className="flex items-center justify-between gap-3 text-sm"
                        >
                          <span className="text-ink">
                            {pName} × {item.quantity}
                          </span>
                          <span className="nums font-medium text-ink">
                            {formatMoney(item.price * item.quantity)}
                          </span>
                        </div>
                      );
                    })}
                  </div>
                )}
              </div>
            );
          })}
        </div>
      )}
    </AccountShell>
  );
}
