"use client";

import { requestPasswordReset } from "@/actions/auth";
import { SubmitButton, useFormAction } from "@/components/form";
import { Alert, Field, Input } from "@/components/ui";

export function ForgotPasswordForm() {
  const { state, form, pending } = useFormAction(requestPasswordReset);

  if (state.success) return <Alert tone="success">{state.success}</Alert>;

  return (
    <form {...form} className="space-y-5" noValidate>
      {state.error && <Alert>{state.error}</Alert>}
      <Field label="Email" htmlFor="email" error={state.fieldErrors?.email}>
        <Input id="email" name="email" type="email" autoComplete="email" required autoFocus />
      </Field>
      <SubmitButton pending={pending} className="w-full" pendingText="Sending…">
        Send reset link
      </SubmitButton>
    </form>
  );
}
