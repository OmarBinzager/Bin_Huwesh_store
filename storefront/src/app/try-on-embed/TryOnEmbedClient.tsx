"use client";

import { useSearchParams } from "next/navigation";
import TryOnModal from "@/components/store/TryOnModal";

export default function TryOnEmbedClient() {
  const params = useSearchParams();
  const productId = Number(params.get("productId") || "0");
  const productName = params.get("name") || "";
  const garmentImage = params.get("image") || "";
  const selectedColor = params.get("color") || undefined;
  const locale = params.get("lang") === "en" ? "en" : "ar";

  if (!productId || !garmentImage) {
    return (
      <main className="flex min-h-screen items-center justify-center bg-paper p-6 text-center text-sm text-muted">
        {locale === "en" ? "Missing product details for try-on." : "بيانات القطعة غير مكتملة."}
      </main>
    );
  }

  return (
    <TryOnModal
      open
      embedded
      onClose={() => {
        if (typeof window !== "undefined") {
          window.history.back();
        }
      }}
      productId={productId}
      productName={productName}
      garmentImage={garmentImage}
      selectedColor={selectedColor}
      locale={locale}
    />
  );
}
