import type { Metadata } from "next";
import Link from "next/link";
import { Bell, CheckCheck } from "lucide-react";
import { markAllNotificationsRead } from "@/actions/notifications";
import { SubmitButton } from "@/components/form";
import { Card, EmptyState, PageHeader } from "@/components/ui";
import { requireUser } from "@/lib/auth";
import { db } from "@/lib/db";
import { timeAgo } from "@/lib/time";
import { cn } from "@/lib/utils";

export const metadata: Metadata = { title: "Notifications" };

export default async function NotificationsPage() {
  const user = await requireUser();
  const notifications = await db.notification.findMany({
    where: { userId: user.id },
    orderBy: { createdAt: "desc" },
    take: 100,
  });
  const unread = notifications.filter((n) => !n.readAt).length;

  return (
    <>
      <PageHeader
        title="Notifications"
        description={unread ? `You have ${unread} unread notification${unread === 1 ? "" : "s"}.` : "You're all caught up."}
        action={
          unread > 0 && (
            <form action={markAllNotificationsRead}>
              <SubmitButton variant="secondary" size="sm">
                <CheckCheck className="size-4" /> Mark all as read
              </SubmitButton>
            </form>
          )
        }
      />
      <Card className="max-w-3xl overflow-hidden">
        {notifications.length === 0 ? (
          <EmptyState icon={<Bell className="size-6" />} title="No notifications yet" description="Requests, bookings and messages will show up here." />
        ) : (
          <ul className="divide-y divide-slate-100">
            {notifications.map((n) => {
              const content = (
                <div className="flex gap-3">
                  <span className={cn("mt-1.5 size-2 shrink-0 rounded-full", n.readAt ? "bg-transparent" : "bg-brand-600")} />
                  <div className="min-w-0 flex-1">
                    <div className="flex items-baseline justify-between gap-3">
                      <p className={cn("text-sm text-slate-900", !n.readAt && "font-semibold")}>{n.title}</p>
                      <span className="shrink-0 text-xs text-slate-400">{timeAgo(n.createdAt)}</span>
                    </div>
                    <p className="mt-0.5 text-sm text-slate-600">{n.body}</p>
                  </div>
                </div>
              );
              return (
                <li key={n.id} className={cn(!n.readAt && "bg-brand-50/40")}>
                  {n.link ? (
                    <Link href={n.link} className="block px-5 py-4 hover:bg-slate-50">{content}</Link>
                  ) : (
                    <div className="px-5 py-4">{content}</div>
                  )}
                </li>
              );
            })}
          </ul>
        )}
      </Card>
    </>
  );
}
