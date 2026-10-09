import type { Metadata } from "next";
import Link from "next/link";
import { CalendarDays, Clock, Video } from "lucide-react";
import { updateSession } from "@/actions/sessions";
import { ConfirmButton } from "@/components/confirm-button";
import { SessionBadge } from "@/components/session-badge";
import { SubmitButton } from "@/components/form";
import { Alert, Avatar, ButtonLink, Card, EmptyState, PageHeader, Stars } from "@/components/ui";
import type { Prisma } from "@/generated/prisma/client";
import { requireUser } from "@/lib/auth";
import { db } from "@/lib/db";
import { formatDay, formatTime } from "@/lib/time";
import { cn } from "@/lib/utils";
import { ReviewForm } from "./review-form";

export const metadata: Metadata = { title: "Sessions" };

const TABS = [
  { key: "upcoming", label: "Upcoming" },
  { key: "requests", label: "Requests" },
  { key: "past", label: "Past" },
] as const;
type Tab = (typeof TABS)[number]["key"];

export default async function SessionsPage({ searchParams }: PageProps<"/sessions">) {
  const user = await requireUser(["MENTOR", "MENTEE"]);
  const params = await searchParams;
  const tab: Tab = TABS.some((t) => t.key === params.tab) ? (params.tab as Tab) : "upcoming";
  const isMentor = user.role === "MENTOR";
  const now = new Date();
  // A session stays "upcoming" until it has ended.
  const recent = new Date(now.getTime() - 3 * 60 * 60 * 1000);

  const mine: Prisma.MentorSessionWhereInput = isMentor ? { mentorId: user.id } : { menteeId: user.id };
  const filters: Record<Tab, Prisma.MentorSessionWhereInput> = {
    upcoming: { ...mine, status: "CONFIRMED", startsAt: { gte: recent } },
    requests: { ...mine, status: "PENDING", startsAt: { gte: now } },
    past: {
      ...mine,
      OR: [
        { status: { in: ["COMPLETED", "CANCELLED", "DECLINED"] } },
        { status: "CONFIRMED", startsAt: { lt: recent } },
        { status: "PENDING", startsAt: { lt: now } },
      ],
    },
  };

  const [sessions, counts] = await Promise.all([
    db.mentorSession.findMany({
      where: filters[tab],
      include: { mentor: true, mentee: true, review: true },
      orderBy: { startsAt: tab === "past" ? "desc" : "asc" },
      take: 100,
    }),
    Promise.all(TABS.map((t) => db.mentorSession.count({ where: filters[t.key] }))),
  ]);

  return (
    <>
      <PageHeader
        title="Sessions"
        description="Your 1:1 mentoring sessions. Video links appear once a session is confirmed."
        action={!isMentor && <ButtonLink href="/connections">Book with a mentor</ButtonLink>}
      />

      {params.booked && (
        <div className="mb-6">
          <Alert tone="success">Session requested! Your mentor will confirm it soon. You&apos;ll find it under Requests.</Alert>
        </div>
      )}

      <div className="mb-6 flex gap-1 border-b border-slate-200">
        {TABS.map((t, i) => (
          <Link
            key={t.key}
            href={`/sessions?tab=${t.key}`}
            className={cn(
              "-mb-px border-b-2 px-4 py-2.5 text-sm font-medium",
              tab === t.key ? "border-brand-600 text-brand-700" : "border-transparent text-slate-500 hover:text-slate-800",
            )}
          >
            {t.label}
            {counts[i]! > 0 && <span className="ml-2 rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{counts[i]}</span>}
          </Link>
        ))}
      </div>

      {sessions.length === 0 ? (
        <Card>
          <EmptyState
            icon={<CalendarDays className="size-6" />}
            title={tab === "requests" ? "No pending requests" : tab === "past" ? "No past sessions yet" : "No upcoming sessions"}
            description={
              isMentor
                ? "When mentees book time with you, it will show up here."
                : "Book a session from one of your mentors' profiles."
            }
            action={!isMentor && tab !== "past" && <ButtonLink href="/connections" size="sm">Go to my mentors</ButtonLink>}
          />
        </Card>
      ) : (
        <div className="space-y-4">
          {sessions.map((s) => {
            const other = isMentor ? s.mentee : s.mentor;
            const ended = s.startsAt.getTime() + s.durationMinutes * 60 * 1000 < now.getTime();
            const started = s.startsAt <= now;
            const live = s.status === "CONFIRMED" && started && !ended;
            return (
              <Card key={s.id} className="p-5">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start">
                  <div className="flex w-full shrink-0 items-center gap-3 rounded-xl bg-brand-50 px-4 py-3 text-brand-800 sm:w-44 sm:flex-col sm:items-start sm:gap-0">
                    <span className="text-xs font-medium tracking-wide uppercase">{formatDay(s.startsAt, user.timezone).split(",")[0]}</span>
                    <span className="text-lg font-semibold whitespace-nowrap">{formatDay(s.startsAt, user.timezone).split(", ")[1]}</span>
                    <span className="flex items-center gap-1 text-sm whitespace-nowrap"><Clock className="size-3.5" /> {formatTime(s.startsAt, user.timezone)} · {s.durationMinutes}m</span>
                  </div>

                  <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-2">
                      <h3 className="font-semibold text-slate-900">{s.topic}</h3>
                      <SessionBadge status={s.status} />
                      {live && <span className="rounded-full bg-red-500 px-2 py-0.5 text-xs font-semibold text-white">Live now</span>}
                    </div>
                    <div className="mt-2 flex items-center gap-2 text-sm text-slate-600">
                      <Avatar user={other} size="sm" />
                      <span>{isMentor ? "with mentee" : "with mentor"} <span className="font-medium text-slate-900">{other.name}</span></span>
                    </div>
                    {s.agenda && <p className="mt-3 text-sm whitespace-pre-line text-slate-600">{s.agenda}</p>}
                    {s.review && (
                      <div className="mt-3 rounded-lg bg-slate-50 p-3 text-sm">
                        <Stars rating={s.review.rating} />
                        <p className="mt-1 text-slate-600">{s.review.comment}</p>
                      </div>
                    )}
                    {!isMentor && s.status === "COMPLETED" && !s.review && (
                      <div className="mt-3"><ReviewForm sessionId={s.id} mentorName={s.mentor.name} /></div>
                    )}
                  </div>

                  <div className="flex shrink-0 flex-wrap gap-2 sm:flex-col sm:items-stretch">
                    {s.status === "CONFIRMED" && s.meetingUrl && !ended && (
                      <a
                        href={s.meetingUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex h-8 items-center justify-center gap-1.5 rounded-lg bg-emerald-600 px-3 text-sm font-medium text-white hover:bg-emerald-700"
                      >
                        <Video className="size-4" /> Join call
                      </a>
                    )}
                    <form action={updateSession} className="contents">
                      <input type="hidden" name="sessionId" value={s.id} />
                      {isMentor && s.status === "PENDING" && !started && (
                        <>
                          <SubmitButton name="action" value="confirm" size="sm">Confirm</SubmitButton>
                          <SubmitButton name="action" value="decline" size="sm" variant="secondary">Decline</SubmitButton>
                        </>
                      )}
                      {isMentor && s.status === "CONFIRMED" && started && (
                        <SubmitButton name="action" value="complete" size="sm">Mark complete</SubmitButton>
                      )}
                      {["PENDING", "CONFIRMED"].includes(s.status) && !started && (
                        <ConfirmButton name="action" value="cancel" size="sm" variant="ghost" message="Cancel this session? The other person will be notified.">
                          Cancel
                        </ConfirmButton>
                      )}
                    </form>
                  </div>
                </div>
              </Card>
            );
          })}
        </div>
      )}
    </>
  );
}
