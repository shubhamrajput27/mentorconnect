"use client";

import { requestConnection } from "@/actions/connections";
import { SubmitButton, useFormAction } from "@/components/form";
import { Alert, Field, Textarea } from "@/components/ui";

export function RequestForm({ mentorId, mentorName, wasDeclined }: { mentorId: string; mentorName: string; wasDeclined: boolean }) {
  const { state, form, pending } = useFormAction(requestConnection);

  if (state.success) return <Alert tone="success">{state.success}</Alert>;

  return (
    <form {...form} className="space-y-4">
      <input type="hidden" name="mentorId" value={mentorId} />
      {state.error && <Alert>{state.error}</Alert>}
      {wasDeclined && (
        <p className="text-sm text-slate-500">Your last request wasn&apos;t accepted. You can send a new one with more detail.</p>
      )}
      <Field label={`Message to ${mentorName.split(" ")[0]}`} htmlFor="message" error={state.fieldErrors?.message}>
        <Textarea
          id="message"
          name="message"
          rows={5}
          required
          placeholder="Hi! I'm working towards… I'd love your help with…"
        />
      </Field>
      <SubmitButton pending={pending} className="w-full" pendingText="Sending…">
        Request mentorship
      </SubmitButton>
    </form>
  );
}
