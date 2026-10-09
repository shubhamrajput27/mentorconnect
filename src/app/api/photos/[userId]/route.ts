import { db } from "@/lib/db";

// Profile photos are public, like the profiles they belong to. URLs carry a
// version (?v=), so responses can be cached forever.
export async function GET(_request: Request, ctx: RouteContext<"/api/photos/[userId]">) {
  const { userId } = await ctx.params;
  const photo = await db.userPhoto.findUnique({ where: { userId } });
  if (!photo) return new Response("Not found", { status: 404 });

  return new Response(new Uint8Array(photo.data), {
    headers: {
      "Content-Type": photo.mimeType,
      "Cache-Control": "public, max-age=31536000, immutable",
      "X-Content-Type-Options": "nosniff",
    },
  });
}
