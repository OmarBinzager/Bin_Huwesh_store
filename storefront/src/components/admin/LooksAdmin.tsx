"use client";

/* eslint-disable @next/next/no-img-element -- the editor shows freshly uploaded files directly, unoptimised on purpose */

import { AnimatePresence, Reorder, motion } from "framer-motion";
import { useEffect, useMemo, useRef, useState } from "react";
import type { Look, LookItem } from "@/content/types";
import { getPublishedLooks } from "@/content/looks";
import { formatYER } from "@/lib/catalog";
import LooksHero from "@/components/hero/LooksHero";
import BrandLogo from "@/components/store/BrandLogo";
import ThemeToggle from "@/components/store/ThemeToggle";
import AdminNav from "./AdminNav";
import { CloseIcon, PlusIcon } from "@/components/store/icons";
import { processLookImage } from "./process-image";

/**
 * Local looks editor. Holds a draft copy of looks.json; nothing reaches the
 * site until "حفظ" writes the file through /api/admin/looks. IDs are created
 * once (look-xxxx / item-xxxx) and never derived from titles, so renaming or
 * reordering never breaks links, keys or animation state.
 */

const uid = (p: string) => `${p}-${Date.now().toString(36)}${Math.random().toString(36).slice(2, 5)}`;
const ADULT = ["S", "M", "L", "XL", "2XL"];

type Msg = { tone: "ok" | "error" | "info"; text: string } | null;

export default function LooksAdmin({ initial }: { initial: Look[] }) {
  const [saved, setSaved] = useState<Look[]>(initial);
  const [draft, setDraft] = useState<Look[]>(initial);
  const [selectedId, setSelectedId] = useState<string | null>(initial[0]?.id ?? null);
  const [selectedItem, setSelectedItem] = useState<string | null>(null);
  const [preview, setPreview] = useState(false);
  const [busy, setBusy] = useState(false);
  const [msg, setMsg] = useState<Msg>(null);

  const dirty = useMemo(() => JSON.stringify(draft) !== JSON.stringify(saved), [draft, saved]);
  const look = draft.find((l) => l.id === selectedId) ?? null;

  useEffect(() => {
    if (!dirty) return;
    const warn = (e: BeforeUnloadEvent) => e.preventDefault();
    window.addEventListener("beforeunload", warn);
    return () => window.removeEventListener("beforeunload", warn);
  }, [dirty]);

  const flash = (m: Msg) => {
    setMsg(m);
    if (m?.tone !== "error") setTimeout(() => setMsg((cur) => (cur === m ? null : cur)), 3200);
  };

  const patchLook = (id: string, fn: (l: Look) => Look) => setDraft((d) => d.map((l) => (l.id === id ? fn(l) : l)));
  const patchItem = (lookId: string, itemId: string, fn: (it: LookItem) => LookItem) =>
    patchLook(lookId, (l) => ({ ...l, items: l.items.map((it) => (it.id === itemId ? fn(it) : it)) }));

  const addLook = () => {
    const l: Look = {
      id: uid("look"),
      order: draft.length + 1,
      published: false,
      title: "إطلالة جديدة",
      subtitle: "",
      image: { src: "", width: 0, height: 0, alt: "", scale: 1, offsetX: 0, offsetY: 0, isPreview: true },
      items: [],
    };
    setDraft((d) => [...d, l]);
    setSelectedId(l.id);
    flash({ tone: "info", text: "أُضيفت إطلالة جديدة (غير منشورة). ارفع صورة المودل ثم انشرها." });
  };

  const removeLook = (id: string) => {
    const l = draft.find((x) => x.id === id);
    if (!l || !confirm(`حذف إطلالة «${l.title}»؟ يمكن التراجع قبل الحفظ.`)) return;
    setDraft((d) => d.filter((x) => x.id !== id));
    if (selectedId === id) setSelectedId(draft.find((x) => x.id !== id)?.id ?? null);
  };

  const save = async () => {
    setBusy(true);
    try {
      const res = await fetch("/api/admin/looks", {
        method: "PUT",
        headers: { "content-type": "application/json" },
        body: JSON.stringify({ version: 1, looks: draft }),
      });
      const body = await res.json();
      if (!res.ok) throw new Error(body.error ?? "تعذّر الحفظ");
      setSaved(body.looks);
      setDraft(body.looks);
      flash({ tone: "ok", text: "تم الحفظ في src/content/looks.json — الصفحة الرئيسية تعرض التعديلات الآن." });
    } catch (e) {
      flash({ tone: "error", text: (e as Error).message });
    } finally {
      setBusy(false);
    }
  };

  const uploadImage = async (file: File) => {
    if (!look) return;
    setBusy(true);
    try {
      const p = await processLookImage(file);
      if (!p.hasAlpha && !confirm(`الصورة لا تبدو بخلفية شفافة (${p.transparentPct}% شفاف). استخدامها رغم ذلك؟`)) return;
      const form = new FormData();
      form.set("lookId", look.id);
      form.set("file", p.blob, `${look.id}.webp`);
      const res = await fetch("/api/admin/looks/image", { method: "POST", body: form });
      const body = await res.json();
      if (!res.ok) throw new Error(body.error ?? "تعذّر رفع الصورة");
      patchLook(look.id, (l) => ({ ...l, image: { ...l.image, src: body.src, width: p.width, height: p.height } }));
      flash({ tone: "ok", text: `رُفعت الصورة (${p.width}×${p.height}، ${Math.round(p.blob.size / 1024)}KB). اضغط «حفظ» لاعتمادها.` });
    } catch (e) {
      flash({ tone: "error", text: (e as Error).message });
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="min-h-svh bg-paper text-ink">
      {/* ── top bar ── */}
      <header className="sticky top-0 z-40 flex flex-wrap items-center gap-3 border-b border-line bg-card/95 px-4 py-3 backdrop-blur sm:px-6">
        <BrandLogo height={30} />
        <div className="me-auto">
          <h1 className="font-display text-lg font-semibold">إدارة إطلالات الهيرو</h1>
          <p className="text-[11px] text-muted">أداة محلية للتطوير فقط — تحفظ في src/content/looks.json</p>
        </div>
        <AdminNav current="looks" />
        <span className={`rounded-full px-3 py-1 text-[11px] ${dirty ? "bg-brand/10 text-brand" : "bg-ink/6 text-muted"}`}>
          {dirty ? "تعديلات غير محفوظة" : "محفوظ"}
        </span>
        <ThemeToggle />
        <button type="button" onClick={() => setPreview(true)} className="rounded-full border border-ink/20 px-4 py-2 text-[13px] hover:border-ink">
          معاينة
        </button>
        <button
          type="button"
          disabled={!dirty || busy}
          onClick={() => {
            if (confirm("التراجع عن كل التعديلات غير المحفوظة؟")) setDraft(saved);
          }}
          className="rounded-full border border-ink/20 px-4 py-2 text-[13px] hover:border-ink disabled:opacity-40"
        >
          تراجع
        </button>
        <button
          type="button"
          disabled={!dirty || busy}
          onClick={save}
          className="rounded-full bg-brand px-5 py-2 text-[13px] font-semibold text-white hover:bg-brand-deep disabled:opacity-40"
        >
          {busy ? "جارٍ…" : "حفظ"}
        </button>
      </header>

      <AnimatePresence>
        {msg && (
          <motion.p
            initial={{ opacity: 0, y: -6 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0 }}
            role="status"
            className={`mx-4 mt-3 rounded-xl px-4 py-2.5 text-[13px] sm:mx-6 ${
              msg.tone === "error" ? "bg-brand/12 text-brand" : msg.tone === "ok" ? "bg-emerald-500/12 text-emerald-700 dark:text-emerald-400" : "bg-ink/6"
            }`}
            onClick={() => setMsg(null)}
          >
            {msg.text}
          </motion.p>
        )}
      </AnimatePresence>

      <div className="grid gap-5 p-4 sm:p-6 lg:grid-cols-[260px_minmax(0,1fr)_minmax(320px,400px)]">
        {/* ── looks list ── */}
        <aside className="rounded-2xl border border-line bg-card p-3">
          <div className="mb-2 flex items-center justify-between px-1">
            <h2 className="text-[13px] font-semibold">الإطلالات ({draft.length})</h2>
            <button type="button" onClick={addLook} className="flex items-center gap-1 rounded-full bg-ink px-3 py-1.5 text-[12px] text-on-ink">
              <PlusIcon width={14} height={14} /> إطلالة
            </button>
          </div>
          <p className="mb-2 px-1 text-[11px] text-muted">اسحب لإعادة الترتيب. الترتيب هنا هو ترتيب الحلقة والشبكة.</p>
          <Reorder.Group axis="y" values={draft} onReorder={setDraft} className="space-y-1.5">
            {draft.map((l, i) => (
              <Reorder.Item
                key={l.id}
                value={l}
                className={`flex cursor-grab items-center gap-2 rounded-xl border p-1.5 active:cursor-grabbing ${
                  l.id === selectedId ? "border-brand bg-brand/5" : "border-transparent hover:bg-ink/4"
                }`}
              >
                <button type="button" onClick={() => setSelectedId(l.id)} className="flex min-w-0 flex-1 items-center gap-2 text-start">
                  <span className="nums w-5 text-center text-[11px] text-muted">{String(i + 1).padStart(2, "0")}</span>
                  <span className="grid h-12 w-9 shrink-0 place-items-center overflow-hidden rounded-md bg-stage">
                    {l.image.src ? <img src={l.image.src} alt="" className="h-full w-full object-contain" draggable={false} /> : <span className="text-[9px] text-muted">بلا صورة</span>}
                  </span>
                  <span className="min-w-0">
                    <span className="block truncate text-[13px] font-medium">{l.title}</span>
                    <span className={`block text-[10px] ${l.published ? "text-emerald-600 dark:text-emerald-400" : "text-muted"}`}>
                      {l.published ? "منشورة" : "مخفية"}
                    </span>
                  </span>
                </button>
                <button
                  type="button"
                  onClick={() => removeLook(l.id)}
                  aria-label={`حذف ${l.title}`}
                  className="grid size-7 shrink-0 place-items-center rounded-full text-muted hover:bg-brand/10 hover:text-brand"
                >
                  <CloseIcon width={14} height={14} />
                </button>
              </Reorder.Item>
            ))}
          </Reorder.Group>
        </aside>

        {look ? (
          <>
            {/* ── image + hotspots ── */}
            <section className="rounded-2xl border border-line bg-card p-4">
              <div className="mb-3 flex flex-wrap items-center gap-2">
                <h2 className="me-auto text-[13px] font-semibold">الصورة وعلامات القطع</h2>
                <label className="cursor-pointer rounded-full bg-ink px-4 py-2 text-[12px] text-on-ink">
                  {look.image.src ? "استبدال صورة المودل" : "رفع صورة المودل"}
                  <input
                    type="file"
                    accept="image/png,image/webp"
                    className="sr-only"
                    onChange={(e) => {
                      const f = e.target.files?.[0];
                      e.target.value = "";
                      if (f) void uploadImage(f);
                    }}
                  />
                </label>
              </div>
              <HotspotCanvas
                look={look}
                selectedItem={selectedItem}
                onSelectItem={setSelectedItem}
                onMove={(itemId, x, y) => patchItem(look.id, itemId, (it) => ({ ...it, hotspot: { x, y } }))}
              />
              <div className="mt-4 grid gap-3 sm:grid-cols-3">
                <Slider label="الحجم في المشهد" value={look.image.scale} min={0.5} max={1.3} step={0.01} format={(v) => `${Math.round(v * 100)}%`}
                  onChange={(v) => patchLook(look.id, (l) => ({ ...l, image: { ...l.image, scale: v } }))} />
                <Slider label="إزاحة أفقية" value={look.image.offsetX} min={-20} max={20} step={0.5} format={(v) => `${v}%`}
                  onChange={(v) => patchLook(look.id, (l) => ({ ...l, image: { ...l.image, offsetX: v } }))} />
                <Slider label="إزاحة رأسية" value={look.image.offsetY} min={-20} max={20} step={0.5} format={(v) => `${v}%`}
                  onChange={(v) => patchLook(look.id, (l) => ({ ...l, image: { ...l.image, offsetY: v } }))} />
              </div>
              <p className="mt-3 text-[11px] text-muted">
                اسحب الأرقام لتحريك العلامات، أو اختر قطعة ثم اضغط على مكانها في الصورة. الخط المتقطع = ارتفاع المودل البالغ المرجعي.
              </p>
            </section>

            {/* ── fields ── */}
            <section className="space-y-4 rounded-2xl border border-line bg-card p-4">
              <div className="flex items-center justify-between">
                <h2 className="text-[13px] font-semibold">بيانات الإطلالة</h2>
                <label className="flex items-center gap-2 text-[12px]">
                  <input
                    type="checkbox"
                    checked={look.published}
                    onChange={(e) => {
                      if (e.target.checked && !look.image.src) return flash({ tone: "error", text: "ارفع صورة قبل النشر." });
                      patchLook(look.id, (l) => ({ ...l, published: e.target.checked }));
                    }}
                    className="size-4 accent-[var(--color-brand)]"
                  />
                  منشورة
                </label>
              </div>
              <p className="nums text-[11px] text-muted" dir="ltr">id: {look.id}</p>
              <Field label="الاسم" value={look.title} onChange={(v) => patchLook(look.id, (l) => ({ ...l, title: v }))} />
              <Field label="الوصف" value={look.subtitle} onChange={(v) => patchLook(look.id, (l) => ({ ...l, subtitle: v }))} />
              <Field label="وصف الصورة (لقارئ الشاشة)" value={look.image.alt} onChange={(v) => patchLook(look.id, (l) => ({ ...l, image: { ...l.image, alt: v } }))} />
              <Field
                label="خصم الإطلالة كاملة %"
                type="number"
                value={String(look.wholeLookDiscount ?? 0)}
                onChange={(v) => patchLook(look.id, (l) => ({ ...l, wholeLookDiscount: Number(v) || undefined }))}
              />

              <div className="flex items-center justify-between pt-2">
                <h3 className="text-[13px] font-semibold">القطع ({look.items.length})</h3>
                <button
                  type="button"
                  onClick={() => {
                    const it: LookItem = { id: uid("item"), label: "قطعة جديدة", category: "", hotspot: { x: 50, y: 50 }, productId: null, demoPrice: 0, sizes: ADULT };
                    patchLook(look.id, (l) => ({ ...l, items: [...l.items, it] }));
                    setSelectedItem(it.id);
                  }}
                  className="flex items-center gap-1 rounded-full border border-ink/20 px-3 py-1 text-[12px] hover:border-ink"
                >
                  <PlusIcon width={13} height={13} /> قطعة
                </button>
              </div>
              <ol className="space-y-2">
                {look.items.map((it, n) => (
                  <li
                    key={it.id}
                    onFocusCapture={() => setSelectedItem(it.id)}
                    className={`rounded-xl border p-3 ${selectedItem === it.id ? "border-brand" : "border-line"}`}
                  >
                    <div className="mb-2 flex items-center gap-2">
                      <button type="button" onClick={() => setSelectedItem(it.id)}
                        className={`nums grid size-6 place-items-center rounded-full text-[10px] font-semibold text-white ${selectedItem === it.id ? "bg-brand" : "bg-onyx"}`}>
                        {String(n + 1).padStart(2, "0")}
                      </button>
                      <span className="nums me-auto text-[11px] text-muted" dir="ltr">x {it.hotspot.x}% · y {it.hotspot.y}%</span>
                      <button
                        type="button"
                        onClick={() => patchLook(look.id, (l) => ({ ...l, items: l.items.filter((x) => x.id !== it.id) }))}
                        className="text-[11px] text-muted hover:text-brand"
                      >
                        حذف
                      </button>
                    </div>
                    <div className="grid grid-cols-2 gap-2">
                      <Field compact label="اسم القطعة" value={it.label} onChange={(v) => patchItem(look.id, it.id, (x) => ({ ...x, label: v }))} />
                      <Field compact label="الفئة" value={it.category} onChange={(v) => patchItem(look.id, it.id, (x) => ({ ...x, category: v }))} />
                      <Field compact label={`سعر تجريبي (${formatYER(it.demoPrice)})`} type="number" value={String(it.demoPrice)}
                        onChange={(v) => patchItem(look.id, it.id, (x) => ({ ...x, demoPrice: Number(v) || 0 }))} />
                      <Field compact label="رقم المنتج الحقيقي" placeholder="بعد المطابقة فقط" value={it.productId ?? ""}
                        onChange={(v) => patchItem(look.id, it.id, (x) => ({ ...x, productId: v.trim() ? v : null }))} />
                      <div className="col-span-2">
                        <Field compact label="المقاسات (افصل بفاصلة)" value={it.sizes.join("، ")}
                          onChange={(v) => patchItem(look.id, it.id, (x) => ({ ...x, sizes: v.split(/[,،]/).map((s) => s.trim()).filter(Boolean) }))} />
                      </div>
                    </div>
                  </li>
                ))}
              </ol>
            </section>
          </>
        ) : (
          <p className="text-muted lg:col-span-2">اختر إطلالة من القائمة أو أضف واحدة.</p>
        )}
      </div>

      {/* ── live preview of the DRAFT (unsaved) ── */}
      <AnimatePresence>
        {preview && (
          <motion.div
            className="fixed inset-0 z-50 flex flex-col bg-paper"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
          >
            <div className="flex items-center gap-3 border-b border-line bg-card px-4 py-3">
              <p className="me-auto text-[13px]">
                معاينة المسودة {dirty ? <span className="text-brand">(غير محفوظة)</span> : "(محفوظة)"} — {getPublishedLooks(draft).length} إطلالات منشورة
              </p>
              <button type="button" onClick={() => setPreview(false)} className="grid size-10 place-items-center rounded-full border border-ink/20" aria-label="إغلاق المعاينة">
                <CloseIcon />
              </button>
            </div>
            <div className="min-h-0 flex-1">
              <LooksHero looks={getPublishedLooks(draft)} className="h-full" />
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
}

/* ── image editor ─────────────────────────────────────────────── */

function HotspotCanvas({
  look,
  selectedItem,
  onSelectItem,
  onMove,
}: {
  look: Look;
  selectedItem: string | null;
  onSelectItem: (id: string) => void;
  onMove: (itemId: string, x: number, y: number) => void;
}) {
  const boxRef = useRef<HTMLDivElement>(null);
  const dragging = useRef<string | null>(null);
  const STAGE_H = 540;
  const ADULT_H = STAGE_H * 0.86;
  const h = ADULT_H * (look.image.scale || 1);
  const w = look.image.height ? h * (look.image.width / look.image.height) : h * 0.5;

  const toPct = (e: { clientX: number; clientY: number }) => {
    const r = boxRef.current!.getBoundingClientRect();
    const clamp = (v: number) => Math.round(Math.min(100, Math.max(0, v)) * 10) / 10;
    return { x: clamp(((e.clientX - r.left) / r.width) * 100), y: clamp(((e.clientY - r.top) / r.height) * 100) };
  };

  return (
    <div
      className="relative overflow-hidden rounded-xl bg-stage"
      style={{
        height: STAGE_H,
        background: "radial-gradient(60% 45% at 50% 82%, var(--bh-floor-hi), transparent 100%), var(--bh-stage)",
      }}
    >
      {/* reference: adult height + floor */}
      <div aria-hidden className="absolute inset-x-6 border-t border-dashed border-ink/25" style={{ top: STAGE_H - 24 - ADULT_H }} />
      <div aria-hidden className="absolute inset-x-6 border-t border-ink/15" style={{ top: STAGE_H - 24 }} />

      <div
        ref={boxRef}
        className="absolute left-1/2"
        style={{
          width: w,
          height: h,
          bottom: 24,
          transform: `translate(calc(-50% + ${(look.image.offsetX / 100) * h}px), ${(look.image.offsetY / 100) * h}px)`,
        }}
        onPointerDown={(e) => {
          if (dragging.current || !selectedItem || e.target !== e.currentTarget.querySelector("img")) return;
          const p = toPct(e);
          onMove(selectedItem, p.x, p.y);
        }}
      >
        <div aria-hidden className="absolute left-[8%] right-[8%] bottom-[-3.5%] h-[9%] rounded-[50%] bg-[radial-gradient(closest-side,rgb(var(--bh-ground)/var(--bh-ground-a)),transparent)]" />
        {look.image.src ? (
          <img src={look.image.src} alt="" draggable={false} className="relative h-full w-full select-none object-contain" />
        ) : (
          <div className="grid h-full w-full place-items-center rounded-xl border border-dashed border-ink/25 text-[12px] text-muted">لا توجد صورة</div>
        )}
        {look.items.map((it, n) => (
          <button
            key={it.id}
            type="button"
            title={it.label}
            onPointerDown={(e) => {
              e.stopPropagation();
              dragging.current = it.id;
              onSelectItem(it.id);
              (e.currentTarget as HTMLElement).setPointerCapture(e.pointerId);
            }}
            onPointerMove={(e) => {
              if (dragging.current !== it.id) return;
              const p = toPct(e);
              onMove(it.id, p.x, p.y);
            }}
            onPointerUp={() => (dragging.current = null)}
            className={`nums absolute grid size-7 -translate-x-1/2 -translate-y-1/2 cursor-move touch-none place-items-center rounded-full text-[10px] font-semibold text-white shadow-md ring-2 ring-white ${
              selectedItem === it.id ? "z-10 scale-110 bg-brand" : "bg-onyx"
            }`}
            style={{ left: `${it.hotspot.x}%`, top: `${it.hotspot.y}%` }}
          >
            {String(n + 1).padStart(2, "0")}
          </button>
        ))}
      </div>
    </div>
  );
}

/* ── small inputs ─────────────────────────────────────────────── */

function Field({
  label,
  value,
  onChange,
  type = "text",
  placeholder,
  compact,
}: {
  label: string;
  value: string;
  onChange: (v: string) => void;
  type?: string;
  placeholder?: string;
  compact?: boolean;
}) {
  return (
    <label className="block">
      <span className={`mb-1 block text-muted ${compact ? "text-[10.5px]" : "text-[11.5px]"}`}>{label}</span>
      <input
        type={type}
        value={value}
        placeholder={placeholder}
        onChange={(e) => onChange(e.target.value)}
        className={`w-full rounded-lg border border-line bg-paper px-3 text-ink outline-none focus:border-ink ${compact ? "py-1.5 text-[12px]" : "py-2 text-[13px]"}`}
      />
    </label>
  );
}

function Slider({
  label,
  value,
  min,
  max,
  step,
  format,
  onChange,
}: {
  label: string;
  value: number;
  min: number;
  max: number;
  step: number;
  format: (v: number) => string;
  onChange: (v: number) => void;
}) {
  return (
    <label className="block">
      <span className="mb-1 flex justify-between text-[11.5px] text-muted">
        {label}
        <span className="nums text-ink" dir="ltr">{format(value)}</span>
      </span>
      <input
        type="range"
        min={min}
        max={max}
        step={step}
        value={value}
        onChange={(e) => onChange(Number(e.target.value))}
        className="w-full accent-[var(--color-brand)]"
      />
    </label>
  );
}
