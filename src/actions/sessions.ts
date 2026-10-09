"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { Prisma } from "@/generated/prisma/client";
import { db } from "@/lib/db";
import { getCurrentUser } from "@/lib/auth";
import { defaultMeetingUrl, getMentorSlots } from "@/lib/booking";
import { notify } from "@/lib/notify";
import { formatDateTime } from "@/lib/time";
import { bookingSchema, fieldErrors, reviewSchema, type FormState } from "@/lib/validation";

export async function bookSession(_prev: FormState, formData: FormData): Promise<FormState> {
  const user = await getCurrentUser();
  if (!user) redirect("/login");
  if (user.role !== "MENTEE") return { error: "Only mentees can book sessions." };

  const parsed = bookingSchema.safeParse(Object.fromEntries(formData));
  if (!parsed.success) return { fieldErrors: fieldErrors(parsed.error) };
  const { mentorId, topic, agenda } = parsed.data;
  const startsAt = new Date(parsed.data.startsAt);

  const connection = await db.connection.findUnique({
    where: { mentorId_menteeId: { mentorId, menteeId: user.id } },
  });
  if (connection?.status !== "ACCEPTED") {
    return { error: "You can book sessions once the mentor accepts your request." };
  }

  const { slots, durationMinutes } = await getMentorSlots(mentorId);
  if (!slots.some((s) => s.getTime() === startsAt.getTime())) {
    return { error: "That time is no longer available. Please pick another slot." };
  }

  let session;
  try {
    session = await db.mentorSession.create({
      data: { mentorId, menteeId: user.id, startsAt, durationMinutes, topic, agenda, slotKey: slotKey(mentorId, startsAt) },
      include: { mentor: true },
    });
  } catch (e) {
    // Someone booked the same slot between our availability check and this insert.
    if (e instanceof Prisma.PrismaClientKnownRequestError && e.code === "P2002") {
      return { error: "Someone just booked that time. Please pick another slot." };
    }
    throw e;
  }
  await notify(
    mentorId,
    "New session request",
    `${user.name} requested "${topic}" on ${formatDateTime(startsAt, session.mentor.timezone)}.`,
    "/sessions",
  );

  revalidatePath("/", "layout");
  redirect("/sessions?booked=1");
}

/** Unique per mentor and start time; only held while a session is pending or confirmed. */
function slotKey(mentorId: string, startsAt: Date) {
  return `${mentorId}:${startsAt.toISOString()}`;
}

type SessionAction = "confirm" | "decline" | "cancel" | "complete";

export async function updateSession(formData: FormData) {
  const user = await getCurrentUser();
  if (!user) redirect("/login");

  const id = String(formData.get("sessionId"));
  const action = String(formData.get("action")) as SessionAction;
  const session = await db.mentorSession.findFirst({
    where: { id, OR: [{ mentorId: user.id }, { menteeId: user.id }] },
    include: { mentor: true, mentee: true },
  });
  if (!session) return;

  const isMentor = session.mentorId === user.id;
  const other = isMentor ? session.mentee : session.mentor;
  const when = formatDateTime(session.startsAt, other.timezone);

  if (action === "confirm" && isMentor && session.status === "PENDING") {
    await db.mentorSession.update({
      where: { id },
      data: { status: "CONFIRMED", meetingUrl: defaultMeetingUrl(id) },
    });
    await notify(other.id, "Session confirmed", `${user.name} confirmed "${session.topic}" on ${when}.`, "/sessions");
  } else if (action === "decline" && isMentor && session.status === "PENDING") {
    await db.mentorSession.update({ where: { id }, data: { status: "DECLINED", slotKey: null } });
    await notify(other.id, "Session declined", `${user.name} can't make "${session.topic}" on ${when}. Try another slot.`, "/sessions");
  } else if (action === "cancel" && ["PENDING", "CONFIRMED"].includes(session.status)) {
    await db.mentorSession.update({ where: { id }, data: { status: "CANCELLED", slotKey: null } });
    await notify(other.id, "Session cancelled", `${user.name} cancelled "${session.topic}" on ${when}.`, "/sessions");
  } else if (action === "complete" && isMentor && session.status === "CONFIRMED" && session.startsAt <= new Date()) {
    await db.mentorSession.update({ where: { id }, data: { status: "COMPLETED", slotKey: null } });
    await notify(other.id, "How was your session?", `Leave a review for "${session.topic}" with ${user.name}.`, "/sessions");
  } else {
    return;
  }
  revalidatePath("/", "layout");
}

export async function submitReview(_prev: FormState, formData: FormData): Promise<FormState> {
  const user = await getCurrentUser();
  if (!user) redirect("/login");

  const parsed = reviewSchema.safeParse(Object.fromEntries(formData));
  if (!parsed.success) return { fieldErrors: fieldErrors(parsed.error) };
  const { sessionId, rating, comment } = parsed.data;

  const session = await db.mentorSession.findFirst({
    where: { id: sessionId, menteeId: user.id, status: "COMPLETED" },
    include: { review: true },
  });
  if (!session) return { error: "You can only review sessions you attended." };
  if (session.review) return { error: "You've already reviewed this session." };

  await db.review.create({
    data: { sessionId, rating, comment, mentorId: session.mentorId, authorId: user.id },
  });
  await notify(session.mentorId, "New review", `${user.name} left you a ${rating}★ review.`, `/mentors/${session.mentorId}`);

  revalidatePath("/", "layout");
  return { success: "Thanks for your review!" };
}
