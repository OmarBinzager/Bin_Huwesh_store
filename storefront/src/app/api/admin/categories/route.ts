import { adminDenied } from "@/lib/admin-guard";
import { readShopCategories, writeShopCategories } from "@/lib/categories-store";
import { ValidationError } from "@/lib/looks-store";

/** GET: current shop-categories.json — PUT: validate and replace it (dev, local only). */
export async function GET(req: Request) {
  const denied = adminDenied(req);
  if (denied) return denied;
  return Response.json(await readShopCategories());
}

export async function PUT(req: Request) {
  const denied = adminDenied(req);
  if (denied) return denied;
  try {
    return Response.json(await writeShopCategories(await req.json()));
  } catch (e) {
    if (e instanceof ValidationError || e instanceof SyntaxError) return Response.json({ error: e.message }, { status: 400 });
    throw e;
  }
}
