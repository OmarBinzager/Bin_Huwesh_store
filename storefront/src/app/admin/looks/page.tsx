import type { Metadata } from "next";
import { headers } from "next/headers";
import { notFound } from "next/navigation";
import { connection } from "next/server";
import LooksAdmin from "@/components/admin/LooksAdmin";
import { DemoBagProvider } from "@/components/store/DemoBag";
import { adminEnabled, isLocalRequest } from "@/lib/admin-guard";
import { readLooks } from "@/lib/looks-store";

export const metadata: Metadata = {
  title: "إدارة الإطلالات (محلي) — بن هويش",
  robots: { index: false, follow: false },
};

/**
 * Local looks editor — development only, and only from this machine.
 * It edits src/content/looks.json, the same file the home page reads.
 */
export default async function LooksAdminPage() {
  await connection(); // always read the file fresh
  if (!adminEnabled()) notFound();
  const h = await headers();
  if (!isLocalRequest(new Request("http://x", { headers: { host: h.get("host") ?? "" } }))) notFound();

  const data = await readLooks();
  return (
    <DemoBagProvider>
      <LooksAdmin initial={data.looks} />
    </DemoBagProvider>
  );
}
