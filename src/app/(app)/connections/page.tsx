import type { Metadata } from "next";
import Link from "next/link";
import { CalendarPlus, Inbox, MessageSquare, Users } from "lucide-react";
import { endConnection, respondToConnection } from "@/actions/connections";
import { ConfirmButton } from "@/components/confirm-button";
import { SubmitButton } from "@/components/form";
import { Avatar, Badge, ButtonLink, Card, CardHeader, EmptyState, PageHeader } from "@/components/ui";
import { requireUser } from "@/lib/auth";
import { db } from "@/lib/db";
import { timeAgo } from "@/lib/time";

export const metadata: Metadata = { title: "Connections" };

export default async function ConnectionsPage() {
  const user = await requireUser(["MENTOR", "MENTEE"]);
  const isMentor = user.role === "MENTOR";

  const connections = await db.connection.findMany({
    where: isMentor ? { mentorId: user.id } : { menteeId: user.id },
    include: {
      mentor: { include: { mentorProfile: true } },
      mentee: { include: { skills: { include: { skill: true } } } },
    },
    orderBy: { createdAt: "desc" },
  });

  const pending = connections.filter((c) => c.status === "PENDING");
  const active = connections.filter((c) => c.status === "ACCEPTED");
  const declined = connections.filter((c) => c.status === "DECLINED");

  return (
    <>
      <PageHeader
        title={isMentor ? "Mentees" : "My mentors"}
        description={isMentor ? "Review new requests and keep track of who you're mentoring." : "Your mentors and the requests you've sent."}
        action={!isMentor && <ButtonLink href="/mentors">Find more mentors</ButtonLink>}
      />

      <div className="space-y-8">
        <Card>
          <CardHeader
            title={isMentor ? "Requests waiting for you" : "Pending requests"}
            description={isMentor ? "Accept people you can genuinely help." : "Mentors usually reply within a few days."}
          />
          {pending.length === 0 ? (
            <EmptyState
              icon={<Inbox className="size-6" />}
              title="No pending requests"
              description={isMentor ? "New mentorship requests will show up here." : "Send a request from any mentor's profile."}
            />
          ) : (
            <ul className="divide-y divide-slate-100">
              {pending.map((c) => {
                const other = isMentor ? c.mentee : c.mentor;
                return (
                  <li key={c.id} className="flex flex-col gap-4 px-6 py-5 sm:flex-row">
                    <Avatar user={other} />
                    <div className="min-w-0 flex-1">
                      <div className="flex flex-wrap items-center gap-2">
                        <p className="font-medium text-slate-900">{other.name}</p>
                        <span className="text-xs text-slate-400">{timeAgo(c.createdAt)}</span>
                      </div>
                      {other.headline && <p className="text-sm text-slate-500">{other.headline}</p>}
                      {c.message && (
                        <p className="mt-3 rounded-lg bg-slate-50 p-3 text-sm whitespace-pre-line text-slate-700">{c.message}</p>
                      )}
                      {isMentor && c.mentee.goals && (
                        <p className="mt-2 text-sm text-slate-600"><span className="font-medium">Goals:</span> {c.mentee.goals}</p>
                      )}
                      {isMentor && c.mentee.skills.length > 0 && (
                        <div className="mt-2 flex flex-wrap gap-1.5">
                          {c.mentee.skills.map(({ skill }) => <Badge key={skill.id}>{skill.name}</Badge>)}
                        </div>
                      )}
                    </div>
                    {isMentor ? (
                      <form action={respondToConnection} className="flex shrink-0 gap-2 sm:flex-col">
                        <input type="hidden" name="connectionId" value={c.id} />
                        <SubmitButton name="decision" value="accept" size="sm">Accept</SubmitButton>
                        <SubmitButton name="decision" value="decline" size="sm" variant="secondary">Decline</SubmitButton>
                      </form>
                    ) : (
                      <form action={endConnection} className="shrink-0">
                        <input type="hidden" name="connectionId" value={c.id} />
                        <ConfirmButton message="Withdraw this request?" size="sm" variant="secondary">Withdraw</ConfirmButton>
                      </form>
                    )}
                  </li>
                );
              })}
            </ul>
          )}
        </Card>

        <Card>
          <CardHeader title={isMentor ? "Your mentees" : "Your mentors"} description={`${active.length} active connection${active.length === 1 ? "" : "s"}`} />
          {active.length === 0 ? (
            <EmptyState
              icon={<Users className="size-6" />}
              title="No active connections yet"
              description={isMentor ? "Once you accept a request, your mentee appears here." : "When a mentor accepts your request, you can message them and book sessions."}
              action={!isMentor && <ButtonLink href="/mentors" size="sm">Browse mentors</ButtonLink>}
            />
          ) : (
            <ul className="divide-y divide-slate-100">
              {active.map((c) => {
                const other = isMentor ? c.mentee : c.mentor;
                return (
                  <li key={c.id} className="flex flex-wrap items-center gap-4 px-6 py-4">
                    <Avatar user={other} />
                    <div className="min-w-0 flex-1">
                      {isMentor ? (
                        <p className="font-medium text-slate-900">{other.name}</p>
                      ) : (
                        <Link href={`/mentors/${other.id}`} className="font-medium text-slate-900 hover:text-brand-700">{other.name}</Link>
                      )}
                      <p className="truncate text-sm text-slate-500">{other.headline}</p>
                    </div>
                    <div className="flex items-center gap-2">
                      <ButtonLink href={`/messages/${c.id}`} size="sm" variant="secondary">
                        <MessageSquare className="size-4" /> Message
                      </ButtonLink>
                      {!isMentor && (
                        <ButtonLink href={`/mentors/${c.mentorId}`} size="sm">
                          <CalendarPlus className="size-4" /> Book
                        </ButtonLink>
                      )}
                      <form action={endConnection}>
                        <input type="hidden" name="connectionId" value={c.id} />
                        <ConfirmButton
                          message={`End your mentorship with ${other.name}? Your message history will be deleted.`}
                          size="sm"
                          variant="ghost"
                          className="text-slate-500"
                        >
                          End
                        </ConfirmButton>
                      </form>
                    </div>
                  </li>
                );
              })}
            </ul>
          )}
        </Card>

        {!isMentor && declined.length > 0 && (
          <Card>
            <CardHeader title="Not accepted" description="These mentors couldn't take you on. You can send them a new request later." />
            <ul className="divide-y divide-slate-100">
              {declined.map((c) => (
                <li key={c.id} className="flex items-center gap-4 px-6 py-4">
                  <Avatar user={c.mentor} size="sm" />
                  <Link href={`/mentors/${c.mentorId}`} className="flex-1 text-sm font-medium text-slate-700 hover:text-brand-700">{c.mentor.name}</Link>
                  <Badge tone="gray">Declined</Badge>
                </li>
              ))}
            </ul>
          </Card>
        )}
      </div>
    </>
  );
}
