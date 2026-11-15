"use client";

import { AnimatePresence, Reorder, motion, useDragControls } from "framer-motion";
import { useEffect, useMemo, useState } from "react";
import ShoesSection from "@/components/sections/ShoesSection";
import BrandLogo from "@/components/store/BrandLogo";
import ThemeToggle from "@/components/store/ThemeToggle";
import { CloseIcon, PlusIcon } from "@/components/store/icons";
import { SHOE_SIZES, type Shoe, type ShoeColor, type ShoeSize, type ShoesFile } from "@/content/shoes-types";
import AdminNav from "./AdminNav";
import { processShoe } from "./process-shoe";

/**
 * Local editor for «أحذية تكمل إطلالتك». Edits a draft of shoes.json; «حفظ»
 * writes the file the home page reads (the server re-validates everything and
 * checks every image exists). Each colour is a photographed side profile —
 * uploads are framed in the browser exactly like the originals.
 */
const uid = (p: string) => `${p}-${Date.now().toString(36)}${Math.random().toString(36).slice(2, 5)}`;
type Msg = { tone: "ok" | "error" | "info"; text: string } | null;
const checker = "repeating-conic-gradient(rgba(0,0,0,.08) 0 25%, transparent 0 50%) 0 0 / 14px 14px";
const MAX_SHOES = 10;

export default function ShoesAdmin({ initial }: { initial: ShoesFile }) {
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
  const setShoes = (shoes: Shoe[]) => setDraft((d) => ({ ...d, shoes }));
  const patch = (id: string, fn: (s: Shoe) => Shoe) => setDraft((d) => ({ ...d, shoes: d.shoes.map((s) => (s.id === id ? fn(s) : s)) }));
  const setting = (k: "title" | "subtitle", v: string) => setDraft((d) => ({ ...d, settings: { ...d.settings, [k]: v } }));

  const upload = async (shoe: Shoe, color: ShoeColor, file: File) => {
    setBusy(true);
    try {
      const p = await processShoe(file);
      const form = new FormData();
      form.set("shoeId", shoe.id);
      form.set("colorId", color.id);
      form.set("image", p.blob, `${shoe.id}.webp`);
      const res = await fetch("/api/admin/shoes/image", { method: "POST", body: form });
      const body = await res.json();
      if (!res.ok) throw new Error(body.error ?? "تعذّر رفع الصورة");
      patch(shoe.id, (s) => ({ ...s, colors: s.colors.map((c) => (c.id === color.id ? { ...c, image: body.image } : c)) }));
      flash({ tone: "ok", text: `رُفعت صورة الحذاء ووُضعت على الرف (${p.width}×${p.height}). راجع المعاينة ثم اضغط «حفظ».` });
    } catch (e) {
      flash({ tone: "error", text: (e as Error).message });
    } finally {
      setBusy(false);
    }
  };

  const save = async () => {
    setBusy(true);
    try {
      const res = await fetch("/api/admin/shoes", { method: "PUT", headers: { "content-type": "application/json" }, body: JSON.stringify(draft) });
      const body = await res.json();
      if (!res.ok) throw new Error(body.error ?? "تعذّر الحفظ");
      setSaved(body);
      setDraft(body);
      flash({ tone: "ok", text: "تم الحفظ في src/content/shoes.json — الصفحة الرئيسية تعرض الأحذية المحدّثة الآن." });
    } catch (e) {
      flash({ tone: "error", text: (e as Error).message });
    } finally {
      setBusy(false);
    }
  };

  const addShoe = () => {
    if (draft.shoes.length >= MAX_SHOES) {
      flash({ tone: "error", text: `الرفوف تتسع لـ ${MAX_SHOES} أحذية — احذف حذاءً أو أخفه أولًا.` });
      return;
    }
    setShoes([
      ...draft.shoes,
      {
        id: uid("shoe"),
        name: "حذاء جديد",
        description: "",
        price: 0,
        sizes: ["40", "41", "42", "43", "44"],
        colors: [{ id: "c1", name: "", swatch: "#cccccc", image: "" }],
        alt: "",
        visible: false,
      },
    ]);
    flash({ tone: "info", text: "أُضيف حذاء مخفي في آخر القائمة — ارفع صورته الجانبية الشفافة وأكمل البيانات ثم احفظ." });
  };

  const s = draft.settings;
  const visibleCount = draft.shoes.filter((x) => x.visible).length;
  const previewable = { ...draft, shoes: draft.shoes.filter((x) => x.colors.every((c) => c.image)) };
  return (
    <div className="min-h-svh bg-paper text-ink">
      <header className="sticky top-0 z-[200] flex flex-wrap items-center gap-3 border-b border-line bg-card/95 px-4 py-3 backdrop-blur sm:px-6">
        <BrandLogo height={30} />
        <div className="me-auto">
          <h1 className="font-display text-lg font-semibold">إدارة «الأحذية»</h1>
          <p className="text-[11px] text-muted">أداة محلية للتطوير فقط — تحفظ في src/content/shoes.json</p>
        </div>
        <AdminNav current="shoes" />
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
            <h2 className="text-[13px] font-semibold">الأحذية ({draft.shoes.length} من {MAX_SHOES}) — اسحب من الرقم لإعادة الترتيب</h2>
            <button type="button" onClick={addShoe} className="flex items-center gap-1 rounded-full bg-ink px-3 py-1.5 text-[12px] text-on-ink">
              <PlusIcon width={14} height={14} /> حذاء
            </button>
          </div>
          {visibleCount === 0 && <p className="mb-2 rounded-lg bg-amber-500/12 px-3 py-2 text-[11.5px] text-amber-800 dark:text-amber-300">لا توجد أحذية ظاهرة — سيُخفى القسم من الصفحة الرئيسية.</p>}
          <Reorder.Group axis="y" values={draft.shoes} onReorder={setShoes} className="space-y-2">
            {draft.shoes.map((sh, i) => (
              <ShoeRow key={sh.id} shoe={sh} index={i} busy={busy} patch={patch} upload={upload}
                remove={() => confirm(`حذف «${sh.name}» من الرفوف؟ (الصورة تبقى في المجلد)`) && setShoes(draft.shoes.filter((x) => x.id !== sh.id))} />
            ))}
          </Reorder.Group>
        </section>

        <aside className="h-fit space-y-4 rounded-2xl border border-line bg-card p-4">
          <h2 className="text-[13px] font-semibold">نصوص القسم</h2>
          <Field label="العنوان" value={s.title} onChange={(v) => setting("title", v)} />
          <Field label="النص المساعد" value={s.subtitle} onChange={(v) => setting("subtitle", v)} />
          <p className="text-[11px] leading-relaxed text-muted">
            الترتيب هنا هو ترتيب الرفوف: الخمسة الأولى في الصف العلوي من اليمين، ثم الصف السفلي (وعلى الهاتف حذاءان في كل صف).
          </p>
          <p className="text-[11px] leading-relaxed text-muted">
            الصورة: حذاء واحد بصورة جانبية ومقدمته إلى اليسار، بخلفية شفافة حقيقية (PNG أو WebP). تُقصّ وتُوضع على الرف تلقائيًا بنفس حجم وخط قاعدة بقية الأحذية، دون تمديد أو تغيير ألوان.
            يظهر اختيار اللون للزائر فقط إذا كان للحذاء أكثر من لون مصوّر.
          </p>
          <p className="text-[11px] leading-relaxed text-muted">الأسعار والمقاسات تجريبية بالريال اليمني، والصور تمثيلية — لا يوجد مخزون أو طلب شراء حقيقي.</p>
        </aside>
      </div>

      <section className="pb-10">
        <p className="mb-2 px-4 text-[11px] text-muted sm:px-6">معاينة مباشرة للمسودة {dirty && <span className="text-brand">(غير محفوظة)</span>}</p>
        <div className="border-y border-line">
          {/* keyed on the visible order so the shelves re-measure after reordering */}
          <ShoesSection key={previewable.shoes.filter((x) => x.visible).map((x) => x.id).join()} data={previewable} />
        </div>
      </section>
    </div>
  );
}

function Field({ label, value, onChange, dir, compact, type }: {
  label: string; value: string; onChange: (v: string) => void; dir?: "ltr" | "auto"; compact?: boolean; type?: string;
}) {
  return (
    <label className="block min-w-0">
      <span className={`mb-0.5 block text-muted ${compact ? "text-[10.5px]" : "text-[11.5px]"}`}>{label}</span>
      <input value={value} type={type} onChange={(e) => onChange(e.target.value)} dir={dir ?? "auto"}
        className={`w-full rounded-lg border border-line bg-paper outline-none focus:border-ink ${compact ? "px-2.5 py-1.5 text-[12.5px]" : "px-3 py-2 text-[13px]"}`} />
    </label>
  );
}

function ShoeRow({ shoe: sh, index, busy, patch, upload, remove }: {
  shoe: Shoe;
  index: number;
  busy: boolean;
  patch: (id: string, fn: (s: Shoe) => Shoe) => void;
  upload: (s: Shoe, c: ShoeColor, f: File) => void;
  remove: () => void;
}) {
  const drag = useDragControls();
  const setColor = (cid: string, fn: (c: ShoeColor) => ShoeColor) => patch(sh.id, (s) => ({ ...s, colors: s.colors.map((c) => (c.id === cid ? fn(c) : c)) }));
  const toggleSize = (z: ShoeSize) =>
    patch(sh.id, (s) => ({ ...s, sizes: SHOE_SIZES.filter((x) => (x === z ? !s.sizes.includes(z) : s.sizes.includes(x))) }));

  return (
    <Reorder.Item value={sh} dragListener={false} dragControls={drag} className={`grid gap-3 rounded-xl border border-line bg-card p-2.5 md:grid-cols-[28px_minmax(0,1fr)] ${sh.visible ? "" : "opacity-70"}`}>
      <span onPointerDown={(e) => drag.start(e)} className="nums grid h-8 cursor-grab touch-none place-items-center self-start rounded-md text-center text-[11px] text-muted hover:bg-ink/6 active:cursor-grabbing" title="اسحب لإعادة الترتيب">
        {String(index + 1).padStart(2, "0")}
      </span>
      <div className="grid min-w-0 gap-2.5">
        <div className="grid gap-2 sm:grid-cols-[minmax(0,1fr)_140px]">
          <Field compact label="اسم الحذاء" value={sh.name} onChange={(v) => patch(sh.id, (x) => ({ ...x, name: v }))} />
          <Field compact dir="ltr" type="number" label="السعر التجريبي (ر.ي)" value={String(sh.price)} onChange={(v) => patch(sh.id, (x) => ({ ...x, price: Math.max(0, Math.round(Number(v) || 0)) }))} />
        </div>
        <Field compact label="وصف مختصر (لوحة التفاصيل)" value={sh.description} onChange={(v) => patch(sh.id, (x) => ({ ...x, description: v }))} />
        <Field compact label="وصف الصورة لقارئ الشاشة" value={sh.alt} onChange={(v) => patch(sh.id, (x) => ({ ...x, alt: v }))} />
        <div className="flex flex-wrap items-center gap-x-3.5 gap-y-2">
          <span className="text-[10.5px] text-muted">المقاسات (أوروبي)</span>
          {SHOE_SIZES.map((z) => (
            <label key={z} className="flex items-center gap-1 text-[12px]" dir="ltr">
              <input type="checkbox" checked={sh.sizes.includes(z)} onChange={() => toggleSize(z)} className="size-3.5 accent-[var(--color-brand)]" />
              {z}
            </label>
          ))}
          <label className="ms-auto flex items-center gap-1.5 text-[12px]">
            <input type="checkbox" checked={sh.visible} onChange={(e) => patch(sh.id, (x) => ({ ...x, visible: e.target.checked }))} className="size-4 accent-[var(--color-brand)]" />
            ظاهر
          </label>
          <button type="button" onClick={remove} className="flex items-center gap-1 text-[11px] text-muted hover:text-brand">
            <CloseIcon width={12} height={12} /> حذف
          </button>
        </div>

        <div className="space-y-2">
          {sh.colors.map((c) => (
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
                  <label className={`cursor-pointer rounded-full border border-ink/20 px-3 py-1 text-[11.5px] hover:border-ink ${busy ? "pointer-events-none opacity-50" : ""}`}>
                    {c.image ? "تغيير الصورة" : "رفع صورة الحذاء"}
                    <input type="file" accept="image/png,image/webp" className="sr-only"
                      onChange={(e) => { const f = e.target.files?.[0]; e.target.value = ""; if (f) upload(sh, c, f); }} />
                  </label>
                  {sh.colors.length > 1 && (
                    <button type="button" className="text-[11px] text-muted hover:text-brand" onClick={() => patch(sh.id, (x) => ({ ...x, colors: x.colors.filter((y) => y.id !== c.id) }))}>
                      حذف اللون
                    </button>
                  )}
                </div>
                {!c.image && <p className="text-[11px] text-brand">ارفع صورة لهذا اللون — لا يُحفظ لون بلا صورة.</p>}
              </div>
              <div className="relative overflow-hidden rounded-md border border-line" style={{ width: 126, height: 72, background: checker }}>
                {/* eslint-disable-next-line @next/next/no-img-element -- admin preview of a cut-out */}
                {c.image && <img src={c.image} alt="" className="absolute inset-0 size-full object-contain" />}
              </div>
            </div>
          ))}
          {sh.colors.length < 6 && (
            <button type="button" className="text-[11.5px] text-muted underline hover:text-ink"
              onClick={() => patch(sh.id, (x) => ({ ...x, colors: [...x.colors, { id: uid("c"), name: "", swatch: "#cccccc", image: "" }] }))}>
              + لون مصوّر آخر
            </button>
          )}
        </div>
      </div>
    </Reorder.Item>
  );
}
