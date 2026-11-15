/**
 * Arc maths for the «وصل حديثًا» try-on arc (VirtualFit reference, §5),
 * expressed in the SCENE's own pixels instead of the viewport's, and with a
 * direction sign so RTL places the next garment on the left.
 */

/** Horizontal distance table (× scene width) for |u| = 0…5 cards. */
const XS_WIDE = [0, 0.215, 0.345, 0.432, 0.49, 0.52];
const XS_NARROW = [0, 0.36, 0.5, 0.56, 0.6, 0.62];

export const isNarrow = (w: number) => w < 768;

export function xsTable(w: number): number[] {
  return (isNarrow(w) ? XS_NARROW : XS_WIDE).map((k) => k * w);
}

/** Linear interpolation through the XS table. */
export function xAt(a: number, xs: number[]): number {
  if (a <= 0) return 0;
  const i = Math.min(xs.length - 2, Math.floor(a));
  const f = Math.min(1, a - i);
  return xs[i] + (xs[i + 1] - xs[i]) * f;
}

/** Garment card width for a scene of w × h (reference: min(14.8vw,33vh) · phones min(30vw,170px)). */
export function cardWidth(w: number, h: number): number {
  return isNarrow(w) ? Math.min(w * 0.3, 170) : Math.min(w * 0.148, h * 0.33);
}

export const mod = (a: number, n: number) => ((a % n) + n) % n;

/**
 * Slot layout. With enough items the arc LOOPS over copies of the list (the
 * reference shows 5 items as 10 cards); with 1–3 items it is a straight row,
 * so no garment is ever shown twice side by side.
 */
export function slotPlan(n: number): { slots: number; loop: boolean } {
  if (n >= 4) return { slots: n * Math.ceil(8 / n), loop: true };
  return { slots: n, loop: false };
}

/** Signed distance of slot i from the scroll position. */
export function offset(i: number, pos: number, slots: number, loop: boolean): number {
  return loop ? mod(i - pos + slots / 2, slots) - slots / 2 : i - pos;
}

export type CardPose = {
  x: number;
  y: number;
  z: number;
  ry: number;
  rx: number;
  scale: number;
  blur: number;
  opacity: number;
  zIndex: number;
  hidden: boolean;
};

/**
 * One card's pose (reference §5).
 * @param u    signed slot offset
 * @param dir  +1 LTR (next on the right), −1 RTL (next on the left)
 * @param ie   intro ease 0…1
 * @param bobT seconds (0 when motion is reduced)
 * @param sx,sy smoothed pointer offsets in [−.5, .5]
 */
export function pose(u: number, opts: { dir: number; xs: number[]; h: number; ie: number; bobT: number; seed: number; sx: number; sy: number }): CardPose {
  const { dir, xs, h, ie, bobT, seed, sx, sy } = opts;
  const a = Math.abs(u);
  const sg = Math.sign(u) * dir;
  let s: number, z: number, ry: number, op: number, blur = 0;
  if (a < 1) {
    s = 0.55 + 0.45 * a;
    z = -260 * (1 - a);
    op = a;
    ry = -sg * 16 * a;
  } else {
    s = 1 / (1 + 0.3 * Math.pow(a - 1, 1.15));
    z = -110 * (a - 1);
    ry = -sg * (16 + 9 * Math.min(2, a - 1));
    op = 1;
    blur = Math.max(0, a - 1.6) * 2.4;
  }
  if (a > 3.4) op = Math.min(op, 1 - (a - 3.4) / 0.8);
  const bob = Math.sin(bobT * 1.1 + seed) * h * 0.006 * Math.min(1, a);
  const y = bob - (1 - s) * h * 0.04;
  ry += sx * 10;
  const opacity = Math.max(0, op) * ie;
  return {
    x: sg * xAt(a, xs) * ie,
    y,
    z,
    ry,
    rx: -sy * 6,
    scale: s * (0.6 + 0.4 * ie),
    blur,
    opacity,
    zIndex: a < 1 ? 1 : Math.round(100 - 10 * a),
    hidden: opacity < 0.01,
  };
}

/** Frame-rate independent version of `v += (target − v) × k` (k tuned for 60 fps). */
export function approach(v: number, target: number, k: number, dt: number): number {
  return v + (target - v) * (1 - Math.pow(1 - k, dt * 60));
}
