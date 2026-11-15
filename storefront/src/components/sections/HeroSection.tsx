import ContactButton from "@/components/ui/ContactButton";
import FadeIn from "@/components/ui/FadeIn";
import Magnet from "@/components/ui/Magnet";
import { hero, navLinks } from "@/data/site";

export default function HeroSection() {
  return (
    // Background intentionally transparent: the shared atmospheric
    // background mounted in page.tsx shows through.
    <section className="relative flex h-screen flex-col overflow-x-clip">
      <FadeIn as="nav" delay={0} y={-20}>
        <ul className="flex list-none items-center justify-between px-6 pt-6 md:px-10 md:pt-8">
          {navLinks.map((link) => (
            <li key={link.label}>
              <a
                href={link.href}
                dir="rtl"
                lang="ar"
                className="text-mist font-medium text-sm md:text-lg lg:text-[1.4rem] transition-opacity duration-200 hover:opacity-70"
              >
                {link.label}
              </a>
            </li>
          ))}
        </ul>
      </FadeIn>

      <div className="overflow-hidden">
        <FadeIn delay={0.15} y={40}>
          <h1 className="hero-heading w-full whitespace-nowrap text-center font-black uppercase tracking-tight leading-none text-[14vw] sm:text-[15vw] md:text-[16vw] lg:text-[17.5vw] mt-6 sm:mt-4 md:-mt-5">
            {hero.heading}
          </h1>
        </FadeIn>
      </div>

      <div className="mt-auto flex items-end justify-between px-6 pb-7 sm:pb-8 md:px-10 md:pb-10">
        <FadeIn delay={0.35} y={20}>
          <p
            dir="rtl"
            lang="ar"
            className="text-mist font-light leading-snug whitespace-pre-line text-left max-w-[160px] sm:max-w-[220px] md:max-w-[260px]"
            style={{ fontSize: "clamp(0.75rem, 1.4vw, 1.5rem)" }}
          >
            {hero.tagline}
          </p>
        </FadeIn>

        <FadeIn delay={0.5} y={20}>
          <ContactButton
            label={hero.ctaLabel}
            className="[letter-spacing:normal]"
          />
        </FadeIn>
      </div>

      <FadeIn
        delay={0.6}
        y={30}
        className="pointer-events-none absolute left-1/2 z-10 w-[320px] -translate-x-1/2 top-1/2 -translate-y-1/2 sm:top-auto sm:bottom-0 sm:translate-y-0 sm:w-[min(420px,64vh)] md:w-[min(490px,64vh)] lg:w-[min(570px,64vh)]"
      >
        <Magnet
          padding={150}
          strength={3}
          activeTransition="transform 0.3s ease-out"
          inactiveTransition="transform 0.6s ease-in-out"
          className="pointer-events-auto"
        >
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img
            src={hero.portrait}
            alt="Bin Huwesh emblem"
            className="w-full select-none"
            draggable={false}
          />
        </Magnet>
      </FadeIn>
    </section>
  );
}
