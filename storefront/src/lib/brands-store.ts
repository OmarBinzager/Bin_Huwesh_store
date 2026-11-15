// Server-side only (node:fs) — used by the local admin routes for the brand strip.
import { randomUUID } from "node:crypto";
import { mkdir, readFile, rename, writeFile } from "node:fs/promises";
import path from "node:path";
import type { Brand, BrandsFile } from "@/content/brand-types";
import { ValidationError, safeId } from "./looks-store";

/**
 * File-backed store for the brand strip (src/content/brands.json — the file
 * the home page imports). Same rules as the looks store: validate, write
 * atomically, keep ids stable. Swap for the company's admin API later.
 */
const ROOT = process.cwd();
const BRANDS_FILE = path.join(ROOT, "src", "content", "brands.json");
const LOGO_DIR = path.join(ROOT, "public", "images", "brands");

export async function readBrands(): Promise<BrandsFile> {
  return JSON.parse(await readFile(BRANDS_FILE, "utf8")) as BrandsFile;
}

const isStr = (v: unknown, max = 400): v is string => typeof v === "string" && v.length <= max;
const isNum = (v: unknown, min: number, max: number): v is number =>
  typeof v === "number" && Number.isFinite(v) && v >= min && v <= max;
const LOGO_PATH = /^\/images\/brands\/[a-z0-9-]+\.(svg|webp)$/;

export async function writeBrands(input: unknown): Promise<BrandsFile> {
  const raw = input as Partial<BrandsFile>;
  const s = raw?.settings;
  if (!s) throw new ValidationError("الإعدادات مفقودة");
  if (!isStr(s.title, 60)) throw new ValidationError("عنوان الشريط غير صالح");
  if (!isNum(s.speed, 5, 200)) throw new ValidationError("السرعة يجب أن تكون بين 5 و200");
  if (s.direction !== "left" && s.direction !== "right") throw new ValidationError("الاتجاه غير صالح");
  if (!isNum(s.logoSize, 16, 80)) throw new ValidationError("حجم الشعار يجب أن يكون بين 16 و80");
  if (!Array.isArray(raw.brands)) throw new ValidationError("قائمة الماركات مفقودة");

  const ids = new Set<string>();
  const brands = raw.brands.map((b: Brand, i): Brand => {
    const where = `الماركة ${i + 1}`;
    if (!isStr(b.id, 64)) throw new ValidationError(`${where}: معرّف غير صالح`);
    safeId(b.id);
    if (ids.has(b.id)) throw new ValidationError(`${where}: معرّف مكرر`);
    ids.add(b.id);
    if (!isStr(b.name, 60) || !b.name.trim()) throw new ValidationError(`${where}: الاسم مطلوب`);
    if (!isStr(b.logo) || !LOGO_PATH.test(b.logo)) throw new ValidationError(`${where}: مسار الشعار غير صالح`);
    if (b.colorLogo && !LOGO_PATH.test(b.colorLogo)) throw new ValidationError(`${where}: مسار الشعار الملوّن غير صالح`);
    if (!isNum(b.aspect, 0.2, 40)) throw new ValidationError(`${where}: نسبة الشعار غير صالحة`);
    if (b.mode !== "mono" && b.mode !== "color") throw new ValidationError(`${where}: نوع العرض غير صالح`);
    if (!isNum(b.scale, 0.4, 2)) throw new ValidationError(`${where}: الحجم خارج النطاق`);
    return {
      id: b.id,
      name: b.name.trim(),
      logo: b.logo,
      ...(b.colorLogo ? { colorLogo: b.colorLogo } : {}),
      aspect: Math.round(b.aspect * 10000) / 10000,
      mode: b.mode,
      scale: Math.round(b.scale * 100) / 100,
      visible: Boolean(b.visible),
      source: isStr(b.source, 600) ? b.source.trim() : "",
    };
  });

  const data: BrandsFile = {
    version: 1,
    settings: {
      title: s.title.trim() || "ماركات عالمية",
      showTitle: Boolean(s.showTitle),
      speed: Math.round(s.speed),
      direction: s.direction,
      logoSize: Math.round(s.logoSize),
    },
    brands,
  };
  const tmp = `${BRANDS_FILE}.${randomUUID()}.tmp`;
  await writeFile(tmp, JSON.stringify(data, null, 2) + "\n", "utf8");
  await rename(tmp, BRANDS_FILE);
  return data;
}

/** Saves an already-processed logo (SVG or WebP). Returns its public path. */
export async function saveBrandLogo(brandId: string, variant: "mono" | "color", bytes: Uint8Array): Promise<string> {
  safeId(brandId);
  const buf = Buffer.from(bytes);
  const head = buf.subarray(0, 12).toString("latin1");
  let ext: "svg" | "webp";
  if (head.startsWith("RIFF") && head.slice(8, 12) === "WEBP") ext = "webp";
  else {
    const text = buf.toString("utf8");
    if (!/^\s*(<\?xml[^>]*>\s*)?<svg[\s>]/i.test(text)) throw new ValidationError("الشعار يجب أن يكون SVG أو WebP");
    // Defence in depth — the admin already sanitises before upload.
    if (/<script|<foreignObject|javascript:|\son[a-z]+\s*=/i.test(text)) throw new ValidationError("ملف SVG يحتوي على عناصر غير مسموحة");
    ext = "svg";
  }
  await mkdir(LOGO_DIR, { recursive: true });
  const name = `${brandId}${variant === "color" ? "-color" : ""}-${Date.now().toString(36)}.${ext}`;
  await writeFile(path.join(LOGO_DIR, name), buf);
  return `/images/brands/${name}`;
}
