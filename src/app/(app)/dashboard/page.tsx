import type { Metadata } from "next";
import Link from "next/link";
import { redirect } from "next/navigation";
import {
  ArrowRight,
  CalendarCheck,
  CalendarDays,
  CheckCircle2,
  Circle,
  Clock,
  Inbox,
  Star,
  Users,
  Video,
} from "lucide-react";
import { MentorCard } from "@/components/mentor-card";
import { SessionBadge } from "@/components/session-badge";
import { Avatar, ButtonLink, Card, CardHeader, EmptyState } from "@/components/ui";
import { StatCard } from "@/components/stat-card";
import { requireUser, type CurrentUser } from "@/lib/auth";
import { db } from "@/lib/db";
import { searchMentors } from "@/lib/mentors";
import { formatDateTime, timeAgo } from "@/lib/time";
import { averageRating } from "@/lib/utils";

export const metadata: Metadata = { title: "Dashboard" };

function greeting(timeZone: string) {
  const hour = Number(new Intl.DateTimeFormat("en-US", { timeZone, hour: "numeric", hourCycle: "h23" }).format(new Date()));
  return hour < 12 ? "Good morning" : hour < 17 ? "Good afternoon" : "Good evening";
}

async function upcomingSessions(user: CurrentUser) {
  return db.mentorSession.findMany({
    where: {
      ...(user.role === "MENTOR" ? { mentorId: user.id } : { menteeId: user.id }),
      status: { in: ["CONFIRMED", "PENDING"] },
      startsAt: { gte: new Date(Date.now() - 60 * 60 * 1000) },
    },
    include: { mentor: true, mentee: true },
    orderBy: { startsAt: "asc" },
    take: 5,
  });
}

function UpcomingList({ user, sessions }: { user: CurrentUser; sessions: Awaited<ReturnType<typeof upcomingSessions>> }) {
  if (sessions.length === 0) {
    return (
      <EmptyState
        icon={<CalendarDays className="size-6" />}
        title="Nothing scheduled"
        description={user.role === "MENTOR" ? "Make sure your availability is up to date." : "Book a session with one of your mentors."}
        action={
          <ButtonLink href={user.role === "MENTOR" ? "/settings/availability" : "/connections"} size="sm" variant="secondary">
            {user.role === "MENTOR" ? "Update availability" : "Book a session"}
          </ButtonLink>
        }
      />
    );
  }
  return (
    <ul className="divide-y divide-slate-100">
      {sessions.map((s) => {
        const other = user.role === "MENTOR" ? s.mentee : s.mentor;
        return (
          <li key={s.id} className="flex items-center gap-4 px-6 py-4">
            <Avatar user={other} size="sm" />
            <div className="min-w-0 flex-1">
              <p className="truncate text-sm font-medium text-slate-900">{s.topic}</p>
              <p className="flex items-center gap-1 text-xs text-slate-500">
                <Clock className="size-3" /> {formatDateTime(s.startsAt, user.timezone)} · with {other.name}
              </p>
            </div>
            {s.status === "CONFIRMED" && s.meetingUrl ? (
              <a href={s.meetingUrl} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-1 text-sm font-medium text-emerald-700 hover:text-emerald-800">
                <Video className="size-4" /> Join
              </a>
            ) : (
              <SessionBadge status={s.status} />
            )}
          </li>
        );
      })}
    </ul>
  );
}

async function MenteeDashboard({ user }: { user: CurrentUser }) {
  const [mentorCount, pendingCount, completed, sessions, mySkills] = await Promise.all([
    db.connection.count({ where: { menteeId: user.id, status: "ACCEPTED" } }),
    db.connection.count({ where: { menteeId: user.id, status: "PENDING" } }),
    db.mentorSession.findMany({ where: { menteeId: user.id, status: "COMPLETED" }, select: { durationMinutes: true } }),
    upcomingSessions(user),
    db.userSkill.findMany({ where: { userId: user.id }, include: { skill: true } }),
  ]);

  // Recommend mentors who teach the skills this mentee wants to learn.
  const connectedIds = new Set(
    (await db.connection.findMany({ where: { menteeId: user.id }, select: { mentorId: true } })).map((c) => c.mentorId),
  );
  const skillSlugs = new Set(mySkills.map((s) => s.skill.slug));
  const recommended = (await searchMentors({ sort: "rating" }))
    .filter((m) => !connectedIds.has(m.id) && m.mentorProfile?.acceptingMentees)
    .map((m) => ({ m, overlap: m.skills.filter((s) => skillSlugs.has(s.skill.slug)).length }))
    .sort((a, b) => b.overlap - a.overlap)
    .slice(0, 3)
    .map(({ m }) => m);

  const hours = Math.round((completed.reduce((sum, s) => sum + s.durationMinutes, 0) / 60) * 10) / 10;

  return (
    <div className="space-y-8">
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard label="My mentors" value={mentorCount} icon={<Users className="size-5" />} hint={pendingCount ? `${pendingCount} request${pendingCount > 1 ? "s" : ""} pending` : undefined} />
        <StatCard label="Upcoming sessions" value={sessions.length} icon={<CalendarDays className="size-5" />} />
        <StatCard label="Sessions completed" value={completed.length} icon={<CalendarCheck className="size-5" />} />
        <StatCard label="Hours of mentoring" value={hours} icon={<Clock className="size-5" />} />
      </div>

      <Card>
        <CardHeader title="Upcoming sessions" action={<Link href="/sessions" className="text-sm font-medium text-brand-700">View all</Link>} />
        <UpcomingList user={user} sessions={sessions} />
      </Card>

      {recommended.length > 0 && (
        <section>
          <div className="mb-4 flex items-end justify-between">
            <div>
              <h2 className="text-lg font-semibold text-slate-900">Recommended for you</h2>
              <p className="text-sm text-slate-500">Mentors who match the skills you want to learn.</p>
            </div>
            <Link href="/mentors" className="flex items-center gap-1 text-sm font-medium text-brand-700">
              Browse all <ArrowRight className="size-4" />
            </Link>
          </div>
          <div className="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
            {recommended.map((m) => <MentorCard key={m.id} mentor={m} />)}
          </div>
        </section>
      )}
    </div>
  );
}

async function MentorDashboard({ user }: { user: CurrentUser }) {
  const [menteeCount, requests, sessions, reviews, completedCount, availabilityCount] = await Promise.all([
    db.connection.count({ where: { mentorId: user.id, status: "ACCEPTED" } }),
    db.connection.findMany({
      where: { mentorId: user.id, status: "PENDING" },
      include: { mentee: true },
      orderBy: { createdAt: "desc" },
      take: 4,
    }),
    upcomingSessions(user),
    db.review.findMany({ where: { mentorId: user.id }, select: { rating: true } }),
    db.mentorSession.count({ where: { mentorId: user.id, status: "COMPLETED" } }),
    db.availability.count({ where: { mentorId: user.id } }),
  ]);
  const rating = averageRating(reviews.map((r) => r.rating));

  const checklist = [
    { done: Boolean(user.photoVersion), label: "Add a profile photo", href: "/settings/profile" },
    { done: Boolean(user.bio && user.bio.length > 50), label: "Write a detailed bio", href: "/settings/profile" },
    { done: Boolean(user.mentorProfile?.jobTitle && user.mentorProfile.company), label: "Add your role and company", href: "/settings/profile" },
    { done: Boolean(user.mentorProfile?.linkedinUrl), label: "Link your LinkedIn", href: "/settings/profile" },
    { done: availabilityCount > 0, label: "Set your weekly availability", href: "/settings/availability" },
  ];
  const progress = checklist.filter((c) => c.done).length;

  return (
    <div className="space-y-8">
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard label="Active mentees" value={menteeCount} icon={<Users className="size-5" />} />
        <StatCard label="Pending requests" value={requests.length} icon={<Inbox className="size-5" />} />
        <StatCard label="Sessions completed" value={completedCount} icon={<CalendarCheck className="size-5" />} />
        <StatCard
          label="Average rating"
          value={rating ? rating.toFixed(1) : "—"}
          icon={<Star className="size-5" />}
          hint={`${reviews.length} review${reviews.length === 1 ? "" : "s"}`}
        />
      </div>

      <div className="grid gap-8 lg:grid-cols-[1fr_20rem]">
        <div className="space-y-8">
          <Card>
            <CardHeader title="Upcoming sessions" action={<Link href="/sessions" className="text-sm font-medium text-brand-700">View all</Link>} />
            <UpcomingList user={user} sessions={sessions} />
          </Card>
          <Card>
            <CardHeader title="New requests" action={<Link href="/connections" className="text-sm font-medium text-brand-700">Review all</Link>} />
            {requests.length === 0 ? (
              <EmptyState icon={<Inbox className="size-6" />} title="No new requests" description="Mentees who want to work with you will appear here." />
            ) : (
              <ul className="divide-y divide-slate-100">
                {requests.map((r) => (
                  <li key={r.id} className="flex items-center gap-4 px-6 py-4">
                    <Avatar user={r.mentee} size="sm" />
                    <div className="min-w-0 flex-1">
                      <p className="text-sm font-medium text-slate-900">{r.mentee.name}</p>
                      <p className="truncate text-xs text-slate-500">{r.message}</p>
                    </div>
                    <span className="text-xs text-slate-400">{timeAgo(r.createdAt)}</span>
                  </li>
                ))}
              </ul>
            )}
          </Card>
        </div>

        {progress < checklist.length && (
          <Card className="self-start p-6">
            <h2 className="font-semibold text-slate-900">Complete your profile</h2>
            <p className="mt-1 text-sm text-slate-500">Complete profiles get far more requests.</p>
            <div className="mt-4 h-2 overflow-hidden rounded-full bg-slate-100">
              <div className="h-full rounded-full bg-brand-600" style={{ width: `${(progress / checklist.length) * 100}%` }} />
            </div>
            <ul className="mt-5 space-y-3">
              {checklist.map((c) => (
                <li key={c.label}>
                  <Link href={c.href} className="flex items-center gap-2.5 text-sm text-slate-700 hover:text-brand-700">
                    {c.done ? <CheckCircle2 className="size-5 text-emerald-500" /> : <Circle className="size-5 text-slate-300" />}
                    <span className={c.done ? "text-slate-400 line-through" : ""}>{c.label}</span>
                  </Link>
                </li>
              ))}
            </ul>
          </Card>
        )}
      </div>
    </div>
  );
}

export default async function DashboardPage() {
  const user = await requireUser();
  if (user.role === "ADMIN") redirect("/admin");

  return (
    <>
      <div className="mb-8">
        <h1 className="text-2xl font-semibold tracking-tight text-slate-900">
          {greeting(user.timezone)}, {user.name.split(" ")[0]} 👋
        </h1>
        <p className="mt-1 text-sm text-slate-500">
          {user.role === "MENTOR" ? "Here's what's happening with your mentees." : "Here's your learning at a glance."}
        </p>
      </div>
      {user.role === "MENTOR" ? <MentorDashboard user={user} /> : <MenteeDashboard user={user} />}
    </>
  );
}
