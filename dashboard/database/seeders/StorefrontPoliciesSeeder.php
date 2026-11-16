<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StorefrontPoliciesSeeder extends Seeder
{
    public function run(): void
    {
        $policies = [
            'terms_and_conditions' => [
                'background_image' => null,
                'description' => '<div class="space-y-6 text-base leading-relaxed text-muted">
  <p class="text-lg font-medium text-ink">مرحباً بكم في متجر <strong>بن هويش للأزياء والأحذية</strong>. تحدد هذه الشروط والأحكام القواعد والضوابط المنظمة لاستخدامكم لموقعنا وتطبيقاتنا ولعمليات الشراء والطلبات التي تتم عبر منصتنا.</p>

  <h3 class="text-xl font-bold text-ink mt-8 mb-3">1. الأهلية وقبول الشروط</h3>
  <p>باستخدامك لمتجر بن هويش الإلكتروني أو إتمام أي عملية شراء، فإنك تقر وتوافق على الالتزام الكامل بهذه الشروط والأحكام وجميع السياسات المرتبطة بها. إذا كنت لا توافق على أي بند منها، يُرجى التوقف عن استخدام المنصة.</p>

  <h3 class="text-xl font-bold text-ink mt-8 mb-3">2. الحسابات والتسجيل وتأكيد الطلبات</h3>
  <ul class="list-disc list-inside space-y-2 pr-2">
    <li>يلتزم العميل بتقديم بيانات دقيقة وصحيحة ومحدثة عند التسجيل أو تقديم الطلب (الاسم الكامل، رقم الهاتف المحمول، العنوان بالتفصيل).</li>
    <li>العميل مسؤول مسؤولية كاملة عن الحفاظ على سرية بيانات حسابه وكلمة المرور الخاصة به.</li>
    <li>يُعد الطلب مؤكداً بعد مراجعته من قبل فريق المبيعات والتواصل مع العميل لتأكيد تفاصيل المقاس والتوصيل.</li>
  </ul>

  <h3 class="text-xl font-bold text-ink mt-8 mb-3">3. الأسعار والعملات وسياسة الدفع</h3>
  <p>تُعرض الأسعار في المتجر بالريال اليمني، مع إمكانية عرض القيمة المقابلة بالريال السعودي أو الدولار الأمريكي حسب سعر الصرف المعتمد في المتجر وقت الشراء. يحتفظ المتجر بحقه الكامل في تعديل الأسعار والعروض الترويجية والخصومات في أي وقت دون إشعار مسبق.</p>

  <h3 class="text-xl font-bold text-ink mt-8 mb-3">4. توفر المخزون ودقة الألوان والمقاسات</h3>
  <p>نبذل قصارى جهدنا لضمان دقة المخزون ومطابقة صور القطع الواقعية وخاصة في أقسام المعاوز التراثية، البدلات الرسمية، والأحذية الإيطالية والتركية. ومع ذلك، قد تختلف درجات الألوان اختلافاً طفيفاً نتيجة إعدادات الشاشة والإضاءة التصويرية.</p>

  <h3 class="text-xl font-bold text-ink mt-8 mb-3">5. حقوق الملكية الفكرية والعلامة التجارية</h3>
  <p>كافة العلامات التجارية، والشعارات، والصور، والعروض ثلاثية الأبعاد (3D)، والتصاميم، والنصوص المتاحة على هذا الموقع هي ملكية حصرية لشركة <strong>بن هويش التجارية</strong>، ومحمية بموجب القوانين النافذة.</p>

  <h3 class="text-xl font-bold text-ink mt-8 mb-3">6. القانون الواجب التطبيق</h3>
  <p>تخضع هذه الشروط والأحكام وتُفسر وفقاً للأنظمة والقوانين المعمول بها في الجمهورية اليمنية، وتختص محاكم مدينة عدن بالنظر في أي نزاع قد ينشأ بخصوصها.</p>
</div>',
            ],

            'privacy_policy' => [
                'background_image' => null,
                'description' => '<div class="space-y-6 text-base leading-relaxed text-muted">
  <p class="text-lg font-medium text-ink">نحن في <strong>متجر بن هويش</strong> نعتز بثقتكم الغالية، ونلتزم التزاماً صارماً بحماية خصوصية وأمان بياناتكم الشخصية وفق أعلى المعايير المعتمدة عالمياً.</p>

  <h3 class="text-xl font-bold text-ink mt-8 mb-3">1. البيانات التي نجمعها</h3>
  <ul class="list-disc list-inside space-y-2 pr-2">
    <li><strong>معلومات الاتصال والهوية:</strong> الاسم الكامل، رقم الهاتف المحمول، وعنوان التوصيل التفصيلي (المدينة، المديرية، الحي، أقرب معلم).</li>
    <li><strong>تفاصيل الطلبات والمشتريات:</strong> المنتجات المختارة، المقاسات، الألوان، سجل الطلبات السابقة، وتفضيلات الأزياء.</li>
    <li><strong>بيانات الدفع:</strong> في حال الدفع الإلكتروني أو التحويل البنكي، نقوم بالتحقق من إشعار التحويل فقط دون حفظ بيانات سرية للبطاقات أو الحسابات.</li>
    <li><strong>البيانات التقنية:</strong> عنوان IP، نوع المتصفح والجهاز لتوفير أداء سلس لعروض الأقمشة والأحذية التفاعلية ثلاثية الأبعاد.</li>
  </ul>

  <h3 class="text-xl font-bold text-ink mt-8 mb-3">2. الغرض من جمع واستخدام البيانات</h3>
  <p>تُستخدم بياناتكم الشخصية حصراً للأغراض التشغيلية التالية:</p>
  <ul class="list-disc list-inside space-y-2 pr-2">
    <li>تجهيز طلباتكم وشحنها وتسليمها بدقة وأمان.</li>
    <li>إرسال تحديثات حالة الطلب عبر واتساب أو الرسائل النصية.</li>
    <li>تقديم الدعم الفني وخدمة العملاء والرد على استفساراتكم.</li>
    <li>تحسين واجهة المستخدم وتجربة التسوق الرقمية في المتجر.</li>
  </ul>

  <h3 class="text-xl font-bold text-ink mt-8 mb-3">3. حماية البيانات وعدم المشاركة مع أطراف ثالثة</h3>
  <p>نؤكد لكم أننا لا نقوم ببيع أو تأجير أو مقايضة بياناتكم مع أي جهة خارجية أو شركات إعلانية. وتقتصر مشاركة بيانات العنوان ورقم الهاتف فقط مع مندوبي التوصيل الداخليين أو شركات النقل الرسمية لإيصال شحنتكم.</p>

  <h3 class="text-xl font-bold text-ink mt-8 mb-3">4. حقوقكم في إدارة البيانات الشخصية</h3>
  <p>يحق لكم في أي وقت مراجعة بياناتكم المسجلة لدينا، أو طلب تعديلها أو حذفها عبر حسابكم الشخصي أو عبر التواصل مع خدمة العملاء.</p>
</div>',
            ],

            'return_policy' => [
                'background_image' => null,
                'description' => '<div class="space-y-6 text-base leading-relaxed text-muted">
  <p class="text-lg font-medium text-ink">نهدف دائماً إلى أن تنال كل قطعة تختارونها من <strong>بن هويش</strong> كامل إعجابكم ورضاكم. إذا لم يكن المقاس مناسباً أو رغبتم في استبدال القطعة، فقد جعلنا إجراءاتنا سهلة ومرنة.</p>

  <h3 class="text-xl font-bold text-ink mt-8 mb-3">مهلة الاستبدال والاسترجاع</h3>
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 my-4">
    <div class="rounded-2xl border border-line bg-card p-5">
      <div class="text-2xl font-bold text-ink mb-1">5 أيام</div>
      <div class="text-sm font-semibold text-brand mb-2">مهلة الاستبدال</div>
      <p class="text-xs text-muted leading-normal">تتيح لكم استبدال المقاس أو اللون أو تبديل القطعة بمنتج آخر من المتجر خلال 5 أيام من الاستلام.</p>
    </div>
    <div class="rounded-2xl border border-line bg-card p-5">
      <div class="text-2xl font-bold text-ink mb-1">3 أيام</div>
      <div class="text-sm font-semibold text-brand mb-2">مهلة الاسترجاع</div>
      <p class="text-xs text-muted leading-normal">يحق لكم طلب استرجاع المنتج واسترداد قيمته خلال 3 أيام من تاريخ استلام الشحنة.</p>
    </div>
  </div>

  <h3 class="text-xl font-bold text-ink mt-8 mb-3">شروط قبول الاستبدال والاسترجاع</h3>
  <ol class="list-decimal list-inside space-y-2 pr-2">
    <li>أن تكون القطعة في حالتها الأصلية الجديدة تماماً، غير ملبوسة أو مستخدمة أو مغسولة.</li>
    <li>وجود كافة البطاقات والملصقات التعريفية للعلامة التجارية (Tags) سليمة ومثبتة على القطعة.</li>
    <li>سلامة علبة الحذاء أو غلاف المنتج الأصلي دون أي تلف أو كتابة عليها.</li>
    <li>إرفاق فاتورة الشراء الأصلية أو الرقم المرجعي للطلب.</li>
  </ol>

  <h3 class="text-xl font-bold text-ink mt-8 mb-3">المنتجات المستثناة من الاسترجاع</h3>
  <p>لدواعي الصحة والسلامة العامة، لا تشمل سياسة الاسترجاع: الملابس الداخلية، الجوارب، والإكسسوارات الملامسة للبشرة، أو القطع التي خضعت للتعديل أو التقصير حسب رغبة العميل الخاصة.</p>

  <h3 class="text-xl font-bold text-ink mt-8 mb-3">آلية تقديم طلب استبدال أو إرجاع</h3>
  <p>يُرجى التواصل مباشرة مع فريق خدمة العملاء عبر واتساب <strong><a href="https://wa.me/967780024406" class="text-brand underline font-semibold">780024406</a></strong>، مع تزويدنا برقم الطلب وصورة القطعة والسبب، وسيتم ترتيب استلام القطعة فوراً.</p>
</div>',
            ],

            'refund_policy' => [
                'background_image' => null,
                'description' => '<div class="space-y-6 text-base leading-relaxed text-muted">
  <p class="text-lg font-medium text-ink">نضمن لعملائنا الكرام استرداد مستحقاتهم المالية بكل نزاهة وشفافية وسرعة وفق الضوابط الموضحة أدناه.</p>

  <h3 class="text-xl font-bold text-ink mt-8 mb-3">طريقة استلام المبلغ المسترد</h3>
  <ul class="list-disc list-inside space-y-2 pr-2">
    <li><strong>طلبات الدفع عند الاستلام (COD):</strong> يتم تحويل المبلغ المسترد عبر حوالة صرافة رسمية باسم صاحب الطلب عبر (بنك الكريمي، بنك القطيبي، شبكة النجم، أو شبكة الامتياز)، أو استلامه نقداً من معرضنا في عدن.</li>
    <li><strong>التحويلات المصرفية والمحافظ:</strong> يُعاد تحويل المبلغ إلى نفس الحساب البنكي أو المحفظة الإلكترونية التي تم السداد منها.</li>
    <li><strong>رصيد محفظة المتجر:</strong> يمكن للعميل طلب إيداع القيمة كرصيد فوري في حسابه بالمتجر لاستخدامه في مشترياته القادمة في أي وقت.</li>
  </ul>

  <h3 class="text-xl font-bold text-ink mt-8 mb-3">المدة الزمنية لمعالجة الاسترداد</h3>
  <p>يتم تحويل المبلغ المالي المسترد خلال <strong>2 إلى 4 أيام عمل</strong> بعد وصول الشحنة المرتجعة إلى مركز المعالجة في عدن وفحصها من قبل قسم الجودة للتأكد من استيفائها شروط الإرجاع.</p>

  <h3 class="text-xl font-bold text-ink mt-8 mb-3">سياسة رسوم الشحن والتوصيل</h3>
  <ul class="list-disc list-inside space-y-2 pr-2">
    <li>إذا كان سبب الاسترجاع ناتجاً عن عيب مصنعي في القطعة أو خطأ في المقاس أو اللون من قبل المتجر، يتحمل متجر بن هويش كامل نفقات الشحن والتوصيل، ويُسترد للعميل كامل المبلغ المدفوع.</li>
    <li>إذا كان الاسترجاع بناءً على رغبة العميل الشخصية (بدون وجود عيب أو خطأ في الطلب)، فسيتم خصم قيمة رسوم التوصيل الفعلية وإعادة باقي المبلغ.</li>
  </ul>
</div>',
            ],

            'cancellation_policy' => [
                'background_image' => null,
                'description' => '<div class="space-y-6 text-base leading-relaxed text-muted">
  <p class="text-lg font-medium text-ink">يسرنا في <strong>متجر بن هويش</strong> تقديم تجربة تسوق مريحة تتسم بالمرونة والوضوح في كافة مراحل الطلب.</p>

  <h3 class="text-xl font-bold text-ink mt-8 mb-3">إلغاء الطلب قبل الشحن (مجاناً)</h3>
  <p>يحق للعميل إلغاء أي طلب قام بتسجيله دون أي تكاليف أو غرامات على الإطلاق، شريطة أن يكون الطلب في مرحلة <strong>"قيد المراجعة"</strong> أو <strong>"قيد التجهيز"</strong> وقبل خروج الشحنة مع مندوب التوصيل أو تسليمها لمكتب النقل بين المحافظات.</p>

  <h3 class="text-xl font-bold text-ink mt-8 mb-3">إلغاء الطلب بعد خروجه للتوصيل</h3>
  <p>في حال تم تسليم الطلب إلى مندوب التوصيل الداخلي في عدن أو لشركة الشحن، ورغب العميل في الإلغاء عند وصول المندوب، يحق للمتجر مطالبة العميل بسداد قيمة رسوم التوصيل والانتقال الفعلية فقط، مع إمكانية استرجاع قيمة المنتجات كاملة.</p>

  <h3 class="text-xl font-bold text-ink mt-8 mb-3">خطوات طلب الإلغاء</h3>
  <p>لإلغاء طلبكم بشكل فوري، يُرجى مراسلة خدمة العملاء عبر واتساب <strong><a href="https://wa.me/967780024406" class="text-brand underline font-semibold">780024406</a></strong> مع تزويدنا برقم الطلب ورقم الهاتف المسجل، ليتم إيقاف الشحنة وتأكيد الإلغاء على الفور.</p>
</div>',
            ],

            'about_us' => [
                'background_image' => null,
                'description' => '<div class="space-y-6 text-base leading-relaxed text-muted">
  <p class="text-lg font-medium text-ink">أهلاً بكم في <strong>متجر بن هويش للأزياء والأحذية</strong>، عنوان الأناقة الرفيعة والذوق الأصيل في العاصمة عدن وكافة ربوع اليمن.</p>

  <h3 class="text-xl font-bold text-ink mt-8 mb-3">قصة بن هويش</h3>
  <p>انطلقت <strong>شركة بن هويش التجارية</strong> برؤية واضحة تهدف إلى إعادة تعريف مفهوم الأزياء الرجالية والشبابية والولادية في السوق اليمني، من خلال المزج المتناغم بين عراقة الهوية التراثية الأصيلة وأحدث صيحات الموضة العالمية المعاصرة. نحرص على انتقاء أفضل الخامات والأقمشة القطنية والصوفية، وأرقى تصاميم الأحذية والبدلات والمعاوز المتقنة، لتلبي تطلعات الرجل العصري في كل مناسبة.</p>

  <h3 class="text-xl font-bold text-ink mt-8 mb-3">رؤيتنا ورسالتنا</h3>
  <p>نسعى لأن نكون الوجهة الأولى والملاذ الموثوق لكل من يبحث عن الأناقة المتكاملة والجودة الاستثنائية، من خلال الجمع بين صالات عرض متميزة في عدن ومنصة تسوق رقمية متطورة توفر تجربة ثلاثية الأبعاد تفاعلية غير مسبوقة تضع أدق تفاصيل المنتجات بين أيديكم.</p>

  <h3 class="text-xl font-bold text-ink mt-8 mb-3">ما الذي يميزنا؟</h3>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 my-6">
    <div class="rounded-2xl border border-line bg-card p-5">
      <div class="text-brand font-bold text-lg mb-2">جودة لا تساوم</div>
      <p class="text-xs text-muted leading-relaxed">أقمشة مختارة من كبرى بيوت النسيج العالمية، وخياطة دقيقة تضمن ثبات الألوان وراحة الارتداء الدائمة.</p>
    </div>
    <div class="rounded-2xl border border-line bg-card p-5">
      <div class="text-brand font-bold text-lg mb-2">تشكيلات حصرية ومتنوعة</div>
      <p class="text-xs text-muted leading-relaxed">من البدلات الرسمية والأحذية الفاخرة إلى المعاوز التراثية والملابس الشبابية اليومية والجامبو.</p>
    </div>
    <div class="rounded-2xl border border-line bg-card p-5">
      <div class="text-brand font-bold text-lg mb-2">خدمة وتوصيل فوري</div>
      <p class="text-xs text-muted leading-relaxed">توصيل سريع وموثوق داخل عدن ولكافة المحافظات اليمنية، مع خدمة عملاء واستشارات مظهر مستمرة.</p>
    </div>
  </div>

  <h3 class="text-xl font-bold text-ink mt-8 mb-3">تفضلوا بزيارتنا في صالة العرض</h3>
  <p>يسعدنا ويسرنا دائماً أن نرحب بكم في معرضنا الرئيسي: <strong>عدن – المنصورة – خط الحجاز</strong>، حيث ينتظركم فريقنا المتخصص ليقدم لكم تجربة استثنائية تناسب ذوقكم الرفيع.</p>
</div>',
            ],

            'faq' => [
                'background_image' => null,
                'description' => '<div class="space-y-6 text-base leading-relaxed text-muted">
  <p class="text-lg font-medium text-ink">جمعنا لكم هنا إجابات وافية ومفصلة عن أكثر الأسئلة شيوعاً حول التسوق والطلبات وخدمات التوصيل لدى متجر بن هويش.</p>

  <div class="space-y-4 my-6">
    <div class="rounded-2xl border border-line bg-card p-6">
      <h3 class="text-lg font-bold text-ink mb-2">1. كم يستغرق توصيل الطلب في عدن والمحافظات الأخرى؟</h3>
      <p class="text-sm text-muted leading-relaxed">داخل مدينة <strong>عدن</strong>، يتم توصيل الطلب خلال <strong>24 إلى 48 ساعة</strong> كحد أقصى عبر مندوبي التوصيل التابعين للمتجر. أما بالنسبة لبقية <strong>المحافظات اليمنية</strong> (حضرموت، صنعاء، تعز، مأرب، شبوة، إب، وغيرها) فيستغرق الشحن بين <strong>2 إلى 4 أيام عمل</strong> عبر مكاتب وشركات النقل المعتمدة.</p>
    </div>

    <div class="rounded-2xl border border-line bg-card p-6">
      <h3 class="text-lg font-bold text-ink mb-2">2. ما هي خيارات وطرق الدفع المتوفرة في المتجر؟</h3>
      <p class="text-sm text-muted leading-relaxed">نوفر عدة وسائل دفع لتسهيل الشراء على عملائنا:</p>
      <ul class="list-disc list-inside space-y-1 mt-2 text-sm text-muted pr-2">
        <li>الدفع نقداً عند الاستلام (COD) داخل مدينة عدن.</li>
        <li>التحويل البنكي المباشر عبر حساباتنا في (بنك الكريمي للتمويل الأصغر، بنك القطيبي الإسلامي، بنك البسيري).</li>
        <li>التحويل عبر شبكات الصرافة والتحويلات المحلية (النجم، الامتياز، يمن إكسبرس).</li>
        <li>الدفع المباشر عبر المحافظ الإلكترونية المتوافقة.</li>
      </ul>
    </div>

    <div class="rounded-2xl border border-line bg-card p-6">
      <h3 class="text-lg font-bold text-ink mb-2">3. كيف يمكنني التأكد من اختيار المقاس الصحيح؟</h3>
      <p class="text-sm text-muted leading-relaxed">تحتوي صفحة كل منتج على دليل وجدول مقاسات دقيق يوضح قياسات الصدر والخصر والطول للأزياء، وقياسات الأقدام بالسنتيمتر والمقاس الأوروبي (EU) للأحذية. كما يسعد فريق خدمة العملاء عبر واتساب تقديم المشورة المباشرة بناءً على طولكم ووزنكم.</p>
    </div>

    <div class="rounded-2xl border border-line bg-card p-6">
      <h3 class="text-lg font-bold text-ink mb-2">4. هل يمكنني معاينة القطعة والتأكد منها عند وصول المندوب؟</h3>
      <p class="text-sm text-muted leading-relaxed">نعم، لطلبات مدينة عدن، نتيح لعملائنا معاينة المنتج والتأكد من المقاس والجودة وسلامة القطعة بحضور مندوب التوصيل قبل السداد النهائي.</p>
    </div>

    <div class="rounded-2xl border border-line bg-card p-6">
      <h3 class="text-lg font-bold text-ink mb-2">5. هل جميع الملبوسات والأحذية أصلية وذات جودة مضمونة؟</h3>
      <p class="text-sm text-muted leading-relaxed">نعم بكل تأكيد، تضمن شركة بن هويش التجارية أن جميع المعروضات في أقسام البدلات والأحذية والقمصان والمعاوز التراثية مصنعة وفق أعلى معايير الجودة وخاضعة للفحص قبل الشحن لضمان خلوها التام من أي عيوب.</p>
    </div>

    <div class="rounded-2xl border border-line bg-card p-6">
      <h3 class="text-lg font-bold text-ink mb-2">6. ما هي مواعيد العمل وأين يقع معرضكم في عدن؟</h3>
      <p class="text-sm text-muted leading-relaxed">يسعدنا استقبالكم في صالة عرضنا الرئيسية الكائنة في: <strong>عدن – المنصورة – خط الحجاز</strong>. تفتح صالة العرض أبوابها يومياً من الساعة 9:00 صباحاً حتى 11:00 مساءً، كما يمكنكم التواصل مع خدمة العملاء هاتفياً أو عبر واتساب على الرقم: <strong><a href="https://wa.me/967780024406" class="text-brand underline font-semibold">780024406</a></strong>.</p>
    </div>
  </div>
</div>',
            ],
        ];

        foreach ($policies as $key => $data) {
            $jsonValue = json_encode($data, JSON_UNESCAPED_UNICODE);

            $exists = DB::table('business_settings')->where('key', $key)->exists();
            if ($exists) {
                DB::table('business_settings')->where('key', $key)->update([
                    'value' => $jsonValue,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('business_settings')->insert([
                    'key' => $key,
                    'value' => $jsonValue,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
