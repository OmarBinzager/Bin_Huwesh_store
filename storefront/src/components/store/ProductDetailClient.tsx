"use client";

import Link from "next/link";
import { useMemo, useState } from "react";
import { useDemoBag } from "@/components/store/DemoBag";
import { formatYER as formatPrice } from "@/lib/catalog";
import type { ProductDetailResponse } from "@/lib/storefront-api";

export default function ProductDetailClient({ data }: { data: ProductDetailResponse }) {
  const { product, related_products } = data;
  const bag = useDemoBag();

  const isUniquePatterns =
    product.variant_mode === "unique_patterns" && (product.patterns?.length ?? 0) > 0;

  const availablePatterns = useMemo(() => {
    if (!isUniquePatterns) return [];
    return (product.patterns ?? []).filter((p) => !p.is_sold && p.stock > 0);
  }, [isUniquePatterns, product.patterns]);

  const galleryImages = useMemo(() => {
    const list: string[] = [];
    if (isUniquePatterns) {
      for (const p of availablePatterns) {
        if (p.image_url && !list.includes(p.image_url)) list.push(p.image_url);
      }
    }
    if (product.image && !list.includes(product.image)) list.push(product.image);
    for (const img of product.images ?? []) {
      if (img && !list.includes(img)) list.push(img);
    }
    if (!isUniquePatterns) {
      for (const ci of product.color_images ?? []) {
        const url = ci.image_url || ci.image;
        if (url && !list.includes(url)) list.push(url);
      }
    }
    return list;
  }, [product, isUniquePatterns, availablePatterns]);

  const [selectedImage, setSelectedImage] = useState<string>(
    (isUniquePatterns && availablePatterns[0]?.image_url) || galleryImages[0] || "",
  );
  const [selectedColor, setSelectedColor] = useState<string>(
    isUniquePatterns
      ? availablePatterns[0]?.name || ""
      : product.color_images?.[0]?.color_name || product.color_images?.[0]?.color || "",
  );
  const availableSizes = useMemo(() => {
    if (product.sizes && product.sizes.length > 0) return product.sizes;
    const sizeOption = product.choice_options?.find((opt) =>
      /size|مقاس/i.test(opt.title || opt.name),
    );
    return sizeOption?.options ?? [];
  }, [product]);

  const [selectedSize, setSelectedSize] = useState<string>(availableSizes[0] || "Standard");
  const [qty, setQty] = useState<number>(1);
  const [sizeChartOpen, setSizeChartOpen] = useState(false);
  const [addedFeedback, setAddedFeedback] = useState(false);

  const activePattern = useMemo(() => {
    if (!isUniquePatterns) return null;
    return availablePatterns.find((p) => p.name === selectedColor) || availablePatterns[0] || null;
  }, [isUniquePatterns, availablePatterns, selectedColor]);

  // Match variation price & stock if variations exist
  const activeVariation = useMemo(() => {
    if (isUniquePatterns) {
      return activePattern
        ? { type: activePattern.type, price: activePattern.price, stock: activePattern.stock }
        : { type: "", price: product.price, stock: 0 };
    }
    if (!product.variations || product.variations.length === 0) return null;
    const sizeNorm = selectedSize.trim().toLowerCase();
    const colorNorm = selectedColor.trim().toLowerCase();
    return (
      product.variations.find((v) => {
        const typeNorm = v.type.toLowerCase();
        const matchesSize = !sizeNorm || sizeNorm === "standard" || typeNorm.includes(sizeNorm);
        const matchesColor = !colorNorm || typeNorm.includes(colorNorm);
        return matchesSize && matchesColor;
      }) ||
      product.variations.find((v) => v.type.toLowerCase().includes(sizeNorm)) ||
      null
    );
  }, [isUniquePatterns, activePattern, product.price, product.variations, selectedSize, selectedColor]);

  const basePrice =
    activeVariation && activeVariation.price > 0 ? activeVariation.price : product.price;

  const activePrice =
    product.discount > 0
      ? product.discount_type === "percent"
        ? Math.max(0, Math.round(basePrice * (1 - product.discount / 100)))
        : Math.max(0, Math.round(basePrice - product.discount))
      : basePrice;

  const activeStock = activeVariation ? activeVariation.stock : product.total_stock;
  const outOfStock = activeStock <= 0;

  const handleSelectColor = (ci: {
    color?: string;
    color_name?: string;
    image?: string;
    image_url?: string;
  }) => {
    setSelectedColor(ci.color_name || ci.color || "");
    const url = ci.image_url || ci.image;
    if (url) setSelectedImage(url);
  };

  const handleAddToBag = () => {
    if (outOfStock) return;
    bag.add([
      {
        lookId: "catalog",
        itemId: String(product.id),
        productId: product.id,
        label: product.name,
        size: selectedSize || "Standard",
        color: selectedColor || undefined,
        price: activePrice,
        qty,
        image: selectedImage || product.image || undefined,
      },
    ]);
    setAddedFeedback(true);
    setTimeout(() => setAddedFeedback(false), 1800);
  };

  const hasDiscount = product.discount > 0 && activePrice < basePrice;
  const discountLabel = hasDiscount
    ? product.discount_type === "percent"
      ? `خصم ${Math.round(product.discount)}%`
      : `وفر ${formatPrice(basePrice - activePrice)}`
    : null;

  return (
    <div className="mx-auto max-w-[1440px] px-4 py-6 sm:px-8 sm:py-10">
      <nav aria-label="مسار التصفح" className="mb-6 text-[13px] text-muted">
        <ol className="flex flex-wrap items-center gap-1.5">
          <li>
            <Link href="/" className="hover:text-ink">
              الرئيسية
            </Link>
          </li>
          <li aria-hidden>/</li>
          <li>
            <Link href="/#shop-by-category" className="hover:text-ink">
              الأقسام
            </Link>
          </li>
          <li aria-hidden>/</li>
          <li aria-current="page" className="text-ink">
            {product.name}
          </li>
        </ol>
      </nav>

      <div className="grid gap-8 lg:grid-cols-12 lg:gap-12">
        {/* Gallery */}
        <div className="lg:col-span-7">
          <div className="relative aspect-[4/5] w-full overflow-hidden rounded-3xl border border-line bg-stage">
            {selectedImage ? (
              <img
                src={selectedImage}
                alt={product.name}
                className="h-full w-full object-cover"
              />
            ) : (
              <div className="flex h-full w-full items-center justify-center text-sm text-muted">
                لا توجد صورة متاحة
              </div>
            )}
            {hasDiscount && discountLabel && (
              <span className="absolute top-4 right-4 rounded-full bg-brand px-3 py-1 text-xs font-semibold text-white">
                {discountLabel}
              </span>
            )}
          </div>

          {galleryImages.length > 1 && (
            <div className="mt-3 flex gap-3 overflow-x-auto pb-1">
              {galleryImages.map((img) => {
                const active = img === selectedImage;
                return (
                  <button
                    key={img}
                    type="button"
                    onClick={() => setSelectedImage(img)}
                    className={`relative h-20 w-16 shrink-0 overflow-hidden rounded-xl border transition-all ${
                      active ? "border-brand ring-2 ring-brand/30" : "border-line opacity-75 hover:opacity-100"
                    }`}
                  >
                    <img src={img} alt="" className="h-full w-full object-cover" />
                  </button>
                );
              })}
            </div>
          )}
        </div>

        {/* Details */}
        <div className="flex flex-col lg:col-span-5">
          {product.brand?.name && (
            <p className="text-[12px] font-semibold tracking-wider text-brand uppercase">
              {product.brand.name}
            </p>
          )}
          <h1 className="mt-1 font-display text-2xl font-semibold text-ink sm:text-3xl lg:text-4xl">
            {product.name}
          </h1>

          <div className="mt-4 flex flex-wrap items-baseline gap-3">
            <span className="text-2xl font-bold text-ink">{formatPrice(activePrice)}</span>
            {hasDiscount && (
              <>
                <span className="text-base text-muted line-through">
                  {formatPrice(basePrice)}
                </span>
                {discountLabel && (
                  <span className="rounded-full bg-brand/12 px-2.5 py-0.5 text-xs font-semibold text-brand">
                    {discountLabel}
                  </span>
                )}
              </>
            )}
            <span
              className={`mr-auto rounded-full px-3 py-1 text-xs font-medium ${
                outOfStock
                  ? "bg-red-500/10 text-red-500"
                  : isUniquePatterns && activeStock === 1
                    ? "bg-amber-500/15 text-amber-600"
                    : "bg-emerald-500/10 text-emerald-600"
              }`}
            >
              {outOfStock
                ? "نفذت الكمية"
                : isUniquePatterns && activeStock === 1
                  ? "قطعة فريدة — متبقي 1 فقط"
                  : `متوفر (${activeStock})`}
            </span>
          </div>

          {/* Unique Patterns Visual Grid (Maawaz / One-of-a-kind pieces) */}
          {isUniquePatterns ? (
            <div className="mt-6">
              <div className="mb-2.5 flex items-center justify-between text-[13px]">
                <span className="font-medium text-ink">اختر الشكل / النقشة:</span>
                <span className="font-semibold text-brand">
                  {activePattern ? activePattern.name : "غير متوفر"}
                </span>
              </div>
              {availablePatterns.length > 0 ? (
                <div className="grid grid-cols-3 gap-2.5 sm:grid-cols-4">
                  {availablePatterns.map((p) => {
                    const active = activePattern?.name === p.name;
                    return (
                      <button
                        key={p.type}
                        type="button"
                        onClick={() => {
                          setSelectedColor(p.name);
                          if (p.image_url) setSelectedImage(p.image_url);
                          setQty((q) => Math.min(q, Math.max(1, p.stock)));
                        }}
                        className={`group relative overflow-hidden rounded-2xl border text-right transition-all ${
                          active
                            ? "border-brand ring-2 ring-brand/30"
                            : "border-line hover:border-ink/40"
                        }`}
                      >
                        <div className="aspect-[3/4] w-full overflow-hidden bg-stage">
                          <img
                            src={p.image_url}
                            alt={p.name}
                            className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                          />
                        </div>
                        <div className="flex items-center justify-between gap-1 bg-card px-2 py-1.5 text-[11px]">
                          <span className="truncate font-medium text-ink">{p.name}</span>
                          <span className="shrink-0 rounded-full bg-stage px-1.5 py-0.5 text-[10px] text-muted">
                            {p.stock === 1 ? "وحيدة" : `${p.stock}`}
                          </span>
                        </div>
                      </button>
                    );
                  })}
                </div>
              ) : (
                <div className="rounded-2xl border border-line bg-stage p-4 text-center text-[13px] text-muted">
                  تم بيع جميع النقشات المتوفرة حالياً لهذا الصنف.
                </div>
              )}
            </div>
          ) : (
            /* Color Swatches (with thumbnail or color dot) */
            product.color_images &&
            product.color_images.length > 0 && (
              <div className="mt-6">
                <div className="mb-2 flex items-center justify-between text-[13px]">
                  <span className="font-medium text-ink">اللون:</span>
                  <span className="text-muted">{selectedColor}</span>
                </div>
                <div className="flex flex-wrap gap-2.5">
                  {product.color_images.map((ci, idx) => {
                    const name = ci.color_name || ci.color || `لون ${idx + 1}`;
                    const active = selectedColor === name;
                    const hex = ci.color_code?.startsWith("#")
                      ? ci.color_code
                      : ci.color?.startsWith("#")
                        ? ci.color
                        : undefined;
                    const thumbUrl = ci.image_url && !ci.image_url.endsWith("/2.png") ? ci.image_url : undefined;
                    return (
                      <button
                        key={`${name}-${idx}`}
                        type="button"
                        onClick={() => handleSelectColor(ci)}
                        title={name}
                        className={`flex items-center gap-2 rounded-2xl border px-3 py-1.5 text-[12px] transition-all ${
                          active
                            ? "border-brand bg-brand/10 font-semibold text-ink ring-1 ring-brand/40"
                            : "border-line bg-card text-muted hover:border-ink/40"
                        }`}
                      >
                        {thumbUrl ? (
                          <img
                            src={thumbUrl}
                            alt={name}
                            className="h-6 w-6 rounded-lg border border-black/10 object-cover"
                          />
                        ) : hex ? (
                          <span
                            className="h-4 w-4 rounded-full border border-black/15"
                            style={{ backgroundColor: hex }}
                          />
                        ) : null}
                        <span>{name}</span>
                      </button>
                    );
                  })}
                </div>
              </div>
            )
          )}

          {/* Size Selector */}
          {availableSizes.length > 0 && (
            <div className="mt-6">
              <div className="mb-2 flex items-center justify-between text-[13px]">
                <span className="font-medium text-ink">المقاس:</span>
                {product.size_chart && (
                  <button
                    type="button"
                    onClick={() => setSizeChartOpen(true)}
                    className="text-[12px] font-medium text-brand underline underline-offset-4 hover:opacity-80"
                  >
                    جدول المقاسات
                  </button>
                )}
              </div>
              <div className="flex flex-wrap gap-2">
                {availableSizes.map((size) => {
                  const active = selectedSize === size;
                  return (
                    <button
                      key={size}
                      type="button"
                      onClick={() => setSelectedSize(size)}
                      className={`min-w-[3rem] rounded-xl border px-3.5 py-2 text-[13px] font-medium transition-all ${
                        active
                          ? "border-ink bg-ink text-on-ink"
                          : "border-line bg-card text-ink hover:border-brand"
                      }`}
                    >
                      {size}
                    </button>
                  );
                })}
              </div>
            </div>
          )}

          {/* Full Variants Matrix Table (for standard products with variations) */}
          {!isUniquePatterns && product.variations && product.variations.length > 0 && (
            <div className="mt-6">
              <div className="mb-2 flex items-center justify-between text-[13px]">
                <span className="font-medium text-ink">جدول المتغيرات المتاحة:</span>
                <span className="text-[11px] text-muted">اختر من الجدول أو من الأعلى</span>
              </div>
              <div className="no-scrollbar max-h-56 overflow-auto rounded-2xl border border-line bg-card">
                <table className="w-full border-collapse text-right text-[12px]">
                  <thead className="sticky top-0 bg-stage text-ink">
                    <tr className="border-b border-line">
                      <th className="px-3 py-2 font-semibold">المتغير</th>
                      <th className="px-3 py-2 font-semibold">السعر</th>
                      <th className="px-3 py-2 font-semibold">المخزون</th>
                    </tr>
                  </thead>
                  <tbody>
                    {product.variations.map((v) => {
                      const isActive = activeVariation?.type === v.type;
                      const mergedLabel = v.type.split("-").filter(Boolean).join(" • ");
                      const varPrice =
                        product.discount > 0 && product.discount_type === "percent"
                          ? Math.max(0, Math.round(v.price * (1 - product.discount / 100)))
                          : v.price;
                      return (
                        <tr
                          key={v.type}
                          onClick={() => {
                            const parts = v.type.split("-").filter(Boolean);
                            if (parts.length >= 2) {
                              const matchedColor = product.color_images?.find(
                                (ci) =>
                                  (ci.color_name || ci.color || "").replace(/\s+/g, "").toLowerCase() ===
                                  parts[0].toLowerCase(),
                              );
                              if (matchedColor) {
                                handleSelectColor(matchedColor);
                              } else {
                                setSelectedColor(parts[0]);
                              }
                              setSelectedSize(parts.slice(1).join("-"));
                            } else if (parts.length === 1) {
                              setSelectedSize(parts[0]);
                            }
                          }}
                          className={`cursor-pointer border-b border-line/60 transition-colors last:border-0 ${
                            isActive ? "bg-brand/10 font-semibold text-ink" : "hover:bg-stage/70 text-muted"
                          }`}
                        >
                          <td className="px-3 py-2 text-ink">{mergedLabel}</td>
                          <td className="px-3 py-2">{formatPrice(varPrice)}</td>
                          <td className="px-3 py-2">
                            {v.stock > 0 ? (
                              <span className="text-emerald-600">متوفر ({v.stock})</span>
                            ) : (
                              <span className="text-red-500">نفد</span>
                            )}
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* Quantity + Add to Cart */}
          <div className="mt-8 flex flex-wrap items-center gap-3">
            <div className="flex h-12 items-center rounded-full border border-line bg-card px-2">
              <button
                type="button"
                aria-label="إنقاص الكمية"
                onClick={() => setQty((q) => Math.max(1, q - 1))}
                className="flex h-8 w-8 items-center justify-center rounded-full text-lg text-ink hover:bg-stage"
              >
                -
              </button>
              <span className="w-8 text-center text-sm font-semibold text-ink">{qty}</span>
              <button
                type="button"
                aria-label="زيادة الكمية"
                onClick={() => setQty((q) => Math.min(Math.max(1, activeStock), q + 1))}
                className="flex h-8 w-8 items-center justify-center rounded-full text-lg text-ink hover:bg-stage"
              >
                +
              </button>
            </div>

            <button
              type="button"
              disabled={outOfStock}
              onClick={handleAddToBag}
              className={`h-12 flex-1 rounded-full px-6 text-[14px] font-semibold transition-all ${
                outOfStock
                  ? "cursor-not-allowed bg-line text-muted"
                  : "bg-ink text-on-ink hover:bg-brand hover:text-white"
              }`}
            >
              {outOfStock
                ? "غير متوفر حالياً"
                : addedFeedback
                  ? "تمت الإضافة إلى الحقيبة ✓"
                  : "أضف إلى الحقيبة"}
            </button>
          </div>

          {/* Description */}
          {product.description && (
            <div className="mt-8 border-t border-line pt-6">
              <h2 className="mb-2 text-[14px] font-semibold text-ink">تفاصيل المنتج</h2>
              <div
                className="prose prose-sm max-w-none text-[14px] leading-relaxed text-muted"
                dangerouslySetInnerHTML={{ __html: product.description }}
              />
            </div>
          )}

          {/* Reviews */}
          {product.reviews && product.reviews.length > 0 && (
            <div className="mt-8 border-t border-line pt-6">
              <h2 className="mb-3 text-[14px] font-semibold text-ink">
                آراء العملاء ({product.reviews.length})
              </h2>
              <div className="space-y-3">
                {product.reviews.map((rev) => (
                  <div key={rev.id} className="rounded-2xl border border-line bg-card p-3.5">
                    <div className="flex items-center justify-between text-[12px]">
                      <span className="font-semibold text-ink">{rev.customer}</span>
                      <span className="text-brand">{"★".repeat(Math.round(rev.rating))}</span>
                    </div>
                    {rev.comment && (
                      <p className="mt-1.5 text-[13px] leading-relaxed text-muted">
                        {rev.comment}
                      </p>
                    )}
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Related Products */}
      {related_products && related_products.length > 0 && (
        <section className="mt-16 border-t border-line pt-10">
          <h2 className="mb-6 font-display text-2xl font-semibold text-ink">منتجات قد تعجبك</h2>
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:gap-6">
            {related_products.map((rel) => (
              <Link
                key={rel.id}
                href={`/products/${rel.id}`}
                className="group overflow-hidden rounded-2xl border border-line bg-card transition-all hover:border-brand/50"
              >
                <div className="relative aspect-[3/4] bg-stage">
                  {rel.image && (
                    <img
                      src={rel.image}
                      alt={rel.name}
                      loading="lazy"
                      className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                    />
                  )}
                </div>
                <div className="p-3.5">
                  <h3 className="line-clamp-1 text-[13px] font-semibold text-ink group-hover:text-brand">
                    {rel.name}
                  </h3>
                  <p className="mt-1 text-[14px] font-bold text-ink">
                    {formatPrice(rel.final_price)}
                  </p>
                </div>
              </Link>
            ))}
          </div>
        </section>
      )}

      {/* Size Chart Modal */}
      {sizeChartOpen && product.size_chart && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs"
          onClick={() => setSizeChartOpen(false)}
        >
          <div
            className="w-full max-w-xl rounded-3xl border border-line bg-card p-6 shadow-2xl"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="mb-4 flex items-center justify-between">
              <h3 className="font-display text-lg font-semibold text-ink">
                {product.size_chart.name || "جدول المقاسات"}
              </h3>
              <button
                type="button"
                onClick={() => setSizeChartOpen(false)}
                className="rounded-full px-3 py-1 text-sm text-muted hover:bg-stage hover:text-ink"
              >
                إغلاق ✕
              </button>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full border-collapse text-center text-[13px]">
                <thead>
                  <tr className="border-b border-line bg-stage">
                    {product.size_chart.columns.map((col, idx) => (
                      <th key={idx} className="px-3 py-2.5 font-semibold text-ink">
                        {col}
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {product.size_chart.rows.map((row, rIdx) => {
                    const cells = Array.isArray(row)
                      ? row
                      : product.size_chart!.columns.map((col) => String((row as Record<string, unknown>)[col] ?? ""));
                    return (
                      <tr key={rIdx} className="border-b border-line/60">
                        {cells.map((cell, cIdx) => (
                          <td key={cIdx} className="px-3 py-2 text-muted">
                            {cell}
                          </td>
                        ))}
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
            {product.size_chart.note && (
              <p className="mt-3 text-[12px] text-muted">{product.size_chart.note}</p>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
