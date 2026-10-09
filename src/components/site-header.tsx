import Link from "next/link";
import { Logo } from "@/components/logo";
import { ButtonLink } from "@/components/ui";
import { getCurrentUser } from "@/lib/auth";

export async function SiteHeader() {
  const user = await getCurrentUser();
  return (
    <header className="sticky top-0 z-30 border-b border-slate-200/70 bg-white/80 backdrop-blur">
      <div className="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
        <Logo />
        <nav className="hidden items-center gap-8 text-sm font-medium text-slate-600 md:flex">
          <Link href="/mentors" className="hover:text-slate-900">Find mentors</Link>
          <Link href="/#how-it-works" className="hover:text-slate-900">How it works</Link>
          <Link href="/signup?role=MENTOR" className="hover:text-slate-900">Become a mentor</Link>
        </nav>
        <div className="flex items-center gap-2">
          {user ? (
            <ButtonLink href="/dashboard">Go to dashboard</ButtonLink>
          ) : (
            <>
              <ButtonLink href="/login" variant="ghost">Log in</ButtonLink>
              <ButtonLink href="/signup">Get started</ButtonLink>
            </>
          )}
        </div>
      </div>
    </header>
  );
}

export function SiteFooter() {
  return (
    <footer className="border-t border-slate-200 bg-white">
      <div className="mx-auto flex max-w-6xl flex-col gap-6 px-4 py-10 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div>
          <Logo />
          <p className="mt-2 text-sm text-slate-500">Your bridge to expert mentorship.</p>
        </div>
        <nav className="flex flex-wrap gap-6 text-sm text-slate-600">
          <Link href="/mentors" className="hover:text-slate-900">Mentors</Link>
          <Link href="/signup?role=MENTOR" className="hover:text-slate-900">Become a mentor</Link>
          <Link href="/login" className="hover:text-slate-900">Log in</Link>
        </nav>
        <p className="text-sm text-slate-400">© {new Date().getFullYear()} MentorConnect</p>
      </div>
    </footer>
  );
}
