import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { ArrowLeft, CalendarPlus } from "lucide-react";
import { Avatar, ButtonLink, Card } from "@/components/ui";
import { requireUser } from "@/lib/auth";
import { getConversations } from "@/lib/conversations";
import { db } from "@/lib/db";
import { ConversationList } from "../conversation-list";
import { ChatWindow } from "./chat-window";

export const metadata: Metadata = { title: "Messages" };

export default async function ConversationPage({ params }: PageProps<"/messages/[id]">) {
  const user = await requireUser(["MENTOR", "MENTEE"]);
  const { id } = await params;

  const connection = await db.connection.findFirst({
    where: { id, status: "ACCEPTED", OR: [{ mentorId: user.id }, { menteeId: user.id }] },
    include: { mentor: { include: { mentorProfile: true } }, mentee: true },
  });
  if (!connection) notFound();

  await db.message.updateMany({
    where: { connectionId: id, senderId: { not: user.id }, readAt: null },
    data: { readAt: new Date() },
  });
  const [messages, conversations] = await Promise.all([
    db.message.findMany({ where: { connectionId: id }, orderBy: { createdAt: "asc" }, take: 500 }),
    getConversations(user.id),
  ]);
  const isMentor = connection.mentorId === user.id;
  const other = isMentor ? connection.mentee : connection.mentor;

  return (
    <Card className="flex h-[calc(100vh-8rem)] min-h-[32rem] overflow-hidden lg:h-[calc(100vh-4rem)]">
      <aside className="hidden w-80 shrink-0 overflow-y-auto border-r border-slate-100 md:block">
        <div className="border-b border-slate-100 px-4 py-4">
          <h1 className="font-semibold text-slate-900">Messages</h1>
        </div>
        <ConversationList conversations={conversations} activeId={id} userId={user.id} />
      </aside>

      <section className="flex min-w-0 flex-1 flex-col">
        <header className="flex items-center gap-3 border-b border-slate-100 px-4 py-3">
          <Link href="/messages" className="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 md:hidden" aria-label="Back to messages">
            <ArrowLeft className="size-5" />
          </Link>
          <Avatar user={other} />
          <div className="min-w-0 flex-1">
            {isMentor ? (
              <p className="truncate font-medium text-slate-900">{other.name}</p>
            ) : (
              <Link href={`/mentors/${other.id}`} className="truncate font-medium text-slate-900 hover:text-brand-700">{other.name}</Link>
            )}
            <p className="truncate text-xs text-slate-500">{other.headline}</p>
          </div>
          {!isMentor && (
            <ButtonLink href={`/mentors/${other.id}`} size="sm" variant="secondary">
              <CalendarPlus className="size-4" /> <span className="hidden sm:inline">Book session</span>
            </ButtonLink>
          )}
        </header>
        <ChatWindow
          connectionId={id}
          userId={user.id}
          otherName={other.name}
          timeZone={user.timezone}
          initial={messages.map((m) => ({ id: m.id, body: m.body, senderId: m.senderId, createdAt: m.createdAt.toISOString() }))}
        />
      </section>
    </Card>
  );
}
