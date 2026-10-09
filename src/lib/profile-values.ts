import "server-only";
import { db } from "@/lib/db";
import type { CurrentUser } from "@/lib/auth";
import type { ProfileFormValues } from "@/components/profile-form";

export async function loadProfileForm(user: CurrentUser) {
  const [userSkills, allSkills] = await Promise.all([
    db.userSkill.findMany({ where: { userId: user.id }, include: { skill: true } }),
    // Most-used skills first, so the quick-add suggestions are the useful ones.
    db.skill.findMany({ orderBy: [{ users: { _count: "desc" } }, { name: "asc" }], select: { name: true } }),
  ]);
  const m = user.mentorProfile;

  const values: ProfileFormValues = {
    role: user.role,
    name: user.name,
    headline: user.headline ?? "",
    bio: user.bio ?? "",
    location: user.location ?? "",
    timezone: user.timezone,
    goals: user.goals ?? "",
    skills: userSkills.map((s) => s.skill.name),
    mentor:
      user.role === "MENTOR"
        ? {
            jobTitle: m?.jobTitle ?? "",
            company: m?.company ?? "",
            yearsExperience: m?.yearsExperience ?? 0,
            hourlyRate: m?.hourlyRate ?? 0,
            sessionMinutes: m?.sessionMinutes ?? 60,
            languages: m?.languages ?? "English",
            linkedinUrl: m?.linkedinUrl ?? "",
            acceptingMentees: m?.acceptingMentees ?? true,
          }
        : undefined,
  };
  return { values, suggestions: allSkills.map((s) => s.name) };
}
