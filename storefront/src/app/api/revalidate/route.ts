import { revalidateTag } from "next/cache";
import { NextResponse } from "next/server";

export async function POST(req: Request) {
  const secret = process.env.REVALIDATE_SECRET;
  const authHeader = req.headers.get("x-revalidate-secret") || "";

  if (secret && authHeader !== secret) {
    return NextResponse.json({ ok: false, error: "Unauthorized" }, { status: 401 });
  }

  revalidateTag("storefront", "max");
  return NextResponse.json({ ok: true, revalidated: "storefront", at: new Date().toISOString() });
}
