import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import {
  ArrowLeft,
  Briefcase,
  CalendarCheck,
  Clock,
  Globe2,
  Languages,
  MapPin,
  MessageSquare,
  Users,
} from "lucide-react";
import { Avatar, Badge, ButtonLink, Card, Stars } from "@/components/ui";
import { getCurrentUser } from "@/lib/auth";
import { getMentorSlots } from "@/lib/booking";
import { db } from "@/lib/db";
import { getMentor } from "@/lib/mentors";
import { DAYS, minutesToLabel, timeAgo } from "@/lib/time";
import { formatRate } from "@/lib/utils";
import { BookingForm } from "./booking-form";
import { RequestForm } from "./request-form";

export async function generateMetadata({ params }: PageProps<"/mentors/[id]">): Promise<Metadata> {
  const { id } = await params;
  const mentor = await getMentor(id);
  return mentor ? { title: `${mentor.name} — Mentor`, description: mentor.headline ?? undefined } : { title: "Mentor not found" };
}

export default async function MentorPage({ params }: PageProps<"/mentors/[id]">) {
  const { id } = await params;
  const [mentor, viewer] = await Promise.all([getMentor(id), getCurrentUser()]);
  if (!mentor) notFound();

  const profile = mentor.mentorProfile;
  const connection =
    viewer?.role === "MENTEE"
      ? await db.connection.findUnique({ where: { mentorId_menteeId: { mentorId: mentor.id, menteeId: viewer.id } } })
      : null;
  const slots = connection?.status === "ACCEPTED" ? (await getMentorSlots(mentor.id)).slots : [];

  const facts = [
    { icon: Briefcase, label: `${profile?.yearsExperience ?? 0}+ years experience` },
    { icon: Clock, label: `${profile?.sessionMinutes ?? 60}-minute sessions` },
    { icon: Languages, label: profile?.languages ?? "English" },
    { icon: Globe2, label: mentor.timezone.replace("_", " ") },
    { icon: Users, label: `${mentor._count.mentorConnections} active mentee${mentor._count.mentorConnections === 1 ? "" : "s"}` },
    { icon: CalendarCheck, label: `${mentor._count.mentorSessions} sessions completed` },
  ];

  return (
    <>
      <Link href="/mentors" className="mb-6 inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-900">
        <ArrowLeft className="size-4" /> All mentors
      </Link>

      <div className="grid gap-8 lg:grid-cols-[1fr_22rem]">
        <div className="space-y-8">
          <Card className="overflow-hidden">
            <div className="h-28 bg-linear-to-r from-brand-900 to-brand-600" />
            <div className="px-6 pb-6">
              <Avatar user={mentor} size="xl" className="-mt-12 ring-4 ring-white" />
              <div className="mt-4 flex flex-wrap items-start justify-between gap-4">
                <div>
                  <h1 className="text-2xl font-semibold tracking-tight text-slate-900">{mentor.name}</h1>
                  {(profile?.jobTitle || profile?.company) && (
                    <p className="mt-1 text-slate-600">{[profile.jobTitle, profile.company].filter(Boolean).join(" at ")}</p>
                  )}
                  <div className="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                    {mentor.location && (
                      <span className="flex items-center gap-1"><MapPin className="size-4" /> {mentor.location}</span>
                    )}
                    {mentor.rating ? (
                      <span className="flex items-center gap-1.5">
                        <Stars rating={mentor.rating} /> {mentor.rating.toFixed(1)} · {mentor.reviewCount} review{mentor.reviewCount === 1 ? "" : "s"}
                      </span>
                    ) : (
                      <Badge tone="brand">New mentor</Badge>
                    )}
                  </div>
                </div>
                {profile?.linkedinUrl && (
                  <a href={profile.linkedinUrl} target="_blank" rel="noopener noreferrer" className="text-sm font-medium text-brand-700 hover:text-brand-800">
                    LinkedIn ↗
                  </a>
                )}
              </div>
              {mentor.headline && <p className="mt-5 text-lg text-slate-800">{mentor.headline}</p>}
            </div>
          </Card>

          <Card className="p-6">
            <h2 className="font-semibold text-slate-900">About</h2>
            <p className="mt-3 whitespace-pre-line text-slate-600">{mentor.bio || "This mentor hasn't written a bio yet."}</p>
            <h3 className="mt-6 text-sm font-semibold text-slate-900">Can help with</h3>
            <div className="mt-3 flex flex-wrap gap-2">
              {mentor.skills.map(({ skill }) => (
                <Link key={skill.id} href={`/mentors?skill=${skill.slug}`}>
                  <Badge tone="brand" className="px-3 py-1 text-sm hover:bg-brand-100">{skill.name}</Badge>
                </Link>
              ))}
            </div>
            <dl className="mt-6 grid gap-3 border-t border-slate-100 pt-6 sm:grid-cols-2">
              {facts.map((f) => (
                <div key={f.label} className="flex items-center gap-2.5 text-sm text-slate-600">
                  <f.icon className="size-4 text-slate-400" /> {f.label}
                </div>
              ))}
            </dl>
          </Card>

          <Card className="p-6">
            <h2 className="font-semibold text-slate-900">Reviews {mentor.reviewCount > 0 && <span className="text-slate-400">({mentor.reviewCount})</span>}</h2>
            {mentor.reviewsReceived.length === 0 ? (
              <p className="mt-3 text-sm text-slate-500">No reviews yet. Reviews appear after completed sessions.</p>
            ) : (
              <ul className="mt-4 divide-y divide-slate-100">
                {mentor.reviewsReceived.map((r) => (
                  <li key={r.id} className="py-5 first:pt-0 last:pb-0">
                    <div className="flex items-center gap-3">
                      <Avatar user={r.author} size="sm" />
                      <div className="flex-1">
                        <p className="text-sm font-medium text-slate-900">{r.author.name}</p>
                        <p className="text-xs text-slate-500">{r.session.topic} · {timeAgo(r.createdAt)}</p>
                      </div>
                      <Stars rating={r.rating} />
                    </div>
                    <p className="mt-3 text-sm text-slate-600">{r.comment}</p>
                  </li>
                ))}
              </ul>
            )}
          </Card>
        </div>

        <aside className="space-y-6 lg:sticky lg:top-8 lg:self-start">
          <Card className="p-6">
            <div className="flex items-baseline justify-between">
              <span className="text-2xl font-semibold text-slate-900">{formatRate(profile?.hourlyRate ?? 0)}</span>
              {profile?.acceptingMentees ? <Badge tone="green">Accepting mentees</Badge> : <Badge tone="amber">Not accepting</Badge>}
            </div>

            <div className="mt-6">
              {!viewer ? (
                <div className="space-y-3">
                  <ButtonLink href="/signup" className="w-full">Sign up to connect</ButtonLink>
                  <p className="text-center text-sm text-slate-500">
                    Have an account? <Link href={`/login?next=/mentors/${mentor.id}`} className="font-medium text-brand-700">Log in</Link>
                  </p>
                </div>
              ) : viewer.id === mentor.id ? (
                <ButtonLink href="/settings/profile" variant="secondary" className="w-full">Edit your profile</ButtonLink>
              ) : viewer.role !== "MENTEE" ? (
                <p className="text-sm text-slate-500">Only mentees can request mentorship.</p>
              ) : connection?.status === "ACCEPTED" ? (
                <div className="space-y-4">
                  <ButtonLink href={`/messages/${connection.id}`} variant="secondary" className="w-full">
                    <MessageSquare className="size-4" /> Message {mentor.name.split(" ")[0]}
                  </ButtonLink>
                  <BookingForm mentorId={mentor.id} slots={slots.map((s) => s.toISOString())} timeZone={viewer.timezone} />
                </div>
              ) : connection?.status === "PENDING" ? (
                <div className="rounded-xl bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-amber-200">
                  <p className="font-medium">Request sent</p>
                  <p className="mt-1">{mentor.name.split(" ")[0]} will review your request soon. We&apos;ll notify you when they reply.</p>
                </div>
              ) : profile?.acceptingMentees ? (
                <RequestForm mentorId={mentor.id} mentorName={mentor.name} wasDeclined={connection?.status === "DECLINED"} />
              ) : (
                <p className="text-sm text-slate-500">This mentor has paused new requests. Check back later.</p>
              )}
            </div>
          </Card>

          {mentor.availability.length > 0 && (
            <Card className="p-6">
              <h2 className="text-sm font-semibold text-slate-900">Weekly availability</h2>
              <p className="mt-0.5 text-xs text-slate-500">In {mentor.name.split(" ")[0]}&apos;s timezone ({mentor.timezone})</p>
              <ul className="mt-4 space-y-2 text-sm">
                {mentor.availability.map((w) => (
                  <li key={w.id} className="flex justify-between text-slate-600">
                    <span className="font-medium text-slate-700">{DAYS[w.dayOfWeek]!.slice(0, 3)}</span>
                    <span>{minutesToLabel(w.startMinute)} – {w.endMinute === 1440 ? "Midnight" : minutesToLabel(w.endMinute)}</span>
                  </li>
                ))}
              </ul>
            </Card>
          )}
        </aside>
      </div>
    </>
  );
}
