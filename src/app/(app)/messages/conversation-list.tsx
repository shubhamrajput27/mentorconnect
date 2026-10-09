import Link from "next/link";
import { Avatar } from "@/components/ui";
import type { getConversations } from "@/lib/conversations";
import { timeAgo } from "@/lib/time";
import { cn } from "@/lib/utils";

export function ConversationList({
  conversations,
  activeId,
  userId,
}: {
  conversations: Awaited<ReturnType<typeof getConversations>>;
  activeId?: string;
  userId: string;
}) {
  return (
    <ul className="divide-y divide-slate-100">
      {conversations.map((c) => (
        <li key={c.id}>
          <Link
            href={`/messages/${c.id}`}
            className={cn("flex items-center gap-3 px-4 py-3.5 hover:bg-slate-50", activeId === c.id && "bg-brand-50/60 hover:bg-brand-50")}
          >
            <Avatar user={c.other} />
            <div className="min-w-0 flex-1">
              <div className="flex items-baseline justify-between gap-2">
                <p className={cn("truncate text-sm text-slate-900", c.unread ? "font-semibold" : "font-medium")}>{c.other.name}</p>
                {c.last && <span className="shrink-0 text-xs text-slate-400">{timeAgo(c.last.createdAt)}</span>}
              </div>
              <div className="flex items-center justify-between gap-2">
                <p className={cn("truncate text-sm", c.unread ? "text-slate-900" : "text-slate-500")}>
                  {c.last ? `${c.last.senderId === userId ? "You: " : ""}${c.last.body}` : `New ${c.otherRole.toLowerCase()} · say hello 👋`}
                </p>
                {c.unread > 0 && (
                  <span className="flex size-5 shrink-0 items-center justify-center rounded-full bg-brand-600 text-xs font-semibold text-white">
                    {c.unread}
                  </span>
                )}
              </div>
            </div>
          </Link>
        </li>
      ))}
    </ul>
  );
}
