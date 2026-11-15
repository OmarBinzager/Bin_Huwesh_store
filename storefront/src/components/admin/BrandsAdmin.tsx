"use client";

import { AnimatePresence, Reorder, motion } from "framer-motion";
import { useEffect, useMemo, useState } from "react";
import type { Brand, BrandsFile } from "@/content/brand-types";
import BrandStrip from "@/components/sections/BrandStrip";
import BrandLogo from "@/components/store/BrandLogo";
import ThemeToggle from "@/components/store/ThemeToggle";
import { CloseIcon, PlusIcon } from "@/components/store/icons";
import AdminNav from "./AdminNav";
import { processBrandLogo } from "./process-logo";

/**
 * Local editor for the brand strip. Edits a draft of brands.json; "حفظ"
 * writes the file the home page reads. The preview at the top is the real
 * <BrandStrip> fed with the draft, so speed/direction/order show live.
 */
const uid = () => `brand-${Date.now().toString(36)}${Math.random().toString(36).slice(2, 5)}`;
type Msg = { tone: "ok" | "error" | "info"; text: string } | null;

export default function BrandsAdmin({ initial }: { initial: BrandsFile }) {
  const [saved, setSaved] = useState<BrandsFile>(initial);
  const [draft, setDraft] = useState<BrandsFile>(initial);
  const [busy, setBusy] = useState(false);
  const [msg, setMsg] = useState<Msg>(null);
  const dirty = useMemo(() => JSON.stringify(draft) !== JSON.stringify(saved), [draft, saved]);

  useEffect(() => {
    if (!dirty) return;
    const warn = (e: BeforeUnloadEvent) => e.preventDefault();
    window.addEventListener("beforeunload", warn);
    return () => window.removeEventListener("beforeunload", warn);
  }, [dirty]);

  const flash = (m: Msg) => {
    setMsg(m);
    if (m?.tone !== "error") setTimeout(() => setMsg((c) => (c === m ? null : c)), 3200);
  };
  const setBrands = (brands: Brand[]) => setDraft((d) => ({ ...d, brands }));
  const patch = (id: string, fn: (b: Brand) => Brand) => setDraft((d) => ({ ...d, brands: d.brands.map((b) => (b.id === id ? fn(b) : b)) }));
  const setting = <K extends keyof BrandsFile["settings"]>(k: K, v: BrandsFile["settings"][K]) =>
    setDraft((d) => ({ ...d, settings: { ...d.settings, [k]: v } }));

  const upload = async (brand: Brand, file: File) => {
    setBusy(true);
    try {
      const p = await processBrandLogo(file);
      const form = new FormData();
      form.set("brandId", brand.id);
      form.set("mono", p.mono, `${brand.id}.${p.mono.type === "image/webp" ? "webp" : "svg"}`);
      if (p.color) form.set("color", p.color, `${brand.id}-color.${p.color.type === "image/webp" ? "webp" : "svg"}`);
      const res = await fetch("/api/admin/brands/logo", { method: "POST", body: form });
      const body = await res.json();
      if (!res.ok) throw new Error(body.error ?? "تعذّر رفع الشعار");
      patch(brand.id, (b) => ({ ...b, logo: body.logo, colorLogo: body.colorLogo, aspect: p.aspect }));
      flash({ tone: "ok", text: `رُفع شعار ${brand.name}. ${p.warning ?? ""} اضغط «حفظ» لاعتماده.` });
    } catch (e) {
      flash({ tone: "error", text: (e as Error).message });
    } finally {
      setBusy(false);
    }
  };

  const save = async () => {
    setBusy(true);
    try {
      const res = await fetch("/api/admin/brands", { method: "PUT", headers: { "content-type": "application/json" }, body: JSON.stringify(draft) });
      const body = await res.json();
      if (!res.ok) throw new Error(body.error ?? "تعذّر الحفظ");
      setSaved(body);
      setDraft(body);
      flash({ tone: "ok", text: "تم الحفظ في src/content/brands.json — الصفحة الرئيسية تعرض الشريط المحدّث الآن." });
    } catch (e) {
      flash({ tone: "error", text: (e as Error).message });
    } finally {
      setBusy(false);
    }
  };

  const s = draft.settings;
  return (
    <div className="min-h-svh bg-paper text-ink">
      <header className="sticky top-0 z-40 flex flex-wrap items-center gap-3 border-b border-line bg-card/95 px-4 py-3 backdrop-blur sm:px-6">
        <BrandLogo height={30} />
        <div className="me-auto">
          <h1 className="font-display text-lg font-semibold">إدارة شريط الماركات</h1>
          <p className="text-[11px] text-muted">أداة محلية للتطوير فقط — تحفظ في src/content/brands.json</p>
        </div>
        <AdminNav current="brands" />
        <span className={`rounded-full px-3 py-1 text-[11px] ${dirty ? "bg-brand/10 text-brand" : "bg-ink/6 text-muted"}`}>
          {dirty ? "تعديلات غير محفوظة" : "محفوظ"}
        </span>
        <ThemeToggle />
        <button type="button" disabled={!dirty || busy} onClick={() => confirm("التراجع عن كل التعديلات غير المحفوظة؟") && setDraft(saved)}
          className="rounded-full border border-ink/20 px-4 py-2 text-[13px] hover:border-ink disabled:opacity-40">
          تراجع
        </button>
        <button type="button" disabled={!dirty || busy} onClick={save}
          className="rounded-full bg-brand px-5 py-2 text-[13px] font-semibold text-white hover:bg-brand-deep disabled:opacity-40">
          {busy ? "جارٍ…" : "حفظ"}
        </button>
      </header>

      <AnimatePresence>
        {msg && (
          <motion.p initial={{ opacity: 0, y: -6 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0 }} role="status" onClick={() => setMsg(null)}
            className={`mx-4 mt-3 rounded-xl px-4 py-2.5 text-[13px] sm:mx-6 ${msg.tone === "error" ? "bg-brand/12 text-brand" : msg.tone === "ok" ? "bg-emerald-500/12 text-emerald-700 dark:text-emerald-400" : "bg-ink/6"}`}>
            {msg.text}
          </motion.p>
        )}
      </AnimatePresence>

      {/* live preview of the draft */}
      <section className="mt-4">
        <p className="mb-2 px-4 text-[11px] text-muted sm:px-6">معاينة مباشرة للمسودة {dirty && <span className="text-brand">(غير محفوظة)</span>}</p>
        <BrandStrip data={draft} />
      </section>

      <div className="grid gap-5 p-4 sm:p-6 lg:grid-cols-[minmax(0,1fr)_340px]">
        <section className="rounded-2xl border border-line bg-card p-3">
          <div className="mb-2 flex items-center justify-between px-1">
            <h2 className="text-[13px] font-semibold">الماركات ({draft.brands.length}) — اسحب لإعادة الترتيب</h2>
            <button type="button"
              onClick={() => setBrands([...draft.brands, { id: uid(), name: "ماركة جديدة", logo: "/images/brands/nike.svg", aspect: 2.76, mode: "mono", scale: 1, visible: false, source: "" }])}
              className="flex items-center gap-1 rounded-full bg-ink px-3 py-1.5 text-[12px] text-on-ink">
              <PlusIcon width={14} height={14} /> ماركة
            </button>
          </div>
          <Reorder.Group axis="y" values={draft.brands} onReorder={setBrands} className="space-y-2">
            {draft.brands.map((b, i) => (
              <Reorder.Item key={b.id} value={b} className="grid cursor-grab grid-cols-[28px_120px_minmax(0,1fr)] items-center gap-3 rounded-xl border border-line p-2.5 active:cursor-grabbing sm:grid-cols-[28px_140px_minmax(0,1fr)_auto]">
                <span className="nums text-center text-[11px] text-muted">{String(i + 1).padStart(2, "0")}</span>
                <span className={`grid h-12 place-items-center rounded-lg bg-stage ${b.visible ? "" : "opacity-40"}`}>
                  <span className="block h-7 w-[110px] bg-ink"
                    style={{ WebkitMaskImage: `url("${b.logo}")`, maskImage: `url("${b.logo}")`, WebkitMaskSize: "contain", maskSize: "contain", WebkitMaskRepeat: "no-repeat", maskRepeat: "no-repeat", WebkitMaskPosition: "center", maskPosition: "center" }} />
                </span>
                <div className="grid min-w-0 gap-1.5 sm:grid-cols-2">
                  <label className="block">
                    <span className="mb-0.5 block text-[10.5px] text-muted">الاسم</span>
                    <input value={b.name} onChange={(e) => patch(b.id, (x) => ({ ...x, name: e.target.value }))}
                      className="w-full rounded-lg border border-line bg-paper px-2.5 py-1.5 text-[12.5px] outline-none focus:border-ink" dir="auto" />
                  </label>
                  <label className="block">
                    <span className="mb-0.5 flex justify-between text-[10.5px] text-muted">الحجم <span className="nums text-ink" dir="ltr">{Math.round(b.scale * 100)}%</span></span>
                    <input type="range" min={0.6} max={1.5} step={0.05} value={b.scale} onChange={(e) => patch(b.id, (x) => ({ ...x, scale: Number(e.target.value) }))} className="w-full accent-[var(--color-brand)]" />
                  </label>
                  <label className="block sm:col-span-2">
                    <span className="mb-0.5 block text-[10.5px] text-muted">مصدر الشعار</span>
                    <input value={b.source} onChange={(e) => patch(b.id, (x) => ({ ...x, source: e.target.value }))} dir="ltr"
                      className="w-full rounded-lg border border-line bg-paper px-2.5 py-1.5 text-[11px] text-muted outline-none focus:border-ink" />
                  </label>
                </div>
                <div className="col-span-3 flex flex-wrap items-center gap-2 sm:col-span-1 sm:flex-col sm:items-stretch">
                  <label className="flex items-center gap-1.5 text-[12px]">
                    <input type="checkbox" checked={b.visible} onChange={(e) => patch(b.id, (x) => ({ ...x, visible: e.target.checked }))} className="size-4 accent-[var(--color-brand)]" />
                    ظاهرة
                  </label>
                  <select value={b.mode} onChange={(e) => patch(b.id, (x) => ({ ...x, mode: e.target.value as Brand["mode"] }))}
                    title="أحادي اللون يتكيّف مع الوضعين؛ الألوان الأصلية قد لا تظهر في الوضع الليلي"
                    className="rounded-lg border border-line bg-paper px-2 py-1 text-[11.5px]">
                    <option value="mono">لون واحد (يتبع الوضع)</option>
                    <option value="color" disabled={!b.colorLogo}>الألوان الأصلية</option>
                  </select>
                  <label className="cursor-pointer rounded-full border border-ink/20 px-3 py-1 text-center text-[11.5px] hover:border-ink">
                    تغيير الشعار
                    <input type="file" accept=".svg,image/svg+xml,image/png,image/webp" className="sr-only"
                      onChange={(e) => { const f = e.target.files?.[0]; e.target.value = ""; if (f) void upload(b, f); }} />
                  </label>
                  <button type="button" onClick={() => confirm(`حذف «${b.name}» من الشريط؟`) && setBrands(draft.brands.filter((x) => x.id !== b.id))}
                    className="flex items-center justify-center gap-1 text-[11px] text-muted hover:text-brand">
                    <CloseIcon width={12} height={12} /> حذف
                  </button>
                </div>
              </Reorder.Item>
            ))}
          </Reorder.Group>
        </section>

        <aside className="h-fit space-y-4 rounded-2xl border border-line bg-card p-4">
          <h2 className="text-[13px] font-semibold">إعدادات الشريط</h2>
          <label className="block">
            <span className="mb-1 block text-[11.5px] text-muted">العنوان</span>
            <input value={s.title} onChange={(e) => setting("title", e.target.value)} className="w-full rounded-lg border border-line bg-paper px-3 py-2 text-[13px] outline-none focus:border-ink" />
          </label>
          <label className="flex items-center gap-2 text-[12.5px]">
            <input type="checkbox" checked={s.showTitle} onChange={(e) => setting("showTitle", e.target.checked)} className="size-4 accent-[var(--color-brand)]" />
            إظهار العنوان
          </label>
          <label className="block">
            <span className="mb-1 flex justify-between text-[11.5px] text-muted">السرعة <span className="nums text-ink" dir="ltr">{s.speed} px/s</span></span>
            <input type="range" min={10} max={120} step={1} value={s.speed} onChange={(e) => setting("speed", Number(e.target.value))} className="w-full accent-[var(--color-brand)]" />
            <span className="mt-0.5 flex justify-between text-[10px] text-muted"><span>أهدأ</span><span>أسرع</span></span>
          </label>
          <div>
            <span className="mb-1 block text-[11.5px] text-muted">اتجاه الحركة</span>
            <div role="radiogroup" className="flex rounded-full border border-line p-1">
              {(["left", "right"] as const).map((d) => (
                <button key={d} type="button" role="radio" aria-checked={s.direction === d} onClick={() => setting("direction", d)}
                  className={`flex-1 rounded-full py-1.5 text-[12px] ${s.direction === d ? "bg-ink text-on-ink" : "text-muted"}`}>
                  {d === "left" ? "← نحو اليسار" : "نحو اليمين →"}
                </button>
              ))}
            </div>
          </div>
          <label className="block">
            <span className="mb-1 flex justify-between text-[11.5px] text-muted">حجم الشعارات <span className="nums text-ink" dir="ltr">{s.logoSize}px</span></span>
            <input type="range" min={22} max={56} step={1} value={s.logoSize} onChange={(e) => setting("logoSize", Number(e.target.value))} className="w-full accent-[var(--color-brand)]" />
          </label>
          <p className="text-[11px] leading-relaxed text-muted">
            الشعارات تُعرض بلون واحد يتبع الوضع النهاري/الليلي، وتُوحَّد أحجامها بصريًا مع حفظ نسبها. القائمة مستقلة عن المخزون إلى أن تُربط بمنتجات المتجر.
          </p>
        </aside>
      </div>
    </div>
  );
}
