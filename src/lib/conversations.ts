import "server-only";
import { db } from "@/lib/db";

/** Accepted connections for a user, with the latest message and unread count, newest activity first. */
export async function getConversations(userId: string) {
  const connections = await db.connection.findMany({
    where: { status: "ACCEPTED", OR: [{ mentorId: userId }, { menteeId: userId }] },
    include: {
      mentor: true,
      mentee: true,
      messages: { orderBy: { createdAt: "desc" }, take: 1 },
      _count: { select: { messages: { where: { readAt: null, senderId: { not: userId } } } } },
    },
  });

  return connections
    .map((c) => ({
      id: c.id,
      other: c.mentorId === userId ? c.mentee : c.mentor,
      otherRole: c.mentorId === userId ? ("Mentee" as const) : ("Mentor" as const),
      last: c.messages[0] ?? null,
      unread: c._count.messages,
      activity: (c.messages[0]?.createdAt ?? c.respondedAt ?? c.createdAt).getTime(),
    }))
    .sort((a, b) => b.activity - a.activity);
}
