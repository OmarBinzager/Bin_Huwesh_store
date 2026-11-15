// Server-side only (node:fs) — used by the local admin routes for the shoe shelves.
import { randomUUID } from "node:crypto";
import { access, mkdir, readFile, rename, writeFile } from "node:fs/promises";
import path from "node:path";
import { SHOE_SIZES, type Shoe, type ShoeColor, type ShoeSize, type ShoesFile } from "@/content/shoes-types";
import { ValidationError, safeId } from "./looks-store";

/**
 * File-backed store for src/content/shoes.json (the file the home page
 * imports). Same rules as the other admin stores: validate everything, check
 * that every referenced image exists, write atomically, keep ids stable.
 */
const ROOT = process.cwd();
const DATA_FILE = path.join(ROOT, "src", "content", "shoes.json");
const IMAGE_DIR = path.join(ROOT, "public", "images", "shoes");
const IMAGE_PATH = /^\/images\/shoes\/[a-z0-9-]+\.webp$/;
const SWATCH = /^#[0-9a-f]{6}$/i;
/** The shelves hold two rows of five on desktop. */
export const MAX_SHOES = 10;
const MAX_COLORS = 6;

export async function readShoes(): Promise<ShoesFile> {
  return JSON.parse(await readFile(DATA_FILE, "utf8")) as ShoesFile;
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

export async function writeShoes(input: unknown): Promise<ShoesFile> {
  const raw = input as Partial<ShoesFile>;
  const s = raw?.settings;
  if (!s) throw new ValidationError("الإعدادات مفقودة");
  if (!isStr(s.title, 60) || !s.title.trim()) throw new ValidationError("عنوان القسم مطلوب (حتى 60 حرفًا)");
  if (!isStr(s.subtitle, 140)) throw new ValidationError("النص المساعد طويل جدًا (حتى 140 حرفًا)");

  if (!Array.isArray(raw.shoes)) throw new ValidationError("قائمة الأحذية مفقودة");
  if (raw.shoes.length > MAX_SHOES) throw new ValidationError(`الحد الأقصى ${MAX_SHOES} أحذية (صفّان من خمسة)`);
  const ids = new Set<string>();
  const shoes: Shoe[] = [];
  for (const [i, it] of (raw.shoes as Shoe[]).entries()) {
    const where = `الحذاء ${i + 1}`;
    if (!isStr(it.id, 64)) throw new ValidationError(`${where}: معرّف غير صالح`);
    safeId(it.id);
    if (ids.has(it.id)) throw new ValidationError(`${where}: معرّف مكرر`);
    ids.add(it.id);
    if (!isStr(it.name, 40) || !it.name.trim()) throw new ValidationError(`${where}: الاسم مطلوب (حتى 40 حرفًا)`);
    if (!isStr(it.description, 160)) throw new ValidationError(`${where}: الوصف طويل جدًا (حتى 160 حرفًا)`);
    if (!isStr(it.alt, 160) || !it.alt.trim()) throw new ValidationError(`${where}: وصف الصورة مطلوب (حتى 160 حرفًا)`);
    if (!Number.isInteger(it.price) || it.price < 0 || it.price > 10_000_000) throw new ValidationError(`${where}: السعر يجب أن يكون رقمًا صحيحًا`);
    if (!Array.isArray(it.sizes) || it.sizes.length === 0) throw new ValidationError(`${where}: اختر مقاسًا واحدًا على الأقل`);
    const sizes = SHOE_SIZES.filter((z) => it.sizes.includes(z)) as ShoeSize[];
    if (sizes.length !== it.sizes.length) throw new ValidationError(`${where}: مقاس غير معروف`);
    if (!Array.isArray(it.colors) || it.colors.length === 0 || it.colors.length > MAX_COLORS)
      throw new ValidationError(`${where}: لكل حذاء لون مصوّر واحد على الأقل (حتى ${MAX_COLORS})`);
    const colorIds = new Set<string>();
    const colors: ShoeColor[] = [];
    for (const [j, c] of it.colors.entries()) {
      const cw = `${where}، اللون ${j + 1}`;
      if (!isStr(c.id, 40)) throw new ValidationError(`${cw}: معرّف غير صالح`);
      safeId(c.id);
      if (colorIds.has(c.id)) throw new ValidationError(`${cw}: معرّف مكرر`);
      colorIds.add(c.id);
      if (!isStr(c.name, 30) || !c.name.trim()) throw new ValidationError(`${cw}: اسم اللون مطلوب`);
      if (!isStr(c.swatch, 7) || !SWATCH.test(c.swatch)) throw new ValidationError(`${cw}: رمز اللون يجب أن يكون مثل #f4f2ee`);
      if (!isStr(c.image) || !IMAGE_PATH.test(c.image)) throw new ValidationError(`${cw}: ارفع صورة الحذاء`);
      if (!(await exists(c.image))) throw new ValidationError(`${cw}: صورة الحذاء غير موجودة في المجلد`);
      colors.push({ id: c.id, name: c.name.trim(), swatch: c.swatch.toLowerCase(), image: c.image });
    }
    shoes.push({
      id: it.id,
      name: it.name.trim(),
      description: it.description.trim(),
      price: it.price,
      sizes,
      colors,
      alt: it.alt.trim(),
      visible: Boolean(it.visible),
    });
  }

  const data: ShoesFile = { version: 1, settings: { title: s.title.trim(), subtitle: s.subtitle.trim() }, shoes };
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
export async function saveShoeImage(shoeId: string, colorId: string, bytes: Uint8Array): Promise<string> {
  safeId(shoeId);
  safeId(colorId);
  const buf = Buffer.from(bytes);
  const head = buf.subarray(0, 12).toString("latin1");
  if (!(head.startsWith("RIFF") && head.slice(8, 12) === "WEBP")) throw new ValidationError("الصورة يجب أن تكون WebP");
  if (!webpHasAlpha(buf)) throw new ValidationError("الصورة يجب أن تكون بخلفية شفافة");
  await mkdir(IMAGE_DIR, { recursive: true });
  const name = `${shoeId}-${colorId}-${Date.now().toString(36)}.webp`;
  await writeFile(path.join(IMAGE_DIR, name), buf, { flag: "wx" });
  return `/images/shoes/${name}`;
}
