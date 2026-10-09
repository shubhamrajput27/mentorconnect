"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { db } from "@/lib/db";
import { getCurrentUser } from "@/lib/auth";
import type { FormState } from "@/lib/validation";

const MAX_BYTES = 300 * 1024;

/** Detect the image type from its first bytes rather than trusting the browser. */
function sniff(bytes: Uint8Array) {
  const ascii = (from: number, to: number) => String.fromCharCode(...bytes.slice(from, to));
  if (bytes[0] === 0xff && bytes[1] === 0xd8 && bytes[2] === 0xff) return "image/jpeg";
  if (bytes[0] === 0x89 && ascii(1, 4) === "PNG") return "image/png";
  if (ascii(0, 4) === "RIFF" && ascii(8, 12) === "WEBP") return "image/webp";
  return null;
}

export async function uploadPhoto(_prev: FormState, formData: FormData): Promise<FormState> {
  const user = await getCurrentUser();
  if (!user) redirect("/login");

  const file = formData.get("photo");
  if (!(file instanceof File) || file.size === 0) return { error: "Choose an image to upload." };
  if (file.size > MAX_BYTES) return { error: "That image is too large. Please try a smaller one." };

  const bytes = new Uint8Array(await file.arrayBuffer());
  const mimeType = sniff(bytes);
  if (!mimeType) return { error: "Please upload a JPG, PNG or WebP image." };

  await db.$transaction([
    db.userPhoto.upsert({
      where: { userId: user.id },
      update: { data: bytes, mimeType },
      create: { userId: user.id, data: bytes, mimeType },
    }),
    db.user.update({ where: { id: user.id }, data: { photoVersion: (user.photoVersion ?? 0) + 1 } }),
  ]);
  revalidatePath("/", "layout");
  return { success: "Photo updated." };
}

export async function removePhoto(): Promise<FormState> {
  const user = await getCurrentUser();
  if (!user) redirect("/login");
  await db.$transaction([
    db.userPhoto.deleteMany({ where: { userId: user.id } }),
    db.user.update({ where: { id: user.id }, data: { photoVersion: null } }),
  ]);
  revalidatePath("/", "layout");
  return { success: "Photo removed." };
}
