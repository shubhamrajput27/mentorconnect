"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { db } from "@/lib/db";
import { getCurrentUser } from "@/lib/auth";
import { notify } from "@/lib/notify";
import { connectionRequestSchema, fieldErrors, type FormState } from "@/lib/validation";

export async function requestConnection(_prev: FormState, formData: FormData): Promise<FormState> {
  const user = await getCurrentUser();
  if (!user) redirect("/login");
  if (user.role !== "MENTEE") return { error: "Only mentees can send mentorship requests." };

  const parsed = connectionRequestSchema.safeParse(Object.fromEntries(formData));
  if (!parsed.success) return { fieldErrors: fieldErrors(parsed.error) };
  const { mentorId, message } = parsed.data;

  const mentor = await db.user.findFirst({
    where: { id: mentorId, role: "MENTOR", status: "ACTIVE" },
    include: { mentorProfile: true },
  });
  if (!mentor) return { error: "This mentor is no longer available." };
  if (!mentor.mentorProfile?.acceptingMentees) return { error: "This mentor isn't accepting new mentees right now." };

  const existing = await db.connection.findUnique({
    where: { mentorId_menteeId: { mentorId, menteeId: user.id } },
  });
  if (existing?.status === "ACCEPTED") return { error: "You're already connected with this mentor." };
  if (existing?.status === "PENDING") return { error: "Your request is already waiting for a reply." };

  await db.connection.upsert({
    where: { mentorId_menteeId: { mentorId, menteeId: user.id } },
    update: { status: "PENDING", message, createdAt: new Date(), respondedAt: null },
    create: { mentorId, menteeId: user.id, message },
  });
  await notify(mentorId, "New mentorship request", `${user.name} would like you to mentor them.`, "/connections");

  revalidatePath("/", "layout");
  return { success: "Request sent! You'll be notified when the mentor replies." };
}

export async function respondToConnection(formData: FormData) {
  const user = await getCurrentUser();
  if (!user || user.role !== "MENTOR") redirect("/login");

  const id = String(formData.get("connectionId"));
  const accept = formData.get("decision") === "accept";

  const connection = await db.connection.findFirst({
    where: { id, mentorId: user.id, status: "PENDING" },
  });
  if (!connection) return;

  await db.connection.update({
    where: { id },
    data: { status: accept ? "ACCEPTED" : "DECLINED", respondedAt: new Date() },
  });
  await notify(
    connection.menteeId,
    accept ? "Request accepted 🎉" : "Request declined",
    accept
      ? `${user.name} accepted your mentorship request. Say hello and book your first session!`
      : `${user.name} can't take you on right now. Keep exploring other mentors.`,
    accept ? `/messages/${id}` : "/mentors",
  );
  revalidatePath("/", "layout");
}

export async function endConnection(formData: FormData) {
  const user = await getCurrentUser();
  if (!user) redirect("/login");

  const id = String(formData.get("connectionId"));
  const connection = await db.connection.findFirst({
    where: { id, OR: [{ mentorId: user.id }, { menteeId: user.id }] },
  });
  if (!connection) return;

  await db.connection.delete({ where: { id } });
  revalidatePath("/", "layout");
}
