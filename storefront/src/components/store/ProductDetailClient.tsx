"use client";

import Link from "next/link";
import { useEffect, useMemo, useState } from "react";
import { useDemoBag } from "@/components/store/DemoBag";
import { TryOnButton } from "@/components/store/TryOnButton";
import type { TryOnVariant } from "@/components/store/TryOnModal";
import { useCurrency } from "@/lib/currency";
import { useI18n } from "@/lib/i18n/context";
import { productHref } from "@/lib/product-url";
import type { ProductDetailResponse } from "@/lib/storefront-api";

export default function ProductDetailClient({ data }: { data: ProductDetailResponse }) {
  const { locale, t } = useI18n();
  const { formatMoney: formatPrice } = useCurrency();
  const { product, related_products, complete_the_look } = data;
  const bag = useDemoBag();
  const [quickAddedId, setQuickAddedId] = useState<number | null>(null);
  const [copiedLink, setCopiedLink] = useState(false);
  const [recentlyViewed, setRecentlyViewed] = useState<
    Array<{ id: number; name: string; image: string; final_price: number; brandName?: string }>
  >([]);

  useEffect(() => {
    try {
      const storageKey = "bh_recently_viewed_v1";
      const raw = localStorage.getItem(storageKey);
      const parsed: Array<{
        id: number;
        name: string;
        image: string;
        final_price: number;
        brandName?: string;
      }> = raw ? JSON.parse(raw) : [];
      const others = parsed.filter((p) => p.id !== product.id);
      setRecentlyViewed(others.slice(0, 4));
      const updated = [
        {
          id: product.id,
          name: product.name,
          image: product.image,
          final_price: product.final_price,
          brandName: product.brand?.name,
        },
        ...others,
      ].slice(0, 10);
      localStorage.setItem(storageKey, JSON.stringify(updated));
    } catch {
      // Ignore storage quota / privacy mode errors
    }
  }, [product.id, product.name, product.image, product.final_price, product.brand?.name]);

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
      ? t.catalog.discountPct(Math.round(product.discount))
      : t.catalog.discountAmt(formatPrice(basePrice - activePrice))
    : null;

  return (
    <div className="mx-auto max-w-[1440px] px-4 py-6 sm:px-8 sm:py-10">
      <nav aria-label={t.breadcrumbs.ariaLabel} className="mb-6 text-[13px] text-muted">
        <ol className="flex flex-wrap items-center gap-1.5">
          <li>
            <Link href="/" className="hover:text-ink">
              {t.breadcrumbs.home}
            </Link>
          </li>
          <li aria-hidden>/</li>
          <li>
            <Link href="/#shop-by-category" className="hover:text-ink">
              {t.breadcrumbs.categories}
            </Link>
          </li>
          <li aria-hidden>/</li>
          <li aria-current="page" className="text-ink">
            {product.name}
          </li>
        </ol>
      </nav>

      <div className="grid gap-8 lg:grid-cols-12 lg:gap-12 lg:items-start">
        {/* Gallery - sticky & fitted to viewport taking header into account */}
        <div className="lg:col-span-7 lg:sticky lg:top-28 lg:flex lg:flex-col lg:h-[calc(100vh-8.5rem)]">
          <div className="relative min-h-0 flex-1 w-full overflow-hidden rounded-3xl border border-line bg-stage">
            {selectedImage ? (
              <img
                src={selectedImage}
                alt={product.name}
                className="h-full w-full object-contain p-2"
              />
            ) : (
              <div className="flex h-full w-full items-center justify-center text-sm text-muted">
                {t.product.noImageAvailable}
              </div>
            )}
            {hasDiscount && discountLabel && (
              <span className="absolute top-4 right-4 rounded-full bg-brand px-3 py-1 text-xs font-semibold text-white">
                {discountLabel}
              </span>
            )}
            {product.try_on_enabled && selectedImage && (
              <div className="absolute bottom-4 left-4 z-10">
                <TryOnButton
                  productId={product.id}
                  productName={product.name}
                  garmentImage={selectedImage}
                  selectedColor={selectedColor || undefined}
                  variants={
                    isUniquePatterns
                      ? availablePatterns
                          .filter((p) => Boolean(p.image_url))
                          .map((p): TryOnVariant => ({
                            label: p.name,
                            image: p.image_url!,
                          }))
                      : (product.color_images ?? [])
                          .filter((ci) => Boolean(ci.image_url || ci.image))
                          .map((ci): TryOnVariant => ({
                            label: ci.color_name || ci.color || "",
                            image: (ci.image_url || ci.image)!,
                            swatch: ci.color_code,
                          }))
                          .filter((v) => v.label !== "")
                  }
                  locale={locale}
                  variant="detail"
                />
              </div>
            )}
          </div>

          {galleryImages.length > 1 && (
            <div className="mt-3 flex shrink-0 gap-3 overflow-x-auto pb-1">
              {galleryImages.map((img) => {
                const active = img === selectedImage;
                return (
                  <button
                    key={img}
                    type="button"
                    onClick={() => setSelectedImage(img)}
                    className={`relative h-18 w-14 shrink-0 overflow-hidden rounded-xl border bg-stage transition-all sm:h-20 sm:w-16 ${
                      active ? "border-brand ring-2 ring-brand/30" : "border-line opacity-75 hover:opacity-100"
                    }`}
                  >
                    <img src={img} alt="" className="h-full w-full object-contain p-1" />
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
            <span className="nums text-2xl font-bold text-ink">{formatPrice(activePrice)}</span>
            {hasDiscount && (
              <>
                <span className="nums text-base text-muted line-through">
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
              className={`ms-auto rounded-full px-3 py-1 text-xs font-medium ${
                outOfStock
                  ? "bg-red-500/10 text-red-500"
                  : isUniquePatterns && activeStock === 1
                    ? "bg-amber-500/15 text-amber-600"
                    : "bg-emerald-500/10 text-emerald-600"
              }`}
            >
              {outOfStock
                ? t.product.outOfStock
                : isUniquePatterns && activeStock === 1
                  ? t.product.uniquePieceLeft
                  : t.product.inStock(activeStock)}
            </span>
          </div>

          {/* Unique Patterns Visual Grid (Maawaz / One-of-a-kind pieces) */}
          {isUniquePatterns ? (
            <div className="mt-6">
              <div className="mb-2.5 flex items-center justify-between text-[13px]">
                <span className="font-medium text-ink">{t.product.choosePattern}</span>
                <span className="font-semibold text-brand">
                  {activePattern ? activePattern.name : t.product.outOfStock}
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
                          <span className="nums shrink-0 rounded-full bg-stage px-1.5 py-0.5 text-[10px] text-muted">
                            {p.stock === 1 ? (locale === "en" ? "Unique" : "وحيدة") : `${p.stock}`}
                          </span>
                        </div>
                      </button>
                    );
                  })}
                </div>
              ) : (
                <div className="rounded-2xl border border-line bg-stage p-4 text-center text-[13px] text-muted">
                  {locale === "en"
                    ? "All available patterns for this item are currently sold out."
                    : "تم بيع جميع النقشات المتوفرة حالياً لهذا الصنف."}
                </div>
              )}
            </div>
          ) : (
            /* Color Swatches (with thumbnail or color dot) */
            product.color_images &&
            product.color_images.length > 0 && (
              <div className="mt-6">
                <div className="mb-2 flex items-center justify-between text-[13px]">
                  <span className="font-medium text-ink">{t.product.chooseColor}</span>
                  <span className="text-muted">{selectedColor}</span>
                </div>
                <div className="flex flex-wrap gap-2.5">
                  {product.color_images.map((ci, idx) => {
                    const name = ci.color_name || ci.color || `${locale === "en" ? "Color" : "لون"} ${idx + 1}`;
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
                <span className="font-medium text-ink">{t.product.chooseSize}</span>
                {product.size_chart && (
                  <button
                    type="button"
                    onClick={() => setSizeChartOpen(true)}
                    className="text-[12px] font-medium text-brand underline underline-offset-4 hover:opacity-80"
                  >
                    {t.product.sizeChartBtn}
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
                <span className="font-medium text-ink">
                  {locale === "en" ? "Available Variants:" : "جدول المتغيرات المتاحة:"}
                </span>
                <span className="text-[11px] text-muted">
                  {locale === "en" ? "Select from table or above" : "اختر من الجدول أو من الأعلى"}
                </span>
              </div>
              <div className="no-scrollbar max-h-56 overflow-auto rounded-2xl border border-line bg-card">
                <table className="w-full border-collapse text-start text-[12px]">
                  <thead className="sticky top-0 bg-stage text-ink">
                    <tr className="border-b border-line">
                      <th className="px-3 py-2 font-semibold">{locale === "en" ? "Variant" : "المتغير"}</th>
                      <th className="px-3 py-2 font-semibold">{locale === "en" ? "Price" : "السعر"}</th>
                      <th className="px-3 py-2 font-semibold">{locale === "en" ? "Stock" : "المخزون"}</th>
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
                          <td className="nums px-3 py-2">{formatPrice(varPrice)}</td>
                          <td className="px-3 py-2">
                            {v.stock > 0 ? (
                              <span className="text-emerald-600">{t.product.inStock(v.stock)}</span>
                            ) : (
                              <span className="text-red-500">{t.product.outOfStock}</span>
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
                aria-label={t.product.decreaseQty}
                onClick={() => setQty((q) => Math.max(1, q - 1))}
                className="flex h-8 w-8 items-center justify-center rounded-full text-lg text-ink hover:bg-stage"
              >
                -
              </button>
              <span className="nums w-8 text-center text-sm font-semibold text-ink">{qty}</span>
              <button
                type="button"
                aria-label={t.product.increaseQty}
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
                ? t.product.outOfStock
                : addedFeedback
                  ? t.product.addedToBag
                  : t.product.addToBag}
            </button>
          </div>

          {/* Quick Share & WhatsApp Fit Inquiry */}
          <div className="mt-4 flex flex-wrap items-center gap-2.5">
            <a
              href={`https://wa.me/967780024406?text=${encodeURIComponent(
                locale === "en"
                  ? `Hello, I have a question about "${product.name}" (Size: ${selectedSize})`
                  : `مرحباً، أرغب بالاستفسار عن مقاس وتوفر "${product.name}" (المقاس: ${selectedSize})`,
              )}`}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center gap-2 rounded-full border border-emerald-600/30 bg-emerald-600/8 px-4 py-2 text-[12px] font-semibold text-emerald-700 transition-colors hover:bg-emerald-600 hover:text-white dark:text-emerald-400"
            >
              <svg className="h-4 w-4 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91C21.95 6.45 17.5 2 12.04 2zm5.8 14.11c-.25.7-1.44 1.34-2 1.42-.52.08-1.18.11-1.9-.12-.44-.14-1-.33-1.72-.64-3.03-1.31-5-4.37-5.15-4.57-.15-.2-1.23-1.64-1.23-3.13 0-1.49.78-2.22 1.06-2.52.28-.3.61-.38.81-.38.2 0 .41 0 .59.01.19.01.44-.07.69.53.25.61.86 2.1.94 2.25.08.15.13.33.02.53-.1.2-.15.33-.3.51-.15.18-.32.4-.45.54-.15.15-.31.31-.13.61.18.3.79 1.3 1.7 2.11 1.17 1.04 2.15 1.36 2.45 1.51.3.15.48.13.66-.08.18-.2.76-.88.96-1.18.2-.3.41-.25.68-.15.28.1 1.76.83 2.06.98.3.15.5.23.58.35.08.13.08.73-.17 1.43z" />
              </svg>
              <span>{locale === "en" ? "Ask about size on WhatsApp" : "اسأل عن المقاس عبر واتساب"}</span>
            </a>

            <button
              type="button"
              onClick={async () => {
                const url = window.location.href;
                if (navigator.share) {
                  try {
                    await navigator.share({ title: product.name, url });
                    return;
                  } catch {
                    // Fallback to clipboard copy
                  }
                }
                await navigator.clipboard?.writeText(url).catch(() => {});
                setCopiedLink(true);
                setTimeout(() => setCopiedLink(false), 1800);
              }}
              className="inline-flex items-center gap-1.5 rounded-full border border-line bg-card px-4 py-2 text-[12px] font-semibold text-ink transition-colors hover:border-brand/50"
            >
              <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2} aria-hidden="true">
                <path strokeLinecap="round" strokeLinejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
              </svg>
              <span>
                {copiedLink
                  ? locale === "en"
                    ? "Link Copied ✓"
                    : "تم نسخ الرابط ✓"
                  : locale === "en"
                    ? "Share Product"
                    : "مشاركة المنتج"}
              </span>
            </button>
          </div>

          {/* Trust & Delivery Badges */}
          <div className="mt-6 grid grid-cols-2 gap-2.5 sm:grid-cols-4">
            {[
              {
                icon: "✓",
                title: locale === "en" ? "100% Authentic" : "أصلي 100%",
                sub: locale === "en" ? "Guaranteed quality" : "جودة مضمونة",
              },
              {
                icon: "⚡",
                title: locale === "en" ? "Fast Delivery" : "توصيل سريع",
                sub: locale === "en" ? "24–48h dispatch" : "شحن خلال 24-48 ساعة",
              },
              {
                icon: "↺",
                title: locale === "en" ? "Easy Exchange" : "استبدال مرن",
                sub: locale === "en" ? "Hassle-free returns" : "إرجاع واستبدال ميسّر",
              },
              {
                icon: "★",
                title: locale === "en" ? "Flexible Pay" : "دفع ميسّر",
                sub: locale === "en" ? "COD & online" : "عند الاستلام أو إلكترونياً",
              },
            ].map((b) => (
              <div
                key={b.title}
                className="rounded-2xl border border-line bg-stage/35 px-3 py-2.5 text-center"
              >
                <div className="text-[13px] font-bold text-brand">{b.icon}</div>
                <div className="mt-0.5 text-[12px] font-semibold text-ink">{b.title}</div>
                <div className="text-[11px] text-muted">{b.sub}</div>
              </div>
            ))}
          </div>

          {/* Specifications */}
          {product.specifications && product.specifications.length > 0 && (
            <div className="mt-8 border-t border-line pt-6">
              <h2 className="mb-3 text-[14px] font-semibold text-ink">
                {locale === "en" ? "Product Specifications" : "مواصفات المنتج"}
              </h2>
              <dl className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                {product.specifications.map((spec, idx) => (
                  <div
                    key={`${spec.label}-${idx}`}
                    className="flex items-center justify-between gap-3 rounded-xl border border-line bg-card px-3.5 py-2.5 text-[13px]"
                  >
                    <dt className="font-medium text-muted">{spec.label}</dt>
                    <dd className="font-semibold text-ink">{spec.value}</dd>
                  </div>
                ))}
              </dl>
            </div>
          )}

          {/* Description */}
          {product.description && (
            <div className="mt-8 border-t border-line pt-6">
              <h2 className="mb-2 text-[14px] font-semibold text-ink">{t.product.descriptionTitle}</h2>
              <div
                className="prose prose-sm max-w-none text-[14px] leading-relaxed text-muted"
                dangerouslySetInnerHTML={{ __html: product.description }}
              />
            </div>
          )}

          {/* Care Instructions */}
          <div className="mt-8 border-t border-line pt-6">
            <h2 className="mb-3 text-[14px] font-semibold text-ink">
              {locale === "en" ? "Care Instructions" : "إرشادات العناية بالقطعة"}
            </h2>
            <div className="grid grid-cols-2 gap-2.5 sm:grid-cols-4">
              {[
                {
                  symbol: "30°",
                  label: locale === "en" ? "Gentle cold wash" : "غسيل لطيف بدرجة 30°",
                },
                {
                  symbol: "✕",
                  label: locale === "en" ? "Do not bleach" : "يُمنع استخدام المبيض",
                },
                {
                  symbol: "♨",
                  label: locale === "en" ? "Medium heat iron" : "كي بدرجة حرارة متوسطة",
                },
                {
                  symbol: "☀",
                  label: locale === "en" ? "Dry in shade" : "تجفيف بعيداً عن الشمس",
                },
              ].map((care) => (
                <div
                  key={care.label}
                  className="flex items-center gap-2.5 rounded-xl border border-line bg-card px-3 py-2.5"
                >
                  <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-stage text-[11px] font-bold text-ink">
                    {care.symbol}
                  </span>
                  <span className="text-[12px] leading-snug text-muted">{care.label}</span>
                </div>
              ))}
            </div>
          </div>

          {/* Reviews */}
          {product.reviews && product.reviews.length > 0 && (
            <div className="mt-8 border-t border-line pt-6">
              <h2 className="mb-3 text-[14px] font-semibold text-ink">
                {t.product.reviewsTitle} ({product.reviews.length})
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

      {/* Complete Your Look (Distinct Horizontal Compact Cards) */}
      {complete_the_look && complete_the_look.length > 0 && (
        <section className="mt-14 rounded-3xl border border-line bg-stage/35 p-5 sm:p-7">
          <div className="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div>
              <span className="inline-block rounded-full bg-brand/12 px-3 py-0.5 text-[11px] font-semibold text-brand">
                {locale === "en" ? "STYLIST PICKS" : "تنسيق الإطلالة"}
              </span>
              <h2 className="mt-1.5 font-display text-2xl font-semibold text-ink">
                {locale === "en" ? "Complete Your Look" : "أكمل إطلالتك"}
              </h2>
              <p className="mt-0.5 text-[13px] text-muted">
                {locale === "en"
                  ? "Curated pieces that pair effortlessly with this item"
                  : "قطع متناسقة تكمل أناقتك مع هذا المنتج"}
              </p>
            </div>
          </div>

          <div className="grid grid-cols-1 gap-3.5 md:grid-cols-2 lg:grid-cols-3">
            {complete_the_look.map((item) => {
              const isAdded = quickAddedId === item.id;
              const defaultSize = item.sizes?.[0] || "Standard";
              const defaultColor =
                item.color_images?.[0]?.color_name || item.color_images?.[0]?.color || undefined;
              return (
                <div
                  key={item.id}
                  className="group flex items-center gap-3.5 rounded-2xl border border-line bg-card p-3 transition-all hover:border-brand/50 hover:shadow-sm"
                >
                  <Link
                    href={productHref(item.id, item.name)}
                    className="relative h-24 w-20 shrink-0 overflow-hidden rounded-xl bg-stage"
                  >
                    {item.image && (
                      <img
                        src={item.image}
                        alt={item.name}
                        loading="lazy"
                        className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                      />
                    )}
                  </Link>

                  <div className="min-w-0 flex-1">
                    {item.brand?.name && (
                      <p className="text-[11px] font-semibold text-brand uppercase">
                        {item.brand.name}
                      </p>
                    )}
                    <Link
                      href={productHref(item.id, item.name)}
                      className="line-clamp-1 text-[13px] font-semibold text-ink hover:text-brand"
                    >
                      {item.name}
                    </Link>
                    <div className="mt-1 flex items-center gap-2">
                      <span className="nums text-[13px] font-bold text-ink">
                        {formatPrice(item.final_price)}
                      </span>
                      {item.discount > 0 && item.final_price < item.price && (
                        <span className="nums text-[11px] text-muted line-through">
                          {formatPrice(item.price)}
                        </span>
                      )}
                    </div>
                  </div>

                  <button
                    type="button"
                    onClick={() => {
                      bag.add([
                        {
                          lookId: "complete-look",
                          itemId: String(item.id),
                          productId: item.id,
                          label: item.name,
                          size: defaultSize,
                          color: defaultColor,
                          price: item.final_price,
                          qty: 1,
                          image: item.image || undefined,
                        },
                      ]);
                      setQuickAddedId(item.id);
                      setTimeout(() => setQuickAddedId((prev) => (prev === item.id ? null : prev)), 1600);
                    }}
                    className={`shrink-0 rounded-full px-3.5 py-2 text-[12px] font-semibold transition-all ${
                      isAdded
                        ? "bg-emerald-600 text-white"
                        : "border border-brand/40 bg-brand/8 text-brand hover:bg-brand hover:text-white"
                    }`}
                  >
                    {isAdded
                      ? locale === "en"
                        ? "Added ✓"
                        : "تمت الإضافة ✓"
                      : locale === "en"
                        ? "+ Add"
                        : "+ أضف"}
                  </button>
                </div>
              );
            })}
          </div>
        </section>
      )}

      {/* Related Products */}
      {related_products && related_products.length > 0 && (
        <section className="mt-16 border-t border-line pt-10">
          <h2 className="mb-6 font-display text-2xl font-semibold text-ink">{t.product.relatedTitle}</h2>
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:gap-6">
            {related_products.map((rel) => (
              <Link
                key={rel.id}
                href={productHref(rel.id, rel.name)}
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
                  <p className="nums mt-1 text-[14px] font-bold text-ink">
                    {formatPrice(rel.final_price)}
                  </p>
                </div>
              </Link>
            ))}
          </div>
        </section>
      )}

      {/* Recently Viewed Products */}
      {recentlyViewed.length > 0 && (
        <section className="mt-14 border-t border-line pt-10">
          <h2 className="mb-5 font-display text-xl font-semibold text-ink sm:text-2xl">
            {locale === "en" ? "Recently Viewed" : "شاهدتها مؤخراً"}
          </h2>
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:gap-6">
            {recentlyViewed.map((rv) => (
              <Link
                key={rv.id}
                href={productHref(rv.id, rv.name)}
                className="group overflow-hidden rounded-2xl border border-line bg-card transition-all hover:border-brand/50"
              >
                <div className="relative aspect-[3/4] bg-stage">
                  {rv.image && (
                    <img
                      src={rv.image}
                      alt={rv.name}
                      loading="lazy"
                      className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                    />
                  )}
                </div>
                <div className="p-3.5">
                  {rv.brandName && (
                    <p className="text-[10px] font-semibold tracking-wider text-brand uppercase">
                      {rv.brandName}
                    </p>
                  )}
                  <h3 className="line-clamp-1 text-[13px] font-semibold text-ink group-hover:text-brand">
                    {rv.name}
                  </h3>
                  <p className="nums mt-1 text-[14px] font-bold text-ink">
                    {formatPrice(rv.final_price)}
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
                {product.size_chart.name || t.product.sizeChartTitle}
              </h3>
              <button
                type="button"
                onClick={() => setSizeChartOpen(false)}
                className="rounded-full px-3 py-1 text-sm text-muted hover:bg-stage hover:text-ink"
              >
                {t.product.close} ✕
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
