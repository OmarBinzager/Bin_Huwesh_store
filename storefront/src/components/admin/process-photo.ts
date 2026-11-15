/**
 * Browser-side preparation of a category-card photo: scale the long side to
 * at most 1800 px (never upscale) and encode as WebP. Unlike the looks
 * cut-outs there is no trimming — card photos are full-bleed scenes.
 */
export type ProcessedPhoto = { blob: Blob; width: number; height: number };

const MAX_SIDE = 1800;

export async function processCategoryPhoto(file: File): Promise<ProcessedPhoto> {
  const bmp = await createImageBitmap(file);
  const scale = Math.min(1, MAX_SIDE / Math.max(bmp.width, bmp.height));
  const out = document.createElement("canvas");
  out.width = Math.round(bmp.width * scale);
  out.height = Math.round(bmp.height * scale);
  const ctx = out.getContext("2d")!;
  ctx.imageSmoothingQuality = "high";
  ctx.drawImage(bmp, 0, 0, out.width, out.height);
  bmp.close();
  const blob = await new Promise<Blob | null>((r) => out.toBlob(r, "image/webp", 0.84));
  if (!blob || blob.type !== "image/webp") throw new Error("المتصفح لا يدعم حفظ WebP — استخدم Chrome أو Edge");
  if (out.width < 500 || out.height < 500) throw new Error("الصورة صغيرة جدًا — استخدم صورة لا يقل ضلعها عن 500px");
  return { blob, width: out.width, height: out.height };
}
