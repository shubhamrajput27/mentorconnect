"use client";

import Link from "next/link";
import { login } from "@/actions/auth";
import { SubmitButton, useFormAction } from "@/components/form";
import { Alert, Field, Input } from "@/components/ui";

export function LoginForm({ next }: { next?: string }) {
  const { state, form, pending } = useFormAction(login);
  return (
    <form {...form} className="space-y-5" noValidate>
      {state.error && <Alert>{state.error}</Alert>}
      {next && <input type="hidden" name="next" value={next} />}
      <Field label="Email" htmlFor="email" error={state.fieldErrors?.email}>
        <Input id="email" name="email" type="email" autoComplete="email" required autoFocus />
      </Field>
      <Field label="Password" htmlFor="password" error={state.fieldErrors?.password}>
        <Input id="password" name="password" type="password" autoComplete="current-password" required />
      </Field>
      <div className="-mt-2 text-right">
        <Link href="/forgot-password" className="text-sm font-medium text-brand-700 hover:text-brand-800">
          Forgot password?
        </Link>
      </div>
      <SubmitButton pending={pending} className="w-full" pendingText="Signing in…">
        Sign in
      </SubmitButton>
    </form>
  );
}
