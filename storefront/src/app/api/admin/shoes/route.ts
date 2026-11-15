import { adminDenied } from "@/lib/admin-guard";
import { ValidationError } from "@/lib/looks-store";
import { readShoes, writeShoes } from "@/lib/shoes-store";

/** GET: current shoes.json — PUT: validate and replace it (dev, local only). */
export async function GET(req: Request) {
  const denied = adminDenied(req);
  if (denied) return denied;
  return Response.json(await readShoes());
}

export async function PUT(req: Request) {
  const denied = adminDenied(req);
  if (denied) return denied;
  try {
    return Response.json(await writeShoes(await req.json()));
  } catch (e) {
    if (e instanceof ValidationError || e instanceof SyntaxError) return Response.json({ error: e.message }, { status: 400 });
    throw e;
  }
}
