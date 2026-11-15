import { adminDenied } from "@/lib/admin-guard";
import { saveLookImage, ValidationError } from "@/lib/looks-store";

const MAX_BYTES = 8 * 1024 * 1024;

/**
 * POST multipart { lookId, file } — saves an already-processed WebP (the
 * admin trims and resizes it in the browser) into public/images/looks and
 * returns its public path. The look itself changes only when the admin saves.
 */
export async function POST(req: Request) {
  const denied = adminDenied(req);
  if (denied) return denied;
  try {
    const form = await req.formData();
    const lookId = String(form.get("lookId") ?? "");
    const file = form.get("file");
    if (!(file instanceof Blob)) throw new ValidationError("لم تُرفع صورة");
    if (file.size > MAX_BYTES) throw new ValidationError("الصورة أكبر من 8MB");
    const src = await saveLookImage(lookId, new Uint8Array(await file.arrayBuffer()));
    return Response.json({ src });
  } catch (e) {
    if (e instanceof ValidationError) return Response.json({ error: e.message }, { status: 400 });
    throw e;
  }
}
