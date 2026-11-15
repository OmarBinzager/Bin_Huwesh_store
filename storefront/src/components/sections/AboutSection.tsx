import AnimatedText from "@/components/ui/AnimatedText";
import ContactButton from "@/components/ui/ContactButton";
import FadeIn from "@/components/ui/FadeIn";
import { about, aboutDecor } from "@/data/site";

export default function AboutSection() {
  return (
    <section
      id="about"
      className="relative flex min-h-screen flex-col items-center justify-center overflow-x-clip bg-ink px-5 py-20 sm:px-8 md:px-10"
    >
      {aboutDecor.map((item) => (
        <FadeIn
          key={item.key}
          delay={item.delay}
          duration={0.9}
          x={item.x}
          y={0}
          className={`pointer-events-none absolute ${item.className} ${item.width}`}
        >
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src={item.src} alt="" aria-hidden="true" className="w-full" />
        </FadeIn>
      ))}

      <div className="relative z-10 flex flex-col items-center gap-16 sm:gap-20 md:gap-24">
        <div className="flex flex-col items-center gap-10 sm:gap-14 md:gap-16">
          <FadeIn delay={0} y={40}>
            <h2
              className="hero-heading text-center font-black uppercase leading-none tracking-tight"
              style={{ fontSize: "clamp(3rem, 12vw, 160px)" }}
            >
              {about.heading}
            </h2>
          </FadeIn>

          <AnimatedText
            text={about.body}
            className="text-mist max-w-[560px] text-center font-medium leading-relaxed"
            style={{ fontSize: "clamp(1rem, 2vw, 1.35rem)" }}
          />
        </div>

        <div id="contact">
          <FadeIn delay={0.1}>
            <ContactButton />
          </FadeIn>
        </div>
      </div>
    </section>
  );
}
