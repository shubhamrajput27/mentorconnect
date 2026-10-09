"use client";

import { useMemo, useState } from "react";
import { CalendarX } from "lucide-react";
import { bookSession } from "@/actions/sessions";
import { SubmitButton, useFormAction } from "@/components/form";
import { Alert, Field, Input, Textarea } from "@/components/ui";
import { formatTime } from "@/lib/time";
import { cn } from "@/lib/utils";

export function BookingForm({ mentorId, slots, timeZone }: { mentorId: string; slots: string[]; timeZone: string }) {
  const { state, form, pending } = useFormAction(bookSession);

  // Group slots by calendar day in the viewer's timezone.
  const days = useMemo(() => {
    const keyFmt = new Intl.DateTimeFormat("en-CA", { timeZone, year: "numeric", month: "2-digit", day: "2-digit" });
    const labelFmt = new Intl.DateTimeFormat("en-IN", { timeZone, weekday: "short", day: "numeric", month: "short" });
    const map = new Map<string, { label: string; slots: string[] }>();
    for (const iso of slots) {
      const d = new Date(iso);
      const key = keyFmt.format(d);
      if (!map.has(key)) map.set(key, { label: labelFmt.format(d), slots: [] });
      map.get(key)!.slots.push(iso);
    }
    return [...map.entries()];
  }, [slots, timeZone]);

  const [dayKey, setDayKey] = useState(days[0]?.[0]);
  const [slot, setSlot] = useState<string>();
  const activeDay = days.find(([k]) => k === dayKey)?.[1];

  if (slots.length === 0) {
    return (
      <div className="rounded-xl bg-slate-50 p-4 text-center text-sm text-slate-500 ring-1 ring-slate-200">
        <CalendarX className="mx-auto mb-2 size-5 text-slate-400" />
        No open slots in the next two weeks. Send a message to find a time.
      </div>
    );
  }

  return (
    <form {...form} className="space-y-4">
      <input type="hidden" name="mentorId" value={mentorId} />
      <input type="hidden" name="startsAt" value={slot ?? ""} />
      <h3 className="text-sm font-semibold text-slate-900">Book a session</h3>
      {state.error && <Alert>{state.error}</Alert>}

      <div className="-mx-1 flex gap-2 overflow-x-auto px-1 pb-1">
        {days.map(([key, day]) => (
          <button
            key={key}
            type="button"
            onClick={() => {
              setDayKey(key);
              setSlot(undefined);
            }}
            className={cn(
              "shrink-0 rounded-lg px-3 py-2 text-xs font-medium ring-1",
              key === dayKey ? "bg-brand-600 text-white ring-brand-600" : "text-slate-700 ring-slate-200 hover:bg-slate-50",
            )}
          >
            {day.label}
          </button>
        ))}
      </div>

      <div className="grid grid-cols-3 gap-2">
        {activeDay?.slots.map((iso) => (
          <button
            key={iso}
            type="button"
            onClick={() => setSlot(iso)}
            className={cn(
              "rounded-lg py-2 text-sm ring-1",
              slot === iso ? "bg-brand-50 font-medium text-brand-700 ring-2 ring-brand-500" : "text-slate-700 ring-slate-200 hover:bg-slate-50",
            )}
          >
            {formatTime(new Date(iso), timeZone)}
          </button>
        ))}
      </div>
      {state.fieldErrors?.startsAt && <p className="text-sm text-red-600">{state.fieldErrors.startsAt}</p>}
      <p className="text-xs text-slate-500">Times shown in your timezone ({timeZone}).</p>

      <Field label="Topic" htmlFor="topic" error={state.fieldErrors?.topic}>
        <Input id="topic" name="topic" placeholder="e.g. Resume review" required />
      </Field>
      <Field label="Agenda (optional)" htmlFor="agenda" error={state.fieldErrors?.agenda}>
        <Textarea id="agenda" name="agenda" rows={3} placeholder="What would you like to cover?" />
      </Field>
      <SubmitButton pending={pending} className="w-full" disabled={!slot} pendingText="Booking…">
        Request this time
      </SubmitButton>
    </form>
  );
}
