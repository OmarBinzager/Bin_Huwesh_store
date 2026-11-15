/**
 * Browser-side preparation of an uploaded brand logo — the same steps used
 * for the initial set (see assets/brands/SOURCES.md):
 *  SVG  → sanitise, trim the viewBox to the drawing, build a single-colour
 *         "mono" version where light parts of a two-tone logo become holes
 *         (so white text inside a red badge stays readable), keep a trimmed
 *         original-colour copy.
 *  PNG/WebP → must have real transparency; trimmed and re-encoded as WebP
 *         (its alpha channel is the mono mask, so one file serves both).
 */
export type ProcessedLogo = { mono: Blob; color: Blob | null; aspect: number; warning?: string };

export async function processBrandLogo(file: File): Promise<ProcessedLogo> {
  const isSvg = file.type === "image/svg+xml" || file.name.toLowerCase().endsWith(".svg");
  return isSvg ? processSvg(await file.text()) : processRaster(file);
}

function sanitise(svg: SVGSVGElement) {
  svg.querySelectorAll("script, foreignObject, metadata, title, desc").forEach((n) => n.remove());
  for (const n of [svg, ...svg.querySelectorAll("*")]) {
    for (const a of [...n.attributes]) {
      const name = a.name.toLowerCase();
      const val = a.value.trim().toLowerCase();
      if (name.startsWith("on")) n.removeAttribute(a.name);
      else if ((name === "href" || name === "xlink:href") && !val.startsWith("#")) n.removeAttribute(a.name);
      else if (/^(sodipodi|inkscape):/.test(name) || name.startsWith("data-")) n.removeAttribute(a.name);
      else if (val.includes("javascript:")) n.removeAttribute(a.name);
    }
  }
}

async function processSvg(text: string): Promise<ProcessedLogo> {
  const doc = new DOMParser().parseFromString(text, "image/svg+xml");
  const parsed = doc.documentElement;
  if (parsed.nodeName.toLowerCase() !== "svg") throw new Error("ملف SVG غير صالح");

  // Measure inside a hidden live node so getBBox / computed styles work.
  const host = document.createElement("div");
  host.style.cssText = "position:fixed;left:-10000px;top:0;width:600px;visibility:hidden";
  document.body.appendChild(host);
  const svg = document.importNode(parsed, true) as unknown as SVGSVGElement;
  host.appendChild(svg);
  try {
    sanitise(svg);
    svg.removeAttribute("width");
    svg.removeAttribute("height");
    svg.style.width = "600px";
    const bb = svg.getBBox();
    if (!bb.width || !bb.height) throw new Error("الشعار فارغ");
    const pad = Math.max(bb.width, bb.height) * 0.01;
    const vb = [bb.x - pad, bb.y - pad, bb.width + 2 * pad, bb.height + 2 * pad].map((v) => +v.toFixed(3));
    svg.setAttribute("viewBox", vb.join(" "));

    const SHAPES = "path, rect, circle, ellipse, polygon, polyline, line, text";
    const shapes = [...svg.querySelectorAll(SHAPES)].filter((n) => !n.closest("defs, clipPath, mask"));
    const lum = (c: string) => {
      const m = c.match(/rgba?\(([^)]+)\)/);
      if (!m) return null;
      const [r, g, b, a = 1] = m[1].split(",").map(Number);
      return a === 0 ? null : (0.2126 * r + 0.7152 * g + 0.0722 * b) / 255;
    };
    const info = shapes.map((n) => {
      const cs = getComputedStyle(n);
      return { fill: cs.fill === "none" ? null : lum(cs.fill), stroke: cs.stroke === "none" ? null : lum(cs.stroke) };
    });
    const isDark = (v: number | null) => v !== null && v < 0.85;
    const isLight = (v: number | null) => v !== null && v >= 0.85;
    const knockout = info.some((i) => isDark(i.fill) || isDark(i.stroke)) && info.some((i) => isLight(i.fill) || isLight(i.stroke));

    svg.style.removeProperty("width");
    const color = svg.outerHTML;

    const clone = svg.cloneNode(true) as SVGSVGElement;
    clone.querySelectorAll("style").forEach((s) => s.remove());
    [...clone.querySelectorAll(SHAPES)]
      .filter((n) => !n.closest("defs, clipPath, mask"))
      .forEach((n, k) => {
        const paint = (L: number) => (knockout && L >= 0.85 ? "#000" : "#fff");
        n.removeAttribute("style");
        n.removeAttribute("class");
        n.setAttribute("fill", info[k].fill === null ? "none" : paint(info[k].fill!));
        if (info[k].stroke !== null) n.setAttribute("stroke", paint(info[k].stroke!));
        else n.removeAttribute("stroke");
      });
    const [x, y, w, h] = vb;
    const mono =
      `<svg xmlns="http://www.w3.org/2000/svg" viewBox="${vb.join(" ")}"><defs><mask id="k" maskUnits="userSpaceOnUse" x="${x}" y="${y}" width="${w}" height="${h}">` +
      clone.innerHTML +
      `</mask></defs><rect x="${x}" y="${y}" width="${w}" height="${h}" fill="#000" mask="url(#k)"/></svg>`;

    return {
      mono: new Blob([mono], { type: "image/svg+xml" }),
      color: new Blob([color], { type: "image/svg+xml" }),
      aspect: +(w / h).toFixed(4),
    };
  } finally {
    host.remove();
  }
}

async function processRaster(file: File): Promise<ProcessedLogo> {
  const bmp = await createImageBitmap(file);
  const c = document.createElement("canvas");
  c.width = bmp.width;
  c.height = bmp.height;
  const ctx = c.getContext("2d", { willReadFrequently: true })!;
  ctx.drawImage(bmp, 0, 0);
  const { data } = ctx.getImageData(0, 0, c.width, c.height);
  let x0 = c.width, y0 = c.height, x1 = -1, y1 = -1, clear = 0;
  for (let y = 0; y < c.height; y++)
    for (let x = 0; x < c.width; x++) {
      const a = data[(y * c.width + x) * 4 + 3];
      if (a < 8) clear++;
      else {
        if (x < x0) x0 = x;
        if (x > x1) x1 = x;
        if (y < y0) y0 = y;
        if (y > y1) y1 = y;
      }
    }
  const pct = clear / (c.width * c.height);
  if (data[3] !== 0 || pct < 0.05) throw new Error("الصورة بلا خلفية شفافة حقيقية — استخدم SVG أو PNG شفاف");
  const w = x1 - x0 + 1, h = y1 - y0 + 1;
  const scale = Math.min(1, 400 / h);
  const out = document.createElement("canvas");
  out.width = Math.round(w * scale);
  out.height = Math.round(h * scale);
  out.getContext("2d")!.drawImage(c, x0, y0, w, h, 0, 0, out.width, out.height);
  const blob = await new Promise<Blob | null>((r) => out.toBlob(r, "image/webp", 0.92));
  if (!blob || blob.type !== "image/webp") throw new Error("المتصفح لا يدعم حفظ WebP — استخدم Chrome أو Edge");
  return { mono: blob, color: blob, aspect: +(w / h).toFixed(4), warning: "صورة نقطية: يُفضّل SVG للحفاظ على الحدّة." };
}
