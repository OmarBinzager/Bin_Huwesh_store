import type { Metadata } from "next";
import CheckoutClient from "@/components/store/CheckoutClient";
import { DemoBagProvider } from "@/components/store/DemoBag";
import StoreHeader from "@/components/store/StoreHeader";

export const metadata: Metadata = {
  title: "إتمام الطلب — بن هويش",
  description: "مراجعة حقيبة التسوق وإتمام الطلب من متجر بن هويش.",
};

export default function CheckoutPage() {
  return (
    <DemoBagProvider>
      <StoreHeader />
      <main className="min-h-screen bg-background">
        <CheckoutClient />
      </main>
    </DemoBagProvider>
  );
}
