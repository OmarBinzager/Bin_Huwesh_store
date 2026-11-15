"use client";

import { AnimatePresence, Reorder, motion, useDragControls } from "framer-motion";
import { useEffect, useMemo, useState } from "react";
import SiteFooter from "@/components/closing/SiteFooter";
import TestimonialsSection from "@/components/closing/TestimonialsSection";
import VisitSection from "@/components/closing/VisitSection";
import BrandLogo from "@/components/store/BrandLogo";
import ThemeToggle from "@/components/store/ThemeToggle";
import { CloseIcon, PlusIcon } from "@/components/store/icons";
import { STORE_CATEGORIES } from "@/content/categories";
import {
  HOME_ANCHORS,
  SOCIAL_PLATFORMS,
  telHref,
  whatsappHref,
  type ClosingFile,
  type FooterGroup,
  type SocialPlatform,
  type Testimonial,
} from "@/content/closing-types";
import { SOCIAL_META } from "@/components/closing/icons";
import AdminNav from "./AdminNav";

/**
 * Local editor for the page ending: testimonials, the visit/contact block
 * and the footer. «حفظ» writes src/content/closing.json (the server
 * re-validates everything and enforces the honesty rules).
 */
const uid = (p: string) => `${p}-${Date.now().toString(36)}${Math.random().toString(36).slice(2, 5)}`;
type Msg = { tone: "ok" | "error" | "info"; text: string } | null;

export default function ClosingAdmin({ initial }: { initial: ClosingFile }) {
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
  const setItems = (items: Testimonial[]) => setDraft((d) => ({ ...d, testimonials: { ...d.testimonials, items } }));
  const patchItem = (id: string, fn: (t: Testimonial) => Testimonial) => setItems(draft.testimonials.items.map((t) => (t.id === id ? fn(t) : t)));
  const setT = (k: "title" | "subtitle", v: string) => setDraft((d) => ({ ...d, testimonials: { ...d.testimonials, [k]: v } }));
  const setC = (k: keyof ClosingFile["contact"], v: string) => setDraft((d) => ({ ...d, contact: { ...d.contact, [k]: v } }));
  const setGroups = (groups: FooterGroup[]) => setDraft((d) => ({ ...d, footer: { ...d.footer, groups } }));
  const patchGroup = (id: string, fn: (g: FooterGroup) => FooterGroup) => setGroups(draft.footer.groups.map((g) => (g.id === id ? fn(g) : g)));

  const save = async () => {
    setBusy(true);
    try {
      const res = await fetch("/api/admin/closing", { method: "PUT", headers: { "content-type": "application/json" }, body: JSON.stringify(draft) });
      const body = await res.json();
      if (!res.ok) throw new Error(body.error ?? "تعذّر الحفظ");
      setSaved(body);
      setDraft(body);
      flash({ tone: "ok", text: "تم الحفظ في src/content/closing.json — الصفحة الرئيسية تعرض التحديث الآن." });
    } catch (e) {
      flash({ tone: "error", text: (e as Error).message });
    } finally {
      setBusy(false);
    }
  };

  const c = draft.contact;
  const targets = [
    "/",
    ...HOME_ANCHORS.map((a) => `/#${a}`),
    ...STORE_CATEGORIES.map((x) => `/categories/${x.id}`),
    whatsappHref(c.phoneIntl),
    telHref(c.phoneIntl),
  ];
  const previews = draft.testimonials.items.filter((t) => !t.verified && t.visible).length;
  const usedPlatforms = new Set(draft.footer.social.map((x) => x.platform));
  const freePlatform = SOCIAL_PLATFORMS.find((p) => !usedPlatforms.has(p));

  return (
    <div className="min-h-svh bg-paper text-ink">
      <header className="sticky top-0 z-[200] flex flex-wrap items-center gap-3 border-b border-line bg-card/95 px-4 py-3 backdrop-blur sm:px-6">
        <BrandLogo height={30} />
        <div className="me-auto">
          <h1 className="font-display text-lg font-semibold">إدارة «الآراء والتواصل والفوتر»</h1>
          <p className="text-[11px] text-muted">أداة محلية للتطوير فقط — تحفظ في src/content/closing.json</p>
        </div>
        <AdminNav current="closing" />
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

      <datalist id="closing-targets">
        {targets.map((t) => <option key={t} value={t} />)}
      </datalist>

      <div className="grid gap-5 p-4 sm:p-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
        {/* testimonials */}
        <section className="space-y-3 rounded-2xl border border-line bg-card p-4">
          <div className="flex flex-wrap items-center justify-between gap-2">
            <h2 className="text-[13px] font-semibold">آراء العملاء ({draft.testimonials.items.length}) — اسحب من الرقم لإعادة الترتيب</h2>
            <button type="button" className="flex items-center gap-1 rounded-full bg-ink px-3 py-1.5 text-[12px] text-on-ink"
              onClick={() => { setItems([...draft.testimonials.items, { id: uid("review"), quote: "", name: "", verified: false, visible: false }]); flash({ tone: "info", text: "أُضيف رأي مخفي في آخر القائمة — أكمل النص والاسم ثم أظهره واحفظ." }); }}>
              <PlusIcon width={14} height={14} /> رأي
            </button>
          </div>
          <div className="grid gap-2 sm:grid-cols-2">
            <Field label="عنوان القسم" value={draft.testimonials.title} onChange={(v) => setT("title", v)} />
            <Field label="النص المساعد" value={draft.testimonials.subtitle} onChange={(v) => setT("subtitle", v)} />
          </div>
          {previews > 0 && (
            <p className="rounded-lg bg-amber-500/12 px-3 py-2 text-[11.5px] text-amber-800 dark:text-amber-300">
              {previews} من البطاقات الظاهرة أمثلة للمعاينة — استبدلها بآراء حقيقية موثّقة قبل الإطلاق.
            </p>
          )}
          <Reorder.Group axis="y" values={draft.testimonials.items} onReorder={setItems} className="space-y-2">
            {draft.testimonials.items.map((t, i) => (
              <ReviewRow key={t.id} item={t} index={i} patch={patchItem}
                remove={() => confirm("حذف هذا الرأي من القسم؟") && setItems(draft.testimonials.items.filter((x) => x.id !== t.id))} />
            ))}
          </Reorder.Group>
          <p className="text-[11px] leading-relaxed text-muted">
            «رأي حقيقي موثّق» يتطلب ذكر المصدر، والنجوم مسموحة فقط للرأي الموثّق الذي يحمل تقييمًا في مصدره. متوسط التقييم وعدد الآراء يُحسبان تلقائيًا من الآراء الموثّقة فقط. لا تُكتب أرقام هواتف أو بريد في النص أو الاسم، واستخدم اسم العرض الذي وافق عليه العميل.
          </p>
        </section>

        <div className="space-y-5">
          {/* contact */}
          <section className="space-y-2.5 rounded-2xl border border-line bg-card p-4">
            <h2 className="text-[13px] font-semibold">مساحة «ننتظرك» ومعلومات الشركة</h2>
            <Field label="العنوان الكبير" value={c.title} onChange={(v) => setC("title", v)} />
            <Field label="النص تحت العنوان" value={c.subtitle} onChange={(v) => setC("subtitle", v)} />
            <div className="grid gap-2 sm:grid-cols-2">
              <Field label="اسم الشركة" value={c.company} onChange={(v) => setC("company", v)} />
              <Field label="العنوان" value={c.address} onChange={(v) => setC("address", v)} />
              <Field label="الهاتف كما يظهر" dir="ltr" value={c.phoneDisplay} onChange={(v) => setC("phoneDisplay", v)} />
              <Field label="الرقم الدولي (967…) لواتساب والاتصال" dir="ltr" value={c.phoneIntl} onChange={(v) => setC("phoneIntl", v.replace(/\D/g, ""))} />
            </div>
            <Field label="رابط موقع المعرض على خرائط Google (اتركه فارغًا إن لم يتوفر)" dir="ltr" value={c.mapUrl} onChange={(v) => setC("mapUrl", v)} />
            {!c.mapUrl && <p className="text-[11px] text-muted">لا يوجد رابط موقع معتمد بعد، لذلك يُخفى زر «موقع المعرض» ويُكتفى بالعنوان. أضف رابط المعرض الصحيح من خرائط Google ليظهر الزر.</p>}
            <p className="text-[11px] text-muted" dir="ltr">{whatsappHref(c.phoneIntl)} · {telHref(c.phoneIntl)}</p>
          </section>

          {/* footer */}
          <section className="space-y-3 rounded-2xl border border-line bg-card p-4">
            <h2 className="text-[13px] font-semibold">الفوتر</h2>
            <Field label="العبارة تحت الشعار" value={draft.footer.tagline} onChange={(v) => setDraft((d) => ({ ...d, footer: { ...d.footer, tagline: v } }))} />
            {draft.footer.groups.map((g) => (
              <div key={g.id} className="space-y-2 rounded-lg border border-line p-2.5">
                <Field compact label="عنوان المجموعة" value={g.title} onChange={(v) => patchGroup(g.id, (x) => ({ ...x, title: v }))} />
                {g.links.map((l, j) => (
                  <div key={j} className="grid grid-cols-[minmax(0,1fr)_minmax(0,1.3fr)_auto] items-end gap-1.5">
                    <Field compact label="النص" value={l.label} onChange={(v) => patchGroup(g.id, (x) => ({ ...x, links: x.links.map((y, k) => (k === j ? { ...y, label: v } : y)) }))} />
                    <Field compact label="الوجهة" dir="ltr" list="closing-targets" value={l.href} onChange={(v) => patchGroup(g.id, (x) => ({ ...x, links: x.links.map((y, k) => (k === j ? { ...y, href: v } : y)) }))} />
                    <button type="button" aria-label="حذف الرابط" className="mb-1 rounded p-1 text-muted hover:text-brand" onClick={() => patchGroup(g.id, (x) => ({ ...x, links: x.links.filter((_, k) => k !== j) }))}>
                      <CloseIcon width={14} height={14} />
                    </button>
                  </div>
                ))}
                <button type="button" className="text-[11.5px] text-muted underline hover:text-ink" onClick={() => patchGroup(g.id, (x) => ({ ...x, links: [...x.links, { label: "", href: "" }] }))}>
                  + رابط
                </button>
              </div>
            ))}
            <p className="text-[11px] leading-relaxed text-muted">
              الوجهات المسموحة: الصفحة الرئيسية وأقسامها، صفحات الأقسام، واتساب ورقم الشركة، أو رابط https لصفحة موجودة فعلًا (مثل سياسة منشورة). لا توجد صفحات سياسات في الموقع حاليًا، لذلك لم تُضف روابطها.
            </p>

            <div className="space-y-2 rounded-lg border border-line p-2.5">
              <p className="text-[12px] font-semibold">حسابات التواصل الاجتماعي</p>
              {draft.footer.social.length === 0 && <p className="text-[11px] text-muted">لا توجد حسابات معتمدة في بيانات المشروع، لذلك لا تظهر أيقونات. أضف رابط الحساب الرسمي الكامل لتظهر.</p>}
              {draft.footer.social.map((x, j) => (
                <div key={x.platform} className="grid grid-cols-[120px_minmax(0,1fr)_auto] items-end gap-1.5">
                  <label className="block">
                    <span className="mb-0.5 block text-[10.5px] text-muted">المنصة</span>
                    <select value={x.platform} className="w-full rounded-lg border border-line bg-paper px-2 py-1.5 text-[12.5px]"
                      onChange={(e) => setDraft((d) => ({ ...d, footer: { ...d.footer, social: d.footer.social.map((y, k) => (k === j ? { ...y, platform: e.target.value as SocialPlatform } : y)) } }))}>
                      {SOCIAL_PLATFORMS.map((p) => <option key={p} value={p} disabled={p !== x.platform && usedPlatforms.has(p)}>{SOCIAL_META[p].label}</option>)}
                    </select>
                  </label>
                  <Field compact label="رابط الحساب" dir="ltr" value={x.url}
                    onChange={(v) => setDraft((d) => ({ ...d, footer: { ...d.footer, social: d.footer.social.map((y, k) => (k === j ? { ...y, url: v } : y)) } }))} />
                  <button type="button" aria-label="حذف الحساب" className="mb-1 rounded p-1 text-muted hover:text-brand"
                    onClick={() => setDraft((d) => ({ ...d, footer: { ...d.footer, social: d.footer.social.filter((_, k) => k !== j) } }))}>
                    <CloseIcon width={14} height={14} />
                  </button>
                </div>
              ))}
              {freePlatform && (
                <button type="button" className="text-[11.5px] text-muted underline hover:text-ink"
                  onClick={() => setDraft((d) => ({ ...d, footer: { ...d.footer, social: [...d.footer.social, { platform: freePlatform, url: "" }] } }))}>
                  + حساب
                </button>
              )}
            </div>
          </section>
        </div>
      </div>

      <section className="pb-10">
        <p className="mb-2 px-4 text-[11px] text-muted sm:px-6">معاينة مباشرة للمسودة {dirty && <span className="text-brand">(غير محفوظة)</span>}</p>
        <div className="border-y border-line">
          <TestimonialsSection data={draft} />
          <VisitSection data={draft} />
          <SiteFooter data={draft} />
        </div>
      </section>
    </div>
  );
}

function Field({ label, value, onChange, dir, compact, list }: {
  label: string; value: string; onChange: (v: string) => void; dir?: "ltr" | "auto"; compact?: boolean; list?: string;
}) {
  return (
    <label className="block min-w-0">
      <span className={`mb-0.5 block text-muted ${compact ? "text-[10.5px]" : "text-[11.5px]"}`}>{label}</span>
      <input value={value} list={list} onChange={(e) => onChange(e.target.value)} dir={dir ?? "auto"}
        className={`w-full rounded-lg border border-line bg-paper outline-none focus:border-ink ${compact ? "px-2.5 py-1.5 text-[12.5px]" : "px-3 py-2 text-[13px]"}`} />
    </label>
  );
}

function ReviewRow({ item: t, index, patch, remove }: {
  item: Testimonial;
  index: number;
  patch: (id: string, fn: (t: Testimonial) => Testimonial) => void;
  remove: () => void;
}) {
  const drag = useDragControls();
  return (
    <Reorder.Item value={t} dragListener={false} dragControls={drag} className={`grid gap-3 rounded-xl border border-line bg-card p-2.5 md:grid-cols-[28px_minmax(0,1fr)] ${t.visible ? "" : "opacity-70"}`}>
      <span onPointerDown={(e) => drag.start(e)} className="nums grid h-8 cursor-grab touch-none place-items-center self-start rounded-md text-center text-[11px] text-muted hover:bg-ink/6 active:cursor-grabbing" title="اسحب لإعادة الترتيب">
        {String(index + 1).padStart(2, "0")}
      </span>
      <div className="grid min-w-0 gap-2">
        <label className="block">
          <span className="mb-0.5 block text-[10.5px] text-muted">نص الرأي (حتى 280 حرفًا)</span>
          <textarea value={t.quote} rows={2} onChange={(e) => patch(t.id, (x) => ({ ...x, quote: e.target.value }))}
            className="w-full rounded-lg border border-line bg-paper px-2.5 py-1.5 text-[12.5px] outline-none focus:border-ink" />
        </label>
        <div className="grid gap-2 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_110px]">
          <Field compact label="اسم العرض المصرّح به" value={t.name} onChange={(v) => patch(t.id, (x) => ({ ...x, name: v }))} />
          <Field compact label="المصدر (للرأي الموثّق)" value={t.source ?? ""} onChange={(v) => patch(t.id, (x) => ({ ...x, source: v }))} />
          <label className="block">
            <span className="mb-0.5 block text-[10.5px] text-muted">النجوم</span>
            <select value={t.rating ?? ""} disabled={!t.verified} className="w-full rounded-lg border border-line bg-paper px-2 py-1.5 text-[12.5px] disabled:opacity-50"
              onChange={(e) => patch(t.id, (x) => ({ ...x, rating: e.target.value ? Number(e.target.value) : undefined }))}>
              <option value="">بدون</option>
              {[5, 4, 3, 2, 1].map((r) => <option key={r} value={r}>{r}</option>)}
            </select>
          </label>
        </div>
        <div className="flex flex-wrap items-center gap-x-4 gap-y-2">
          <label className="flex items-center gap-1.5 text-[12px]">
            <input type="checkbox" checked={t.verified} className="size-4 accent-[var(--color-brand)]"
              onChange={(e) => patch(t.id, (x) => ({ ...x, verified: e.target.checked, rating: e.target.checked ? x.rating : undefined }))} />
            رأي حقيقي موثّق
          </label>
          <label className="flex items-center gap-1.5 text-[12px]">
            <input type="checkbox" checked={t.visible} className="size-4 accent-[var(--color-brand)]" onChange={(e) => patch(t.id, (x) => ({ ...x, visible: e.target.checked }))} />
            ظاهر
          </label>
          {!t.verified && <span className="rounded-full border border-dashed border-line px-2 py-0.5 text-[10.5px] text-muted">يظهر للزوار بعلامة «مثال للمعاينة»</span>}
          <button type="button" onClick={remove} className="ms-auto flex items-center gap-1 text-[11px] text-muted hover:text-brand">
            <CloseIcon width={12} height={12} /> حذف
          </button>
        </div>
      </div>
    </Reorder.Item>
  );
}
