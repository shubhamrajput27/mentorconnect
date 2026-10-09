import "server-only";
import { headers } from "next/headers";

// Fixed-window limiter held in memory. Good for a single server; use Redis
// (e.g. Upstash) when running several instances.
const buckets = new Map<string, { count: number; resetAt: number }>();

export async function rateLimit(name: string, limit: number, windowMs: number) {
  const h = await headers();
  const ip = h.get("x-forwarded-for")?.split(",")[0]?.trim() || h.get("x-real-ip") || "local";
  const key = `${name}:${ip}`;
  const now = Date.now();
  const bucket = buckets.get(key);

  if (!bucket || bucket.resetAt < now) {
    buckets.set(key, { count: 1, resetAt: now + windowMs });
    return true;
  }
  bucket.count++;
  return bucket.count <= limit;
}
