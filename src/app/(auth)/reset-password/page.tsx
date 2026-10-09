import type { Metadata } from "next";
import { ButtonLink } from "@/components/ui";
import { findToken } from "@/lib/tokens";
import { ResetPasswordForm } from "./reset-password-form";

export const metadata: Metadata = { title: "Choose a new password" };

export default async function ResetPasswordPage({ searchParams }: PageProps<"/reset-password">) {
  const { token } = await searchParams;
  const valid = typeof token === "string" && (await findToken(token, "PASSWORD_RESET"));

  if (!valid) {
    return (
      <>
        <h1 className="text-2xl font-semibold tracking-tight text-slate-900">Link expired</h1>
        <p className="mt-2 text-sm text-slate-600">
          This password reset link is invalid or has already been used. Reset links work once and expire after 1 hour.
        </p>
        <ButtonLink href="/forgot-password" className="mt-8 w-full">Request a new link</ButtonLink>
      </>
    );
  }

  return (
    <>
      <h1 className="text-2xl font-semibold tracking-tight text-slate-900">Choose a new password</h1>
      <p className="mt-2 text-sm text-slate-600">You&apos;ll be signed out on all other devices.</p>
      <div className="mt-8">
        <ResetPasswordForm token={token} />
      </div>
    </>
  );
}
