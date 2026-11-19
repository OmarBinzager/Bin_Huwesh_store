"use client";

import { useCurrency } from "@/lib/currency";

export function Price({ amount }: { amount: number }) {
  const { formatMoney } = useCurrency();
  return <>{formatMoney(amount)}</>;
}
