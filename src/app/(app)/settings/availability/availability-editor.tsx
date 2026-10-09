"use client";

import { useState } from "react";
import { Plus, Trash2 } from "lucide-react";
import { saveAvailability } from "@/actions/profile";
import { SubmitButton, useFormAction } from "@/components/form";
import { Alert, Button, Select } from "@/components/ui";
import { DAYS, minutesToLabel, type AvailabilityWindow } from "@/lib/time";

const TIME_OPTIONS = Array.from({ length: 49 }, (_, i) => i * 30); // 00:00 … 24:00

export function AvailabilityEditor({ initial, timezone }: { initial: AvailabilityWindow[]; timezone: string }) {
  const [windows, setWindows] = useState(initial);
  const { state, form, pending } = useFormAction(saveAvailability);

  const update = (index: number, patch: Partial<AvailabilityWindow>) =>
    setWindows(windows.map((w, i) => (i === index ? { ...w, ...patch } : w)));

  const addBlock = (day: number) => {
    const dayBlocks = windows.filter((w) => w.dayOfWeek === day);
    const start = dayBlocks.length ? Math.min(Math.max(...dayBlocks.map((w) => w.endMinute)) + 60, 22 * 60) : 18 * 60;
    setWindows([...windows, { dayOfWeek: day, startMinute: start, endMinute: Math.min(start + 120, 24 * 60) }]);
  };

  return (
    <form {...form} className="space-y-6">
      <input type="hidden" name="windows" value={JSON.stringify(windows)} />
      {state.error && <Alert>{state.error}</Alert>}
      {state.success && <Alert tone="success">{state.success}</Alert>}

      <p className="text-sm text-slate-500">
        Times are in <span className="font-medium text-slate-700">{timezone}</span>. Mentees see them converted to their own timezone.
      </p>

      <div className="divide-y divide-slate-100 rounded-xl ring-1 ring-slate-200">
        {DAYS.map((day, dayIndex) => {
          const blocks = windows.map((w, i) => ({ w, i })).filter(({ w }) => w.dayOfWeek === dayIndex);
          return (
            <div key={day} className="flex flex-col gap-3 p-4 sm:flex-row sm:items-start">
              <div className="w-32 shrink-0 pt-2 text-sm font-medium text-slate-900">{day}</div>
              <div className="flex-1 space-y-2">
                {blocks.length === 0 && <p className="pt-2 text-sm text-slate-400">Unavailable</p>}
                {blocks.map(({ w, i }) => (
                  <div key={i} className="flex items-center gap-2">
                    <Select
                      aria-label={`${day} start time`}
                      value={w.startMinute}
                      onChange={(e) => update(i, { startMinute: Number(e.target.value) })}
                      className="w-32"
                    >
                      {TIME_OPTIONS.slice(0, -1).map((m) => (
                        <option key={m} value={m}>{minutesToLabel(m)}</option>
                      ))}
                    </Select>
                    <span className="text-slate-400">–</span>
                    <Select
                      aria-label={`${day} end time`}
                      value={w.endMinute}
                      onChange={(e) => update(i, { endMinute: Number(e.target.value) })}
                      className="w-32"
                    >
                      {TIME_OPTIONS.slice(1).map((m) => (
                        <option key={m} value={m}>{m === 1440 ? "Midnight" : minutesToLabel(m)}</option>
                      ))}
                    </Select>
                    <button
                      type="button"
                      onClick={() => setWindows(windows.filter((_, j) => j !== i))}
                      className="rounded-lg p-2 text-slate-400 hover:bg-red-50 hover:text-red-600"
                      aria-label="Remove time block"
                    >
                      <Trash2 className="size-4" />
                    </button>
                  </div>
                ))}
              </div>
              <Button type="button" variant="ghost" size="sm" onClick={() => addBlock(dayIndex)} className="self-start">
                <Plus className="size-4" /> Add time
              </Button>
            </div>
          );
        })}
      </div>

      <div className="flex justify-end">
        <SubmitButton pending={pending} pendingText="Saving…">Save availability</SubmitButton>
      </div>
    </form>
  );
}
