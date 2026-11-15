"use client";

import { AnimatePresence, Reorder, motion, useDragControls } from "framer-motion";
import { useEffect, useMemo, useState } from "react";
import NewArrivalsSection from "@/components/sections/NewArrivalsSection";
import BrandLogo from "@/components/store/BrandLogo";
import ThemeToggle from "@/components/store/ThemeToggle";
import { CloseIcon, PlusIcon } from "@/components/store/icons";
import { NA_SIZES, type NaColor, type NaItem, type NaSize, type NewArrivalsFile } from "@/content/new-arrivals-types";
import AdminNav from "./AdminNav";
import { processCutout, type CutoutKind } from "./process-cutout";

/**
 * Local editor for «وصل حديثًا». Edits a draft of new-arrivals.json; «حفظ»
 * writes the file the home page reads (the server re-validates everything and
 * checks every image exists). Each colour is a photographed pair: a garment
 * cut-out and the model wearing it — both transparent, uploaded together.
 */
const uid = (p: string) => `${p}-${Date.now().toString(36)}${Math.random().toString(36).slice(2, 5)}`;
type Msg = { tone: "ok" | "error" | "info"; text: string } | null;
const checker = "repeating-conic-gradient(rgba(0,0,0,.08) 0 25%, transparent 0 50%) 0 0 / 14px 14px";

export default function NewArrivalsAdmin({ initial }: { initial: NewArrivalsFile }) {
  const [saved, setSaved] = useState(initial);
  const [draft, setDraft] = useState(initial);
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
    if (m?.tone !== "error") setTimeout(() => setMsg((c) => (c === m ? null : c)), 3600);
  };
  const setItems = (items: NaItem[]) => setDraft((d) => ({ ...d, items }));
  const patch = (id: string, fn: (i: NaItem) => NaItem) => setDraft((d) => ({ ...d, items: d.items.map((i) => (i.id === id ? fn(i) : i)) }));
  const setting = (k: "title" | "subtitle" | "watermark", v: string) => setDraft((d) => ({ ...d, settings: { ...d.settings, [k]: v } }));

  const upload = async (item: NaItem, color: NaColor, kind: CutoutKind, file: File) => {
    setBusy(true);
    try {
      const p = await processCutout(file, kind);
      const form = new FormData();
      form.set("itemId", item.id);
      form.set("colorId", color.id);
      form.set("kind", kind);
      form.set("image", p.blob, `${item.id}.webp`);
      const res = await fetch("/api/admin/new-arrivals/image", { method: "POST", body: form });
      const body = await res.json();
      if (!res.ok) throw new Error(body.error ?? "تعذّر رفع الصورة");
      patch(item.id, (i) => ({ ...i, colors: i.colors.map((c) => (c.id === color.id ? { ...c, [kind]: body.image } : c)) }));
      flash({ tone: "ok", text: `رُفعت ${kind === "model" ? "صورة المودل" : "صورة القطعة"} (${p.width}×${p.height}). تأكد أن الصورتين لنفس اللون ثم اضغط «حفظ».` });
    } catch (e) {
      flash({ tone: "error", text: (e as Error).message });
    } finally {
      setBusy(false);
    }
  };

  const save = async () => {
    setBusy(true);
    try {
      const res = await fetch("/api/admin/new-arrivals", { method: "PUT", headers: { "content-type": "application/json" }, body: JSON.stringify(draft) });
      const body = await res.json();
      if (!res.ok) throw new Error(body.error ?? "تعذّر الحفظ");
      setSaved(body);
      setDraft(body);
      flash({ tone: "ok", text: "تم الحفظ في src/content/new-arrivals.json — الصفحة الرئيسية تعرض القطع المحدّثة الآن." });
    } catch (e) {
      flash({ tone: "error", text: (e as Error).message });
    } finally {
      setBusy(false);
    }
  };

  const addItem = () => {
    setItems([
      ...draft.items,
      {
        id: uid("item"),
        name: "قطعة جديدة",
        categoryId: draft.categories[0].id,
        price: 0,
        sizes: ["S", "M", "L", "XL"],
        colors: [{ id: "c1", name: "", swatch: "#f4f2ee", garment: "", model: "" }],
        visible: false,
      },
    ]);
    flash({ tone: "info", text: "أُضيفت قطعة مخفية في آخر القائمة — ارفع صورة القطعة وصورة المودل للون ثم احفظ." });
  };

  const s = draft.settings;
  const visibleCount = draft.items.filter((i) => i.visible).length;
  return (
    <div className="min-h-svh bg-paper text-ink">
      <header className="sticky top-0 z-[200] flex flex-wrap items-center gap-3 border-b border-line bg-card/95 px-4 py-3 backdrop-blur sm:px-6">
        <BrandLogo height={30} />
        <div className="me-auto">
          <h1 className="font-display text-lg font-semibold">إدارة «وصل حديثًا»</h1>
          <p className="text-[11px] text-muted">أداة محلية للتطوير فقط — تحفظ في src/content/new-arrivals.json</p>
        </div>
        <AdminNav current="new-arrivals" />
        <span className={`rounded-full px-3 py-1 text-[11px] ${dirty ? "bg-brand/10 text-brand" : "bg-ink/6 text-muted"}`}>{dirty ? "تعديلات غير محفوظة" : "محفوظ"}</span>
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

      <div className="grid gap-5 p-4 sm:p-6 xl:grid-cols-[minmax(0,1fr)_320px]">
        <section className="rounded-2xl border border-line bg-card p-3">
          <div className="mb-2 flex flex-wrap items-center justify-between gap-2 px-1">
            <h2 className="text-[13px] font-semibold">القطع ({draft.items.length}) — اسحب من الرقم لإعادة الترتيب</h2>
            <button type="button" onClick={addItem} className="flex items-center gap-1 rounded-full bg-ink px-3 py-1.5 text-[12px] text-on-ink">
              <PlusIcon width={14} height={14} /> قطعة
            </button>
          </div>
          {visibleCount === 0 && <p className="mb-2 rounded-lg bg-amber-500/12 px-3 py-2 text-[11.5px] text-amber-800 dark:text-amber-300">لا توجد قطع ظاهرة — سيُخفى القسم من الصفحة الرئيسية.</p>}
          <Reorder.Group axis="y" values={draft.items} onReorder={setItems} className="space-y-2">
            {draft.items.map((it, i) => (
              <ItemRow key={it.id} item={it} index={i} busy={busy} categories={draft.categories} patch={patch} upload={upload}
                remove={() => confirm(`حذف «${it.name}» من القسم؟ (الصور تبقى في المجلد)`) && setItems(draft.items.filter((x) => x.id !== it.id))} />
            ))}
          </Reorder.Group>
        </section>

        <aside className="h-fit space-y-4 rounded-2xl border border-line bg-card p-4">
          <h2 className="text-[13px] font-semibold">نصوص القسم</h2>
          <Field label="العنوان" value={s.title} onChange={(v) => setting("title", v)} />
          <Field label="النص المساعد" value={s.subtitle} onChange={(v) => setting("subtitle", v)} />
          <Field label="الكلمة الخلفية خلف المودل" value={s.watermark} dir="ltr" onChange={(v) => setting("watermark", v)} />
          <div>
            <p className="mb-1 text-[11.5px] text-muted">الفئات (تظهر كتبويبات حين تحتوي قطعًا ظاهرة)</p>
            <ul className="flex flex-wrap gap-1.5">
              {draft.categories.map((c) => (
                <li key={c.id} className="rounded-full border border-line px-2.5 py-1 text-[11.5px]">
                  {c.name} · {draft.items.filter((i) => i.visible && i.categoryId === c.id).length}
                </li>
              ))}
            </ul>
          </div>
          <p className="text-[11px] leading-relaxed text-muted">
            كل لون = صورتان متطابقتان بخلفية شفافة: القطعة وحدها، والمودل نفسه يرتديها بنفس الوقفة والإطار (640×1360 أو 524×1360، من الرأس إلى منتصف الفخذ، والرأس أعلى الإطار في المنتصف) حتى لا يقفز الوجه أثناء المسح. لا تُضاف ألوان بلا صور، ولا تُلوَّن الصور بفلتر.
          </p>
          <p className="text-[11px] leading-relaxed text-muted">الأسعار تجريبية بالريال اليمني، ولا يوجد مخزون أو طلب شراء حقيقي.</p>
        </aside>
      </div>

      <section className="pb-10">
        <p className="mb-2 px-4 text-[11px] text-muted sm:px-6">معاينة مباشرة للمسودة {dirty && <span className="text-brand">(غير محفوظة)</span>}</p>
        <div className="border-y border-line">
          <NewArrivalsSection data={{ ...draft, items: draft.items.filter((i) => i.colors.every((c) => c.garment && c.model)) }} />
        </div>
      </section>
    </div>
  );
}

function Field({ label, value, onChange, dir, compact, type, placeholder }: {
  label: string; value: string; onChange: (v: string) => void; dir?: "ltr" | "auto"; compact?: boolean; type?: string; placeholder?: string;
}) {
  return (
    <label className="block min-w-0">
      <span className={`mb-0.5 block text-muted ${compact ? "text-[10.5px]" : "text-[11.5px]"}`}>{label}</span>
      <input value={value} type={type} placeholder={placeholder} onChange={(e) => onChange(e.target.value)} dir={dir ?? "auto"}
        className={`w-full rounded-lg border border-line bg-paper outline-none focus:border-ink ${compact ? "px-2.5 py-1.5 text-[12.5px]" : "px-3 py-2 text-[13px]"}`} />
    </label>
  );
}

function ItemRow({ item: it, index, busy, categories, patch, upload, remove }: {
  item: NaItem;
  index: number;
  busy: boolean;
  categories: NewArrivalsFile["categories"];
  patch: (id: string, fn: (i: NaItem) => NaItem) => void;
  upload: (i: NaItem, c: NaColor, kind: CutoutKind, f: File) => void;
  remove: () => void;
}) {
  const drag = useDragControls();
  const setColor = (cid: string, fn: (c: NaColor) => NaColor) => patch(it.id, (i) => ({ ...i, colors: i.colors.map((c) => (c.id === cid ? fn(c) : c)) }));
  const toggleSize = (z: NaSize) =>
    patch(it.id, (i) => ({ ...i, sizes: NA_SIZES.filter((x) => (x === z ? !i.sizes.includes(z) : i.sizes.includes(x))) }));

  return (
    <Reorder.Item value={it} dragListener={false} dragControls={drag} className={`grid gap-3 rounded-xl border border-line bg-card p-2.5 md:grid-cols-[28px_minmax(0,1fr)] ${it.visible ? "" : "opacity-70"}`}>
      <span onPointerDown={(e) => drag.start(e)} className="nums grid h-8 cursor-grab touch-none place-items-center self-start rounded-md text-center text-[11px] text-muted hover:bg-ink/6 active:cursor-grabbing" title="اسحب لإعادة الترتيب">
        {String(index + 1).padStart(2, "0")}
      </span>
      <div className="grid min-w-0 gap-2.5">
        <div className="grid gap-2 sm:grid-cols-[minmax(0,1fr)_150px_120px]">
          <Field compact label="اسم القطعة" value={it.name} onChange={(v) => patch(it.id, (x) => ({ ...x, name: v }))} />
          <label className="block">
            <span className="mb-0.5 block text-[10.5px] text-muted">الفئة</span>
            <select value={it.categoryId} onChange={(e) => patch(it.id, (x) => ({ ...x, categoryId: e.target.value }))}
              className="w-full rounded-lg border border-line bg-paper px-2 py-1.5 text-[12.5px]">
              {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
            </select>
          </label>
          <Field compact dir="ltr" type="number" label="السعر التجريبي (ر.ي)" value={String(it.price)} onChange={(v) => patch(it.id, (x) => ({ ...x, price: Math.max(0, Math.round(Number(v) || 0)) }))} />
        </div>
        <Field compact label="وصف قصير (يظهر تحت الاسم)" value={it.description ?? ""} onChange={(v) => patch(it.id, (x) => ({ ...x, description: v }))} />
        <div className="flex flex-wrap items-center gap-x-4 gap-y-2">
          <span className="text-[10.5px] text-muted">المقاسات</span>
          {NA_SIZES.map((z) => (
            <label key={z} className="flex items-center gap-1 text-[12px]" dir="ltr">
              <input type="checkbox" checked={it.sizes.includes(z)} onChange={() => toggleSize(z)} className="size-3.5 accent-[var(--color-brand)]" />
              {z}
            </label>
          ))}
          <label className="ms-auto flex items-center gap-1.5 text-[12px]">
            <input type="checkbox" checked={it.visible} onChange={(e) => patch(it.id, (x) => ({ ...x, visible: e.target.checked }))} className="size-4 accent-[var(--color-brand)]" />
            ظاهرة
          </label>
          <button type="button" onClick={remove} className="flex items-center gap-1 text-[11px] text-muted hover:text-brand">
            <CloseIcon width={12} height={12} /> حذف
          </button>
        </div>

        <div className="space-y-2">
          {it.colors.map((c) => (
            <div key={c.id} className="grid gap-2 rounded-lg border border-line p-2 sm:grid-cols-[minmax(0,1fr)_auto]">
              <div className="grid content-start gap-2">
                <div className="grid grid-cols-[minmax(0,1fr)_auto] gap-2">
                  <Field compact label="اسم اللون" value={c.name} onChange={(v) => setColor(c.id, (x) => ({ ...x, name: v }))} />
                  <label className="block">
                    <span className="mb-0.5 block text-[10.5px] text-muted">النقطة</span>
                    <input type="color" value={c.swatch} onChange={(e) => setColor(c.id, (x) => ({ ...x, swatch: e.target.value }))} className="h-[31px] w-12 rounded-lg border border-line bg-paper" aria-label={`لون نقطة ${c.name}`} />
                  </label>
                </div>
                <div className="flex flex-wrap gap-2">
                  {(["garment", "model"] as const).map((k) => (
                    <label key={k} className={`cursor-pointer rounded-full border border-ink/20 px-3 py-1 text-[11.5px] hover:border-ink ${busy ? "pointer-events-none opacity-50" : ""}`}>
                      {k === "garment" ? "صورة القطعة" : "صورة المودل"}
                      <input type="file" accept="image/png,image/webp" className="sr-only"
                        onChange={(e) => { const f = e.target.files?.[0]; e.target.value = ""; if (f) upload(it, c, k, f); }} />
                    </label>
                  ))}
                  {it.colors.length > 1 && (
                    <button type="button" className="text-[11px] text-muted hover:text-brand" onClick={() => patch(it.id, (x) => ({ ...x, colors: x.colors.filter((y) => y.id !== c.id) }))}>
                      حذف اللون
                    </button>
                  )}
                </div>
                {(!c.garment || !c.model) && <p className="text-[11px] text-brand">ارفع الصورتين لهذا اللون — لا يُحفظ لون بلا صور متطابقة.</p>}
              </div>
              <div className="flex gap-2">
                {(["garment", "model"] as const).map((k) => (
                  <figure key={k} className="space-y-1 text-center">
                    <div className="relative overflow-hidden rounded-md border border-line" style={{ width: k === "garment" ? 72 : 54, height: k === "garment" ? 72 : 114, background: checker }}>
                      {/* eslint-disable-next-line @next/next/no-img-element -- admin preview of a cut-out */}
                      {c[k] && <img src={c[k]} alt="" className="absolute inset-0 size-full object-contain" />}
                    </div>
                    <figcaption className="text-[10px] text-muted">{k === "garment" ? "القطعة" : "المودل"}</figcaption>
                  </figure>
                ))}
              </div>
            </div>
          ))}
          {it.colors.length < 6 && (
            <button type="button" className="text-[11.5px] text-muted underline hover:text-ink"
              onClick={() => patch(it.id, (x) => ({ ...x, colors: [...x.colors, { id: uid("c"), name: "", swatch: "#cccccc", garment: "", model: "" }] }))}>
              + لون مصوّر آخر
            </button>
          )}
        </div>
      </div>
    </Reorder.Item>
  );
}
