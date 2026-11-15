// Server-side only (node:fs) — used by the local admin routes for «وصل حديثًا».
import { randomUUID } from "node:crypto";
import { access, mkdir, readFile, rename, writeFile } from "node:fs/promises";
import path from "node:path";
import { NA_SIZES, type NaCategory, type NaItem, type NaSize, type NewArrivalsFile } from "@/content/new-arrivals-types";
import { ValidationError, safeId } from "./looks-store";

/**
 * File-backed store for src/content/new-arrivals.json (the file the home page
 * imports). Same rules as the other admin stores: validate everything, check
 * that every referenced image exists, write atomically, keep ids stable.
 */
const ROOT = process.cwd();
const DATA_FILE = path.join(ROOT, "src", "content", "new-arrivals.json");
const IMAGE_DIR = path.join(ROOT, "public", "images", "new-arrivals");
const IMAGE_PATH = /^\/images\/new-arrivals\/[a-z0-9-]+\.webp$/;
const SWATCH = /^#[0-9a-f]{6}$/i;
const MAX_ITEMS = 24;
const MAX_COLORS = 6;

export async function readNewArrivals(): Promise<NewArrivalsFile> {
  return JSON.parse(await readFile(DATA_FILE, "utf8")) as NewArrivalsFile;
}

const isStr = (v: unknown, max = 400): v is string => typeof v === "string" && v.length <= max;
const exists = async (publicPath: string) => {
  try {
    await access(path.join(ROOT, "public", ...publicPath.split("/").filter(Boolean)));
    return true;
  } catch {
    return false;
  }
};

export async function writeNewArrivals(input: unknown): Promise<NewArrivalsFile> {
  const raw = input as Partial<NewArrivalsFile>;
  const s = raw?.settings;
  if (!s) throw new ValidationError("الإعدادات مفقودة");
  if (!isStr(s.title, 60) || !s.title.trim()) throw new ValidationError("عنوان القسم مطلوب (حتى 60 حرفًا)");
  if (!isStr(s.subtitle, 140)) throw new ValidationError("النص المساعد طويل جدًا (حتى 140 حرفًا)");
  if (!isStr(s.watermark, 20) || !s.watermark.trim()) throw new ValidationError("الكلمة الخلفية مطلوبة (حتى 20 حرفًا)");
  if (!isStr(s.room) || !IMAGE_PATH.test(s.room) || !(await exists(s.room))) throw new ValidationError("صورة الغرفة غير موجودة");

  if (!Array.isArray(raw.categories) || raw.categories.length === 0 || raw.categories.length > 12) throw new ValidationError("قائمة الفئات غير صالحة");
  const catIds = new Set<string>();
  const categories: NaCategory[] = raw.categories.map((c: NaCategory) => {
    if (!isStr(c.id, 40)) throw new ValidationError("معرّف فئة غير صالح");
    safeId(c.id);
    if (catIds.has(c.id)) throw new ValidationError("معرّف فئة مكرر");
    catIds.add(c.id);
    if (!isStr(c.name, 30) || !c.name.trim()) throw new ValidationError("اسم الفئة مطلوب");
    return { id: c.id, name: c.name.trim() };
  });

  if (!Array.isArray(raw.items)) throw new ValidationError("قائمة القطع مفقودة");
  if (raw.items.length > MAX_ITEMS) throw new ValidationError(`الحد الأقصى ${MAX_ITEMS} قطعة`);
  const ids = new Set<string>();
  const items: NaItem[] = [];
  for (const [i, it] of (raw.items as NaItem[]).entries()) {
    const where = `القطعة ${i + 1}`;
    if (!isStr(it.id, 64)) throw new ValidationError(`${where}: معرّف غير صالح`);
    safeId(it.id);
    if (ids.has(it.id)) throw new ValidationError(`${where}: معرّف مكرر`);
    ids.add(it.id);
    if (!isStr(it.name, 40) || !it.name.trim()) throw new ValidationError(`${where}: الاسم مطلوب (حتى 40 حرفًا)`);
    if (it.description !== undefined && !isStr(it.description, 120)) throw new ValidationError(`${where}: الوصف طويل جدًا (حتى 120 حرفًا)`);
    if (!catIds.has(it.categoryId)) throw new ValidationError(`${where}: الفئة غير موجودة`);
    if (!Number.isInteger(it.price) || it.price < 0 || it.price > 10_000_000) throw new ValidationError(`${where}: السعر يجب أن يكون رقمًا صحيحًا`);
    if (!Array.isArray(it.sizes) || it.sizes.length === 0) throw new ValidationError(`${where}: اختر مقاسًا واحدًا على الأقل`);
    const sizes = NA_SIZES.filter((z) => it.sizes.includes(z)) as NaSize[];
    if (sizes.length !== it.sizes.length) throw new ValidationError(`${where}: مقاس غير معروف`);
    if (!Array.isArray(it.colors) || it.colors.length === 0 || it.colors.length > MAX_COLORS)
      throw new ValidationError(`${where}: لكل قطعة لون مصوّر واحد على الأقل (حتى ${MAX_COLORS})`);
    const colorIds = new Set<string>();
    const colors = [];
    for (const [j, c] of it.colors.entries()) {
      const cw = `${where}، اللون ${j + 1}`;
      if (!isStr(c.id, 40)) throw new ValidationError(`${cw}: معرّف غير صالح`);
      safeId(c.id);
      if (colorIds.has(c.id)) throw new ValidationError(`${cw}: معرّف مكرر`);
      colorIds.add(c.id);
      if (!isStr(c.name, 30) || !c.name.trim()) throw new ValidationError(`${cw}: اسم اللون مطلوب`);
      if (!isStr(c.swatch, 7) || !SWATCH.test(c.swatch)) throw new ValidationError(`${cw}: رمز اللون يجب أن يكون مثل #f4f2ee`);
      for (const [key, label] of [["garment", "صورة القطعة"], ["model", "صورة المودل"]] as const) {
        const v = c[key];
        if (!isStr(v) || !IMAGE_PATH.test(v)) throw new ValidationError(`${cw}: ارفع ${label} — لكل لون صورتان متطابقتان`);
        if (!(await exists(v))) throw new ValidationError(`${cw}: ${label} غير موجودة في المجلد`);
      }
      colors.push({ id: c.id, name: c.name.trim(), swatch: c.swatch.toLowerCase(), garment: c.garment, model: c.model });
    }
    items.push({ id: it.id, name: it.name.trim(), ...(it.description?.trim() ? { description: it.description.trim() } : {}), categoryId: it.categoryId, price: it.price, sizes, colors, visible: Boolean(it.visible) });
  }

  const data: NewArrivalsFile = {
    version: 1,
    settings: { title: s.title.trim(), subtitle: s.subtitle.trim(), watermark: s.watermark.trim(), room: s.room },
    categories,
    items,
  };
  const tmp = `${DATA_FILE}.${randomUUID()}.tmp`;
  await writeFile(tmp, JSON.stringify(data, null, 2) + "\n", "utf8");
  await rename(tmp, DATA_FILE);
  return data;
}

/** True when a WebP file carries an alpha channel (VP8X with the alpha flag, or VP8L). */
function webpHasAlpha(buf: Buffer): boolean {
  const kind = buf.subarray(12, 16).toString("latin1");
  if (kind === "VP8X") return (buf[20] & 0x10) !== 0;
  if (kind === "VP8L") return ((buf[24] >> 4) & 1) === 1; // alpha_is_used bit
  return false;
}

/**
 * Saves an already-processed transparent cut-out (WebP made in the browser)
 * under a NEW name — existing images are never overwritten.
 */
export async function saveNewArrivalImage(itemId: string, colorId: string, kind: string, bytes: Uint8Array): Promise<string> {
  safeId(itemId);
  safeId(colorId);
  if (kind !== "garment" && kind !== "model") throw new ValidationError("نوع الصورة غير معروف");
  const buf = Buffer.from(bytes);
  const head = buf.subarray(0, 12).toString("latin1");
  if (!(head.startsWith("RIFF") && head.slice(8, 12) === "WEBP")) throw new ValidationError("الصورة يجب أن تكون WebP");
  if (!webpHasAlpha(buf)) throw new ValidationError("الصورة يجب أن تكون بخلفية شفافة");
  await mkdir(IMAGE_DIR, { recursive: true });
  const name = `${itemId}-${colorId}-${kind}-${Date.now().toString(36)}.webp`;
  await writeFile(path.join(IMAGE_DIR, name), buf, { flag: "wx" });
  return `/images/new-arrivals/${name}`;
}
