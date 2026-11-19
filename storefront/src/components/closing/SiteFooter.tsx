"use client";

import Link from "next/link";
import type { CSSProperties } from "react";
import BrandLogo from "@/components/store/BrandLogo";
import { closingData } from "@/content/closing";
import { telHref, whatsappHref, type ClosingFile } from "@/content/closing-types";
import { useI18n } from "@/lib/i18n/context";
import { BackToTop, CurrentYear, FooterGroup } from "./FooterParts";
import { PhoneIcon, PinIcon, SOCIAL_META, WhatsAppIcon } from "./icons";
import s from "./closing.module.css";

const BUILD_YEAR = new Date().getFullYear();

/**
 * Site footer: logo + tagline + always-visible contact, link groups
 * (collapsible on phones), social accounts only when the admin has set real
 * ones, the large faint BIN HUWESH wordmark, copyright and back-to-top.
 */
export default function SiteFooter({ data }: { data?: ClosingFile }) {
  const closing = data || closingData;
  const { footer, contact } = closing;
  const { t, locale } = useI18n();
  return (
    <footer className={s.footer} aria-labelledby="footer-title">
      <h2 id="footer-title" className="sr-only">
        {t.sections.footer.siteInfoSr}
      </h2>
      <div className="mx-auto max-w-[1440px] px-4 pt-12 sm:px-8 sm:pt-16">
        <div className={s.fGrid} style={{ "--groups": footer.groups.length } as CSSProperties}>
          <div className={s.fBrand}>
            <Link href="/" aria-label={t.meta.brandHomeAria} className={s.fLogo}>
              <BrandLogo height={50} className="hidden sm:inline-block" />
              <BrandLogo height={42} className="inline-block sm:hidden" />
            </Link>
            <p className={s.tagline}>{footer.tagline}</p>
            <ul className={s.fContact}>
              <li>
                <PinIcon width={17} height={17} />
                <span>{contact.address}</span>
              </li>
              <li>
                <PhoneIcon width={17} height={17} />
                <a href={telHref(contact.phoneIntl)} dir="ltr" className={s.fLink}>
                  {contact.phoneDisplay}
                </a>
              </li>
              <li>
                <WhatsAppIcon width={17} height={17} />
                <a href={whatsappHref(contact.phoneIntl)} target="_blank" rel="noopener noreferrer" className={s.fLink}>
                  {t.sections.footer.whatsapp}
                </a>
              </li>
            </ul>
            {footer.social.length > 0 && (
              <ul className={s.social} aria-label={t.sections.footer.socialAria}>
                {footer.social.map((x) => {
                  const m = SOCIAL_META[x.platform];
                  const Icon = m.icon;
                  return (
                    <li key={x.platform}>
                      <a href={x.url} target="_blank" rel="noopener noreferrer" aria-label={locale === "en" ? m.labelEn : m.label} className={s.socialBtn}>
                        <Icon width={18} height={18} />
                      </a>
                    </li>
                  );
                })}
              </ul>
            )}
          </div>
          {footer.groups.map((g) => (
            <FooterGroup key={g.id} group={g} />
          ))}
        </div>

        <p className={s.wordmark} aria-hidden dir="ltr">
          BIN HUWESH
        </p>

        <div className={s.fBottom}>
          <p>
            © <CurrentYear fallback={BUILD_YEAR} /> {contact.company}. {locale === "en" ? "All rights reserved." : "جميع الحقوق محفوظة."}
          </p>
          <BackToTop />
        </div>
      </div>
    </footer>
  );
}
