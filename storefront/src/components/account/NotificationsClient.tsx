"use client";

import { useEffect, useState } from "react";
import { fetchNotifications, type NotificationItem } from "@/lib/customer-api";
import { useI18n } from "@/lib/i18n/context";
import { AccountShell } from "./AccountShell";

export default function NotificationsClient() {
  const { locale, t } = useI18n();
  const [items, setItems] = useState<NotificationItem[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let active = true;
    fetchNotifications(locale).then((list) => {
      if (!active) return;
      setItems(list);
      setLoading(false);
    });
    return () => {
      active = false;
    };
  }, [locale]);

  return (
    <AccountShell
      current="notifications"
      title={t.account.notificationsTitle}
      subtitle={t.account.notificationsSubtitle}
    >
      {loading ? (
        <div className="rounded-3xl border border-line bg-card p-8 text-center text-sm text-muted">
          {t.modals.loggingIn}
        </div>
      ) : items.length === 0 ? (
        <div className="rounded-3xl border border-line bg-card p-10 text-center text-sm text-muted">
          {t.account.noNotifications}
        </div>
      ) : (
        <div className="space-y-3 max-w-3xl">
          {items.map((item) => (
            <article
              key={item.id}
              className="rounded-3xl border border-line bg-card p-5 space-y-1.5"
            >
              <div className="flex items-center justify-between gap-3">
                <h2 className="font-display text-base font-semibold text-ink">{item.title}</h2>
                <span className="text-xs text-muted" dir="ltr">
                  {new Date(item.created_at).toLocaleDateString(
                    locale === "en" ? "en-US" : "ar-YE",
                  )}
                </span>
              </div>
              <p className="text-sm text-muted">{item.description}</p>
            </article>
          ))}
        </div>
      )}
    </AccountShell>
  );
}
