/**
 * Pure layout math for the looks hero — no React, no animation library.
 *
 * Every figure is drawn at a "base" height (its size in the detail view) and
 * placed by three poses that the hero blends between:
 *   ring   → figures on an ellipse; front is large and sharp, the back is
 *            smaller, higher, softer (blurred, faded) — depth on a flat page;
 *   grid   → a tidy catalogue of all looks;
 *   detail → the chosen look alone, beside its receipt panel.
 * `y` is always the FEET position (figures scale from bottom-centre), and `x`
 * is the offset from the stage centre. RTL: the next look sits to the LEFT.
 */

export type Stage = { w: number; h: number };

export type Pose = {
  x: number;
  y: number;
  scale: number;
  blur: number;
  opacity: number;
  z: number;
};

export type Metrics = {
  compact: boolean;
  /** Figure height (px) at scale 1 — the size used in the detail view. */
  baseH: number;
  frontH: number;
  feetY: number;
  backRise: number;
  radiusX: number;
  /** Pixels of drag that move the ring by one look. */
  stepPx: number;
  /** Desktop receipt panel width, used to centre the detail figure. */
  panelW: number;
};

export const COMPACT_BREAKPOINT = 768;

export function metrics({ w, h }: Stage): Metrics {
  const compact = w < COMPACT_BREAKPOINT;
  const bottomUi = compact ? 150 : 112;
  const baseH = compact ? h * 0.5 : h * 0.8;
  const frontH = compact ? Math.min(h * 0.52, w * 1.15) : h * 0.64;
  const radiusX = compact ? w * 0.74 : Math.min(w * 0.34, 640);
  return {
    compact,
    baseH,
    frontH,
    feetY: h - bottomUi - (compact ? 12 : 18),
    backRise: compact ? h * 0.17 : h * 0.3,
    radiusX,
    stepPx: Math.max(120, radiusX * 0.75),
    panelW: Math.min(400, w * 0.34),
  };
}

const mod = (n: number, m: number) => ((n % m) + m) % m;

/** Signed distance (in looks) of index i from the ring position r. */
export function ringOffset(i: number, r: number, n: number): number {
  const d = mod(i - r, n);
  return d > n / 2 ? d - n : d;
}

export function ringPose(i: number, r: number, n: number, m: Metrics): Pose {
  const theta = (ringOffset(i, r, n) * 2 * Math.PI) / n;
  const t = (Math.cos(theta) + 1) / 2; // 1 = front, 0 = back
  const depthScale = 0.36 + 0.64 * Math.pow(t, 2.2);
  return {
    x: -Math.sin(theta) * m.radiusX,
    y: m.feetY - (1 - t) * m.backRise,
    scale: (m.frontH / m.baseH) * depthScale,
    blur: m.compact ? (1 - t) * 2.5 : Math.pow(1 - t, 1.2) * 8,
    opacity: 0.42 + 0.58 * t,
    z: Math.round(t * 1000),
  };
}

export type GridCell = { cx: number; feetY: number; figH: number; labelY: number };

export function gridCell(i: number, n: number, { w, h }: Stage, m: Metrics): GridCell {
  const cols = m.compact ? 3 : 4;
  const rows = Math.ceil(n / cols);
  const top = m.compact ? 70 : 92;
  const bottom = m.compact ? 40 : 64;
  const pad = m.compact ? 8 : Math.max(32, w * 0.08);
  const cellW = (w - pad * 2) / cols;
  const cellH = (h - top - bottom) / rows;
  const row = Math.floor(i / cols);
  const col = i % cols;
  const inRow = Math.min(cols, n - row * cols);
  const rowShift = ((cols - inRow) * cellW) / 2;
  // RTL: column 0 is on the right.
  const cxAbs = w - pad - rowShift - (col + 0.5) * cellW;
  const labelSpace = m.compact ? 30 : 34;
  const feetY = top + (row + 1) * cellH - labelSpace;
  return {
    cx: cxAbs - w / 2,
    feetY,
    figH: Math.min(cellH - labelSpace - 10, cellW * 1.6),
    labelY: feetY + 8,
  };
}

export function gridPose(i: number, n: number, stage: Stage, m: Metrics): Pose {
  const c = gridCell(i, n, stage, m);
  return { x: c.cx, y: c.feetY, scale: c.figH / m.baseH, blur: 0, opacity: 1, z: 500 };
}

/** `heightScale` = the look's scale in the scene (detail shows every look full-size). */
export function detailPose({ w, h }: Stage, m: Metrics, heightScale: number): Pose {
  if (m.compact) {
    // Feet just above the receipt sheet (top 54% of the screen is free);
    // 0.84 leaves room for the look title above the head.
    return { x: 0, y: h * 0.535, scale: 0.84 / heightScale, blur: 0, opacity: 1, z: 2000 };
  }
  // Centre of the space left of the panel (panel sits on the left in RTL).
  const freeCentre = (24 + m.panelW + w) / 2;
  return { x: freeCentre - w / 2, y: h * 0.93, scale: 1 / Math.max(heightScale, 0.85), blur: 0, opacity: 1, z: 2000 };
}

const lerp = (a: number, b: number, t: number) => a + (b - a) * t;

export function mixPose(a: Pose, b: Pose, t: number): Pose {
  if (t <= 0) return a;
  if (t >= 1) return b;
  return {
    x: lerp(a.x, b.x, t),
    y: lerp(a.y, b.y, t),
    scale: lerp(a.scale, b.scale, t),
    blur: lerp(a.blur, b.blur, t),
    opacity: lerp(a.opacity, b.opacity, t),
    z: t < 0.5 ? a.z : b.z,
  };
}

/** Per-figure stagger so ring ⇄ grid feels like people walking to places. */
export function stagger(g: number, i: number, n: number, k = 0.05): number {
  const v = g * (1 + k * (n - 1)) - k * i;
  return Math.min(1, Math.max(0, v));
}

export { mod };
