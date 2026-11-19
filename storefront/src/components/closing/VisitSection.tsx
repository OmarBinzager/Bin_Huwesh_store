"use client";

import { telHref, whatsappHref, type ClosingFile } from "@/content/closing-types";
import { useI18n } from "@/lib/i18n/context";
import { MapIcon, PhoneIcon, PinIcon, StoreIcon, WhatsAppIcon } from "./icons";
import s from "./closing.module.css";

/**
 * «ننتظرك في بن هويش» — showroom and contact. WhatsApp is the primary (red)
 * action; the map button only exists when a real map link is set.
 */
export default function VisitSection({ data }: { data: ClosingFile }) {
  const { locale } = useI18n();
  const c = data.contact;
  const isEn = locale === "en";
  return (
    <section id="visit" aria-labelledby="visit-title" className={s.closingBand}>
      <div className="mx-auto max-w-[1440px] px-4 py-12 sm:px-8 sm:py-16">
        <div className={s.visit}>
          <div className={s.glow} aria-hidden />
          <div className={s.visitText}>
            <h2 id="visit-title" className={s.visitTitle}>
              {keepNameTogether(c.title)}
            </h2>
            <p className={s.visitSub}>{c.subtitle}</p>
            <div className={s.actions}>
              <a href={whatsappHref(c.phoneIntl)} target="_blank" rel="noopener noreferrer" className={`${s.btn} ${s.btnPrimary}`}>
                <WhatsAppIcon />
                {isEn ? "Chat on WhatsApp" : "تواصل عبر واتساب"}
              </a>
              <a href={telHref(c.phoneIntl)} className={`${s.btn} ${s.btnGhost}`}>
                <PhoneIcon />
                {isEn ? "Call Us" : "اتصل بنا"}
              </a>
              {c.mapUrl && (
                <a href={c.mapUrl} target="_blank" rel="noopener noreferrer" className={`${s.btn} ${s.btnGhost}`}>
                  <MapIcon />
                  {isEn ? "Showroom Location" : "موقع المعرض"}
                </a>
              )}
            </div>
          </div>
          <address className={s.info}>
            <p className={s.infoRow}>
              <span className={s.infoIcon}>
                <StoreIcon />
              </span>
              <span>
                <span className={s.infoLabel}>{isEn ? "Company" : "الشركة"}</span>
                <span className={s.infoValue}>{c.company}</span>
              </span>
            </p>
            <p className={s.infoRow}>
              <span className={s.infoIcon}>
                <PinIcon />
              </span>
              <span>
                <span className={s.infoLabel}>{isEn ? "Showroom" : "المعرض"}</span>
                <span className={s.infoValue}>{c.address}</span>
              </span>
            </p>
            <p className={s.infoRow}>
              <span className={s.infoIcon}>
                <PhoneIcon />
              </span>
              <span>
                <span className={s.infoLabel}>{isEn ? "Phone" : "الهاتف"}</span>
                <a href={telHref(c.phoneIntl)} className={`${s.infoValue} ${s.phone}`} dir="ltr">
                  {c.phoneDisplay}
                </a>
              </span>
            </p>
          </address>
        </div>
      </div>
    </section>
  );
}

/** «بن هويش» never breaks across two lines in the big title. */
function keepNameTogether(text: string) {
  return text.replace(/بن\s+هويش/g, "بن هويش");
}
