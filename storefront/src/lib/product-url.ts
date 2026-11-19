export function slugifyProductName(name?: string | null): string {
  if (!name) return "";
  return name
    .trim()
    .toLowerCase()
    .replace(/[^\p{L}\p{N}\s-]+/gu, "")
    .replace(/[\s_-]+/g, "-")
    .replace(/^-+|-+$/g, "")
    .slice(0, 80);
}

export function productHref(id: number | string, name?: string | null): string {
  const numericId = String(id).trim();
  const slug = slugifyProductName(name);
  return slug ? `/products/${numericId}-${slug}` : `/products/${numericId}`;
}

export function extractProductIdFromParam(param: string): string {
  let decoded = param;
  try {
    decoded = decodeURIComponent(param);
  } catch {
    decoded = param;
  }
  const match = decoded.trim().match(/^(\d+)/);
  return match ? match[1] : decoded.trim();
}
