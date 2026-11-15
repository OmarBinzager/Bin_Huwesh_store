"use client";

import { AnimatePresence, Reorder, motion, useDragControls } from "framer-motion";
import { useEffect, useMemo, useState } from "react";
import ShopByCategorySection from "@/components/sections/ShopByCategorySection";
import BrandLogo from "@/components/store/BrandLogo";
import ThemeToggle from "@/components/store/ThemeToggle";
import { CloseIcon, PlusIcon } from "@/components/store/icons";
import { STORE_CATEGORIES, categoryHref, getStoreCategory } from "@/content/categories";
import type { CategoryCard, ShopCategoriesFile } from "@/content/category-card-types";
import AdminNav from "./AdminNav";
import { processCategoryPhoto } from "./process-photo";

/**
 * Local editor for the «تسوّق حسب القسم» cards. Edits a draft of
 * shop-categories.json; «حفظ» writes the file the home page reads. The
 * preview at the top is the real section fed with the draft.
 */
const uid = () => `card-${Date.now().toString(36)}${Math.random().toString(36).slice(2, 5)}`;
type Msg = { tone: "ok" | "error" | "info"; text: string } | null;


export default function CategoriesAdmin({ initial }: { initial: ShopCategoriesFile }) {
  const [saved, setSaved] = useState<ShopCategoriesFile>(initial);
  const [draft, setDraft] = useState<ShopCategoriesFile>(initial);
  const [busy, setBusy] = useState(false);
  const [msg, setMsg] = useState<Msg>(null);
  const [addCat, setAddCat] = useState(STORE_CATEGORIES[0].id);
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
  const setCards = (cards: CategoryCard[]) => setDraft((d) => ({ ...d, cards }));
  const patch = (id: string, fn: (c: CategoryCard) => CategoryCard) =>
    setDraft((d) => ({ ...d, cards: d.cards.map((c) => (c.id === id ? fn(c) : c)) }));
  const setting = <K extends keyof ShopCategoriesFile["settings"]>(k: K, v: string) =>
    setDraft((d) => ({ ...d, settings: { ...d.settings, [k]: v } }));

  const visibleCount = draft.cards.filter((c) => c.visible).length;
  const countNote =
    visibleCount === 0
      ? "لا توجد بطاقات ظاهرة — سيُخفى القسم من الصفحة الرئيسية."
      : visibleCount === 1
        ? "بطاقة واحدة ظاهرة فقط — يتوقف التنقل في العرض حتى تُظهر بطاقة أخرى."
        : null;

  const upload = async (card: CategoryCard, file: File) => {
    setBusy(true);
    try {
      const p = await processCategoryPhoto(file);
      const form = new FormData();
      form.set("cardId", card.id);
      form.set("image", p.blob, `${card.id}.webp`);
      const res = await fetch("/api/admin/categories/image", { method: "POST", body: form });
      const body = await res.json();
      if (!res.ok) throw new Error(body.error ?? "تعذّر رفع الصورة");
      patch(card.id, (c) => ({ ...c, image: body.image, focus: { x: 50, y: 40 } }));
      flash({ tone: "ok", text: `رُفعت صورة «${card.name}» (${p.width}×${p.height}). حدّد نقطة التركيز ثم اضغط «حفظ».` });
    } catch (e) {
      flash({ tone: "error", text: (e as Error).message });
    } finally {
      setBusy(false);
    }
  };

  const save = async () => {
    setBusy(true);
    try {
      const res = await fetch("/api/admin/categories", { method: "PUT", headers: { "content-type": "application/json" }, body: JSON.stringify(draft) });
      const body = await res.json();
      if (!res.ok) throw new Error(body.error ?? "تعذّر الحفظ");
      setSaved(body);
      setDraft(body);
      flash({ tone: "ok", text: "تم الحفظ في src/content/shop-categories.json — الصفحة الرئيسية تعرض البطاقات المحدّثة الآن." });
    } catch (e) {
      flash({ tone: "error", text: (e as Error).message });
    } finally {
      setBusy(false);
    }
  };

  const addCard = () => {
    const cat = getStoreCategory(addCat)!;
    const sample = draft.cards.find((c) => c.categoryId === cat.id) ?? draft.cards[0];
    setCards([
      ...draft.cards,
      {
        id: uid(),
        categoryId: cat.id,
        name: cat.name,
        caption: cat.blurb,
        href: categoryHref(cat.id),
        image: sample?.image ?? "/images/categories/men-01.webp",
        alt: sample?.alt ?? cat.name,
        focus: { x: 50, y: 40 },
        size: "regular",
        visible: false,
      },
    ]);
    flash({ tone: "info", text: `أُضيفت بطاقة «${cat.name}» مخفية في آخر القائمة — غيّر صورتها ثم أظهرها.` });
  };

  const s = draft.settings;
  return (
    <div className="min-h-svh bg-paper text-ink">
      <header className="sticky top-0 z-40 flex flex-wrap items-center gap-3 border-b border-line bg-card/95 px-4 py-3 backdrop-blur sm:px-6">
        <BrandLogo height={30} />
        <div className="me-auto">
          <h1 className="font-display text-lg font-semibold">إدارة «تسوّق حسب القسم»</h1>
          <p className="text-[11px] text-muted">أداة محلية للتطوير فقط — تحفظ في src/content/shop-categories.json</p>
        </div>
        <AdminNav current="categories" />
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

      <div className="grid gap-5 p-4 sm:p-6 xl:grid-cols-[minmax(0,1fr)_320px]">
        <section className="rounded-2xl border border-line bg-card p-3">
          <div className="mb-2 flex flex-wrap items-center justify-between gap-2 px-1">
            <h2 className="text-[13px] font-semibold">البطاقات ({draft.cards.length}) — اسحب من الرقم لإعادة الترتيب</h2>
            <div className="flex items-center gap-1.5">
              <select value={addCat} onChange={(e) => setAddCat(e.target.value)} aria-label="قسم البطاقة الجديدة"
                className="rounded-full border border-line bg-paper px-2.5 py-1.5 text-[12px]">
                {STORE_CATEGORIES.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
              </select>
              <button type="button" onClick={addCard} className="flex items-center gap-1 rounded-full bg-ink px-3 py-1.5 text-[12px] text-on-ink">
                <PlusIcon width={14} height={14} /> بطاقة
              </button>
            </div>
          </div>
          {countNote && <p className="mb-2 rounded-lg bg-amber-500/12 px-3 py-2 text-[11.5px] text-amber-800 dark:text-amber-300">{countNote}</p>}

          <Reorder.Group axis="y" values={draft.cards} onReorder={setCards} className="space-y-2">
            {draft.cards.map((c, i) => (
              <CardRow key={c.id} card={c} index={i} busy={busy} patch={patch} upload={upload}
                remove={() => confirm(`حذف بطاقة «${c.name}» من القسم؟ (الصورة تبقى في المجلد)`) && setCards(draft.cards.filter((x) => x.id !== c.id))} />
            ))}
          </Reorder.Group>
        </section>

        <aside className="h-fit space-y-4 rounded-2xl border border-line bg-card p-4">
          <h2 className="text-[13px] font-semibold">نصوص القسم</h2>
          <Field label="العنوان" value={s.title} onChange={(v) => setting("title", v)} />
          <Field label="النص المساعد" value={s.subtitle} onChange={(v) => setting("subtitle", v)} />
          <Field label="نص رابط القسم تحت الاسم" value={s.cta} onChange={(v) => setting("cta", v)} />
          <p className="text-[11px] leading-relaxed text-muted">
            أقسام البطاقات تأتي من قائمة أقسام المتجر نفسها المستخدمة في رأس الصفحة، فلا يمكن إضافة قسم غير موجود. الصور تعبيرية وغير مرتبطة بمنتجات حقيقية حتى يُربط الكتالوج.
          </p>
          <p className="text-[11px] leading-relaxed text-muted">
            العرض: كاروسيل منحنٍ بالصور فقط، وبطاقاته بنسبة 4:5. الترتيب هنا هو ترتيب الكاروسيل، والاسم والوصف والرابط تظهر تحته عند اختيار البطاقة.
          </p>
        </aside>
      </div>

      <section className="pb-10">
        <p className="mb-2 px-4 text-[11px] text-muted sm:px-6">معاينة مباشرة للمسودة {dirty && <span className="text-brand">(غير محفوظة)</span>}</p>
        <div className="border-y border-line">
          <ShopByCategorySection data={draft} />
        </div>
      </section>
    </div>
  );
}

function Field({ label, value, onChange, dir, compact, placeholder }: { label: string; value: string; onChange: (v: string) => void; dir?: "ltr" | "auto"; compact?: boolean; placeholder?: string }) {
  return (
    <label className="block min-w-0">
      <span className={`mb-0.5 block text-muted ${compact ? "text-[10.5px]" : "text-[11.5px]"}`}>{label}</span>
      <input value={value} onChange={(e) => onChange(e.target.value)} dir={dir ?? "auto"} placeholder={placeholder}
        className={`w-full rounded-lg border border-line bg-paper outline-none focus:border-ink ${compact ? "px-2.5 py-1.5 text-[12.5px]" : "px-3 py-2 text-[13px]"}`} />
    </label>
  );
}

function CardRow({ card: c, index, busy, patch, upload, remove }: {
  card: CategoryCard;
  index: number;
  busy: boolean;
  patch: (id: string, fn: (c: CategoryCard) => CategoryCard) => void;
  upload: (c: CategoryCard, f: File) => void;
  remove: () => void;
}) {
  const pos = `${c.focus.x}% ${c.focus.y}%`;
  const setFocus = (e: React.PointerEvent<HTMLElement>) => {
    const r = e.currentTarget.getBoundingClientRect();
    const x = Math.round(Math.min(100, Math.max(0, ((e.clientX - r.left) / r.width) * 100)));
    const y = Math.round(Math.min(100, Math.max(0, ((e.clientY - r.top) / r.height) * 100)));
    patch(c.id, (x0) => ({ ...x0, focus: { x, y } }));
  };
  const cat = getStoreCategory(c.categoryId);
  // Only the number handle starts a reorder drag — the photo is a focal-point picker.
  const drag = useDragControls();

  return (
    <Reorder.Item value={c} dragListener={false} dragControls={drag} className="grid gap-3 rounded-xl border border-line bg-card p-2.5 md:grid-cols-[28px_150px_minmax(0,1fr)]">
      <span onPointerDown={(e) => drag.start(e)} className="nums grid h-8 cursor-grab touch-none place-items-center self-start rounded-md text-center text-[11px] text-muted hover:bg-ink/6 active:cursor-grabbing" title="اسحب لإعادة الترتيب">
        {String(index + 1).padStart(2, "0")}
      </span>

      {/* focal-point picker: click/drag on the full photo */}
      <div className="space-y-1.5">
        <div
          role="slider"
          aria-label={`نقطة التركيز لصورة ${c.name}`}
          aria-valuetext={`أفقي ${c.focus.x}٪، عمودي ${c.focus.y}٪`}
          aria-valuenow={c.focus.x}
          tabIndex={0}
          onPointerDown={(e) => {
            try { e.currentTarget.setPointerCapture(e.pointerId); } catch { /* pointer already gone */ }
            setFocus(e);
          }}
          onPointerMove={(e) => e.buttons === 1 && setFocus(e)}
          onKeyDown={(e) => {
            const d = { ArrowLeft: [-2, 0], ArrowRight: [2, 0], ArrowUp: [0, -2], ArrowDown: [0, 2] }[e.key];
            if (!d) return;
            e.preventDefault();
            patch(c.id, (x0) => ({ ...x0, focus: { x: Math.min(100, Math.max(0, x0.focus.x + d[0])), y: Math.min(100, Math.max(0, x0.focus.y + d[1])) } }));
          }}
          className={`relative cursor-crosshair touch-none overflow-hidden rounded-lg bg-stage outline-none focus-visible:ring-2 focus-visible:ring-brand ${c.visible ? "" : "opacity-50"}`}
        >
          {/* eslint-disable-next-line @next/next/no-img-element -- admin preview of a freshly uploaded file */}
          <img src={c.image} alt="" draggable={false} className="block h-auto w-full select-none" />
          <span aria-hidden className="pointer-events-none absolute size-4 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white bg-brand shadow"
            style={{ left: `${c.focus.x}%`, top: `${c.focus.y}%` }} />
        </div>
        <p className="text-[10px] leading-snug text-muted">انقر على الصورة لتحديد ما يبقى ظاهرًا عند القص</p>
      </div>

      <div className="grid min-w-0 gap-2">
        <div className="grid gap-2 sm:grid-cols-2">
          <Field compact label="اسم القسم في العرض" value={c.name} onChange={(v) => patch(c.id, (x) => ({ ...x, name: v }))} />
          <label className="block">
            <span className="mb-0.5 block text-[10.5px] text-muted">القسم</span>
            <select value={c.categoryId}
              onChange={(e) => {
                const next = getStoreCategory(e.target.value)!;
                patch(c.id, (x) => ({ ...x, categoryId: next.id, href: x.href === categoryHref(x.categoryId) ? categoryHref(next.id) : x.href }));
              }}
              className="w-full rounded-lg border border-line bg-paper px-2 py-1.5 text-[12.5px]">
              {STORE_CATEGORIES.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
            </select>
          </label>
          <div className="sm:col-span-2">
            <Field compact dir="ltr" label="الرابط (داخل الموقع)" value={c.href} onChange={(v) => patch(c.id, (x) => ({ ...x, href: v.trim() }))} />
            {cat && c.href !== categoryHref(cat.id) && (
              <button type="button" onClick={() => patch(c.id, (x) => ({ ...x, href: categoryHref(cat.id) }))} className="mt-1 text-[10.5px] text-muted underline hover:text-ink">
                إرجاع إلى رابط القسم الافتراضي
              </button>
            )}
          </div>
          <div className="sm:col-span-2">
            <Field compact label="وصف القسم (يظهر تحت الاسم)" value={c.caption ?? ""} placeholder={cat?.blurb}
              onChange={(v) => patch(c.id, (x) => ({ ...x, caption: v }))} />
          </div>
          <div className="sm:col-span-2">
            <Field compact label="وصف الصورة (لقارئ الشاشة)" value={c.alt} onChange={(v) => patch(c.id, (x) => ({ ...x, alt: v }))} />
          </div>
        </div>

        <div className="flex flex-wrap items-end gap-x-4 gap-y-2">
          <label className="flex items-center gap-1.5 pb-1.5 text-[12px]">
            <input type="checkbox" checked={c.visible} onChange={(e) => patch(c.id, (x) => ({ ...x, visible: e.target.checked }))} className="size-4 accent-[var(--color-brand)]" />
            ظاهرة
          </label>
          <label className={`mb-0.5 cursor-pointer rounded-full border border-ink/20 px-3 py-1 text-center text-[11.5px] hover:border-ink ${busy ? "pointer-events-none opacity-50" : ""}`}>
            تغيير الصورة
            <input type="file" accept="image/png,image/jpeg,image/webp,image/avif" className="sr-only"
              onChange={(e) => { const f = e.target.files?.[0]; e.target.value = ""; if (f) upload(c, f); }} />
          </label>
          <button type="button" onClick={remove} className="mb-1 flex items-center gap-1 text-[11px] text-muted hover:text-brand">
            <CloseIcon width={12} height={12} /> حذف
          </button>
        </div>

        {/* how the crop lands on the carousel card (4:5, with the lens' 1.08 zoom) */}
        <figure className="space-y-1">
          <div className="relative overflow-hidden rounded-md bg-stage" style={{ height: 80, width: 64 }}>
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img src={c.image} alt="" className="absolute inset-0 size-full object-cover"
              style={{ objectPosition: pos, transform: "scale(1.08)", transformOrigin: pos }} />
          </div>
          <figcaption className="text-[10px] text-muted">قص البطاقة</figcaption>
        </figure>
      </div>
    </Reorder.Item>
  );
}
