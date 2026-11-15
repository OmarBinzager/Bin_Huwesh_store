import type { Metadata } from "next";
import { headers } from "next/headers";
import { notFound } from "next/navigation";
import { connection } from "next/server";
import ClosingAdmin from "@/components/admin/ClosingAdmin";
import { adminEnabled, isLocalRequest } from "@/lib/admin-guard";
import { readClosing } from "@/lib/closing-store";

export const metadata: Metadata = {
  title: "إدارة الآراء والتواصل (محلي) — بن هويش",
  robots: { index: false, follow: false },
};

/** Local editor for the page ending — development only, and only from this machine. */
export default async function ClosingAdminPage() {
  await connection();
  if (!adminEnabled()) notFound();
  const h = await headers();
  if (!isLocalRequest(new Request("http://x", { headers: { host: h.get("host") ?? "" } }))) notFound();
  return <ClosingAdmin initial={await readClosing()} />;
}
