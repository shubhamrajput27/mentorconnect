import type { Metadata } from "next";
import { MessageSquare } from "lucide-react";
import { ButtonLink, Card, EmptyState, PageHeader } from "@/components/ui";
import { requireUser } from "@/lib/auth";
import { getConversations } from "@/lib/conversations";
import { ConversationList } from "./conversation-list";

export const metadata: Metadata = { title: "Messages" };

export default async function MessagesPage() {
  const user = await requireUser(["MENTOR", "MENTEE"]);
  const conversations = await getConversations(user.id);

  return (
    <>
      <PageHeader title="Messages" description="Chat with your mentors and mentees." />
      <Card className="max-w-3xl overflow-hidden">
        {conversations.length === 0 ? (
          <EmptyState
            icon={<MessageSquare className="size-6" />}
            title="No conversations yet"
            description={
              user.role === "MENTOR"
                ? "Accept a mentorship request to start chatting."
                : "Once a mentor accepts your request, you can message them here."
            }
            action={user.role === "MENTEE" && <ButtonLink href="/mentors" size="sm">Find a mentor</ButtonLink>}
          />
        ) : (
          <ConversationList conversations={conversations} userId={user.id} />
        )}
      </Card>
    </>
  );
}
