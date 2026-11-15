import FadeIn from "@/components/ui/FadeIn";
import { services } from "@/data/services";

export default function ServicesSection() {
  return (
    <section
      id="services"
      className="relative z-0 rounded-t-[40px] bg-white px-5 py-20 sm:rounded-t-[50px] sm:px-8 sm:py-24 md:rounded-t-[60px] md:px-10 md:py-32"
    >
      <FadeIn y={40}>
        <h2
          className="text-center font-black uppercase leading-none tracking-tight text-ink mb-16 sm:mb-20 md:mb-28"
          style={{ fontSize: "clamp(3rem, 12vw, 160px)" }}
        >
          Services
        </h2>
      </FadeIn>

      <ul className="mx-auto max-w-5xl list-none">
        {services.map((service, index) => (
          <FadeIn
            as="li"
            key={service.number}
            delay={index * 0.1}
            className="flex items-start gap-5 py-8 sm:gap-8 sm:py-10 md:py-12"
            style={{
              borderTop: "1px solid rgba(12, 12, 12, 0.15)",
              borderBottom:
                index === services.length - 1
                  ? "1px solid rgba(12, 12, 12, 0.15)"
                  : undefined,
            }}
          >
            <span
              className="shrink-0 font-black leading-none text-ink"
              style={{ fontSize: "clamp(3rem, 10vw, 140px)" }}
            >
              {service.number}
            </span>

            <div className="flex flex-col gap-2 pt-1 sm:gap-3">
              <h3
                className="font-medium uppercase leading-tight text-ink"
                style={{ fontSize: "clamp(1rem, 2.2vw, 2.1rem)" }}
              >
                {service.name}
              </h3>
              <p
                className="max-w-2xl font-light leading-relaxed text-ink opacity-60"
                style={{ fontSize: "clamp(0.85rem, 1.6vw, 1.25rem)" }}
              >
                {service.description}
              </p>
            </div>
          </FadeIn>
        ))}
      </ul>
    </section>
  );
}
