"use client";

import { resetPassword } from "@/actions/auth";
import { SubmitButton, useFormAction } from "@/components/form";
import { Alert, Field, Input } from "@/components/ui";

export function ResetPasswordForm({ token }: { token: string }) {
  const { state, form, pending } = useFormAction(resetPassword);
  return (
    <form {...form} className="space-y-5" noValidate>
      <input type="hidden" name="token" value={token} />
      {state.error && <Alert>{state.error}</Alert>}
      <Field
        label="New password"
        htmlFor="password"
        error={state.fieldErrors?.password}
        hint="At least 8 characters, with a letter and a number."
      >
        <Input id="password" name="password" type="password" autoComplete="new-password" required autoFocus />
      </Field>
      <Field label="Confirm new password" htmlFor="confirm" error={state.fieldErrors?.confirm}>
        <Input id="confirm" name="confirm" type="password" autoComplete="new-password" required />
      </Field>
      <SubmitButton pending={pending} className="w-full" pendingText="Saving…">
        Save new password
      </SubmitButton>
    </form>
  );
}
