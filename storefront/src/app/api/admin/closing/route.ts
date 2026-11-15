import { adminDenied } from "@/lib/admin-guard";
import { readClosing, writeClosing } from "@/lib/closing-store";
import { ValidationError } from "@/lib/looks-store";

/** GET: current closing.json — PUT: validate and replace it (dev, local only). */
export async function GET(req: Request) {
  const denied = adminDenied(req);
  if (denied) return denied;
  return Response.json(await readClosing());
}

export async function PUT(req: Request) {
  const denied = adminDenied(req);
  if (denied) return denied;
  try {
    return Response.json(await writeClosing(await req.json()));
  } catch (e) {
    if (e instanceof ValidationError || e instanceof SyntaxError) return Response.json({ error: e.message }, { status: 400 });
    throw e;
  }
}
