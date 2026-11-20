import { Suspense } from "react";
import TryOnEmbedClient from "./TryOnEmbedClient";

export const dynamic = "force-dynamic";

export default function TryOnEmbedPage() {
  return (
    <Suspense fallback={<div className="min-h-screen bg-paper" />}>
      <TryOnEmbedClient />
    </Suspense>
  );
}
