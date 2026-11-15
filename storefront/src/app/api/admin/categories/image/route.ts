import { adminDenied } from "@/lib/admin-guard";
import { saveCategoryImage } from "@/lib/categories-store";
import { ValidationError } from "@/lib/looks-store";

const MAX_BYTES = 4 * 1024 * 1024;

/**
 * POST multipart { cardId, image } — stores a card photo the admin has
 * already resized/encoded in the browser. The card changes only on save.
 */
export async function POST(req: Request) {
  const denied = adminDenied(req);
  if (denied) return denied;
  try {
    const form = await req.formData();
    const cardId = String(form.get("cardId") ?? "");
    const image = form.get("image");
    if (!(image instanceof Blob)) throw new ValidationError("لم تُرفع صورة");
    if (image.size > MAX_BYTES) throw new ValidationError("الصورة أكبر من 4MB بعد الضغط");
    return Response.json({ image: await saveCategoryImage(cardId, new Uint8Array(await image.arrayBuffer())) });
  } catch (e) {
    if (e instanceof ValidationError) return Response.json({ error: e.message }, { status: 400 });
    throw e;
  }
}
