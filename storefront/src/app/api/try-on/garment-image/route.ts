import { NextRequest, NextResponse } from "next/server";

/**
 * Same-origin proxy for garment images passed into Decart's WebRTC VTON SDK,
 * avoiding browser CORS restrictions when converting product images to Blob/File.
 */
export async function GET(req: NextRequest) {
  const url = req.nextUrl.searchParams.get("url");
  if (!url) {
    return NextResponse.json({ error: "Missing url parameter" }, { status: 400 });
  }

  let parsed: URL;
  try {
    parsed = new URL(url);
  } catch {
    return NextResponse.json({ error: "Invalid url" }, { status: 400 });
  }

  if (parsed.protocol !== "https:" && parsed.protocol !== "http:") {
    return NextResponse.json({ error: "Unsupported protocol" }, { status: 400 });
  }

  try {
    const upstream = await fetch(parsed.toString(), { cache: "force-cache" });
    if (!upstream.ok) {
      return NextResponse.json({ error: "Failed to fetch garment image" }, { status: 502 });
    }
    const contentType = upstream.headers.get("content-type") || "image/jpeg";
    if (!contentType.startsWith("image/")) {
      return NextResponse.json({ error: "Upstream is not an image" }, { status: 400 });
    }
    const buffer = await upstream.arrayBuffer();
    return new NextResponse(buffer, {
      status: 200,
      headers: {
        "Content-Type": contentType,
        "Cache-Control": "public, max-age=3600",
      },
    });
  } catch {
    return NextResponse.json({ error: "Image proxy error" }, { status: 500 });
  }
}
