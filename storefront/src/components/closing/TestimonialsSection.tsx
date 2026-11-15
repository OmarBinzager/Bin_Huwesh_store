import { verifiedRating, visibleTestimonials } from "@/content/closing";
import type { ClosingFile } from "@/content/closing-types";
import { StarIcon } from "./icons";
import Testimonials from "./Testimonials";
import s from "./closing.module.css";

/**
 * «قالوا عن بن هويش». Shows the visible testimonials; a summary line appears
 * only when there are VERIFIED rated reviews (computed, never typed in), and
 * a notice appears while any card is still a preview example.
 */
export default function TestimonialsSection({ data }: { data: ClosingFile }) {
  const items = visibleTestimonials(data);
  if (items.length === 0) return null;
  const { title, subtitle } = data.testimonials;
  const rating = verifiedRating(items);
  const previews = items.some((t) => !t.verified);
  return (
    <section id="testimonials" aria-labelledby="testimonials-title" className={`${s.closingBand} border-b border-line`}>
      <div className="mx-auto max-w-[1440px] px-4 py-12 sm:px-8 sm:py-16">
        <header className="mb-7 flex flex-wrap items-end justify-between gap-x-8 gap-y-3 sm:mb-9">
          <div>
            <h2 id="testimonials-title" className="font-display text-[28px] leading-tight font-semibold text-ink sm:text-4xl lg:text-[2.75rem]">
              {title}
            </h2>
            {subtitle && <p className="mt-2 text-[15px] text-muted sm:text-base">{subtitle}</p>}
          </div>
          {rating && (
            <p className={s.ratingSummary}>
              <StarIcon width={16} height={16} filled className="text-brand" />
              <span>
                <b dir="ltr">{rating.average.toFixed(1)}</b> من 5 · من <span dir="ltr">{rating.count}</span>{" "}
                {rating.count === 1 ? "رأي موثّق" : "آراء موثّقة"}
              </span>
            </p>
          )}
        </header>
        <Testimonials items={items} />
        {previews && (
          <p className="mt-4 text-[12px] text-muted">
            البطاقات المعلَّمة «مثال للمعاينة» نصوص تجريبية، وتُستبدل بآراء العملاء الموثّقة قبل الإطلاق.
          </p>
        )}
      </div>
    </section>
  );
}
