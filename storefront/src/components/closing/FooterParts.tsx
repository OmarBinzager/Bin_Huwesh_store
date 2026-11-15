"use client";

import Link from "next/link";
import { useId, useState, useSyncExternalStore } from "react";
import type { FooterGroup as Group } from "@/content/closing-types";
import { useI18n } from "@/lib/i18n/context";
import { ArrowUpIcon, ChevronIcon } from "./icons";
import s from "./closing.module.css";

/** One footer link group: a plain heading on desktop, a disclosure button on phones. */
export function FooterGroup({ group }: { group: Group }) {
  const [open, setOpen] = useState(false);
  const id = useId();
  return (
    <div className={s.fGroup} data-open={open ? "" : undefined}>
      <h3 className={s.fTitle}>{group.title}</h3>
      <button type="button" className={s.fToggle} aria-expanded={open} aria-controls={id} onClick={() => setOpen((v) => !v)}>
        {group.title}
        <ChevronIcon width={18} height={18} className={s.fChevron} />
      </button>
      <ul id={id} className={s.fList}>
        {group.links.map((l) => (
          <li key={l.href + l.label}>
            <FooterLink href={l.href}>{l.label}</FooterLink>
          </li>
        ))}
      </ul>
    </div>
  );
}

/** Internal pages through next/link, anchors / tel / external as plain links. */
export function FooterLink({ href, children, className }: { href: string; children: React.ReactNode; className?: string }) {
  const cls = className ?? s.fLink;
  if (href === "/" || href.startsWith("/categories/")) {
    return (
      <Link href={href} className={cls}>
        {children}
      </Link>
    );
  }
  const external = href.startsWith("https://");
  return (
    <a href={href} className={cls} {...(external ? { target: "_blank", rel: "noopener noreferrer" } : {})}>
      {children}
    </a>
  );
}

/** Back to the top of the page; focus goes to the first header link (keyboard users stay oriented). */
export function BackToTop() {
  const { t } = useI18n();
  return (
    <button
      type="button"
      className={s.toTop}
      aria-label={t.sections.footer.backToTop}
      onClick={() => {
        const reduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
        window.scrollTo({ top: 0, behavior: reduced ? "auto" : "smooth" });
        document.querySelector<HTMLElement>("header a[href]")?.focus({ preventScroll: true });
      }}
    >
      <ArrowUpIcon width={18} height={18} />
    </button>
  );
}

const noop = () => () => {};
/** The current year in the visitor's clock (the build year is only the server fallback). */
export function CurrentYear({ fallback }: { fallback: number }) {
  const year = useSyncExternalStore(noop, () => new Date().getFullYear(), () => fallback);
  return <span dir="ltr">{year}</span>;
}
