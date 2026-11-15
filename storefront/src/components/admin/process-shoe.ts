/**
 * Browser-side framing of a shoe cut-out for «أحذية تكمل إطلالتك» — the same
 * rules as assets/shoes/process_shoes.py, so admin uploads sit on the shelves
 * exactly like the originals:
 *   trim to the visible pixels → uniform scale into 1180×620 (never stretched)
 *   → centred on a 1400×800 transparent canvas with the sole on y = 760.
 * The image MUST already be transparent (side profile, toe pointing left).
 */
export type ProcessedShoe = { blob: Blob; width: number; height: number };

const W = 1400;
const H = 800;
const BASE = 760;
const MAX_LEN = 1180;
const MAX_H = 620;

export async function processShoe(file: File): Promise<ProcessedShoe> {
  const bmp = await createImageBitmap(file);
  if (Math.min(bmp.width, bmp.height) < 200) {
    bmp.close();
    throw new Error("الصورة صغيرة جدًا");
  }
  const src = document.createElement("canvas");
  src.width = bmp.width;
  src.height = bmp.height;
  const sctx = src.getContext("2d", { willReadFrequently: true })!;
  sctx.drawImage(bmp, 0, 0);
  bmp.close();

  const { data } = sctx.getImageData(0, 0, src.width, src.height);
  const a = (x: number, y: number) => data[(y * src.width + x) * 4 + 3];
  const w = src.width - 1;
  const h = src.height - 1;
  const opaqueCorners = [a(0, 0), a(w, 0), a(0, h), a(w, h)].filter((v) => v > 16).length;
  if (opaqueCorners >= 3) throw new Error("صورة الحذاء يجب أن تكون بخلفية شفافة (PNG أو WebP مقصوصة)");

  // bounds of the visible shoe
  let x0 = src.width, y0 = src.height, x1 = -1, y1 = -1;
  for (let y = 0; y < src.height; y++) {
    for (let x = 0; x < src.width; x++) {
      if (data[(y * src.width + x) * 4 + 3] > 8) {
        if (x < x0) x0 = x;
        if (x > x1) x1 = x;
        if (y < y0) y0 = y;
        if (y > y1) y1 = y;
      }
    }
  }
  if (x1 < 0) throw new Error("الصورة شفافة بالكامل");
  const bw = x1 - x0 + 1;
  const bh = y1 - y0 + 1;
  if (bw / bh < 1.1) throw new Error("صورة الحذاء يجب أن تكون جانبية (أعرض من ارتفاعها)");

  const scale = Math.min(MAX_LEN / bw, MAX_H / bh);
  const dw = Math.round(bw * scale);
  const dh = Math.round(bh * scale);
  const out = document.createElement("canvas");
  out.width = W;
  out.height = H;
  const ctx = out.getContext("2d")!;
  ctx.imageSmoothingQuality = "high";
  ctx.drawImage(src, x0, y0, bw, bh, Math.round((W - dw) / 2), BASE - dh, dw, dh);

  const blob = await new Promise<Blob | null>((r) => out.toBlob(r, "image/webp", 0.9));
  if (!blob || blob.type !== "image/webp") throw new Error("المتصفح لا يدعم حفظ WebP — استخدم Chrome أو Edge");
  return { blob, width: W, height: H };
}
