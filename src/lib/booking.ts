import "server-only";
import { db } from "@/lib/db";
import { generateSlots } from "@/lib/time";

/** Open slots for a mentor, excluding times already booked with anyone. */
export async function getMentorSlots(mentorId: string) {
  const mentor = await db.user.findUnique({
    where: { id: mentorId },
    include: { mentorProfile: true, availability: true },
  });
  if (!mentor?.mentorProfile) return { slots: [], durationMinutes: 60 };

  const durationMinutes = mentor.mentorProfile.sessionMinutes;
  const booked = await db.mentorSession.findMany({
    where: {
      mentorId,
      status: { in: ["PENDING", "CONFIRMED"] },
      startsAt: { gte: new Date(Date.now() - 24 * 60 * 60 * 1000) },
    },
    select: { startsAt: true, durationMinutes: true },
  });

  const slots = generateSlots({
    windows: mentor.availability,
    timeZone: mentor.timezone,
    durationMinutes,
    busy: booked.map((s) => ({
      start: s.startsAt,
      end: new Date(s.startsAt.getTime() + s.durationMinutes * 60 * 1000),
    })),
  });
  return { slots, durationMinutes };
}

export function defaultMeetingUrl(sessionId: string) {
  return `https://meet.jit.si/MentorConnect-${sessionId}`;
}
