// Server-side only (node:fs) — imported by the admin route handlers and page.
import { randomUUID } from "node:crypto";
import { mkdir, readFile, rename, writeFile } from "node:fs/promises";
import path from "node:path";
import type { Look } from "@/content/types";

/**
 * File-backed store for the hero looks, used ONLY by the local admin tool
 * (/admin/looks) while developing. It reads and writes the same
 * src/content/looks.json the site imports, so a save is what the site shows.
 * When the partner company's admin panel is connected, swap these two
 * functions for its API and keep the same Look shape.
 */
const ROOT = process.cwd();
export const LOOKS_FILE = path.join(ROOT, "src", "content", "looks.json");
export const LOOKS_IMAGE_DIR = path.join(ROOT, "public", "images", "looks");

export type LooksFile = { version: 1; looks: Look[] };

export async function readLooks(): Promise<LooksFile> {
  return JSON.parse(await readFile(LOOKS_FILE, "utf8")) as LooksFile;
}

/** Validates, renumbers `order` by position, and writes atomically. */
export async function writeLooks(input: unknown): Promise<LooksFile> {
  const looks = validate(input);
  const data: LooksFile = { version: 1, looks: looks.map((l, i) => ({ ...l, order: i + 1 })) };
  const tmp = `${LOOKS_FILE}.${randomUUID()}.tmp`;
  await writeFile(tmp, JSON.stringify(data, null, 2) + "\n", "utf8");
  await rename(tmp, LOOKS_FILE);
  return data;
}

const ID = /^[a-z0-9][a-z0-9-]{0,63}$/;

export function safeId(id: string): string {
  if (!ID.test(id)) throw new ValidationError(`معرّف غير صالح: ${id}`);
  return id;
}

export async function saveLookImage(lookId: string, bytes: Uint8Array): Promise<string> {
  safeId(lookId);
  // WebP magic: "RIFF....WEBP"
  const head = Buffer.from(bytes.subarray(0, 12)).toString("latin1");
  if (!head.startsWith("RIFF") || head.slice(8, 12) !== "WEBP") throw new ValidationError("الصورة يجب أن تكون WebP");
  await mkdir(LOOKS_IMAGE_DIR, { recursive: true });
  // Timestamped name: replacing an image never fights the browser cache.
  const name = `${lookId}-${Date.now().toString(36)}.webp`;
  await writeFile(path.join(LOOKS_IMAGE_DIR, name), bytes);
  return `/images/looks/${name}`;
}

export class ValidationError extends Error {}

const isStr = (v: unknown, max = 400): v is string => typeof v === "string" && v.length <= max;
const isNum = (v: unknown, min: number, max: number): v is number =>
  typeof v === "number" && Number.isFinite(v) && v >= min && v <= max;

function validate(input: unknown): Look[] {
  const list = (input as { looks?: unknown })?.looks;
  if (!Array.isArray(list)) throw new ValidationError("صيغة غير صالحة: looks مفقودة");
  const ids = new Set<string>();
  return list.map((raw, i): Look => {
    const l = raw as Look;
    const where = `الإطلالة ${i + 1}`;
    if (!isStr(l.id, 64) || !ID.test(l.id)) throw new ValidationError(`${where}: معرّف غير صالح`);
    if (ids.has(l.id)) throw new ValidationError(`${where}: معرّف مكرر ${l.id}`);
    ids.add(l.id);
    if (!isStr(l.title, 80) || !l.title.trim()) throw new ValidationError(`${where}: الاسم مطلوب`);
    if (!isStr(l.subtitle, 160)) throw new ValidationError(`${where}: الوصف غير صالح`);
    const img = l.image;
    if (!img || !isStr(img.src, 300)) throw new ValidationError(`${where}: الصورة غير صالحة`);
    if (img.src && !img.src.startsWith("/images/looks/")) throw new ValidationError(`${where}: مسار الصورة غير مسموح`);
    if (l.published && !img.src) throw new ValidationError(`${where}: لا يمكن نشر إطلالة بلا صورة`);
    if (!isNum(img.width, 0, 10000) || !isNum(img.height, 0, 10000)) throw new ValidationError(`${where}: أبعاد الصورة غير صالحة`);
    if (!isNum(img.scale, 0.3, 2)) throw new ValidationError(`${where}: الحجم خارج النطاق`);
    if (!isNum(img.offsetX, -50, 50) || !isNum(img.offsetY, -50, 50)) throw new ValidationError(`${where}: الإزاحة خارج النطاق`);
    if (l.wholeLookDiscount !== undefined && !isNum(l.wholeLookDiscount, 0, 90)) throw new ValidationError(`${where}: الخصم غير صالح`);
    if (!Array.isArray(l.items)) throw new ValidationError(`${where}: القطع غير صالحة`);
    const itemIds = new Set<string>();
    const items = l.items.map((it, j) => {
      const w = `${where}، القطعة ${j + 1}`;
      if (!isStr(it.id, 64) || !ID.test(it.id) || itemIds.has(it.id)) throw new ValidationError(`${w}: معرّف غير صالح أو مكرر`);
      itemIds.add(it.id);
      if (!isStr(it.label, 80) || !it.label.trim()) throw new ValidationError(`${w}: الاسم مطلوب`);
      if (!isStr(it.category, 40)) throw new ValidationError(`${w}: الفئة غير صالحة`);
      if (!it.hotspot || !isNum(it.hotspot.x, 0, 100) || !isNum(it.hotspot.y, 0, 100)) throw new ValidationError(`${w}: موضع العلامة غير صالح`);
      if (it.productId !== null && !isStr(it.productId, 80)) throw new ValidationError(`${w}: رقم المنتج غير صالح`);
      if (!isNum(it.demoPrice, 0, 100_000_000)) throw new ValidationError(`${w}: السعر غير صالح`);
      if (!Array.isArray(it.sizes) || !it.sizes.every((s) => isStr(s, 24))) throw new ValidationError(`${w}: المقاسات غير صالحة`);
      return {
        id: it.id,
        label: it.label.trim(),
        category: it.category.trim(),
        hotspot: { x: round1(it.hotspot.x), y: round1(it.hotspot.y) },
        productId: it.productId ? it.productId.trim() : null,
        demoPrice: Math.round(it.demoPrice),
        sizes: it.sizes.map((s) => s.trim()).filter(Boolean),
      };
    });
    return {
      id: l.id,
      order: i + 1,
      published: Boolean(l.published),
      title: l.title.trim(),
      subtitle: l.subtitle.trim(),
      image: {
        src: img.src,
        width: Math.round(img.width),
        height: Math.round(img.height),
        alt: isStr(img.alt) ? img.alt.trim() : "",
        scale: round2(img.scale),
        offsetX: round1(img.offsetX),
        offsetY: round1(img.offsetY),
        isPreview: Boolean(img.isPreview),
      },
      items,
      ...(l.wholeLookDiscount ? { wholeLookDiscount: Math.round(l.wholeLookDiscount) } : {}),
      ...(isStr(l.href) && l.href ? { href: l.href } : {}),
    };
  });
}

const round1 = (n: number) => Math.round(n * 10) / 10;
const round2 = (n: number) => Math.round(n * 100) / 100;
