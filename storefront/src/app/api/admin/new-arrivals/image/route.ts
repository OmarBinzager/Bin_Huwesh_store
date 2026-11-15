import { adminDenied } from "@/lib/admin-guard";
import { ValidationError } from "@/lib/looks-store";
import { saveNewArrivalImage } from "@/lib/new-arrivals-store";

const MAX_BYTES = 4 * 1024 * 1024;

/**
 * POST multipart { itemId, colorId, kind: "garment" | "model", image } —
 * stores a transparent cut-out the admin has already resized/encoded in the
 * browser. The item changes only when the admin saves.
 */
export async function POST(req: Request) {
  const denied = adminDenied(req);
  if (denied) return denied;
  try {
    const form = await req.formData();
    const image = form.get("image");
    if (!(image instanceof Blob)) throw new ValidationError("لم تُرفع صورة");
    if (image.size > MAX_BYTES) throw new ValidationError("الصورة أكبر من 4MB بعد الضغط");
    const saved = await saveNewArrivalImage(
      String(form.get("itemId") ?? ""),
      String(form.get("colorId") ?? ""),
      String(form.get("kind") ?? ""),
      new Uint8Array(await image.arrayBuffer()),
    );
    return Response.json({ image: saved });
  } catch (e) {
    if (e instanceof ValidationError) return Response.json({ error: e.message }, { status: 400 });
    throw e;
  }
}
