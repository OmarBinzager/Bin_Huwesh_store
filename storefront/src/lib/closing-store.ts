// Server-side only (node:fs) — used by the local admin route for the page ending.
import { randomUUID } from "node:crypto";
import { readFile, rename, writeFile } from "node:fs/promises";
import path from "node:path";
import { STORE_CATEGORIES } from "@/content/categories";
import {
  HOME_ANCHORS,
  SOCIAL_PLATFORMS,
  telHref,
  whatsappHref,
  type ClosingFile,
  type FooterGroup,
  type SocialLink,
  type Testimonial,
} from "@/content/closing-types";
import { ValidationError, safeId } from "./looks-store";

/**
 * File-backed store for src/content/closing.json (testimonials, visit/contact
 * block, footer). Validates everything, writes atomically, and enforces the
 * honesty rules described in closing-types.ts.
 */
const DATA_FILE = path.join(process.cwd(), "src", "content", "closing.json");

export async function readClosing(): Promise<ClosingFile> {
  return JSON.parse(await readFile(DATA_FILE, "utf8")) as ClosingFile;
}

const isStr = (v: unknown, max = 400): v is string => typeof v === "string" && v.length <= max;
const req = (v: unknown, max: number, msg: string) => {
  if (!isStr(v, max) || !v.trim()) throw new ValidationError(msg);
  return v.trim();
};
/** 7+ digits in a row (spaces/dashes allowed) or an e-mail → private data. */
const PRIVATE = /(\+?\d[\d\s\-]{6,}\d)|([^\s@]+@[^\s@]+\.[^\s@]+)/;
const MAP_HOSTS = ["maps.app.goo.gl", "goo.gl", "www.google.com", "google.com", "maps.google.com"];
const SOCIAL_HOSTS: Record<string, string[]> = {
  instagram: ["instagram.com", "www.instagram.com"],
  facebook: ["facebook.com", "www.facebook.com", "m.facebook.com", "fb.com"],
  tiktok: ["tiktok.com", "www.tiktok.com"],
  snapchat: ["snapchat.com", "www.snapchat.com"],
  x: ["x.com", "twitter.com", "www.x.com", "www.twitter.com"],
  youtube: ["youtube.com", "www.youtube.com", "youtu.be"],
};

const httpsUrl = (v: string): URL | null => {
  try {
    const u = new URL(v);
    return u.protocol === "https:" ? u : null;
  } catch {
    return null;
  }
};

/** Internal pages/anchors that exist, the company's own phone/WhatsApp, or an https link. */
function checkHref(href: string, intl: string, where: string): string {
  const internal = new Set<string>(["/", ...HOME_ANCHORS.map((a) => `/#${a}`), ...STORE_CATEGORIES.map((c) => `/categories/${c.id}`)]);
  if (internal.has(href)) return href;
  if (href === telHref(intl) || href === whatsappHref(intl)) return href;
  if (href.startsWith("/") || href.startsWith("#")) throw new ValidationError(`${where}: الرابط الداخلي «${href}» لا يشير إلى صفحة أو قسم موجود`);
  if (!httpsUrl(href)) throw new ValidationError(`${where}: الرابط يجب أن يكون صفحة موجودة أو رابط https كاملًا`);
  return href;
}

export async function writeClosing(input: unknown): Promise<ClosingFile> {
  const raw = input as Partial<ClosingFile>;
  const t = raw?.testimonials;
  const c = raw?.contact;
  const f = raw?.footer;
  if (!t || !c || !f) throw new ValidationError("البيانات ناقصة (الآراء أو التواصل أو الفوتر)");

  // ── contact
  const phoneIntl = String(c.phoneIntl ?? "").trim();
  if (!/^967\d{9}$/.test(phoneIntl)) throw new ValidationError("الرقم الدولي يجب أن يكون 967 ثم 9 أرقام (مثل 967780024406)");
  const phoneDisplay = req(c.phoneDisplay, 20, "رقم الهاتف المعروض مطلوب");
  if (phoneDisplay.replace(/\D/g, "") !== phoneIntl.slice(3)) throw new ValidationError("الرقم المعروض لا يطابق الرقم الدولي");
  const mapUrl = isStr(c.mapUrl, 600) ? c.mapUrl.trim() : "";
  if (mapUrl) {
    const u = httpsUrl(mapUrl);
    const okHost = u && MAP_HOSTS.includes(u.hostname) && (u.hostname.endsWith("goo.gl") || u.pathname.startsWith("/maps"));
    if (!okHost) throw new ValidationError("رابط الموقع يجب أن يكون رابط خرائط Google الصحيح للمعرض (https://maps.app.goo.gl/… أو google.com/maps/…)");
  }
  const contact: ClosingFile["contact"] = {
    title: req(c.title, 60, "عنوان مساحة التواصل مطلوب (حتى 60 حرفًا)"),
    subtitle: req(c.subtitle, 160, "النص تحت العنوان مطلوب (حتى 160 حرفًا)"),
    company: req(c.company, 60, "اسم الشركة مطلوب"),
    address: req(c.address, 120, "العنوان مطلوب"),
    phoneDisplay,
    phoneIntl,
    mapUrl,
  };

  // ── testimonials
  if (!Array.isArray(t.items) || t.items.length > 12) throw new ValidationError("قائمة الآراء غير صالحة (حتى 12 رأيًا)");
  const ids = new Set<string>();
  const items: Testimonial[] = t.items.map((it: Testimonial, i: number) => {
    const where = `الرأي ${i + 1}`;
    if (!isStr(it.id, 64)) throw new ValidationError(`${where}: معرّف غير صالح`);
    safeId(it.id);
    if (ids.has(it.id)) throw new ValidationError(`${where}: معرّف مكرر`);
    ids.add(it.id);
    const quote = req(it.quote, 280, `${where}: نص الرأي مطلوب (حتى 280 حرفًا)`);
    const name = req(it.name, 40, `${where}: اسم العرض مطلوب (حتى 40 حرفًا)`);
    if (PRIVATE.test(quote) || PRIVATE.test(name)) throw new ValidationError(`${where}: احذف أرقام الهواتف أو البريد من النص والاسم`);
    const verified = Boolean(it.verified);
    const source = isStr(it.source, 60) ? it.source.trim() : "";
    if (verified && !source) throw new ValidationError(`${where}: الرأي الموثّق يحتاج مصدره (مثل «تقييم Google»)`);
    let rating: number | undefined;
    if (it.rating !== undefined && it.rating !== null) {
      if (!verified) throw new ValidationError(`${where}: لا تُضاف نجوم إلا لرأي حقيقي موثّق`);
      if (!Number.isInteger(it.rating) || it.rating < 1 || it.rating > 5) throw new ValidationError(`${where}: التقييم من 1 إلى 5`);
      rating = it.rating;
    }
    return {
      id: it.id,
      quote,
      name,
      verified,
      ...(verified && source ? { source } : {}),
      ...(rating ? { rating } : {}),
      visible: Boolean(it.visible),
    };
  });
  const testimonials: ClosingFile["testimonials"] = {
    title: req(t.title, 60, "عنوان قسم الآراء مطلوب"),
    subtitle: isStr(t.subtitle, 140) ? t.subtitle.trim() : "",
    items,
  };

  // ── footer
  if (!Array.isArray(f.groups) || f.groups.length > 5) throw new ValidationError("مجموعات الروابط غير صالحة (حتى 5)");
  const gids = new Set<string>();
  const groups: FooterGroup[] = f.groups.map((g: FooterGroup, i: number) => {
    const where = `مجموعة الروابط ${i + 1}`;
    if (!isStr(g.id, 40)) throw new ValidationError(`${where}: معرّف غير صالح`);
    safeId(g.id);
    if (gids.has(g.id)) throw new ValidationError(`${where}: معرّف مكرر`);
    gids.add(g.id);
    const title = req(g.title, 30, `${where}: العنوان مطلوب`);
    if (!Array.isArray(g.links) || g.links.length === 0 || g.links.length > 12) throw new ValidationError(`${where}: من رابط واحد إلى 12 رابطًا`);
    const links = g.links.map((l, j) => {
      const lw = `${title}، الرابط ${j + 1}`;
      return { label: req(l.label, 40, `${lw}: النص مطلوب`), href: checkHref(String(l.href ?? "").trim(), phoneIntl, lw) };
    });
    return { id: g.id, title, links };
  });
  if (!Array.isArray(f.social) || f.social.length > SOCIAL_PLATFORMS.length) throw new ValidationError("قائمة الحسابات غير صالحة");
  const seen = new Set<string>();
  const social: SocialLink[] = f.social.map((s: SocialLink, i: number) => {
    const where = `الحساب ${i + 1}`;
    if (!SOCIAL_PLATFORMS.includes(s.platform)) throw new ValidationError(`${where}: منصة غير معروفة`);
    if (seen.has(s.platform)) throw new ValidationError(`${where}: المنصة مكررة`);
    seen.add(s.platform);
    const u = httpsUrl(String(s.url ?? "").trim());
    if (!u || !SOCIAL_HOSTS[s.platform].includes(u.hostname) || u.pathname.length < 2)
      throw new ValidationError(`${where}: ضع رابط الحساب المعتمد الكامل على ${s.platform} (https://…)`);
    return { platform: s.platform, url: u.toString() };
  });
  const footer: ClosingFile["footer"] = { tagline: req(f.tagline, 60, "عبارة الفوتر مطلوبة"), groups, social };

  const data: ClosingFile = { version: 1, testimonials, contact, footer };
  const tmp = `${DATA_FILE}.${randomUUID()}.tmp`;
  await writeFile(tmp, JSON.stringify(data, null, 2) + "\n", "utf8");
  await rename(tmp, DATA_FILE);
  return data;
}
