import { adminDenied } from "@/lib/admin-guard";
import { readLooks, ValidationError, writeLooks } from "@/lib/looks-store";

/** GET: current looks.json — PUT: validate and replace it (dev, local only). */
export async function GET(req: Request) {
  const denied = adminDenied(req);
  if (denied) return denied;
  return Response.json(await readLooks());
}

export async function PUT(req: Request) {
  const denied = adminDenied(req);
  if (denied) return denied;
  try {
    const saved = await writeLooks(await req.json());
    return Response.json(saved);
  } catch (e) {
    if (e instanceof ValidationError || e instanceof SyntaxError) {
      return Response.json({ error: e.message }, { status: 400 });
    }
    throw e;
  }
}
