// Server-side only (node:fs) — used by the local admin routes for the
// «تسوّق حسب القسم» cards.
import { randomUUID } from "node:crypto";
import { mkdir, readFile, rename, writeFile } from "node:fs/promises";
import path from "node:path";
import { getStoreCategory } from "@/content/categories";
import type { CategoryCard, CategoryCardSize, ShopCategoriesFile } from "@/content/category-card-types";
import { ValidationError, safeId } from "./looks-store";

/**
 * File-backed store for the department cards (src/content/shop-categories.json
 * — the file the home page imports). Same rules as the looks/brands stores:
 * validate everything, write atomically, keep ids stable. Swap for the
 * company's admin API later.
 */
const ROOT = process.cwd();
const DATA_FILE = path.join(ROOT, "src", "content", "shop-categories.json");
const IMAGE_DIR = path.join(ROOT, "public", "images", "categories");

export async function readShopCategories(): Promise<ShopCategoriesFile> {
  return JSON.parse(await readFile(DATA_FILE, "utf8")) as ShopCategoriesFile;
}

const isStr = (v: unknown, max = 400): v is string => typeof v === "string" && v.length <= max;
const isNum = (v: unknown, min: number, max: number): v is number =>
  typeof v === "number" && Number.isFinite(v) && v >= min && v <= max;
const SIZES: CategoryCardSize[] = ["large", "tall", "wide", "regular"];
const IMAGE_PATH = /^\/images\/categories\/[a-z0-9-]+\.(webp|jpg|png)$/;
/** Internal links only: "/…" but never "//host" (protocol-relative). */
const INTERNAL_HREF = /^\/(?!\/)[A-Za-z0-9\-._~/#?=&%]*$/;
const MAX_CARDS = 16;

export async function writeShopCategories(input: unknown): Promise<ShopCategoriesFile> {
  const raw = input as Partial<ShopCategoriesFile>;
  const s = raw?.settings;
  if (!s) throw new ValidationError("الإعدادات مفقودة");
  if (!isStr(s.title, 60) || !s.title.trim()) throw new ValidationError("عنوان القسم مطلوب (حتى 60 حرفًا)");
  if (!isStr(s.subtitle, 140)) throw new ValidationError("النص المساعد طويل جدًا");
  if (!isStr(s.cta, 30) || !s.cta.trim()) throw new ValidationError("نص الرابط مطلوب (حتى 30 حرفًا)");
  if (!Array.isArray(raw.cards)) throw new ValidationError("قائمة البطاقات مفقودة");
  if (raw.cards.length > MAX_CARDS) throw new ValidationError(`الحد الأقصى ${MAX_CARDS} بطاقة`);

  const ids = new Set<string>();
  const cards = raw.cards.map((c: CategoryCard, i): CategoryCard => {
    const where = `البطاقة ${i + 1}`;
    if (!isStr(c.id, 64)) throw new ValidationError(`${where}: معرّف غير صالح`);
    safeId(c.id);
    if (ids.has(c.id)) throw new ValidationError(`${where}: معرّف مكرر`);
    ids.add(c.id);
    if (!isStr(c.categoryId, 64) || !getStoreCategory(c.categoryId)) throw new ValidationError(`${where}: القسم غير موجود في قائمة أقسام المتجر`);
    if (!isStr(c.name, 40) || !c.name.trim()) throw new ValidationError(`${where}: الاسم مطلوب (حتى 40 حرفًا)`);
    if (!isStr(c.href, 200) || !INTERNAL_HREF.test(c.href)) throw new ValidationError(`${where}: الرابط يجب أن يكون داخل الموقع ويبدأ بـ /`);
    if (!isStr(c.image) || !IMAGE_PATH.test(c.image)) throw new ValidationError(`${where}: مسار الصورة غير صالح`);
    if (c.caption !== undefined && !isStr(c.caption, 140)) throw new ValidationError(`${where}: وصف القسم طويل جدًا (حتى 140 حرفًا)`);
    if (!isStr(c.alt, 160)) throw new ValidationError(`${where}: الوصف البديل طويل جدًا`);
    if (!c.focus || !isNum(c.focus.x, 0, 100) || !isNum(c.focus.y, 0, 100)) throw new ValidationError(`${where}: نقطة التركيز خارج النطاق`);
    if (!SIZES.includes(c.size)) throw new ValidationError(`${where}: حجم البطاقة غير صالح`);
    return {
      id: c.id,
      categoryId: c.categoryId,
      name: c.name.trim(),
      ...(c.caption?.trim() ? { caption: c.caption.trim() } : {}),
      href: c.href,
      image: c.image,
      alt: c.alt.trim(),
      focus: { x: Math.round(c.focus.x), y: Math.round(c.focus.y) },
      size: c.size,
      visible: Boolean(c.visible),
    };
  });

  const data: ShopCategoriesFile = {
    version: 1,
    settings: { title: s.title.trim(), subtitle: s.subtitle.trim(), cta: s.cta.trim() },
    cards,
  };
  const tmp = `${DATA_FILE}.${randomUUID()}.tmp`;
  await writeFile(tmp, JSON.stringify(data, null, 2) + "\n", "utf8");
  await rename(tmp, DATA_FILE);
  return data;
}

/**
 * Saves an already-processed card photo (WebP, made in the browser) under a
 * NEW name — existing images are never overwritten. Returns its public path.
 */
export async function saveCategoryImage(cardId: string, bytes: Uint8Array): Promise<string> {
  safeId(cardId);
  const buf = Buffer.from(bytes);
  const head = buf.subarray(0, 12).toString("latin1");
  if (!(head.startsWith("RIFF") && head.slice(8, 12) === "WEBP")) throw new ValidationError("الصورة يجب أن تكون WebP");
  await mkdir(IMAGE_DIR, { recursive: true });
  const name = `${cardId}-${Date.now().toString(36)}.webp`;
  await writeFile(path.join(IMAGE_DIR, name), buf, { flag: "wx" });
  return `/images/categories/${name}`;
}
