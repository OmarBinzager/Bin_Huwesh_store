"use client";

import { useRouter, useSearchParams } from "next/navigation";
import { useState } from "react";
import { useCurrency } from "@/lib/currency";
import { useI18n } from "@/lib/i18n/context";
import type { CatalogFacets } from "@/lib/storefront-api";

type CatalogSidebarFiltersProps = {
  basePath: string;
  facets?: CatalogFacets;
  subCategories?: Array<{ id: number; name: string }>;
};

const COLOR_SWATCH_FALLBACK: Record<string, string> = {
  أبيض: "#F8F8F8",
  white: "#F8F8F8",
  أسود: "#18181B",
  black: "#18181B",
  رمادي: "#9CA3AF",
  grey: "#9CA3AF",
  gray: "#9CA3AF",
  سماوي: "#60A5FA",
  "sky blue": "#60A5FA",
  أزرق: "#2563EB",
  blue: "#2563EB",
  كحلي: "#1E3A5F",
  navy: "#1E3A5F",
  بيج: "#D6C5A9",
  beige: "#D6C5A9",
  بني: "#78350F",
  brown: "#78350F",
  زيتي: "#4B5320",
  "زيتي هادئ": "#4B5320",
  olive: "#4B5320",
  أخضر: "#16A34A",
  green: "#16A34A",
  أحمر: "#DC2626",
  red: "#DC2626",
  عنابي: "#7F1D1D",
  burgundy: "#7F1D1D",
};

function resolveSwatchHex(name: string, explicitCode: string | null): string {
  if (explicitCode && /^#[0-9A-Fa-f]{3,8}$/.test(explicitCode.trim())) {
    return explicitCode.trim();
  }
  const key = name.trim().toLowerCase();
  return COLOR_SWATCH_FALLBACK[key] || COLOR_SWATCH_FALLBACK[name.trim()] || "#D1D5DB";
}

function parseAttrsParam(raw: string | null): Record<string, string[]> {
  const result: Record<string, string[]> = {};
  if (!raw) return result;
  for (const group of raw.split("|")) {
    const idx = group.indexOf(":");
    if (idx === -1) continue;
    const title = group.slice(0, idx).trim();
    const vals = group
      .slice(idx + 1)
      .split(",")
      .map((v) => v.trim())
      .filter(Boolean);
    if (title && vals.length > 0) result[title] = vals;
  }
  return result;
}

function serializeAttrsParam(map: Record<string, string[]>): string {
  return Object.entries(map)
    .filter(([, vals]) => vals.length > 0)
    .map(([title, vals]) => `${title}:${vals.join(",")}`)
    .join("|");
}

export default function CatalogSidebarFilters({
  basePath,
  facets,
  subCategories,
}: CatalogSidebarFiltersProps) {
  const router = useRouter();
  const sp = useSearchParams();
  const { t } = useI18n();
  const { formatMoney } = useCurrency();

  const [mobileOpen, setMobileOpen] = useState(false);
  const [minPriceInput, setMinPriceInput] = useState(() => sp.get("min_price") ?? "");
  const [maxPriceInput, setMaxPriceInput] = useState(() => sp.get("max_price") ?? "");

  const selectedColors = (sp.get("color") ?? "")
    .split(",")
    .map((v) => v.trim())
    .filter(Boolean);
  const selectedBrands = (sp.get("brand_id") ?? "")
    .split(",")
    .map((v) => v.trim())
    .filter(Boolean);
  const selectedAttrs = parseAttrsParam(sp.get("attrs"));
  const selectedCategory = sp.get("category") ?? "";
  const selectedSubCategory = sp.get("sub_category_id") ?? "";
  const inStockOnly = sp.get("in_stock") === "1";
  const onSaleOnly = sp.get("on_sale") === "1";

  const activeFilterCount =
    selectedColors.length +
    selectedBrands.length +
    Object.values(selectedAttrs).reduce((s, a) => s + a.length, 0) +
    (selectedCategory ? 1 : 0) +
    (selectedSubCategory ? 1 : 0) +
    (inStockOnly ? 1 : 0) +
    (onSaleOnly ? 1 : 0) +
    (sp.get("min_price") ? 1 : 0) +
    (sp.get("max_price") ? 1 : 0);

  const pushParams = (updates: Record<string, string | undefined>) => {
    const next = new URLSearchParams(sp.toString());
    next.delete("offset");
    for (const [k, v] of Object.entries(updates)) {
      if (v === undefined || v === "") next.delete(k);
      else next.set(k, v);
    }
    const q = next.toString();
    router.push(`${basePath}${q ? `?${q}` : ""}`);
  };

  const toggleCsvParam = (key: string, current: string[], value: string) => {
    const exists = current.includes(value);
    const updated = exists ? current.filter((v) => v !== value) : [...current, value];
    pushParams({ [key]: updated.length > 0 ? updated.join(",") : undefined });
  };

  const toggleAttrValue = (title: string, value: string) => {
    const curr = selectedAttrs[title] ?? [];
    const exists = curr.includes(value);
    const nextGroup = exists ? curr.filter((v) => v !== value) : [...curr, value];
    const nextMap = { ...selectedAttrs, [title]: nextGroup };
    const serialized = serializeAttrsParam(nextMap);
    pushParams({ attrs: serialized || undefined });
  };

  const clearAll = () => {
    setMinPriceInput("");
    setMaxPriceInput("");
    const next = new URLSearchParams();
    const q = sp.get("q");
    const sortBy = sp.get("sort_by");
    if (q) next.set("q", q);
    if (sortBy) next.set("sort_by", sortBy);
    const qs = next.toString();
    router.push(`${basePath}${qs ? `?${qs}` : ""}`);
  };

  const handlePriceSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    pushParams({
      min_price: minPriceInput.trim() || undefined,
      max_price: maxPriceInput.trim() || undefined,
    });
  };

  const filterBody = (
    <div className="space-y-6">
      <div className="flex items-center justify-between border-b border-line pb-3">
        <h2 className="font-display text-base font-semibold text-ink">
          {t.catalog.filtersTitle}
        </h2>
        {activeFilterCount > 0 && (
          <button
            type="button"
            onClick={clearAll}
            className="text-xs font-medium text-brand hover:underline"
          >
            {t.catalog.clearAllFilters} ({activeFilterCount})
          </button>
        )}
      </div>

      {/* Availability / Available Data */}
      <div className="space-y-2.5">
        <h3 className="text-xs font-semibold text-muted">{t.catalog.availabilityFilter}</h3>
        <label className="flex min-h-9 cursor-pointer items-center justify-between gap-2 rounded-xl px-2 py-1.5 text-sm transition hover:bg-stage">
          <span className="flex items-center gap-2.5 text-ink">
            <input
              type="checkbox"
              checked={inStockOnly}
              onChange={(e) => pushParams({ in_stock: e.target.checked ? "1" : undefined })}
              className="accent-brand"
            />
            <span>{t.catalog.inStockOnly}</span>
          </span>
          {facets && (
            <span className="nums text-xs text-muted">{facets.in_stock_count}</span>
          )}
        </label>
        <label className="flex min-h-9 cursor-pointer items-center justify-between gap-2 rounded-xl px-2 py-1.5 text-sm transition hover:bg-stage">
          <span className="flex items-center gap-2.5 text-ink">
            <input
              type="checkbox"
              checked={onSaleOnly}
              onChange={(e) => pushParams({ on_sale: e.target.checked ? "1" : undefined })}
              className="accent-brand"
            />
            <span>{t.catalog.onSaleOnly}</span>
          </span>
          {facets && (
            <span className="nums text-xs text-muted">{facets.on_sale_count}</span>
          )}
        </label>
      </div>

      {/* Categories (on /products) */}
      {facets?.categories && facets.categories.length > 0 && (
        <div className="space-y-2.5 border-t border-line pt-4">
          <h3 className="text-xs font-semibold text-muted">{t.catalog.categoriesFilter}</h3>
          <div className="space-y-1">
            <button
              type="button"
              onClick={() => pushParams({ category: undefined })}
              className={`flex w-full items-center justify-between rounded-xl px-3 py-2 text-start text-sm transition ${
                !selectedCategory
                  ? "bg-ink font-medium text-on-ink"
                  : "text-ink hover:bg-stage"
              }`}
            >
              <span>{t.catalog.allCategoriesOption}</span>
            </button>
            {facets.categories.map((cat) => {
              const active = selectedCategory === cat.slug || selectedCategory === String(cat.id);
              return (
                <button
                  key={cat.id}
                  type="button"
                  onClick={() => pushParams({ category: active ? undefined : cat.slug })}
                  className={`flex w-full items-center justify-between rounded-xl px-3 py-2 text-start text-sm transition ${
                    active ? "bg-ink font-medium text-on-ink" : "text-ink hover:bg-stage"
                  }`}
                >
                  <span className="truncate">{cat.name}</span>
                  <span className={`nums text-xs ${active ? "opacity-80" : "text-muted"}`}>
                    {cat.count}
                  </span>
                </button>
              );
            })}
          </div>
        </div>
      )}

      {/* Subcategories (on /categories/[slug]) */}
      {subCategories && subCategories.length > 0 && (
        <div className="space-y-2.5 border-t border-line pt-4">
          <h3 className="text-xs font-semibold text-muted">{t.catalog.categoriesFilter}</h3>
          <div className="flex flex-wrap gap-1.5">
            <button
              type="button"
              onClick={() => pushParams({ sub_category_id: undefined })}
              className={`rounded-full px-3 py-1.5 text-xs font-medium transition ${
                !selectedSubCategory
                  ? "bg-ink text-on-ink"
                  : "border border-line bg-background text-ink hover:border-brand"
              }`}
            >
              {t.catalog.allCategoriesOption}
            </button>
            {subCategories.map((sub) => {
              const active = selectedSubCategory === String(sub.id);
              return (
                <button
                  key={sub.id}
                  type="button"
                  onClick={() =>
                    pushParams({ sub_category_id: active ? undefined : String(sub.id) })
                  }
                  className={`rounded-full px-3 py-1.5 text-xs font-medium transition ${
                    active
                      ? "bg-ink text-on-ink"
                      : "border border-line bg-background text-ink hover:border-brand"
                  }`}
                >
                  {sub.name}
                </button>
              );
            })}
          </div>
        </div>
      )}

      {/* Colors */}
      {facets?.colors && facets.colors.length > 0 && (
        <div className="space-y-2.5 border-t border-line pt-4">
          <h3 className="text-xs font-semibold text-muted">{t.catalog.colorFilter}</h3>
          <div className="flex flex-wrap gap-2">
            {facets.colors.map((c) => {
              const active = selectedColors.includes(c.name);
              const hex = resolveSwatchHex(c.name, c.code);
              return (
                <button
                  key={c.name}
                  type="button"
                  onClick={() => toggleCsvParam("color", selectedColors, c.name)}
                  className={`inline-flex h-9 items-center gap-2 rounded-full border px-3 text-xs font-medium transition ${
                    active
                      ? "border-brand bg-brand/10 text-ink"
                      : "border-line bg-background text-ink hover:border-ink"
                  }`}
                >
                  <span
                    aria-hidden
                    className="size-3.5 shrink-0 rounded-full border border-line"
                    style={{ backgroundColor: hex }}
                  />
                  <span>{c.name}</span>
                  <span className="nums text-[11px] text-muted">({c.count})</span>
                </button>
              );
            })}
          </div>
        </div>
      )}

      {/* Aggregated Attributes & Options */}
      {facets?.attributes &&
        facets.attributes.map((attr) => (
          <div key={attr.title} className="space-y-2.5 border-t border-line pt-4">
            <h3 className="text-xs font-semibold text-muted">{attr.title}</h3>
            <div className="flex flex-wrap gap-1.5">
              {attr.options.map((opt) => {
                const active = (selectedAttrs[attr.title] ?? []).includes(opt.value);
                return (
                  <button
                    key={opt.value}
                    type="button"
                    onClick={() => toggleAttrValue(attr.title, opt.value)}
                    className={`inline-flex h-8 items-center gap-1.5 rounded-full border px-3 text-xs font-medium transition ${
                      active
                        ? "border-ink bg-ink text-on-ink"
                        : "border-line bg-background text-ink hover:border-brand"
                    }`}
                  >
                    <span>{opt.value}</span>
                    <span className={`nums text-[11px] ${active ? "opacity-80" : "text-muted"}`}>
                      {opt.count}
                    </span>
                  </button>
                );
              })}
            </div>
          </div>
        ))}

      {/* Brands */}
      {facets?.brands && facets.brands.length > 0 && (
        <div className="space-y-2 border-t border-line pt-4">
          <h3 className="text-xs font-semibold text-muted">{t.catalog.brandsFilter}</h3>
          <div className="max-h-48 space-y-1 overflow-y-auto pe-1">
            {facets.brands.map((b) => {
              const active = selectedBrands.includes(String(b.id));
              return (
                <label
                  key={b.id}
                  className="flex min-h-8 cursor-pointer items-center justify-between gap-2 rounded-xl px-2 py-1 text-sm transition hover:bg-stage"
                >
                  <span className="flex items-center gap-2.5 text-ink">
                    <input
                      type="checkbox"
                      checked={active}
                      onChange={() => toggleCsvParam("brand_id", selectedBrands, String(b.id))}
                      className="accent-brand"
                    />
                    <span className="truncate">{b.name}</span>
                  </span>
                  <span className="nums text-xs text-muted">{b.count}</span>
                </label>
              );
            })}
          </div>
        </div>
      )}

      {/* Price Range */}
      <form onSubmit={handlePriceSubmit} className="space-y-2.5 border-t border-line pt-4">
        <div className="flex items-center justify-between gap-2">
          <h3 className="text-xs font-semibold text-muted">{t.catalog.priceRangeFilter}</h3>
          {facets?.price_range && facets.price_range.max > 0 && (
            <span className="nums text-[11px] text-muted">
              {formatMoney(facets.price_range.min)} – {formatMoney(facets.price_range.max)}
            </span>
          )}
        </div>
        <div className="grid grid-cols-2 gap-2">
          <input
            type="number"
            min={0}
            dir="ltr"
            value={minPriceInput}
            onChange={(e) => setMinPriceInput(e.target.value)}
            placeholder={t.catalog.minPricePlaceholder}
            className="h-9 w-full rounded-xl border border-line bg-background px-3 text-xs text-ink outline-none focus:border-brand"
          />
          <input
            type="number"
            min={0}
            dir="ltr"
            value={maxPriceInput}
            onChange={(e) => setMaxPriceInput(e.target.value)}
            placeholder={t.catalog.maxPricePlaceholder}
            className="h-9 w-full rounded-xl border border-line bg-background px-3 text-xs text-ink outline-none focus:border-brand"
          />
        </div>
        <button
          type="submit"
          className="h-9 w-full rounded-full bg-ink text-xs font-medium text-on-ink transition hover:bg-brand"
        >
          {t.catalog.applyPriceBtn}
        </button>
      </form>
    </div>
  );

  return (
    <aside className="lg:w-72 shrink-0">
      {/* Mobile toggle button */}
      <div className="mb-4 flex items-center justify-between lg:hidden">
        <button
          type="button"
          onClick={() => setMobileOpen((v) => !v)}
          className="inline-flex h-10 items-center gap-2 rounded-full border border-line bg-card px-4 text-sm font-medium text-ink"
        >
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75">
            <path d="M4 6h16M7 12h10M10 18h4" strokeLinecap="round" />
          </svg>
          <span>{t.catalog.toggleFiltersBtn}</span>
          {activeFilterCount > 0 && (
            <span className="nums grid size-5 place-items-center rounded-full bg-brand text-[11px] font-bold text-white">
              {activeFilterCount}
            </span>
          )}
        </button>
      </div>

      {/* Sidebar panel */}
      <div
        className={`${
          mobileOpen ? "block mb-6" : "hidden"
        } rounded-3xl border border-line bg-card p-5 lg:sticky lg:top-6 lg:block`}
      >
        {filterBody}
      </div>
    </aside>
  );
}
