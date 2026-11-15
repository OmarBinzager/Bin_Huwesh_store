# التراخيص ومصادر المكوّنات والأصول

هذا ملخص للمعلومات المتوفرة في المشروع. ما لم يتوفر له ترخيص موثّق مذكور صراحة، ويجب مراجعته قبل الإطلاق.

## 1. الاعتماديات المباشرة (npm)

الإصدارات المثبتة فعليًا وفق `package-lock.json`، والتراخيص كما تعلنها الحزم نفسها:

| الحزمة | الإصدار | الترخيص |
|---|---|---|
| next | 16.3.4 | MIT |
| react / react-dom | 19.2.8 | MIT |
| framer-motion | 13.1.1 | MIT |
| three | 0.185.1 | MIT |
| @react-three/fiber | 9.7.0 | MIT |
| @react-three/drei | 10.7.8 | MIT |
| hls.js | 1.7.2 | Apache-2.0 |
| clsx | 2.1.1 | MIT |
| tailwind-merge | 3.7.0 | MIT |
| tailwindcss / @tailwindcss/postcss | 4.3.3 | MIT |
| typescript (تطوير) | 5.9.3 | Apache-2.0 |
| eslint (تطوير) | 9.39.5 | MIT |
| eslint-config-next (تطوير) | 16.3.4 | MIT |
| shadcn (تطوير، أداة CLI) | 4.23.0 | MIT |

الاعتماديات غير المباشرة وتراخيصها في `package-lock.json` وفي `node_modules/*/package.json` بعد التثبيت.

## 2. الخطوط

تُحمَّل عبر `next/font/google` وقت البناء:

| الخط | الاستخدام | الترخيص |
|---|---|---|
| IBM Plex Sans Arabic | النص الأساسي | SIL Open Font License 1.1 |
| Alexandria | العناوين | SIL Open Font License 1.1 |
| Kanit | النموذج الأول، وكلمة BIN HUWESH في الفوتر | SIL Open Font License 1.1 |

## 3. مكوّنات مأخوذة من مصادر خارجية

| الملف | المصدر | ملاحظة |
|---|---|---|
| `src/components/ui/3d-curved-carousel.tsx` | 21st.dev «3d-curved-carousel» (CurvedLensCarousel) | مُعدَّل كثيرًا (RTL، الضغط، البديل بلا WebGL) |
| `src/components/ui/product-rail-gallery.tsx` | مكوّن مُورَّد (21st.dev) | مستخدم كما ورد مع تعديلات صغيرة |
| `src/components/ui/category-bento-grid.tsx` | 21st.dev «Shop by Category Grid» | معدَّل |
| `src/components/ui/cosmos-3d-orbit-gallery.tsx` | مكوّن طرف ثالث مُورَّد كما هو | للنموذج الأول فقط |
| `src/components/ui/marquee.tsx` | مكوّن مُورَّد | للنموذج الأول |

**الترخيص:** لا توجد في المشروع ملفات ترخيص لمكوّنات 21st.dev. مكوّنات المجتمع هناك تُنشر غالبًا برخص مفتوحة، لكن **يجب التحقق من صفحة كل مكوّن** قبل الإطلاق التجاري.

بقية المكوّنات في `src/components` مكتوبة خصيصًا لهذا المشروع.

## 4. الصور والأصول

التفاصيل الكاملة، بما فيها الروابط والأوامر المستخدمة، في الملفات المذكورة:

| المجموعة | المصدر | التوثيق |
|---|---|---|
| شعار بن هويش (`brand/logo`, `assets/brand`, `public/images/brand`) | ملفات الشركة | — |
| كرة الشعار ثلاثية الأبعاد (`public/images/hero`) | من `brand/images/binhuwesh-logo-3d-sphere.png` | — |
| صور الإطلالات | مولّدة بـ ChatGPT (حساب صاحب المشروع) | `assets/looks/PROMPTS.md` |
| صور تسوّق حسب القسم | مولّدة بـ ChatGPT | `assets/categories/SOURCES.md` |
| وصل حديثًا: التشكيلة البيضاء الأولى والغرفة | روابط صور من مرجع التصميم (images.higgs.ai). **الرخصة غير موثّقة** | `assets/new-arrivals/SOURCES.md` |
| وصل حديثًا: التشكيلة الشبابية | مولّدة بـ ChatGPT | `assets/new-arrivals/youth/` |
| الأحذية | مولّدة بـ ChatGPT، بلا شعارات | `assets/shoes/SOURCES.md` |
| علاقة الملابس | صور العرض التوضيحي من cdn.21st.dev. **تُستبدل** | `assets/rail/SOURCES.md` |
| شعارات الماركات | من المواقع الرسمية أو Wikimedia Commons. **علامات تجارية لأصحابها** | `assets/brands/SOURCES.md` |
| النموذج الأول | `public/images/collection` و`cosmos` من `brand/images`، وزخارف مستضافة على Figma | `src/data/site.ts` |
| `brand/images/*` | صور مرجعية للتصميم، بعضها من متاجر ومواقع أخرى (SHEIN، صفحات أزياء، ...) | **مرجع داخلي فقط — لا يُنشر** |
| `assets/looks/references/*` | نسخ مصغّرة من صور `brand/images` استُخدمت مرجعًا عند التوليد | مرجع داخلي فقط |

الصور المولّدة بالذكاء الاصطناعي **تمثيلية**: ليست صور منتجات الشركة ولا تُنسب إلى مخزونها.
