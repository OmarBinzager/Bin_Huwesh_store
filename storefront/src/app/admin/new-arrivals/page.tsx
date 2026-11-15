import type { Metadata } from "next";
import { headers } from "next/headers";
import { notFound } from "next/navigation";
import { connection } from "next/server";
import NewArrivalsAdmin from "@/components/admin/NewArrivalsAdmin";
import { DemoBagProvider } from "@/components/store/DemoBag";
import { adminEnabled, isLocalRequest } from "@/lib/admin-guard";
import { readNewArrivals } from "@/lib/new-arrivals-store";

export const metadata: Metadata = {
  title: "إدارة وصل حديثًا (محلي) — بن هويش",
  robots: { index: false, follow: false },
};

/** Local editor for «وصل حديثًا» — development only, and only from this machine. */
export default async function NewArrivalsAdminPage() {
  await connection();
  if (!adminEnabled()) notFound();
  const h = await headers();
  if (!isLocalRequest(new Request("http://x", { headers: { host: h.get("host") ?? "" } }))) notFound();
  return (
    <DemoBagProvider>
      <NewArrivalsAdmin initial={await readNewArrivals()} />
    </DemoBagProvider>
  );
}
