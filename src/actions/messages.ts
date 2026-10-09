"use server";

import { redirect } from "next/navigation";
import { db } from "@/lib/db";
import { getCurrentUser } from "@/lib/auth";
import { notify } from "@/lib/notify";
import { messageSchema } from "@/lib/validation";

export type ChatMessage = {
  id: string;
  body: string;
  senderId: string;
  createdAt: string;
};

export async function sendMessage(input: { connectionId: string; body: string }) {
  const user = await getCurrentUser();
  if (!user) redirect("/login");

  const parsed = messageSchema.safeParse(input);
  if (!parsed.success) return { error: parsed.error.issues[0]?.message ?? "Invalid message." };

  const connection = await db.connection.findFirst({
    where: {
      id: parsed.data.connectionId,
      status: "ACCEPTED",
      OR: [{ mentorId: user.id }, { menteeId: user.id }],
    },
  });
  if (!connection) return { error: "You can only message your connections." };

  const message = await db.message.create({
    data: { connectionId: connection.id, senderId: user.id, body: parsed.data.body },
  });

  // Notify only when this starts a new burst, to avoid one notification per message.
  const recipientId = connection.mentorId === user.id ? connection.menteeId : connection.mentorId;
  const recent = await db.message.count({
    where: {
      connectionId: connection.id,
      senderId: user.id,
      createdAt: { gte: new Date(Date.now() - 10 * 60 * 1000) },
    },
  });
  if (recent === 1) {
    await notify(recipientId, `New message from ${user.name}`, parsed.data.body.slice(0, 120), `/messages/${connection.id}`);
  }

  return {
    message: {
      id: message.id,
      body: message.body,
      senderId: message.senderId,
      createdAt: message.createdAt.toISOString(),
    } satisfies ChatMessage,
  };
}
