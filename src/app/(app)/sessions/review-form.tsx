"use client";

import { useState } from "react";
import { Star } from "lucide-react";
import { submitReview } from "@/actions/sessions";
import { SubmitButton, useFormAction } from "@/components/form";
import { Alert, Button, Textarea } from "@/components/ui";
import { cn } from "@/lib/utils";

export function ReviewForm({ sessionId, mentorName }: { sessionId: string; mentorName: string }) {
  const { state, form, pending } = useFormAction(submitReview);
  const [open, setOpen] = useState(false);
  const [rating, setRating] = useState(0);
  const [hover, setHover] = useState(0);

  if (state.success) return <p className="text-sm font-medium text-emerald-700">{state.success}</p>;
  if (!open) {
    return (
      <Button size="sm" onClick={() => setOpen(true)}>
        <Star className="size-4" /> Leave a review
      </Button>
    );
  }

  return (
    <form {...form} className="mt-3 w-full space-y-3 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200">
      <input type="hidden" name="sessionId" value={sessionId} />
      <input type="hidden" name="rating" value={rating} />
      {state.error && <Alert>{state.error}</Alert>}
      <p className="text-sm font-medium text-slate-900">How was your session with {mentorName.split(" ")[0]}?</p>
      <div className="flex gap-1" onMouseLeave={() => setHover(0)}>
        {[1, 2, 3, 4, 5].map((n) => (
          <button
            key={n}
            type="button"
            onClick={() => setRating(n)}
            onMouseEnter={() => setHover(n)}
            aria-label={`${n} star${n > 1 ? "s" : ""}`}
          >
            <Star className={cn("size-7 transition", n <= (hover || rating) ? "fill-amber-400 text-amber-400" : "fill-slate-200 text-slate-200")} />
          </button>
        ))}
      </div>
      {state.fieldErrors?.rating && <p className="text-sm text-red-600">{state.fieldErrors.rating}</p>}
      <Textarea name="comment" rows={3} placeholder="What did you learn? What stood out?" aria-label="Review" />
      {state.fieldErrors?.comment && <p className="text-sm text-red-600">{state.fieldErrors.comment}</p>}
      <div className="flex justify-end gap-2">
        <Button type="button" variant="ghost" size="sm" onClick={() => setOpen(false)}>Cancel</Button>
        <SubmitButton pending={pending} size="sm" pendingText="Submitting…">Submit review</SubmitButton>
      </div>
    </form>
  );
}
