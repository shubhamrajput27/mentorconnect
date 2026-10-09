"use client";

import { useActionState } from "react";
import { MailWarning } from "lucide-react";
import { resendVerification } from "@/actions/auth";
import type { FormState } from "@/lib/validation";

export function VerifyBanner({ email }: { email: string }) {
  const [state, action, pending] = useActionState<FormState>(resendVerification, {});
  return (
    <div className="border-b border-amber-200 bg-amber-50 px-4 py-2.5 text-sm text-amber-900 sm:px-6 lg:px-10">
      <div className="mx-auto flex max-w-6xl flex-wrap items-center gap-x-3 gap-y-1">
        <MailWarning className="size-4 shrink-0" />
        <span className="flex-1">
          {state.success ?? state.error ?? (
            <>
              Please verify your email address. We sent a link to <span className="font-medium">{email}</span>.
            </>
          )}
        </span>
        {!state.success && (
          <form action={action}>
            <button type="submit" disabled={pending} className="font-medium underline underline-offset-2 hover:text-amber-950 disabled:opacity-60">
              {pending ? "Sending…" : "Resend email"}
            </button>
          </form>
        )}
      </div>
    </div>
  );
}
