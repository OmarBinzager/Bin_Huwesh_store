# موقع بن هويش — دليل التشغيل

واجهة متجر **شركة بن هويش التجارية** (عدن): صفحة رئيسية تفاعلية عربية (RTL) بوضعين نهاري وليلي، وصفحات أقسام، وأدوات إدارة محلية لتعديل المحتوى.

> ابدأ بهذا الملف للتشغيل، ثم اقرأ **[HANDOFF.md](HANDOFF.md)** لمعرفة ما هو جاهز وما هو تجريبي ونقاط الربط مع أنظمة الشركة. التراخيص ومصادر الأصول في **[THIRD-PARTY.md](THIRD-PARTY.md)**.

---

## 1. المتطلبات

| الأداة | الإصدار المستخدم فعليًا في التطوير | ملاحظة |
|---|---|---|
| Node.js | **v24.22.0** | Next 16 يتطلب Node 20.9 أو أحدث؛ يُنصح بـ 22 LTS أو 24 |
| npm | **11.19.1** | مدير الحزم المستخدم (يوجد `package-lock.json`؛ لا تستخدم yarn/pnpm حتى لا يتغير القفل) |
| Next.js | 16.3.4 (Turbopack افتراضيًا) | App Router |
| React | 19.2.8 | |
| TypeScript | 5.9 | |
| Tailwind CSS | v4 (عبر `@tailwindcss/postcss`) | لا يوجد `tailwind.config.*`؛ الألوان في CSS |
| Python (اختياري) | 3.12 + Pillow 13 + numpy 2 | فقط لسكربتات تجهيز الصور في `assets/` |

**اتصال إنترنت مطلوب عند التثبيت والبناء الأول**: الخطوط (Alexandria و IBM Plex Sans Arabic و Kanit) تُحمَّل عبر `next/font/google` وقت البناء ثم تُخدَم من الموقع نفسه.

## 2. التثبيت والتشغيل

```bash
npm ci
```

```bash
npm run dev
```

ثم افتح http://localhost:3000 . خادم التطوير يستمع على كل الواجهات، فيمكن فتحه من هاتف على نفس الشبكة عبر `http://<IPv4-الجهاز>:3000` (الإعداد `allowedDevOrigins` في `next.config.ts` يسمح تلقائيًا بعناوين الجهاز على الشبكة).

| المهمة | الأمر |
|---|---|
| تثبيت الاعتماديات كما في القفل | `npm ci` |
| خادم التطوير (Turbopack) | `npm run dev` |
| فحص الأنواع | `npx tsc --noEmit` (بعد تشغيل `dev` أو `build` مرة واحدة لتوليد أنواع المسارات في `.next/types`) |
| الفحص (ESLint 9، إعداد flat) | `npm run lint` |
| بناء الإنتاج | `npm run build` |
| تشغيل نسخة الإنتاج | `npm start` (بعد `build`) |

لا يوجد إطار اختبارات مثبت في المشروع؛ أضف واحدًا قبل كتابة اختبارات.

### متغيرات البيئة

المشروع لا يحتاج أي مفتاح أو كلمة مرور ليعمل. انسخ `.env.example` إلى `.env.local` عند الحاجة فقط:

| المتغير | الاستخدام |
|---|---|
| `SITE_REVIEW` | ضعه `1` عند بناء نسخة مراجعة مشتركة: يضيف شريط «نسخة مراجعة»، و`noindex` في الرؤوس والميتا، و`robots.txt` يمنع الفهرسة. اتركه فارغًا لغير ذلك. |
| `NODE_ENV` | يضبطه Next تلقائيًا. أدوات الإدارة تعمل فقط حين يكون `development`. |

## 3. أهم المسارات

| المسار | الوصف |
|---|---|
| `/` | الصفحة الرئيسية (كل الأقسام بالترتيب أدناه) |
| `/categories/<slug>` | صفحات الأقسام الثمانية (معاينة بلا منتجات): `men` `youth` `kids` `jumbo` `suits` `maawaz` `shoes` `accessories` |
| `/prototype-v1` | النموذج المرئي الأول (أرشيف، غير مفهرس) |
| `/admin/looks` · `/admin/brands` · `/admin/categories` · `/admin/new-arrivals` · `/admin/shoes` · `/admin/closing` | أدوات الإدارة المحلية — تعمل في `npm run dev` فقط ومن الجهاز نفسه (localhost) |
| `/api/admin/*` | مسارات الحفظ التي تستخدمها أدوات الإدارة (نفس القيود) |

### أقسام الصفحة الرئيسية ومصادر بياناتها

الترتيب في `src/app/page.tsx`:

| القسم | المكوّن | البيانات | الإدارة |
|---|---|---|---|
| الهيدر | `src/components/store/StoreHeader.tsx` | `src/content/categories.ts` | — |
| الإطلالات (الهيرو) | `src/components/hero/*` | `src/content/looks.json` | `/admin/looks` |
| شريط الماركات | `src/components/sections/BrandStrip.tsx` | `src/content/brands.json` | `/admin/brands` |
| علاقة الملابس | `src/components/sections/RailGallerySection.tsx` + `ui/product-rail-gallery.tsx` | `src/content/rail.ts` | — (كود) |
| تسوّق حسب القسم | `src/components/sections/ShopByCategorySection.tsx` + `ui/3d-curved-carousel.tsx` | `src/content/shop-categories.json` | `/admin/categories` |
| وصل حديثًا | `src/components/new-arrivals/*` | `src/content/new-arrivals.json` | `/admin/new-arrivals` |
| أحذية تكمل إطلالتك | `src/components/shoes/*` | `src/content/shoes.json` | `/admin/shoes` |
| قالوا عن بن هويش + ننتظرك + الفوتر | `src/components/closing/*` | `src/content/closing.json` | `/admin/closing` |

## 4. تعديل المحتوى دون تعطيل الحركة

القاعدة في كل الأقسام: **البيانات منفصلة عن الحركة**. ملفات `src/content/*.json` تصف «ماذا يُعرض»، ومكوّنات `src/components/*` تقرر «كيف يتحرك». عدّل البيانات ولا تلمس أكواد الحركة.

1. **الطريقة المفضلة: أدوات الإدارة.** شغّل `npm run dev` وافتح `/admin/...`. كل أداة فيها معاينة مباشرة، وتتحقق من البيانات (الصور موجودة، المعرفات ثابتة، الأسعار أرقام صحيحة، الروابط تشير لصفحات موجودة...) ثم تكتب ملف JSON المعني بشكل ذري. الصور المرفوعة تُجهَّز في المتصفح (قص، مقاس، WebP، فحص الشفافية) وتُحفظ باسم جديد في `public/images/...` ولا تستبدل أي صورة قائمة.
2. **يدويًا:** عدّل ملف JSON مباشرة مع الحفاظ على:
   - **المعرفات `id` ثابتة** — لا تشتقها من الاسم ولا تغيّرها عند إعادة التسمية.
   - **الترتيب = ترتيب المصفوفة** (أو الحقل `order` في الإطلالات).
   - **الإخفاء** عبر `visible: false` / `published: false` بدل الحذف.
   - **مسارات الصور** تبدأ بـ `/images/...` وتشير لملف موجود في `public/images/...`.
3. **قواعد الصور لكل قسم** (مذكورة بالتفصيل في `HANDOFF.md` وفي ملفات `*-types.ts`):
   - وصل حديثًا: صورتا قطعة + مودل شفافتان لكل لون، المودل بإطار 640×1360 (أو 524×1360) ورأسه في نفس الموضع.
   - الأحذية: صورة جانبية شفافة، المقدمة لليسار، على لوحة 1400×800 ونعل الحذاء على y=760.
   - الإطلالات: صورة مودل مقصوصة شفافة 1400px ارتفاعًا مع نقاط تعريف القطع.
4. **الألوان والخطوط** في `src/app/globals.css` (رموز `--bh-*` للوضعين، والأحمر المعتمد `#d0101f`). الخطوط في `src/app/layout.tsx`.
5. **قسم جديد:** أنشئ مكوّنًا في `src/components/sections/`، وبياناته في `src/content/<name>.json` مع ملف أنواع، وأضفه إلى `src/app/page.tsx`. اتبع نمط الأقسام الحالية (تقليل الحركة، الإيقاف خارج الشاشة، الوضعان).

### سكربتات تجهيز الصور (Python، اختيارية)

تُشغَّل من جذر المشروع (`python -I <script>`). كلها **لا تعدّل الأصول في `originals/`**. معظمها يتخطى أي ملف ناتج موجود (`process_shoes.py` يقبل `--force`)، **باستثناء `process_looks.py` و`trim_logo.py` اللذين يعيدان كتابة مخرجاتهما** في `public/images/`.

| السكربت | الوظيفة |
|---|---|
| `assets/brand/trim_logo.py` | قص الهوامش الفارغة لملفي الشعار الكامل → `public/images/brand/` |
| `assets/looks/process_looks.py` | تحويل صور الإطلالات إلى قصاصات WebP شفافة 1400px → `public/images/looks/` |
| `assets/categories/process_categories.py` | تصغير صور «تسوّق حسب القسم» إلى WebP (≤1600px) → `public/images/categories/` |
| `assets/new-arrivals/process_new_arrivals.py` | قص لوحات VirtualFit الأصلية إلى صور مودل/قطعة لكل منتج |
| `assets/new-arrivals/youth/process_youth.py` | تجهيز التشكيلة الشبابية: محاذاة رأس المودل وإطار 640×1360 + قطع 640×640 (يحتاج numpy) |
| `assets/shoes/process_shoes.py` | تأطير الأحذية على لوحة 1400×800 بخط قاعدة موحد → `public/images/shoes/` |

مصادر كل مجموعة صور موثقة بجانبها: `assets/*/SOURCES.md` و`assets/looks/PROMPTS.md`.

## 5. بنية المجلدات

```
src/app/            الصفحات، التخطيط، globals.css، مسارات API للإدارة
src/components/     المكوّنات (hero, sections, new-arrivals, shoes, closing, store, admin, ui)
src/content/        بيانات الأقسام (JSON) وأنواعها ودوال القراءة
src/lib/            مخازن الإدارة (قراءة/تحقق/كتابة JSON)، حماية الإدارة، تنسيق الأسعار
src/data/           نصوص النموذج الأول (prototype-v1) والشريط المتحرك
public/images/      الصور التي يخدمها الموقع (WebP/SVG مجهزة)
assets/             الأصول الأصلية + سكربتات التجهيز + توثيق المصادر (لا تُخدم)
brand/              مصدر الهوية الأصلي (الشعار وصور مرجعية) — لا يُخدم ولا يُعدَّل
```
