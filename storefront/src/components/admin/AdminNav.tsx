import Link from "next/link";

type Section = "looks" | "brands" | "categories" | "new-arrivals" | "shoes" | "closing";

/** Section switcher shared by the local admin pages. */
export default function AdminNav({ current }: { current: Section }) {
  const item = (key: Section, href: string, label: string) => (
    <Link
      href={href}
      aria-current={current === key ? "page" : undefined}
      className={`rounded-full px-3.5 py-1.5 text-[12px] font-medium transition-colors ${
        current === key ? "bg-ink text-on-ink" : "text-muted hover:text-ink"
      }`}
    >
      {label}
    </Link>
  );
  return (
    <nav aria-label="أقسام الإدارة" className="flex rounded-full border border-line p-1">
      {item("looks", "/admin/looks", "الإطلالات")}
      {item("brands", "/admin/brands", "الماركات")}
      {item("categories", "/admin/categories", "الأقسام")}
      {item("new-arrivals", "/admin/new-arrivals", "وصل حديثًا")}
      {item("shoes", "/admin/shoes", "الأحذية")}
      {item("closing", "/admin/closing", "الآراء والتواصل")}
    </nav>
  );
}
