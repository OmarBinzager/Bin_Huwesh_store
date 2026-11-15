import type { Metadata } from "next";
import { headers } from "next/headers";
import { notFound } from "next/navigation";
import { connection } from "next/server";
import CategoriesAdmin from "@/components/admin/CategoriesAdmin";
import { adminEnabled, isLocalRequest } from "@/lib/admin-guard";
import { readShopCategories } from "@/lib/categories-store";

export const metadata: Metadata = {
  title: "إدارة الأقسام (محلي) — بن هويش",
  robots: { index: false, follow: false },
};

/** Local editor for the «تسوّق حسب القسم» cards — development only, and only from this machine. */
export default async function CategoriesAdminPage() {
  await connection();
  if (!adminEnabled()) notFound();
  const h = await headers();
  if (!isLocalRequest(new Request("http://x", { headers: { host: h.get("host") ?? "" } }))) notFound();
  return <CategoriesAdmin initial={await readShopCategories()} />;
}
