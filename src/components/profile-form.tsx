"use client";

import { saveProfile } from "@/actions/profile";
import { SubmitButton, useFormAction } from "@/components/form";
import { SkillsInput } from "@/components/skills-input";
import { Alert, Field, Input, Select, Textarea } from "@/components/ui";
import { TIMEZONES } from "@/lib/time";

export type ProfileFormValues = {
  role: "MENTEE" | "MENTOR" | "ADMIN";
  name: string;
  headline: string;
  bio: string;
  location: string;
  timezone: string;
  goals: string;
  skills: string[];
  mentor?: {
    jobTitle: string;
    company: string;
    yearsExperience: number;
    hourlyRate: number;
    sessionMinutes: number;
    languages: string;
    linkedinUrl: string;
    acceptingMentees: boolean;
  };
};

export function ProfileForm({
  values,
  suggestions,
  submitLabel,
}: {
  values: ProfileFormValues;
  suggestions: string[];
  submitLabel: string;
}) {
  const { state, form, pending } = useFormAction(saveProfile);
  const e = state.fieldErrors ?? {};
  const isMentor = values.role === "MENTOR";

  return (
    <form {...form} className="space-y-8" noValidate>
      {state.error && <Alert>{state.error}</Alert>}
      {state.success && <Alert tone="success">{state.success}</Alert>}

      <section className="space-y-5">
        <h2 className="text-sm font-semibold tracking-wide text-slate-500 uppercase">About you</h2>
        <div className="grid gap-5 sm:grid-cols-2">
          <Field label="Full name" htmlFor="name" error={e.name}>
            <Input id="name" name="name" defaultValue={values.name} required />
          </Field>
          <Field label="Location" htmlFor="location" error={e.location}>
            <Input id="location" name="location" defaultValue={values.location} placeholder="Bengaluru, India" />
          </Field>
        </div>
        <Field
          label="Headline"
          htmlFor="headline"
          error={e.headline}
          hint={isMentor ? "e.g. “Senior engineer helping devs crack system design interviews”" : "e.g. “Final-year CS student aiming for a frontend role”"}
        >
          <Input id="headline" name="headline" defaultValue={values.headline} maxLength={120} required />
        </Field>
        <Field label="Bio" htmlFor="bio" error={e.bio}>
          <Textarea id="bio" name="bio" rows={5} defaultValue={values.bio} placeholder="Your background, what you're working on, and what you care about." />
        </Field>
        {!isMentor && (
          <Field label="Your goals" htmlFor="goals" error={e.goals} hint="Mentors see this when you send a request.">
            <Textarea id="goals" name="goals" rows={3} defaultValue={values.goals} placeholder="What do you want to achieve in the next 3–6 months?" />
          </Field>
        )}
        <Field label={isMentor ? "Skills you can mentor in" : "Skills you want to learn"} error={e.skills}>
          <SkillsInput name="skills" defaultValue={values.skills} suggestions={suggestions} />
        </Field>
        <Field label="Timezone" htmlFor="timezone" error={e.timezone}>
          <Select id="timezone" name="timezone" defaultValue={values.timezone}>
            {TIMEZONES.map((tz) => (
              <option key={tz} value={tz}>
                {tz.replace("_", " ")}
              </option>
            ))}
          </Select>
        </Field>
      </section>

      {isMentor && values.mentor && (
        <section className="space-y-5 border-t border-slate-100 pt-8">
          <h2 className="text-sm font-semibold tracking-wide text-slate-500 uppercase">Mentoring details</h2>
          <div className="grid gap-5 sm:grid-cols-2">
            <Field label="Job title" htmlFor="jobTitle" error={e.jobTitle}>
              <Input id="jobTitle" name="jobTitle" defaultValue={values.mentor.jobTitle} placeholder="Senior Software Engineer" />
            </Field>
            <Field label="Company" htmlFor="company" error={e.company}>
              <Input id="company" name="company" defaultValue={values.mentor.company} placeholder="Acme Inc." />
            </Field>
            <Field label="Years of experience" htmlFor="yearsExperience" error={e.yearsExperience}>
              <Input id="yearsExperience" name="yearsExperience" type="number" min={0} max={60} defaultValue={values.mentor.yearsExperience} />
            </Field>
            <Field label="Rate per hour (₹)" htmlFor="hourlyRate" error={e.hourlyRate} hint="Set 0 to mentor for free.">
              <Input id="hourlyRate" name="hourlyRate" type="number" min={0} step={100} defaultValue={values.mentor.hourlyRate} />
            </Field>
            <Field label="Session length" htmlFor="sessionMinutes" error={e.sessionMinutes}>
              <Select id="sessionMinutes" name="sessionMinutes" defaultValue={values.mentor.sessionMinutes}>
                {[30, 45, 60, 90].map((m) => (
                  <option key={m} value={m}>
                    {m} minutes
                  </option>
                ))}
              </Select>
            </Field>
            <Field label="Languages" htmlFor="languages" error={e.languages}>
              <Input id="languages" name="languages" defaultValue={values.mentor.languages} placeholder="English, Hindi" />
            </Field>
          </div>
          <Field label="LinkedIn URL" htmlFor="linkedinUrl" error={e.linkedinUrl}>
            <Input id="linkedinUrl" name="linkedinUrl" type="url" defaultValue={values.mentor.linkedinUrl} placeholder="https://www.linkedin.com/in/you" />
          </Field>
          <label className="flex items-start gap-3 rounded-xl p-4 ring-1 ring-slate-200">
            <input
              type="checkbox"
              name="acceptingMentees"
              defaultChecked={values.mentor.acceptingMentees}
              className="mt-0.5 size-4 rounded border-slate-300 accent-brand-600"
            />
            <span>
              <span className="block text-sm font-medium text-slate-900">Accepting new mentees</span>
              <span className="block text-sm text-slate-500">Turn this off to pause new requests. Existing mentees aren&apos;t affected.</span>
            </span>
          </label>
        </section>
      )}

      <div className="flex justify-end border-t border-slate-100 pt-6">
        <SubmitButton pending={pending} pendingText="Saving…">
          {submitLabel}
        </SubmitButton>
      </div>
    </form>
  );
}
