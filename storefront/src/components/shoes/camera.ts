/**
 * The "camera" over the shoe shelves: one CSS transform on the world layer.
 *
 * A world point W (the current shoe's centre) is drawn at screen point P with
 * scale s:   screen = P + s · (world − W)   →   translate(P − s·W) scale(s)
 *
 * Only P and log(s) are animated (critically damped springs, time based), so
 * the chosen shoe travels on a straight line while it grows, retargeting
 * mid-flight stays smooth, and the speed is the same on every screen.
 * Changing the subject (next / previous) moves W and compensates P so the
 * frame does not jump; the spring then pans to the new shoe.
 */
export type Vec = { x: number; y: number };

export class Camera {
  private W: Vec = { x: 0, y: 0 };
  private P: Vec = { x: 0, y: 0 };
  private ls = 0;
  private v = { x: 0, y: 0, ls: 0 };
  private T = { x: 0, y: 0, ls: 0 };
  private omega = 7;
  private raf = 0;
  private last = 0;

  constructor(
    private readonly el: HTMLElement,
    private readonly onSettle?: (atRest: boolean) => void,
  ) {}

  get scale() {
    return Math.exp(this.ls);
  }

  /** True when the camera shows the shelves unzoomed (identity transform). */
  get atRest() {
    return Math.abs(this.ls) < 1e-4 && Math.abs(this.P.x - this.W.x) < 0.01 && Math.abs(this.P.y - this.W.y) < 0.01;
  }

  /** Follow another world point without moving the picture (always followed by aim() or rest()). */
  setSubject(w: Vec) {
    const s = this.scale;
    this.P = { x: this.P.x + s * (w.x - this.W.x), y: this.P.y + s * (w.y - this.W.y) };
    this.W = { ...w };
  }

  /** Put the subject at screen point p with scale `scale`. */
  aim(p: Vec, scale: number, omega: number, instant = false) {
    this.T = { x: p.x, y: p.y, ls: Math.log(scale) };
    this.omega = omega;
    if (instant) {
      this.P = { x: p.x, y: p.y };
      this.ls = this.T.ls;
      this.v = { x: 0, y: 0, ls: 0 };
      this.stop();
      this.write();
      this.onSettle?.(this.atRest);
      return;
    }
    this.start();
  }

  /** Back to the plain shelves (identity transform). */
  rest(omega: number, instant = false) {
    this.aim({ ...this.W }, 1, omega, instant);
  }

  private start() {
    if (this.raf) return;
    this.last = performance.now();
    const tick = (now: number) => {
      // Time based; a long frame (tab switch) is capped so nothing teleports.
      const dt = Math.min(0.1, (now - this.last) / 1000);
      this.last = now;
      const w = this.omega;
      const e = Math.exp(-w * dt);
      const step = (x: number, v: number, t: number) => {
        const d = x - t;
        return [t + (d + (v + w * d) * dt) * e, (v - w * (v + w * d) * dt) * e] as const;
      };
      [this.P.x, this.v.x] = step(this.P.x, this.v.x, this.T.x);
      [this.P.y, this.v.y] = step(this.P.y, this.v.y, this.T.y);
      [this.ls, this.v.ls] = step(this.ls, this.v.ls, this.T.ls);
      const done =
        Math.abs(this.P.x - this.T.x) < 0.05 &&
        Math.abs(this.P.y - this.T.y) < 0.05 &&
        Math.abs(this.ls - this.T.ls) < 1e-4 &&
        Math.abs(this.v.x) + Math.abs(this.v.y) < 0.5 &&
        Math.abs(this.v.ls) < 1e-3;
      if (done) {
        this.P = { x: this.T.x, y: this.T.y };
        this.ls = this.T.ls;
        this.v = { x: 0, y: 0, ls: 0 };
        this.raf = 0;
        this.write();
        this.onSettle?.(this.atRest);
        return;
      }
      this.write();
      this.raf = requestAnimationFrame(tick);
    };
    this.raf = requestAnimationFrame(tick);
  }

  stop() {
    if (this.raf) cancelAnimationFrame(this.raf);
    this.raf = 0;
  }

  /** Jump straight to the target (used when the tab is hidden or on resize). */
  finish() {
    if (!this.raf) return;
    this.stop();
    this.P = { x: this.T.x, y: this.T.y };
    this.ls = this.T.ls;
    this.v = { x: 0, y: 0, ls: 0 };
    this.write();
    this.onSettle?.(this.atRest);
  }

  private write() {
    if (this.atRest) {
      this.el.style.transform = "";
      return;
    }
    const s = this.scale;
    this.el.style.transform = `translate3d(${(this.P.x - s * this.W.x).toFixed(2)}px, ${(this.P.y - s * this.W.y).toFixed(2)}px, 0) scale(${s.toFixed(5)})`;
  }
}
