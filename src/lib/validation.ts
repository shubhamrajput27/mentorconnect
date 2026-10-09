import { z } from "zod";
import { TIMEZONES } from "@/lib/time";

const trimmed = (max: number) => z.string().trim().max(max);
const optionalText = (max: number) =>
  trimmed(max).transform((v) => (v === "" ? null : v));

const email = z.string().trim().toLowerCase().pipe(z.email("Enter a valid email address."));

const passwordRule = z
  .string()
  .min(8, "Use at least 8 characters.")
  .max(128)
  .regex(/[A-Za-z]/, "Include at least one letter.")
  .regex(/[0-9]/, "Include at least one number.");

export const signupSchema = z.object({
  name: trimmed(80).min(2, "Please enter your full name."),
  email,
  password: passwordRule,
  role: z.enum(["MENTEE", "MENTOR"], { error: "Choose how you want to join." }),
});

export const loginSchema = z.object({
  email,
  password: z.string().min(1, "Enter your password."),
});

export const forgotPasswordSchema = z.object({ email });

export const resetPasswordSchema = z
  .object({
    token: z.string().min(1),
    password: passwordRule,
    confirm: z.string(),
  })
  .refine((v) => v.password === v.confirm, { message: "Passwords don't match.", path: ["confirm"] });

export const profileSchema = z.object({
  name: trimmed(80).min(2, "Please enter your full name."),
  headline: trimmed(120).min(4, "Add a short headline about yourself."),
  bio: optionalText(2000),
  location: optionalText(80),
  timezone: z.enum(TIMEZONES as [string, ...string[]]),
  goals: optionalText(1000),
  skills: z
    .array(trimmed(40).min(1))
    .max(15, "Add up to 15 skills.")
    .transform((list) => [...new Set(list.map((s) => s.replace(/\s+/g, " ")))]),
});

export const mentorProfileSchema = z.object({
  jobTitle: optionalText(80),
  company: optionalText(80),
  yearsExperience: z.coerce.number().int().min(0).max(60),
  hourlyRate: z.coerce.number().int().min(0).max(100000),
  sessionMinutes: z.coerce.number().pipe(z.union([z.literal(30), z.literal(45), z.literal(60), z.literal(90)])),
  languages: trimmed(120).min(2),
  linkedinUrl: z
    .string()
    .trim()
    .transform((v) => (v === "" ? null : v))
    .pipe(z.url("Enter a full URL starting with https://").nullable()),
  acceptingMentees: z.boolean(),
});

export const availabilitySchema = z
  .array(
    z
      .object({
        dayOfWeek: z.number().int().min(0).max(6),
        startMinute: z.number().int().min(0).max(1440),
        endMinute: z.number().int().min(0).max(1440),
      })
      .refine((w) => w.endMinute - w.startMinute >= 30, {
        message: "Each time block must be at least 30 minutes long.",
      }),
  )
  .max(50);

export const connectionRequestSchema = z.object({
  mentorId: z.string().min(1),
  message: trimmed(1000).min(20, "Tell the mentor a little about what you need (at least 20 characters)."),
});

export const bookingSchema = z.object({
  mentorId: z.string().min(1),
  startsAt: z.iso.datetime({ error: "Pick a time slot." }),
  topic: trimmed(120).min(4, "Add a topic for the session."),
  agenda: optionalText(2000),
});

export const messageSchema = z.object({
  connectionId: z.string().min(1),
  body: trimmed(4000).min(1, "Message can't be empty."),
});

export const reviewSchema = z.object({
  sessionId: z.string().min(1),
  rating: z.coerce.number().int().min(1, "Choose a rating.").max(5),
  comment: trimmed(1500).min(10, "Write at least a sentence (10+ characters)."),
});

/** Flatten zod issues to `{ field: firstMessage }` for forms. */
export function fieldErrors(error: z.ZodError) {
  const out: Record<string, string> = {};
  for (const issue of error.issues) {
    const key = String(issue.path[0] ?? "form");
    out[key] ??= issue.message;
  }
  return out;
}

export type FormState = {
  error?: string;
  fieldErrors?: Record<string, string>;
  success?: string;
};
