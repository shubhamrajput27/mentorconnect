"use client";

import { useId, useState, type KeyboardEvent } from "react";
import { X } from "lucide-react";

export function SkillsInput({
  name,
  defaultValue,
  suggestions,
  max = 15,
}: {
  name: string;
  defaultValue: string[];
  suggestions: string[];
  max?: number;
}) {
  const [skills, setSkills] = useState(defaultValue);
  const [draft, setDraft] = useState("");
  const listId = useId();

  const add = (raw: string) => {
    const value = raw.trim().replace(/\s+/g, " ");
    if (!value || skills.length >= max) return;
    if (skills.some((s) => s.toLowerCase() === value.toLowerCase())) return;
    setSkills([...skills, value]);
    setDraft("");
  };

  const onKeyDown = (e: KeyboardEvent<HTMLInputElement>) => {
    if (e.key === "Enter" || e.key === ",") {
      e.preventDefault();
      add(draft);
    } else if (e.key === "Backspace" && !draft && skills.length) {
      setSkills(skills.slice(0, -1));
    }
  };

  const unused = suggestions.filter((s) => !skills.some((k) => k.toLowerCase() === s.toLowerCase())).slice(0, 10);

  return (
    <div>
      <input type="hidden" name={name} value={JSON.stringify(skills)} />
      <div className="flex min-h-10 flex-wrap items-center gap-1.5 rounded-lg bg-white px-2 py-1.5 shadow-sm ring-1 ring-slate-300 ring-inset focus-within:ring-2 focus-within:ring-brand-500">
        {skills.map((s) => (
          <span key={s} className="inline-flex items-center gap-1 rounded-md bg-brand-50 py-0.5 pr-1 pl-2 text-sm text-brand-800">
            {s}
            <button
              type="button"
              onClick={() => setSkills(skills.filter((k) => k !== s))}
              className="rounded p-0.5 text-brand-500 hover:bg-brand-100 hover:text-brand-800"
              aria-label={`Remove ${s}`}
            >
              <X className="size-3" />
            </button>
          </span>
        ))}
        <input
          value={draft}
          onChange={(e) => setDraft(e.target.value)}
          onKeyDown={onKeyDown}
          onBlur={() => add(draft)}
          list={listId}
          placeholder={skills.length ? "Add another…" : "Type a skill and press Enter"}
          className="h-7 min-w-32 flex-1 bg-transparent px-1 text-sm placeholder:text-slate-400 focus:outline-none"
          aria-label="Add a skill"
        />
        <datalist id={listId}>
          {suggestions.map((s) => (
            <option key={s} value={s} />
          ))}
        </datalist>
      </div>
      {unused.length > 0 && skills.length < max && (
        <div className="mt-2 flex flex-wrap gap-1.5">
          {unused.map((s) => (
            <button
              key={s}
              type="button"
              onClick={() => add(s)}
              className="rounded-full px-2.5 py-0.5 text-xs text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50 hover:text-slate-900"
            >
              + {s}
            </button>
          ))}
        </div>
      )}
    </div>
  );
}
