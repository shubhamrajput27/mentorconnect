import type { Metadata } from "next";
import Link from "next/link";
import { CalendarCheck, GraduationCap, MessageSquare, Presentation, Search, Star } from "lucide-react";
import { setUserStatus } from "@/actions/admin";
import { ConfirmButton } from "@/components/confirm-button";
import { SubmitButton } from "@/components/form";
import { Avatar, Badge, Button, Card, CardHeader, Input, PageHeader, Select } from "@/components/ui";
import { StatCard } from "@/components/stat-card";
import type { Prisma } from "@/generated/prisma/client";
import { requireUser } from "@/lib/auth";
import { db } from "@/lib/db";
import { averageRating } from "@/lib/utils";

export const metadata: Metadata = { title: "Admin" };

export default async function AdminPage({ searchParams }: PageProps<"/admin">) {
  const admin = await requireUser(["ADMIN"]);
  const params = await searchParams;
  const q = typeof params.q === "string" ? params.q.trim() : "";
  const role = params.role === "MENTOR" || params.role === "MENTEE" || params.role === "ADMIN" ? params.role : "";

  const where: Prisma.UserWhereInput = {
    ...(role ? { role } : {}),
    ...(q ? { OR: [{ name: { contains: q, mode: "insensitive" } }, { email: { contains: q, mode: "insensitive" } }] } : {}),
  };

  const [mentors, mentees, sessions, messages, ratings, users, sessionsByStatus] = await Promise.all([
    db.user.count({ where: { role: "MENTOR" } }),
    db.user.count({ where: { role: "MENTEE" } }),
    db.mentorSession.count(),
    db.message.count(),
    db.review.findMany({ select: { rating: true } }),
    db.user.findMany({
      where,
      orderBy: { createdAt: "desc" },
      take: 100,
      include: { _count: { select: { mentorSessions: true, menteeSessions: true } } },
    }),
    db.mentorSession.groupBy({ by: ["status"], _count: { _all: true } }),
  ]);
  const avg = averageRating(ratings.map((r) => r.rating));

  return (
    <>
      <PageHeader title="Admin" description="Platform overview and user management." />

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <StatCard label="Mentors" value={mentors} icon={<Presentation className="size-5" />} />
        <StatCard label="Mentees" value={mentees} icon={<GraduationCap className="size-5" />} />
        <StatCard
          label="Sessions"
          value={sessions}
          icon={<CalendarCheck className="size-5" />}
          hint={sessionsByStatus.map((s) => `${s._count._all} ${s.status.toLowerCase()}`).join(" · ")}
        />
        <StatCard label="Messages" value={messages} icon={<MessageSquare className="size-5" />} />
        <StatCard label="Avg. rating" value={avg ? avg.toFixed(1) : "—"} icon={<Star className="size-5" />} hint={`${ratings.length} reviews`} />
      </div>

      <Card className="mt-8">
        <CardHeader title="Users" description={`Showing ${users.length} user${users.length === 1 ? "" : "s"}`} />
        <form className="flex flex-col gap-3 border-b border-slate-100 px-6 py-4 sm:flex-row">
          <div className="relative flex-1">
            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
            <Input name="q" defaultValue={q} placeholder="Search name or email" className="pl-9" aria-label="Search users" />
          </div>
          <Select name="role" defaultValue={role} className="sm:w-40" aria-label="Role">
            <option value="">All roles</option>
            <option value="MENTOR">Mentors</option>
            <option value="MENTEE">Mentees</option>
            <option value="ADMIN">Admins</option>
          </Select>
          <Button type="submit" variant="secondary">Filter</Button>
        </form>
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className="bg-slate-50 text-xs font-medium tracking-wide text-slate-500 uppercase">
              <tr>
                <th className="px-6 py-3">User</th>
                <th className="px-6 py-3">Role</th>
                <th className="px-6 py-3">Sessions</th>
                <th className="px-6 py-3">Joined</th>
                <th className="px-6 py-3">Status</th>
                <th className="px-6 py-3"><span className="sr-only">Actions</span></th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {users.map((u) => (
                <tr key={u.id}>
                  <td className="px-6 py-3">
                    <div className="flex items-center gap-3">
                      <Avatar user={u} size="sm" />
                      <div>
                        {u.role === "MENTOR" ? (
                          <Link href={`/mentors/${u.id}`} className="font-medium text-slate-900 hover:text-brand-700">{u.name}</Link>
                        ) : (
                          <p className="font-medium text-slate-900">{u.name}</p>
                        )}
                        <p className="text-xs text-slate-500">{u.email}</p>
                      </div>
                    </div>
                  </td>
                  <td className="px-6 py-3">
                    <Badge tone={u.role === "MENTOR" ? "brand" : u.role === "ADMIN" ? "amber" : "gray"}>{u.role.toLowerCase()}</Badge>
                  </td>
                  <td className="px-6 py-3 text-slate-600">{u._count.mentorSessions + u._count.menteeSessions}</td>
                  <td className="px-6 py-3 text-slate-600">{u.createdAt.toLocaleDateString("en-IN", { day: "numeric", month: "short", year: "numeric" })}</td>
                  <td className="px-6 py-3">
                    {u.status === "ACTIVE" ? <Badge tone="green">Active</Badge> : <Badge tone="red">Suspended</Badge>}
                  </td>
                  <td className="px-6 py-3 text-right">
                    {u.id !== admin.id && (
                      <form action={setUserStatus}>
                        <input type="hidden" name="userId" value={u.id} />
                        {u.status === "ACTIVE" ? (
                          <ConfirmButton name="status" value="SUSPENDED" size="sm" variant="ghost" className="text-red-600 hover:bg-red-50" message={`Suspend ${u.name}? They will be signed out and unable to log in.`}>
                            Suspend
                          </ConfirmButton>
                        ) : (
                          <SubmitButton name="status" value="ACTIVE" size="sm" variant="secondary">Reactivate</SubmitButton>
                        )}
                      </form>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </Card>
    </>
  );
}
