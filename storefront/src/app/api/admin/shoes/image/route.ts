import { adminDenied } from "@/lib/admin-guard";
import { ValidationError } from "@/lib/looks-store";
import { saveShoeImage } from "@/lib/shoes-store";

const MAX_BYTES = 4 * 1024 * 1024;

/**
 * POST multipart { shoeId, colorId, image } — stores a transparent cut-out the
 * admin has already framed/encoded in the browser. The shoe changes only when
 * the admin saves.
 */
export async function POST(req: Request) {
  const denied = adminDenied(req);
  if (denied) return denied;
  try {
    const form = await req.formData();
    const image = form.get("image");
    if (!(image instanceof Blob)) throw new ValidationError("لم تُرفع صورة");
    if (image.size > MAX_BYTES) throw new ValidationError("الصورة أكبر من 4MB بعد الضغط");
    const saved = await saveShoeImage(
      String(form.get("shoeId") ?? ""),
      String(form.get("colorId") ?? ""),
      new Uint8Array(await image.arrayBuffer()),
    );
    return Response.json({ image: saved });
  } catch (e) {
    if (e instanceof ValidationError) return Response.json({ error: e.message }, { status: 400 });
    throw e;
  }
}
