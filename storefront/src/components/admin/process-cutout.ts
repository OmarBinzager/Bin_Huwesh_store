/**
 * Browser-side preparation of a «وصل حديثًا» cut-out: the garment (square-ish,
 * long side ≤ 800 px) or the model figure (portrait, height ≤ 1360 px, framed
 * like the originals: 524×1360, head to mid-thigh). Both MUST be transparent —
 * they are layered over the room and over each other. Encoded as WebP with alpha.
 */
export type CutoutKind = "garment" | "model";
export type ProcessedCutout = { blob: Blob; width: number; height: number };

export async function processCutout(file: File, kind: CutoutKind): Promise<ProcessedCutout> {
  const bmp = await createImageBitmap(file);
  const ratio = bmp.width / bmp.height;
  if (kind === "model" && (ratio < 0.3 || ratio > 0.5)) {
    bmp.close();
    throw new Error("صورة المودل يجب أن تكون طولية بنسبة 640×1360 أو 524×1360 (من الرأس إلى منتصف الفخذ)");
  }
  if (kind === "garment" && (ratio < 0.6 || ratio > 1.6)) {
    bmp.close();
    throw new Error("صورة القطعة يجب أن تكون قريبة من المربع");
  }
  const limit = kind === "model" ? 1360 / bmp.height : 800 / Math.max(bmp.width, bmp.height);
  const scale = Math.min(1, limit);
  const out = document.createElement("canvas");
  out.width = Math.round(bmp.width * scale);
  out.height = Math.round(bmp.height * scale);
  const ctx = out.getContext("2d")!;
  ctx.imageSmoothingQuality = "high";
  ctx.drawImage(bmp, 0, 0, out.width, out.height);
  bmp.close();
  if (Math.min(out.width, out.height) < 200) throw new Error("الصورة صغيرة جدًا");
  // transparency check: the four corners must be see-through
  const px = (x: number, y: number) => ctx.getImageData(x, y, 1, 1).data[3];
  const w = out.width - 1, h = out.height - 1;
  const opaqueCorners = [px(0, 0), px(w, 0), px(0, h), px(w, h)].filter((a) => a > 16).length;
  if (opaqueCorners >= 3) throw new Error("الصورة يجب أن تكون بخلفية شفافة (PNG أو WebP مقصوصة)");
  const blob = await new Promise<Blob | null>((r) => out.toBlob(r, "image/webp", 0.9));
  if (!blob || blob.type !== "image/webp") throw new Error("المتصفح لا يدعم حفظ WebP — استخدم Chrome أو Edge");
  return { blob, width: out.width, height: out.height };
}
