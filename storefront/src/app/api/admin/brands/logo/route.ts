import { adminDenied } from "@/lib/admin-guard";
import { saveBrandLogo } from "@/lib/brands-store";
import { ValidationError } from "@/lib/looks-store";

const MAX_BYTES = 2 * 1024 * 1024;

/**
 * POST multipart { brandId, mono, color? } — stores logo files the admin has
 * already trimmed/converted in the browser. The brand changes only on save.
 */
export async function POST(req: Request) {
  const denied = adminDenied(req);
  if (denied) return denied;
  try {
    const form = await req.formData();
    const brandId = String(form.get("brandId") ?? "");
    const mono = form.get("mono");
    const color = form.get("color");
    if (!(mono instanceof Blob)) throw new ValidationError("لم يُرفع شعار");
    if (mono.size > MAX_BYTES || (color instanceof Blob && color.size > MAX_BYTES)) throw new ValidationError("الشعار أكبر من 2MB");
    const logo = await saveBrandLogo(brandId, "mono", new Uint8Array(await mono.arrayBuffer()));
    const colorLogo = color instanceof Blob ? await saveBrandLogo(brandId, "color", new Uint8Array(await color.arrayBuffer())) : undefined;
    return Response.json({ logo, colorLogo });
  } catch (e) {
    if (e instanceof ValidationError) return Response.json({ error: e.message }, { status: 400 });
    throw e;
  }
}
