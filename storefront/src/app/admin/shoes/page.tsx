import type { Metadata } from "next";
import { headers } from "next/headers";
import { notFound } from "next/navigation";
import { connection } from "next/server";
import ShoesAdmin from "@/components/admin/ShoesAdmin";
import { DemoBagProvider } from "@/components/store/DemoBag";
import { adminEnabled, isLocalRequest } from "@/lib/admin-guard";
import { readShoes } from "@/lib/shoes-store";

export const metadata: Metadata = {
  title: "إدارة الأحذية (محلي) — بن هويش",
  robots: { index: false, follow: false },
};

/** Local editor for the shoe shelves — development only, and only from this machine. */
export default async function ShoesAdminPage() {
  await connection();
  if (!adminEnabled()) notFound();
  const h = await headers();
  if (!isLocalRequest(new Request("http://x", { headers: { host: h.get("host") ?? "" } }))) notFound();
  return (
    <DemoBagProvider>
      <ShoesAdmin initial={await readShoes()} />
    </DemoBagProvider>
  );
}
