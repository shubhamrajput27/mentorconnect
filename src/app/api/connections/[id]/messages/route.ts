import { NextResponse } from "next/server";
import { db } from "@/lib/db";
import { getCurrentUser } from "@/lib/auth";
import type { ChatMessage } from "@/actions/messages";

// Polled by the chat window for new messages; also marks incoming ones as read.
export async function GET(request: Request, ctx: RouteContext<"/api/connections/[id]/messages">) {
  const user = await getCurrentUser();
  if (!user) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const { id } = await ctx.params;
  const connection = await db.connection.findFirst({
    where: { id, status: "ACCEPTED", OR: [{ mentorId: user.id }, { menteeId: user.id }] },
  });
  if (!connection) return NextResponse.json({ error: "Not found" }, { status: 404 });

  const afterParam = new URL(request.url).searchParams.get("after");
  const after = afterParam ? new Date(afterParam) : null;

  const messages = await db.message.findMany({
    where: { connectionId: id, ...(after && !isNaN(after.getTime()) ? { createdAt: { gt: after } } : {}) },
    orderBy: { createdAt: "asc" },
    take: 200,
  });

  await db.message.updateMany({
    where: { connectionId: id, senderId: { not: user.id }, readAt: null },
    data: { readAt: new Date() },
  });

  return NextResponse.json({
    messages: messages.map(
      (m): ChatMessage => ({ id: m.id, body: m.body, senderId: m.senderId, createdAt: m.createdAt.toISOString() }),
    ),
  });
}
