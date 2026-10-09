"use client";

import { Button } from "@/components/ui";

export default function ErrorPage({ error, reset }: { error: Error & { digest?: string }; reset: () => void }) {
  return (
    <div className="flex min-h-[60vh] flex-col items-center justify-center px-6 text-center">
      <h1 className="text-2xl font-semibold tracking-tight text-slate-900">Something went wrong</h1>
      <p className="mt-3 max-w-md text-slate-600">
        An unexpected error occurred. Please try again. If it keeps happening, contact support
        {error.digest ? ` and mention reference ${error.digest}` : ""}.
      </p>
      <Button className="mt-8" onClick={reset}>Try again</Button>
    </div>
  );
}
