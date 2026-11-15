import type { Metadata } from "next";
import { headers } from "next/headers";
import { notFound } from "next/navigation";
import { connection } from "next/server";
import BrandsAdmin from "@/components/admin/BrandsAdmin";
import { adminEnabled, isLocalRequest } from "@/lib/admin-guard";
import { readBrands } from "@/lib/brands-store";

export const metadata: Metadata = {
  title: "إدارة الماركات (محلي) — بن هويش",
  robots: { index: false, follow: false },
};

/** Local brand-strip editor — development only, and only from this machine. */
export default async function BrandsAdminPage() {
  await connection();
  if (!adminEnabled()) notFound();
  const h = await headers();
  if (!isLocalRequest(new Request("http://x", { headers: { host: h.get("host") ?? "" } }))) notFound();
  return <BrandsAdmin initial={await readBrands()} />;
}
