"use client"

import Image from "next/image"
import Link from "next/link"
import { useRouter } from "next/navigation"
import * as React from "react"

// A photo carousel seen through a curved lens. The cards sit on one WebGL canvas:
// the middle one reads flat, and the further a card travels from the centre, the
// more the lens bends it, with a faint fringe of chromatic split at the rim.
// Drag, flick, arrow keys or the buttons; it settles on a spring and loops.
//
// Adapted for Bin Huwesh from the 21st.dev "3d-curved-carousel" reference:
//  · A calm lens: gentle bend, side cards only slightly smaller than the active
//    one, a faint colour fringe, and cards fading out softly at the stage edges
//    (narrow screens therefore show the active card alone instead of slivers).
//  · RTL: the slide ORDER follows the reading direction (next card on the left);
//    the WebGL scene itself is not mirrored and drag always follows the finger.
//  · Cards show the photo only; name / description / link / counter / arrows sit
//    in one compact band UNDER the canvas.
//  · A short tap on any visible card opens THAT card's link. The hit test runs
//    the vertex shader's own maths, so it matches the bent, scaled, faded card
//    on screen. Dragging only moves the carousel; releasing a drag opens nothing.
//  · Arabic titles animate word by word (per-letter spans would break joining).
//  · Per-slide focal point (same meaning as CSS object-position) for the crop.
//  · Theme-aware stage, site fonts, pauses off-screen / in hidden tabs, frees GL
//    resources on unmount, image + links fallback without WebGL, direct moves
//    under reduced motion. No autoplay.

export type LensSlide = {
  title: string
  /** Small line above the title, e.g. a place or a category. */
  kicker?: string
  /** One short line under the title. */
  caption?: string
  /** Photo URL. Same-origin (or CORS-enabled) — it is uploaded to WebGL. */
  image: string
  alt?: string
  /** Opened by the caption link and by tapping the card. */
  href?: string
  /** Crop focal point in % (like CSS object-position). Default 50 / 50. */
  focus?: { x: number; y: number }
}

export type CurvedLensCarouselProps = {
  slides: LensSlide[]
  /** Accessible name of the carousel region. */
  label?: string
  /** Small hint over the top of the stage. */
  hint?: string
  /** Label of the link under the title. */
  cta?: string
  /** How hard the lens bends the outer cards. 0 is flat. */
  bend?: number
  /** Reading direction: decides which side "next" comes from. */
  dir?: "rtl" | "ltr"
  className?: string
  style?: React.CSSProperties
  onChange?: (index: number) => void
}

// #region motion
/** Wraps any index into 0..n-1. */
export function wrap(i: number, n: number): number {
  return n > 0 ? ((i % n) + n) % n : 0
}

/** Signed distance of slide i from the scroll position, taking the short way round a loop of n. */
export function offsetOf(i: number, pos: number, n: number): number {
  if (n <= 0) return 0
  const d = wrap(i - pos + n / 2, n) - n / 2
  return d
}

/** Where a flick comes to rest: the nearest whole slide past the release, at most `max` away. */
export function restFor(pos: number, velocity: number, max = 2): number {
  const throwTo = pos + Math.max(-max, Math.min(max, velocity * 0.14))
  return Math.round(throwTo)
}

/** One step of a critically damped spring (slides, slides/s, seconds) — no overshoot. */
export function springStep(pos: number, vel: number, target: number, dt: number, omega = 9): [number, number] {
  const a = (target - pos) * omega * omega - vel * 2 * omega
  const v = vel + a * dt
  return [pos + v * dt, v]
}

/**
 * Card size in px for a canvas band of w × h: portrait 4:5 cards filling ~84 %
 * of the band's height, never more than a share of its width.
 */
export function cardSize(w: number, h: number): [number, number] {
  const share = w < 640 ? 0.6 : w < 1024 ? 0.32 : 0.22
  const cw = Math.min(w * share, h * 0.672)
  return [Math.round(cw), Math.round(cw * 1.25)]
}

/** Gap between cards: wider on phones so the neighbours stay out of view. */
export function cardGap(w: number, cw: number): number {
  return cw * (w < 640 ? 0.32 : 0.14)
}

/** Side cards are only a little smaller than the active one. */
export function cardScale(d: number): number {
  return 1 - 0.06 * Math.min(Math.abs(d), 2)
}

/** Opacity of the lens edge fade at a clamped screen x (−1.4…1.4). */
export function edgeFade(x: number): number {
  const t = Math.min(1, Math.max(0, (Math.abs(x) - 0.74) / 0.26))
  return 1 - t * t * (3 - 2 * t)
}

/** "01" style counter. */
export function pad2(n: number): string {
  return n < 10 ? "0" + n : String(n)
}
// #endregion

const VERT =
  "attribute vec2 aPos;" +
  "uniform vec2 uRes;uniform vec2 uSize;uniform float uX;uniform float uBend;uniform float uVel;uniform float uIntro;uniform float uScale;" +
  "varying vec2 vUv;varying float vX;" +
  "void main(){" +
  "vec2 s=uSize*uScale;" +
  "vec2 p=vec2(uX+(aPos.x-.5)*s.x,(aPos.y-.5)*s.y);" +
  "p.y-=(1.-uIntro)*s.y*.9;" +
  "vec2 n=p/(uRes*.5);" +
  "float x=clamp(n.x,-1.4,1.4);" +
  "n.y*=1.+uBend*x*x;" +
  "n.y+=uVel*.02*sin(aPos.x*3.14159)*(aPos.y-.5);" +
  "n.x*=1.-.06*uBend*x*x;" +
  "vUv=aPos;vX=x;" +
  "gl_Position=vec4(n,0.,1.);}"

// uFocus: crop centre like object-position (0..1, y measured from the bottom
// because the texture is flipped); uBase: placeholder colour; uRim: edge light;
// uDim: how much a side card steps back; the alpha fades cards at the stage edges.
const FRAG =
  "precision highp float;" +
  "uniform sampler2D uTex;uniform vec2 uSize;uniform float uScale;uniform float uAspect;uniform float uVel;uniform float uIntro;uniform float uShift;uniform float uReady;" +
  "uniform vec2 uFocus;uniform vec3 uBase;uniform float uRim;uniform float uDim;" +
  "varying vec2 vUv;varying float vX;" +
  "float box(vec2 q,vec2 b,float r){vec2 d=abs(q)-b+r;return length(max(d,0.))+min(max(d.x,d.y),0.)-r;}" +
  "void main(){" +
  "float a=uSize.x/uSize.y;vec2 win=vec2(1.);" +
  "if(uAspect>a)win.x=a/uAspect;else win.y=uAspect/a;" +
  "win/=1.08;" +
  "vec2 c=.5+(uFocus-.5)*(1.-win);c.x+=uShift;" +
  "c=clamp(c,win*.5,1.-win*.5);" +
  "vec2 uv=c+(vUv-.5)*win;" +
  "float ca=(.0003+.0012*vX*vX)*(1.+abs(uVel));" +
  "vec3 col=vec3(texture2D(uTex,uv+vec2(ca,0.)).r,texture2D(uTex,uv).g,texture2D(uTex,uv-vec2(ca,0.)).b);" +
  "col=mix(uBase,col,uReady);" +
  "col*=1.-uDim;" +
  "vec2 sz=uSize*uScale;" +
  "float d=box((vUv-.5)*sz,sz*.5,14.);" +
  "float rim=smoothstep(-5.,0.,d);" +
  "col+=rim*uRim;" +
  "float edge=1.-smoothstep(.74,1.,abs(vX));" +
  "float al=(1.-smoothstep(-.75,.75,d))*uIntro*edge;" +
  "gl_FragColor=vec4(col*al,al);}"

// Layout: a canvas band (fixed height per breakpoint) and, under it, one compact
// grid holding title · caption · link on the start side and counter · arrows on
// the end side.
const CSS =
  ".clc-root{--clc-band:400px;--clc-pad:clamp(20px,3vw,40px);" +
  "--clc-bg:var(--color-stage,#f7f6f3);--clc-ink:var(--color-ink,#0e0e0e);--clc-accent:var(--color-brand,#d0101f);" +
  "--clc-ph:226,222,215;--clc-rim:-.03;--clc-glow:rgba(255,255,255,.75);" +
  "position:relative;width:100%;display:flex;flex-direction:column;overflow:hidden;isolation:isolate;outline:none;user-select:none;-webkit-user-select:none;" +
  "background:var(--clc-bg);color:var(--clc-ink);font-family:var(--font-plex),ui-sans-serif,system-ui,sans-serif}" +
  "[data-theme=dark] .clc-root{--clc-ph:30,30,32;--clc-rim:.05;--clc-glow:rgba(255,240,220,.05)}" +
  ".clc-root:focus-visible{box-shadow:inset 0 0 0 2px var(--clc-accent)}" +
  ".clc-glow{position:absolute;inset:-20%;background:radial-gradient(50% 40% at 50% 40%,var(--clc-glow),transparent 70%);pointer-events:none}" +
  ".clc-grain{position:absolute;inset:0;opacity:.05;mix-blend-mode:overlay;pointer-events:none;background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160'%3E%3Cfilter id='n'%3E%3CfeTurbulence baseFrequency='.85' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E\")}" +
  ".clc-stage{position:relative;height:var(--clc-band);margin-top:6px}" +
  ".clc-canvas{position:absolute;inset:0;width:100%;height:100%;display:block;cursor:grab;touch-action:pan-y}" +
  ".clc-canvas:active{cursor:grabbing}" +
  ".clc-hint{position:absolute;top:8px;inset-inline-end:var(--clc-pad);font-size:12px;opacity:.55;pointer-events:none}" +
  ".clc-foot{position:relative;display:grid;grid-template-columns:minmax(0,1fr) auto;grid-template-areas:'title meta' 'cap btns' 'cta btns';" +
  "column-gap:24px;align-items:end;padding:2px var(--clc-pad) clamp(16px,2vw,24px);color:var(--clc-ink);text-align:start}" +
  ".clc-anim{display:contents}" +
  ".clc-title{grid-area:title;font-family:var(--clc-font);font-weight:600;font-size:clamp(30px,3vw,44px);line-height:1.25;margin:0;min-width:0}" +
  ".clc-kicker{display:block;font-size:12px;font-weight:400;opacity:.55;margin-bottom:2px}" +
  // Room under the baseline for Arabic dots (ي، ج، ب…) inside the clipping line.
  ".clc-line{display:block;overflow:hidden;padding-bottom:.28em;margin-bottom:-.22em}" +
  ".clc-w{display:inline-block;transform:translateY(100%);opacity:0;transition:transform .8s cubic-bezier(.2,.8,.2,1),opacity .5s ease}" +
  ".clc-in .clc-w{transform:none;opacity:1}" +
  ".clc-cap{grid-area:cap;display:block;margin-top:10px;font-size:15px;line-height:1.6;opacity:0;transform:translateY(8px);transition:opacity .6s ease .2s,transform .6s cubic-bezier(.2,.8,.2,1) .2s;max-width:48ch;min-width:0}" +
  ".clc-in .clc-cap{opacity:.72;transform:none}" +
  ".clc-cta{grid-area:cta;justify-self:start;display:inline-flex;align-items:center;gap:8px;margin-top:4px;min-height:40px;padding:0 2px;font-size:14px;font-weight:600;color:var(--clc-ink);text-decoration:none;border-bottom:2px solid transparent}" +
  ".clc-cta:hover{border-bottom-color:var(--clc-accent)}" +
  ".clc-cta svg{width:16px;height:16px;color:var(--clc-accent);transition:transform .3s ease}" +
  ".clc-cta:hover svg{transform:translateX(-4px)}" +
  ".clc-cta:focus-visible{outline:2px solid var(--clc-accent);outline-offset:4px;border-radius:4px}" +
  ".clc-meta{grid-area:meta;align-self:center;display:flex;align-items:center;gap:12px}" +
  ".clc-count{font-size:12px;letter-spacing:.14em;font-variant-numeric:tabular-nums;opacity:.8;direction:ltr;white-space:nowrap}" +
  ".clc-count i{font-style:normal;opacity:.45}" +
  ".clc-bar{width:112px;height:2px;border-radius:2px;background:color-mix(in srgb,var(--clc-ink) 16%,transparent);overflow:hidden}" +
  ".clc-bar>span{display:block;height:100%;width:100%;background:var(--clc-accent);transform-origin:0 50%;transition:transform .6s cubic-bezier(.2,.8,.2,1)}" +
  "[dir=rtl] .clc-bar>span{transform-origin:100% 50%}" +
  ".clc-btns{grid-area:btns;align-self:end;justify-self:end;display:flex;gap:10px}" +
  ".clc-btn{width:46px;height:46px;border-radius:999px;display:grid;place-items:center;color:var(--clc-ink);cursor:pointer;" +
  "background:color-mix(in srgb,var(--clc-ink) 4%,transparent);border:1px solid color-mix(in srgb,var(--clc-ink) 16%,transparent);" +
  "transition:transform .3s ease,background .3s ease,border-color .3s ease,color .3s ease}" +
  ".clc-btn:hover{transform:translateY(-1px);border-color:var(--clc-accent);color:var(--clc-accent)}" +
  ".clc-btn:active{transform:scale(.96)}" +
  ".clc-btn:disabled{opacity:.35;pointer-events:none}" +
  ".clc-btn:focus-visible{outline:2px solid var(--clc-accent);outline-offset:3px}" +
  ".clc-btn svg{width:18px;height:18px}" +
  "[dir=rtl] .clc-btn svg,[dir=rtl] .clc-cta svg{scale:-1 1}" +
  "[dir=rtl] .clc-cta:hover svg{transform:translateX(4px)}" +
  ".clc-fallback{position:absolute;inset:0;display:grid;place-items:center}" +
  ".clc-fallback>a{position:relative;display:block;height:84%;aspect-ratio:4/5;border-radius:14px;overflow:hidden}" +
  ".clc-sr{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap}" +
  "@media (max-width:1023px){.clc-root{--clc-band:360px}}" +
  "@media (max-width:639px){.clc-root{--clc-band:312px;--clc-pad:20px}.clc-hint{top:2px;font-size:11px}" +
  ".clc-foot{grid-template-areas:'title meta' 'cap cap' 'cta btns';column-gap:12px}.clc-title{font-size:30px}" +
  ".clc-cap{font-size:14px;line-height:1.55}.clc-bar{width:44px}.clc-meta{gap:8px}.clc-btn{width:44px;height:44px}}" +
  "@media (prefers-reduced-motion:reduce){.clc-w,.clc-cap,.clc-bar>span,.clc-btn,.clc-cta svg{transition:opacity .2s ease}.clc-w,.clc-cap{transform:none}}"

type Uniforms = Record<string, WebGLUniformLocation | null>

function shader(gl: WebGLRenderingContext, type: number, src: string) {
  const s = gl.createShader(type) as WebGLShader
  gl.shaderSource(s, src)
  gl.compileShader(s)
  return s
}

/** A (segX × segY) grid of quads as triangles, positions 0..1. */
function grid(segX: number, segY: number): Float32Array {
  const out: number[] = []
  for (let y = 0; y < segY; y++)
    for (let x = 0; x < segX; x++) {
      const x0 = x / segX, x1 = (x + 1) / segX, y0 = y / segY, y1 = (y + 1) / segY
      out.push(x0, y0, x1, y0, x0, y1, x0, y1, x1, y0, x1, y1)
    }
  return new Float32Array(out)
}

/** Title words rise one after another (whole words keep Arabic letters joined). */
function Words({ text, base }: { text: string; base: number }) {
  return (
    <>
      {text.split(" ").map((word, w, all) => (
        <React.Fragment key={w}>
          <span className="clc-w" style={{ transitionDelay: base + w * 80 + "ms" }}>
            {word}
          </span>
          {w < all.length - 1 ? " " : null}
        </React.Fragment>
      ))}
    </>
  )
}

/** Title + caption. Mounted fresh per slide, then flipped on next frame so the words rise. */
function Copy({ slide, shown }: { slide: LensSlide; shown: boolean }) {
  const [on, setOn] = React.useState(false)
  React.useEffect(() => {
    if (!shown) return
    const id = requestAnimationFrame(() => requestAnimationFrame(() => setOn(true)))
    return () => cancelAnimationFrame(id)
  }, [shown])
  return (
    <div className={"clc-anim" + (on ? " clc-in" : "")}>
      <h3 className="clc-title">
        {slide.kicker ? <span className="clc-kicker">{slide.kicker}</span> : null}
        <span className="clc-line">
          <Words text={slide.title} base={40} />
        </span>
      </h3>
      {slide.caption ? <span className="clc-cap">{slide.caption}</span> : null}
    </div>
  )
}

const Arrow = () => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
    <path d="M19 12H5M11 6l-6 6 6 6" />
  </svg>
)

/** Movement (px) under which a press counts as a tap, not a drag. */
const TAP_SLOP = 8

export default function CurvedLensCarousel({
  slides,
  label = "Photo carousel",
  hint = "Drag to wander",
  cta = "Explore",
  bend = 0.14,
  dir = "ltr",
  className,
  style,
  onChange,
}: CurvedLensCarouselProps) {
  const n = slides.length
  const s = dir === "rtl" ? -1 : 1
  const router = useRouter()
  const rootRef = React.useRef<HTMLDivElement | null>(null)
  const canvasRef = React.useRef<HTMLCanvasElement | null>(null)
  const [index, setIndex] = React.useState(0)
  const [shown, setShown] = React.useState(false)
  const [noGL, setNoGL] = React.useState(false)
  const api = React.useRef({ go: (d: number) => void d, redraw: () => {} })
  const onChangeRef = React.useRef(onChange)
  const slidesRef = React.useRef(slides)
  const routerRef = React.useRef(router)
  React.useEffect(() => {
    onChangeRef.current = onChange
    slidesRef.current = slides
    routerRef.current = router
    api.current.redraw()
  })

  // Textures only depend on the image list — text / focus / order edits from
  // the admin reuse the running scene.
  const imagesKey = slides.map((x) => x.image).join("|")

  React.useEffect(() => {
    const canvas = canvasRef.current
    const root = rootRef.current
    if (!canvas || !root || n === 0) return
    const handles = api.current
    let disposed = false
    let fallbackIndex = 0
    const fallback = () => {
      handles.go = (d: number) => {
        fallbackIndex = wrap(fallbackIndex + d, n)
        setIndex(fallbackIndex)
        onChangeRef.current?.(fallbackIndex)
      }
      handles.redraw = () => {}
      setNoGL(true)
      setShown(true)
    }
    const gl = canvas.getContext("webgl", { premultipliedAlpha: true, antialias: true, alpha: true })
    if (!gl) {
      fallback()
      return
    }
    const reduced = window.matchMedia("(prefers-reduced-motion: reduce)")
    const prog = gl.createProgram() as WebGLProgram
    gl.attachShader(prog, shader(gl, gl.VERTEX_SHADER, VERT))
    gl.attachShader(prog, shader(gl, gl.FRAGMENT_SHADER, FRAG))
    gl.linkProgram(prog)
    if (!gl.getProgramParameter(prog, gl.LINK_STATUS)) {
      console.warn("3d-curved-carousel: " + gl.getProgramInfoLog(prog))
      fallback()
      return
    }
    setNoGL(false)
    setIndex(0)
    gl.useProgram(prog)
    const mesh = grid(36, 12)
    const buf = gl.createBuffer() as WebGLBuffer
    gl.bindBuffer(gl.ARRAY_BUFFER, buf)
    gl.bufferData(gl.ARRAY_BUFFER, mesh, gl.STATIC_DRAW)
    const loc = gl.getAttribLocation(prog, "aPos")
    gl.enableVertexAttribArray(loc)
    gl.vertexAttribPointer(loc, 2, gl.FLOAT, false, 0, 0)
    const u: Uniforms = {}
    for (const k of ["uRes", "uSize", "uX", "uBend", "uVel", "uIntro", "uScale", "uTex", "uAspect", "uShift", "uReady", "uFocus", "uBase", "uRim", "uDim"])
      u[k] = gl.getUniformLocation(prog, k)
    gl.enable(gl.BLEND)
    gl.blendFunc(gl.ONE, gl.ONE_MINUS_SRC_ALPHA)
    gl.pixelStorei(gl.UNPACK_FLIP_Y_WEBGL, true)

    const tex = imagesKey.split("|").map((src) => {
      const t = gl.createTexture() as WebGLTexture
      gl.bindTexture(gl.TEXTURE_2D, t)
      gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, 1, 1, 0, gl.RGBA, gl.UNSIGNED_BYTE, new Uint8Array([200, 196, 190, 255]))
      for (const [p, v] of [[gl.TEXTURE_MIN_FILTER, gl.LINEAR], [gl.TEXTURE_MAG_FILTER, gl.LINEAR], [gl.TEXTURE_WRAP_S, gl.CLAMP_TO_EDGE], [gl.TEXTURE_WRAP_T, gl.CLAMP_TO_EDGE]])
        gl.texParameteri(gl.TEXTURE_2D, p, v)
      const entry = { t, aspect: 0.8, ready: false }
      const img = new window.Image()
      if (!src.startsWith("data:")) img.crossOrigin = "anonymous"
      img.decoding = "async"
      img.onload = () => {
        if (disposed) return
        gl.bindTexture(gl.TEXTURE_2D, t)
        gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, gl.RGBA, gl.UNSIGNED_BYTE, img)
        entry.aspect = img.naturalWidth / img.naturalHeight
        entry.ready = true
        kick()
      }
      img.src = src
      return entry
    })

    // Theme colours for the placeholder and the rim light (light / dark stage).
    let base: [number, number, number] = [0.89, 0.87, 0.84]
    let rimLight = 0.05
    const readTheme = () => {
      const cs = getComputedStyle(root)
      const rgb = cs.getPropertyValue("--clc-ph").split(",").map((v) => parseFloat(v) / 255)
      if (rgb.length === 3 && rgb.every((v) => Number.isFinite(v))) base = [rgb[0], rgb[1], rgb[2]]
      const r = parseFloat(cs.getPropertyValue("--clc-rim"))
      if (Number.isFinite(r)) rimLight = r
    }
    readTheme()

    // Stage size, measured on resize only; the frame loop reads these numbers.
    let W = 1, H = 1, dpr = 1, cw = 1, ch = 1, gap = 1
    let pos = 0, vel = 0, target = 0
    let intro = reduced.matches ? 1 : 0
    let introStart = -1
    let dragging = false, dragX = 0, dragPos = 0, lastX = 0, lastT = 0, flick = 0, moved = false
    let pressed = false, downX = 0, downY = 0, downT = 0
    let raf = 0, last = 0, visible = false, current = 0

    const resize = (w: number, h: number) => {
      W = Math.max(1, w)
      H = Math.max(1, h)
      dpr = Math.min(2, window.devicePixelRatio || 1)
      canvas.width = Math.round(W * dpr)
      canvas.height = Math.round(H * dpr)
      ;[cw, ch] = cardSize(W, H)
      gap = cardGap(W, cw)
      gl.viewport(0, 0, canvas.width, canvas.height)
      kick()
    }

    /** Per-card placement shared by drawing and hit testing. */
    const place = (i: number) => {
      const d = offsetOf(i, pos, n)
      const order = Math.abs(d)
      const t = Math.max(0, Math.min(1, (intro - order * 0.12) / 0.6))
      return { d, x: s * d * (cw + gap), sc: cardScale(d), e: 1 - Math.pow(1 - t, 3) }
    }

    /** The vertex shader's transform in JS: card-local (ax, ay) → clip space. */
    const project = (g: ReturnType<typeof place>, ax: number, ay: number) => {
      const px = g.x + (ax - 0.5) * cw * g.sc
      const py = (ay - 0.5) * ch * g.sc - (1 - g.e) * ch * g.sc * 0.9
      let nx = px / (W * 0.5)
      let ny = py / (H * 0.5)
      const xc = Math.max(-1.4, Math.min(1.4, nx))
      ny *= 1 + bend * xc * xc
      nx *= 1 - 0.06 * bend * xc * xc
      return { nx, ny, xc }
    }

    /** Which visible card (if any) is under a client point — after bend, scale, intro and edge fade. */
    const cardAt = (clientX: number, clientY: number): number => {
      const r = canvas.getBoundingClientRect()
      const sx = ((clientX - r.left) / r.width) * 2 - 1
      const sy = 1 - ((clientY - r.top) / r.height) * 2
      for (let i = 0; i < n; i++) {
        const g = place(i)
        if (g.e < 0.5) continue
        const left = project(g, 0, 0.5).nx
        const right = project(g, 1, 0.5).nx
        if (sx < Math.min(left, right) || sx > Math.max(left, right)) continue
        // invert x (monotonic across the card) by bisection
        let lo = 0, hi = 1
        for (let k = 0; k < 24; k++) {
          const mid = (lo + hi) / 2
          if (project(g, mid, 0.5).nx < sx) lo = mid
          else hi = mid
        }
        const ax = (lo + hi) / 2
        const bottom = project(g, ax, 0).ny
        const top = project(g, ax, 1).ny
        if (sy < bottom || sy > top) continue
        if (edgeFade(project(g, ax, 0.5).xc) * g.e < 0.4) continue
        return i
      }
      return -1
    }

    const draw = () => {
      gl.clearColor(0, 0, 0, 0)
      gl.clear(gl.COLOR_BUFFER_BIT)
      gl.uniform2f(u.uRes, W, H)
      gl.uniform2f(u.uSize, cw, ch)
      gl.uniform1f(u.uBend, bend)
      gl.uniform1f(u.uVel, reduced.matches ? 0 : Math.max(-1, Math.min(1, vel * 0.1)))
      gl.uniform3f(u.uBase, base[0], base[1], base[2])
      gl.uniform1f(u.uRim, rimLight)
      const step = cw + gap
      const list = slidesRef.current
      for (let i = 0; i < n; i++) {
        const g = place(i)
        if (Math.abs(g.x) > W * 0.5 + step * 1.5) continue
        const f = list[i]?.focus ?? { x: 50, y: 50 }
        gl.bindTexture(gl.TEXTURE_2D, tex[i].t)
        gl.uniform1f(u.uX, g.x)
        gl.uniform1f(u.uScale, g.sc)
        gl.uniform1f(u.uIntro, g.e)
        gl.uniform1f(u.uDim, 0.1 * Math.min(1, Math.abs(g.d)))
        gl.uniform1f(u.uAspect, tex[i].aspect)
        gl.uniform1f(u.uReady, tex[i].ready ? 1 : 0)
        gl.uniform1f(u.uShift, Math.max(-0.02, Math.min(0.02, -s * g.d * 0.012)))
        gl.uniform2f(u.uFocus, f.x / 100, 1 - f.y / 100)
        gl.drawArrays(gl.TRIANGLES, 0, mesh.length / 2)
      }
    }

    const frame = (now: number) => {
      raf = 0
      const dt = last ? Math.min(0.05, (now - last) / 1000) : 1 / 60
      last = now
      if (intro < 1) {
        if (introStart < 0) introStart = now
        intro = Math.min(1.5, (now - introStart) / 1100)
        if (intro >= 1.5) intro = 1
      }
      if (!dragging) {
        if (reduced.matches) {
          pos = target
          vel = 0
        } else [pos, vel] = springStep(pos, vel, target, dt)
      }
      const idx = wrap(Math.round(pos), n)
      if (idx !== current) {
        current = idx
        setIndex(idx)
        onChangeRef.current?.(idx)
      }
      draw()
      const settled = !dragging && Math.abs(target - pos) < 0.0005 && Math.abs(vel) < 0.001 && (intro >= 1 || reduced.matches)
      if (settled) {
        pos = target
        draw()
        last = 0
      } else if (visible && !document.hidden) raf = requestAnimationFrame(frame)
      else last = 0
    }

    function kick() {
      if (!raf && visible && !document.hidden && !disposed) raf = requestAnimationFrame(frame)
    }

    handles.go = (d: number) => {
      if (n < 2) return
      target = Math.round(target) + d
      kick()
    }
    handles.redraw = kick

    const open = (i: number, newTab: boolean) => {
      const href = slidesRef.current[i]?.href
      if (!href) return
      if (newTab) window.open(href, "_blank", "noopener")
      else routerRef.current.push(href)
    }

    const onDown = (e: PointerEvent) => {
      if (e.button !== 0) return
      pressed = true
      moved = false
      downX = e.clientX
      downY = e.clientY
      downT = e.timeStamp
      if (n < 2) return
      dragging = true
      dragX = lastX = e.clientX
      lastT = e.timeStamp
      dragPos = pos
      flick = 0
      try {
        canvas.setPointerCapture(e.pointerId)
      } catch {
        /* pointer already gone */
      }
    }
    const onMove = (e: PointerEvent) => {
      if (!pressed) {
        // hover: a pointer over a card says it can be opened
        if (e.pointerType === "mouse") canvas.style.cursor = cardAt(e.clientX, e.clientY) >= 0 ? "pointer" : ""
        return
      }
      if (!moved && Math.hypot(e.clientX - downX, e.clientY - downY) >= TAP_SLOP) moved = true
      if (!dragging || !moved) return
      const step = cw + gap
      const p = dragPos - (s * (e.clientX - dragX)) / step
      const dts = Math.max(1, e.timeStamp - lastT) / 1000
      flick = flick * 0.6 + ((-s * (e.clientX - lastX)) / step / dts) * 0.4
      vel = flick
      lastX = e.clientX
      lastT = e.timeStamp
      pos = p
      kick()
    }
    const onUp = (e: PointerEvent) => {
      if (!pressed) return
      pressed = false
      const wasDragging = dragging
      dragging = false
      const tap = e.type === "pointerup" && !moved && Math.hypot(e.clientX - downX, e.clientY - downY) < TAP_SLOP && e.timeStamp - downT < 600
      if (wasDragging) {
        target = tap ? Math.round(pos) : restFor(pos, flick)
        if (tap) vel = 0
        else vel = flick
      }
      kick()
      // Only a still, short press opens — the card actually under the pointer.
      if (tap) {
        const i = cardAt(e.clientX, e.clientY)
        if (i >= 0) open(i, e.ctrlKey || e.metaKey)
      }
    }
    const onKey = (e: KeyboardEvent) => {
      if (e.key === "ArrowRight") handles.go(s)
      else if (e.key === "ArrowLeft") handles.go(-s)
      else return
      e.preventDefault()
    }
    const onMotion = () => kick()
    const onVisibility = () => kick()
    const onLost = (e: Event) => {
      e.preventDefault()
      cancelAnimationFrame(raf)
      raf = 0
      fallback()
    }
    const themeWatch = new MutationObserver(() => {
      readTheme()
      kick()
    })
    themeWatch.observe(document.documentElement, { attributes: true, attributeFilter: ["data-theme"] })

    canvas.addEventListener("pointerdown", onDown)
    canvas.addEventListener("pointermove", onMove)
    canvas.addEventListener("pointerup", onUp)
    canvas.addEventListener("pointercancel", onUp)
    canvas.addEventListener("webglcontextlost", onLost)
    root.addEventListener("keydown", onKey)
    reduced.addEventListener("change", onMotion)
    document.addEventListener("visibilitychange", onVisibility)

    const ro = new ResizeObserver((entries) => {
      const r = entries[0].contentRect
      resize(r.width, r.height)
    })
    ro.observe(canvas)
    const io = new IntersectionObserver((entries) => {
      visible = entries[0].isIntersecting
      if (visible) {
        setShown(true)
        kick()
      } else {
        cancelAnimationFrame(raf)
        raf = 0
        last = 0
      }
    })
    io.observe(root)

    return () => {
      disposed = true
      cancelAnimationFrame(raf)
      ro.disconnect()
      io.disconnect()
      themeWatch.disconnect()
      canvas.removeEventListener("pointerdown", onDown)
      canvas.removeEventListener("pointermove", onMove)
      canvas.removeEventListener("pointerup", onUp)
      canvas.removeEventListener("pointercancel", onUp)
      canvas.removeEventListener("webglcontextlost", onLost)
      root.removeEventListener("keydown", onKey)
      reduced.removeEventListener("change", onMotion)
      document.removeEventListener("visibilitychange", onVisibility)
      for (const t of tex) gl.deleteTexture(t.t)
      gl.deleteBuffer(buf)
      gl.deleteProgram(prog)
      handles.go = () => {}
      handles.redraw = () => {}
    }
  }, [n, imagesKey, bend, s])

  const slide = slides[index] ?? slides[0]
  if (!slide) return null
  const vars = { "--clc-font": "var(--font-alexandria), var(--font-plex), ui-sans-serif, sans-serif", ...style } as React.CSSProperties
  const f = slide.focus ?? { x: 50, y: 50 }

  const isEn = dir === "ltr";
  return (
    <div
      ref={rootRef}
      dir={dir}
      className={"clc-root" + (className ? " " + className : "")}
      style={vars}
      tabIndex={0}
      role="region"
      aria-roledescription={isEn ? "carousel" : "عرض دوّار"}
      aria-label={label}
    >
      <style>{CSS}</style>
      <div className="clc-glow" aria-hidden="true" />
      <div className="clc-grain" aria-hidden="true" />
      <div className="clc-stage">
        <canvas ref={canvasRef} className="clc-canvas" aria-hidden="true" />
        {noGL ? (
          <div className="clc-fallback">
            {slide.href ? (
              <Link href={slide.href} tabIndex={-1}>
                <Image src={slide.image} alt={slide.alt ?? slide.title} fill sizes="320px" className="object-cover" style={{ objectPosition: `${f.x}% ${f.y}%` }} />
              </Link>
            ) : null}
          </div>
        ) : null}
        {hint ? (
          <span className="clc-hint" aria-hidden="true">
            {hint}
          </span>
        ) : null}
      </div>
      <div className="clc-foot">
        <Copy key={index} slide={slide} shown={shown} />
        {slide.href ? (
          <Link href={slide.href} className="clc-cta">
            {cta}
            <span className="clc-sr"> — {slide.title}</span>
            <Arrow />
          </Link>
        ) : null}
        <div className="clc-meta" aria-hidden="true">
          <span className="clc-count">
            {pad2(index + 1)} <i>/ {pad2(n)}</i>
          </span>
          <span className="clc-bar">
            <span style={{ transform: "scaleX(" + (index + 1) / n + ")" }} />
          </span>
        </div>
        <div className="clc-btns">
          <button type="button" className="clc-btn" aria-label={isEn ? "Previous category" : "القسم السابق"} disabled={n < 2} onClick={() => api.current.go(-1)}>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M15 5l-7 7 7 7" /></svg>
          </button>
          <button type="button" className="clc-btn" aria-label={isEn ? "Next category" : "القسم التالي"} disabled={n < 2} onClick={() => api.current.go(1)}>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M9 5l7 7-7 7" /></svg>
          </button>
        </div>
      </div>
      <p className="clc-sr" aria-live="polite">
        {slide.title} — {isEn ? `${index + 1} of ${n}` : `${index + 1} من ${n}`}
      </p>
      {/* Every department link, reachable without seeing (or dragging) the photos. */}
      <ul className="clc-sr">
        {slides.map((x, i) =>
          x.href ? (
            <li key={i}>
              <Link href={x.href} tabIndex={-1}>
                {x.title}
              </Link>
            </li>
          ) : null,
        )}
      </ul>
    </div>
  )
}
