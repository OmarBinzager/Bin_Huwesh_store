/**
 * Browser-side preparation of an uploaded model photo, matching what
 * assets/looks/process_looks.py does for the generated set:
 * clear faint haze (alpha < 12), trim to the subject with a 2 % margin,
 * scale to 1400 px tall (never upscale), encode as WebP with alpha.
 */
export type ProcessedImage = {
  blob: Blob;
  width: number;
  height: number;
  /** Corner pixel is fully transparent and a real share of pixels is clear. */
  hasAlpha: boolean;
  transparentPct: number;
};

const TARGET_H = 1400;

export async function processLookImage(file: File): Promise<ProcessedImage> {
  const bmp = await createImageBitmap(file);
  const src = document.createElement("canvas");
  src.width = bmp.width;
  src.height = bmp.height;
  const ctx = src.getContext("2d", { willReadFrequently: true })!;
  ctx.drawImage(bmp, 0, 0);
  const img = ctx.getImageData(0, 0, src.width, src.height);
  const d = img.data;

  let x0 = src.width, y0 = src.height, x1 = -1, y1 = -1, clear = 0;
  for (let y = 0; y < src.height; y++) {
    for (let x = 0; x < src.width; x++) {
      const i = (y * src.width + x) * 4 + 3;
      if (d[i] < 12) d[i] = 0;
      if (d[i] === 0) clear++;
      if (d[i] > 16) {
        if (x < x0) x0 = x;
        if (x > x1) x1 = x;
        if (y < y0) y0 = y;
        if (y > y1) y1 = y;
      }
    }
  }
  ctx.putImageData(img, 0, 0);
  const transparentPct = Math.round((clear / (src.width * src.height)) * 1000) / 10;
  const hasAlpha = d[3] === 0 && transparentPct > 10;
  if (x1 < 0) throw new Error("الصورة فارغة");

  const pad = Math.round((y1 - y0) * 0.02);
  const cx = Math.max(0, x0 - pad), cy = Math.max(0, y0 - pad);
  const cw = Math.min(src.width, x1 + pad + 1) - cx;
  const ch = Math.min(src.height, y1 + pad + 1) - cy;
  const scale = Math.min(1, TARGET_H / ch);
  const out = document.createElement("canvas");
  out.width = Math.round(cw * scale);
  out.height = Math.round(ch * scale);
  const octx = out.getContext("2d")!;
  octx.imageSmoothingQuality = "high";
  octx.drawImage(src, cx, cy, cw, ch, 0, 0, out.width, out.height);

  const blob = await new Promise<Blob | null>((r) => out.toBlob(r, "image/webp", 0.86));
  if (!blob || blob.type !== "image/webp") throw new Error("المتصفح لا يدعم حفظ WebP — استخدم Chrome أو Edge");
  return { blob, width: out.width, height: out.height, hasAlpha, transparentPct };
}
