import Link from "next/link";
import { cn } from "@/lib/utils";

export function Logo({ className, href = "/" }: { className?: string; href?: string }) {
  return (
    <Link href={href} className={cn("inline-flex items-center gap-2 font-semibold tracking-tight text-slate-900", className)}>
      <span className="flex size-8 items-center justify-center rounded-lg bg-brand-700 text-white shadow-sm">
        <svg viewBox="0 0 24 24" className="size-4.5" fill="none" stroke="currentColor" strokeWidth="2.25" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
          <circle cx="7" cy="8" r="3" />
          <circle cx="17" cy="16" r="3" />
          <path d="M9.5 10.5 14.5 13.5" />
        </svg>
      </span>
      <span>MentorConnect</span>
    </Link>
  );
}
