"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { db } from "@/lib/db";
import { getCurrentUser } from "@/lib/auth";
import { slugify } from "@/lib/utils";
import {
  availabilitySchema,
  fieldErrors,
  mentorProfileSchema,
  profileSchema,
  type FormState,
} from "@/lib/validation";

function parseSkills(raw: FormDataEntryValue | null) {
  try {
    const value = JSON.parse(String(raw ?? "[]"));
    return Array.isArray(value) ? value.map(String) : [];
  } catch {
    return [];
  }
}

export async function saveProfile(_prev: FormState, formData: FormData): Promise<FormState> {
  const user = await getCurrentUser();
  if (!user) redirect("/login");

  const parsed = profileSchema.safeParse({
    name: formData.get("name"),
    headline: formData.get("headline"),
    bio: formData.get("bio") ?? "",
    location: formData.get("location") ?? "",
    timezone: formData.get("timezone"),
    goals: formData.get("goals") ?? "",
    skills: parseSkills(formData.get("skills")),
  });
  if (!parsed.success) return { fieldErrors: fieldErrors(parsed.error) };
  const { skills, ...profile } = parsed.data;
  if (skills.length === 0) return { fieldErrors: { skills: "Add at least one skill." } };

  let mentorData = null;
  if (user.role === "MENTOR") {
    const mentorParsed = mentorProfileSchema.safeParse({
      jobTitle: formData.get("jobTitle") ?? "",
      company: formData.get("company") ?? "",
      yearsExperience: formData.get("yearsExperience") ?? 0,
      hourlyRate: formData.get("hourlyRate") ?? 0,
      sessionMinutes: formData.get("sessionMinutes") ?? 60,
      languages: formData.get("languages") ?? "English",
      linkedinUrl: formData.get("linkedinUrl") ?? "",
      acceptingMentees: formData.get("acceptingMentees") === "on",
    });
    if (!mentorParsed.success) return { fieldErrors: fieldErrors(mentorParsed.error) };
    mentorData = mentorParsed.data;
  }

  const skillRecords = await Promise.all(
    skills.map((name) =>
      db.skill.upsert({
        where: { slug: slugify(name) },
        update: {},
        create: { name, slug: slugify(name) },
      }),
    ),
  );

  const wasOnboarded = user.onboarded;
  await db.$transaction([
    db.user.update({ where: { id: user.id }, data: { ...profile, onboarded: true } }),
    db.userSkill.deleteMany({ where: { userId: user.id } }),
    db.userSkill.createMany({ data: skillRecords.map((s) => ({ userId: user.id, skillId: s.id })) }),
    ...(mentorData
      ? [
          db.mentorProfile.upsert({
            where: { userId: user.id },
            update: mentorData,
            create: { userId: user.id, ...mentorData },
          }),
        ]
      : []),
  ]);

  revalidatePath("/", "layout");
  if (!wasOnboarded) {
    redirect(user.role === "MENTOR" ? "/settings/availability?welcome=1" : "/mentors?welcome=1");
  }
  return { success: "Profile saved." };
}

export async function saveAvailability(_prev: FormState, formData: FormData): Promise<FormState> {
  const user = await getCurrentUser();
  if (!user || user.role !== "MENTOR") redirect("/dashboard");

  let raw: unknown;
  try {
    raw = JSON.parse(String(formData.get("windows") ?? "[]"));
  } catch {
    return { error: "Couldn't read your availability. Please try again." };
  }
  const parsed = availabilitySchema.safeParse(raw);
  if (!parsed.success) return { error: parsed.error.issues[0]?.message ?? "Invalid availability." };

  // Reject overlapping blocks on the same day.
  const byDay = [...parsed.data].sort((a, b) => a.dayOfWeek - b.dayOfWeek || a.startMinute - b.startMinute);
  for (let i = 1; i < byDay.length; i++) {
    const prev = byDay[i - 1]!;
    const cur = byDay[i]!;
    if (prev.dayOfWeek === cur.dayOfWeek && cur.startMinute < prev.endMinute) {
      return { error: "Two of your time blocks overlap on the same day." };
    }
  }

  await db.$transaction([
    db.availability.deleteMany({ where: { mentorId: user.id } }),
    db.availability.createMany({ data: byDay.map((w) => ({ ...w, mentorId: user.id })) }),
  ]);
  revalidatePath("/", "layout");
  return { success: "Availability saved." };
}
