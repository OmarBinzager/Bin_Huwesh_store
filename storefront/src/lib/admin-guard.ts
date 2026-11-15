/**
 * The looks admin is a LOCAL development tool. It refuses to run in a
 * production build, and refuses requests that did not come from this machine
 * (the dev server is also reachable on the LAN for phone testing, but phones
 * must never be able to edit content).
 */
export function adminEnabled(): boolean {
  return process.env.NODE_ENV === "development";
}

const LOCAL_HOSTS = new Set(["localhost", "127.0.0.1", "[::1]", "::1"]);

export function isLocalRequest(req: Request): boolean {
  const host = (req.headers.get("host") ?? "").replace(/:\d+$/, "");
  return LOCAL_HOSTS.has(host);
}

export function adminDenied(req: Request): Response | null {
  if (!adminEnabled()) return new Response("Not found", { status: 404 });
  if (!isLocalRequest(req)) return Response.json({ error: "أداة الإدارة متاحة من هذا الجهاز فقط" }, { status: 403 });
  return null;
}
