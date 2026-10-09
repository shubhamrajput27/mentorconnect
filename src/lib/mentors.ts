import "server-only";
import { db } from "@/lib/db";
import type { Prisma } from "@/generated/prisma/client";

export type MentorSort = "rating" | "experience" | "price-low" | "newest";

export type MentorFilters = {
  q?: string;
  skill?: string;
  maxRate?: number;
  sort?: MentorSort;
};

const mentorInclude = {
  mentorProfile: true,
  skills: { include: { skill: true } },
} satisfies Prisma.UserInclude;

async function withRatings<T extends { id: string }>(mentors: T[]) {
  const stats = await db.review.groupBy({
    by: ["mentorId"],
    where: { mentorId: { in: mentors.map((m) => m.id) } },
    _avg: { rating: true },
    _count: { _all: true },
  });
  const byId = new Map(stats.map((s) => [s.mentorId, s]));
  return mentors.map((m) => {
    const s = byId.get(m.id);
    return {
      ...m,
      rating: s?._avg.rating ? Math.round(s._avg.rating * 10) / 10 : null,
      reviewCount: s?._count._all ?? 0,
    };
  });
}

export async function searchMentors(filters: MentorFilters) {
  const q = filters.q?.trim();
  const where: Prisma.UserWhereInput = {
    role: "MENTOR",
    status: "ACTIVE",
    onboarded: true,
    ...(filters.skill ? { skills: { some: { skill: { slug: filters.skill } } } } : {}),
    ...(filters.maxRate !== undefined ? { mentorProfile: { hourlyRate: { lte: filters.maxRate } } } : {}),
    ...(q
      ? {
          OR: [
            { name: { contains: q, mode: "insensitive" } },
            { headline: { contains: q, mode: "insensitive" } },
            { bio: { contains: q, mode: "insensitive" } },
            { mentorProfile: { company: { contains: q, mode: "insensitive" } } },
            { mentorProfile: { jobTitle: { contains: q, mode: "insensitive" } } },
            { skills: { some: { skill: { name: { contains: q, mode: "insensitive" } } } } },
          ],
        }
      : {}),
  };

  const orderBy: Prisma.UserOrderByWithRelationInput =
    filters.sort === "experience"
      ? { mentorProfile: { yearsExperience: "desc" } }
      : filters.sort === "price-low"
        ? { mentorProfile: { hourlyRate: "asc" } }
        : { createdAt: "desc" };

  const mentors = await withRatings(
    await db.user.findMany({ where, include: mentorInclude, orderBy, take: 60 }),
  );

  if (filters.sort === "rating" || !filters.sort) {
    mentors.sort((a, b) => (b.rating ?? 0) - (a.rating ?? 0) || b.reviewCount - a.reviewCount);
  }
  return mentors;
}

export type MentorCardData = Awaited<ReturnType<typeof searchMentors>>[number];

export async function getMentor(id: string) {
  const mentor = await db.user.findFirst({
    where: { id, role: "MENTOR", status: "ACTIVE" },
    include: {
      ...mentorInclude,
      availability: { orderBy: [{ dayOfWeek: "asc" }, { startMinute: "asc" }] },
      reviewsReceived: {
        include: { author: { select: { id: true, name: true, photoVersion: true } }, session: { select: { topic: true } } },
        orderBy: { createdAt: "desc" },
        take: 20,
      },
      _count: {
        select: {
          mentorSessions: { where: { status: "COMPLETED" } },
          mentorConnections: { where: { status: "ACCEPTED" } },
        },
      },
    },
  });
  if (!mentor) return null;
  const [rated] = await withRatings([mentor]);
  return rated!;
}

export async function popularSkills(limit = 12) {
  const skills = await db.skill.findMany({
    where: { users: { some: { user: { role: "MENTOR", status: "ACTIVE" } } } },
    include: { _count: { select: { users: true } } },
  });
  return skills.sort((a, b) => b._count.users - a._count.users).slice(0, limit);
}
