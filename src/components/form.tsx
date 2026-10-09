"use client";

import { startTransition, useActionState, type FormEvent } from "react";
import { useFormStatus } from "react-dom";
import { Loader2 } from "lucide-react";
import type { ComponentProps } from "react";
import { Button } from "@/components/ui";
import type { FormState } from "@/lib/validation";

/**
 * Like useActionState, but submits without React's automatic form reset,
 * so a failed submission keeps what the user typed. Spread `form` onto <form>.
 *
 * `action` makes the form work before JavaScript loads (a POST to the server
 * action, never a GET that would put fields in the URL). Once hydrated,
 * `onSubmit` takes over and React skips the action because the event is
 * already handled.
 */
export function useFormAction(action: (prev: FormState, data: FormData) => Promise<FormState>) {
  const [state, formAction, pending] = useActionState(action, {});
  const onSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    startTransition(() => formAction(data));
  };
  return { state, form: { action: formAction, onSubmit }, pending };
}

/** Submit button that disables itself and shows a spinner while its form is pending. */
export function SubmitButton({
  children,
  pendingText,
  pending: pendingProp,
  ...props
}: ComponentProps<typeof Button> & { pendingText?: string; pending?: boolean }) {
  const status = useFormStatus();
  const pending = pendingProp ?? status.pending;
  return (
    <Button type="submit" disabled={pending || props.disabled} {...props}>
      {pending && <Loader2 className="size-4 animate-spin" />}
      {pending && pendingText ? pendingText : children}
    </Button>
  );
}
