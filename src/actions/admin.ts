"use server";

import { revalidatePath } from "next/cache";
import { db } from "@/lib/db";
import { requireUser } from "@/lib/auth";

export async function setUserStatus(formData: FormData) {
  const admin = await requireUser(["ADMIN"]);
  const userId = String(formData.get("userId"));
  const status = formData.get("status") === "SUSPENDED" ? "SUSPENDED" : "ACTIVE";
  if (userId === admin.id) return;

  await db.$transaction([
    db.user.update({ where: { id: userId }, data: { status } }),
    // Suspending signs the user out everywhere.
    ...(status === "SUSPENDED" ? [db.authSession.deleteMany({ where: { userId } })] : []),
  ]);
  revalidatePath("/admin");
}
