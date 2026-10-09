import type { Metadata } from "next";
import { CheckCircle2, XCircle } from "lucide-react";
import { Logo } from "@/components/logo";
import { ButtonLink } from "@/components/ui";
import { getCurrentUser } from "@/lib/auth";
import { db } from "@/lib/db";
import { consumeToken, findToken } from "@/lib/tokens";

export const metadata: Metadata = { title: "Verify email" };

export default async function VerifyEmailPage({ searchParams }: PageProps<"/verify-email">) {
  const { token } = await searchParams;
  const record = typeof token === "string" ? await findToken(token, "EMAIL_VERIFY") : null;
  const ok = Boolean(record && (await consumeToken(record.id)));
  if (ok) {
    await db.user.update({ where: { id: record!.userId }, data: { emailVerifiedAt: new Date() } });
  }
  const user = await getCurrentUser();

  return (
    <div className="flex min-h-screen flex-col items-center justify-center px-6 text-center">
      <Logo />
      {ok ? (
        <>
          <CheckCircle2 className="mt-12 size-12 text-emerald-500" />
          <h1 className="mt-4 text-2xl font-semibold tracking-tight text-slate-900">Email verified</h1>
          <p className="mt-2 text-slate-600">Thanks for confirming your email address.</p>
        </>
      ) : (
        <>
          <XCircle className="mt-12 size-12 text-red-500" />
          <h1 className="mt-4 text-2xl font-semibold tracking-tight text-slate-900">Link invalid or expired</h1>
          <p className="mt-2 max-w-md text-slate-600">
            Verification links work once and expire after 24 hours. Log in and use the banner at the top of the page to get a new one.
          </p>
        </>
      )}
      <ButtonLink href={user ? "/dashboard" : "/login"} className="mt-8">
        {user ? "Go to dashboard" : "Log in"}
      </ButtonLink>
    </div>
  );
}
