"use server";

import { redirect } from "next/navigation";
import { db } from "@/lib/db";
import { createSession, destroySession, getCurrentUser, hashPassword, verifyPassword } from "@/lib/auth";
import { appUrl, sendEmail } from "@/lib/email";
import { rateLimit } from "@/lib/rate-limit";
import { consumeToken, findToken, issueToken } from "@/lib/tokens";
import {
  fieldErrors,
  forgotPasswordSchema,
  loginSchema,
  resetPasswordSchema,
  signupSchema,
  type FormState,
} from "@/lib/validation";

function safeRedirect(target: FormDataEntryValue | null) {
  const value = typeof target === "string" ? target : "";
  return value.startsWith("/") && !value.startsWith("//") ? value : "/dashboard";
}

async function sendVerificationEmail(user: { id: string; email: string; name: string }) {
  const token = await issueToken(user.id, "EMAIL_VERIFY");
  await sendEmail({
    to: user.email,
    subject: "Verify your email for MentorConnect",
    heading: `Welcome, ${user.name.split(" ")[0]}!`,
    text: "Please confirm your email address so mentors and mentees know you're real. This link expires in 24 hours.",
    action: { label: "Verify email", url: `${appUrl()}/verify-email?token=${token}` },
  });
}

export async function signup(_prev: FormState, formData: FormData): Promise<FormState> {
  if (!(await rateLimit("signup", 10, 60 * 60 * 1000))) {
    return { error: "Too many sign-up attempts. Please try again later." };
  }

  const parsed = signupSchema.safeParse(Object.fromEntries(formData));
  if (!parsed.success) return { fieldErrors: fieldErrors(parsed.error) };
  const { name, email, password, role } = parsed.data;

  const existing = await db.user.findUnique({ where: { email } });
  if (existing) return { fieldErrors: { email: "An account with this email already exists." } };

  const user = await db.user.create({
    data: {
      name,
      email,
      role,
      passwordHash: await hashPassword(password),
      mentorProfile: role === "MENTOR" ? { create: {} } : undefined,
    },
  });

  await sendVerificationEmail(user);
  await createSession(user.id);
  redirect("/onboarding");
}

export async function login(_prev: FormState, formData: FormData): Promise<FormState> {
  const parsed = loginSchema.safeParse(Object.fromEntries(formData));
  if (!parsed.success) return { fieldErrors: fieldErrors(parsed.error) };

  // Limit per account and IP, so one person can't lock out everyone behind a shared network.
  if (!(await rateLimit(`login:${parsed.data.email}`, 10, 15 * 60 * 1000))) {
    return { error: "Too many login attempts. Please wait 15 minutes and try again." };
  }

  const user = await db.user.findUnique({ where: { email: parsed.data.email } });
  const valid = user && (await verifyPassword(parsed.data.password, user.passwordHash));
  if (!user || !valid) return { error: "Incorrect email or password." };
  if (user.status === "SUSPENDED") {
    return { error: "This account has been suspended. Contact support for help." };
  }

  await createSession(user.id);
  if (user.role === "ADMIN") redirect(formData.get("next") ? safeRedirect(formData.get("next")) : "/admin");
  redirect(user.onboarded ? safeRedirect(formData.get("next")) : "/onboarding");
}

export async function logout() {
  await destroySession();
  redirect("/");
}

export async function resendVerification(): Promise<FormState> {
  const user = await getCurrentUser();
  if (!user) redirect("/login");
  if (user.emailVerifiedAt) return { success: "Your email is already verified." };
  if (!(await rateLimit(`verify:${user.id}`, 3, 60 * 60 * 1000))) {
    return { error: "You've requested several emails already. Please check your inbox or try again in an hour." };
  }
  await sendVerificationEmail(user);
  return { success: `We've sent a new link to ${user.email}.` };
}

export async function requestPasswordReset(_prev: FormState, formData: FormData): Promise<FormState> {
  if (!(await rateLimit("forgot", 5, 60 * 60 * 1000))) {
    return { error: "Too many reset requests. Please try again later." };
  }
  const parsed = forgotPasswordSchema.safeParse(Object.fromEntries(formData));
  if (!parsed.success) return { fieldErrors: fieldErrors(parsed.error) };

  // Same response whether or not the account exists, so emails can't be probed.
  const done: FormState = {
    success: `If an account exists for ${parsed.data.email}, we've sent a link to reset your password. It expires in 1 hour.`,
  };

  const user = await db.user.findUnique({ where: { email: parsed.data.email } });
  if (!user || user.status === "SUSPENDED") return done;

  const token = await issueToken(user.id, "PASSWORD_RESET");
  await sendEmail({
    to: user.email,
    subject: "Reset your MentorConnect password",
    heading: "Reset your password",
    text: "Someone (hopefully you) asked to reset your password. Click below to choose a new one. If this wasn't you, you can ignore this email.",
    action: { label: "Choose a new password", url: `${appUrl()}/reset-password?token=${token}` },
  });
  return done;
}

export async function resetPassword(_prev: FormState, formData: FormData): Promise<FormState> {
  const parsed = resetPasswordSchema.safeParse(Object.fromEntries(formData));
  if (!parsed.success) return { fieldErrors: fieldErrors(parsed.error) };

  const record = await findToken(parsed.data.token, "PASSWORD_RESET");
  if (!record || !(await consumeToken(record.id))) {
    return { error: "This reset link is invalid or has expired. Please request a new one." };
  }

  // A new password signs the user out everywhere; opening the email link also proves the address.
  const user = await db.user.update({
    where: { id: record.userId },
    data: { passwordHash: await hashPassword(parsed.data.password), emailVerifiedAt: new Date() },
  });
  await db.authSession.deleteMany({ where: { userId: user.id } });
  if (user.status === "SUSPENDED") redirect("/login");

  await createSession(user.id);
  redirect(user.role === "ADMIN" ? "/admin" : user.onboarded ? "/dashboard" : "/onboarding");
}
