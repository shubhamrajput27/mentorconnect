"use client";

import Link from "next/link";
import { useState } from "react";
import { GraduationCap, Presentation } from "lucide-react";
import { signup } from "@/actions/auth";
import { SubmitButton, useFormAction } from "@/components/form";
import { Alert, Field, Input } from "@/components/ui";
import { cn } from "@/lib/utils";

const roles = [
  { value: "MENTEE", title: "I want a mentor", text: "Learn and get guidance", icon: GraduationCap },
  { value: "MENTOR", title: "I want to mentor", text: "Share your experience", icon: Presentation },
] as const;

export function SignupForm({ defaultRole }: { defaultRole: "MENTEE" | "MENTOR" }) {
  const { state, form, pending } = useFormAction(signup);
  const [role, setRole] = useState(defaultRole);

  return (
    <form {...form} className="space-y-5" noValidate>
      {state.error && <Alert>{state.error}</Alert>}

      <fieldset>
        <legend className="sr-only">Account type</legend>
        <div className="grid grid-cols-2 gap-3">
          {roles.map((r) => (
            <label
              key={r.value}
              className={cn(
                "cursor-pointer rounded-xl p-4 ring-1 transition",
                role === r.value ? "bg-brand-50 ring-2 ring-brand-500" : "ring-slate-200 hover:ring-slate-300",
              )}
            >
              <input
                type="radio"
                name="role"
                value={r.value}
                checked={role === r.value}
                onChange={() => setRole(r.value)}
                className="sr-only"
              />
              <r.icon className={cn("size-5", role === r.value ? "text-brand-600" : "text-slate-400")} />
              <p className="mt-2 text-sm font-medium text-slate-900">{r.title}</p>
              <p className="text-xs text-slate-500">{r.text}</p>
            </label>
          ))}
        </div>
        {state.fieldErrors?.role && <p className="mt-1.5 text-sm text-red-600">{state.fieldErrors.role}</p>}
      </fieldset>

      <Field label="Full name" htmlFor="name" error={state.fieldErrors?.name}>
        <Input id="name" name="name" autoComplete="name" required />
      </Field>
      <Field label="Email" htmlFor="email" error={state.fieldErrors?.email}>
        <Input id="email" name="email" type="email" autoComplete="email" required />
      </Field>
      <Field
        label="Password"
        htmlFor="password"
        error={state.fieldErrors?.password}
        hint="At least 8 characters, with a letter and a number."
      >
        <Input id="password" name="password" type="password" autoComplete="new-password" required />
      </Field>
      <SubmitButton pending={pending} className="w-full" pendingText="Creating account…">
        Create account
      </SubmitButton>
      <p className="text-center text-xs text-slate-500">
        By signing up you agree to our{" "}
        <Link href="/terms" className="font-medium text-slate-700 underline underline-offset-2 hover:text-slate-900">Terms</Link> and{" "}
        <Link href="/privacy" className="font-medium text-slate-700 underline underline-offset-2 hover:text-slate-900">Privacy policy</Link>.
      </p>
    </form>
  );
}
