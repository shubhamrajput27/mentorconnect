import Link from "next/link";
import { ArrowRight } from "lucide-react";
import { Logo } from "@/components/logo";

const REPO_URL = "https://github.com/shubhamrajput27/mentorconnect";

const COLUMNS = [
  {
    title: "Platform",
    links: [
      { label: "Find a mentor", href: "/mentors" },
      { label: "How it works", href: "/#how-it-works" },
      { label: "Become a mentor", href: "/signup?role=MENTOR" },
    ],
  },
  {
    title: "Account",
    links: [
      { label: "Log in", href: "/login" },
      { label: "Create an account", href: "/signup" },
      { label: "Forgot password", href: "/forgot-password" },
    ],
  },
  {
    title: "Legal",
    links: [
      { label: "Privacy policy", href: "/privacy" },
      { label: "Terms of use", href: "/terms" },
    ],
  },
];

function GitHubIcon(props: React.SVGProps<SVGSVGElement>) {
  return (
    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden {...props}>
      <path d="M12 .5C5.73.5.75 5.48.75 11.75c0 4.97 3.22 9.18 7.69 10.67.56.1.77-.24.77-.54v-1.9c-3.13.68-3.79-1.51-3.79-1.51-.51-1.3-1.25-1.65-1.25-1.65-1.02-.7.08-.69.08-.69 1.13.08 1.72 1.16 1.72 1.16 1 1.72 2.63 1.22 3.27.93.1-.73.39-1.22.71-1.5-2.5-.28-5.13-1.25-5.13-5.57 0-1.23.44-2.24 1.16-3.03-.12-.28-.5-1.43.11-2.98 0 0 .95-.3 3.1 1.16a10.8 10.8 0 0 1 5.64 0c2.15-1.46 3.1-1.16 3.1-1.16.61 1.55.23 2.7.11 2.98.72.79 1.16 1.8 1.16 3.03 0 4.33-2.64 5.28-5.15 5.56.4.35.76 1.03.76 2.08v3.09c0 .3.2.65.78.54a11.26 11.26 0 0 0 7.68-10.67C23.25 5.48 18.27.5 12 .5Z" />
    </svg>
  );
}

export function SiteFooter() {
  return (
    <footer className="bg-brand-950 text-brand-100">
      <div className="mx-auto max-w-6xl px-4 pt-16 pb-10 sm:px-6">
        <div className="grid gap-12 lg:grid-cols-[1.4fr_2fr]">
          <div className="max-w-sm">
            <Logo className="text-white" />
            <p className="mt-4 text-sm leading-6 text-brand-200/80">
              MentorConnect pairs learners with experienced engineers, designers and leaders for
              focused 1:1 mentorship: requests, scheduling, chat and reviews in one place.
            </p>
            <Link
              href="/signup"
              className="mt-6 inline-flex items-center gap-1.5 rounded-lg bg-white px-4 py-2 text-sm font-medium text-brand-900 hover:bg-brand-50"
            >
              Get started for free <ArrowRight className="size-4" />
            </Link>
          </div>

          <nav aria-label="Footer" className="grid grid-cols-2 gap-8 sm:grid-cols-3">
            {COLUMNS.map((col) => (
              <div key={col.title}>
                <h2 className="text-sm font-semibold text-white">{col.title}</h2>
                <ul className="mt-4 space-y-3">
                  {col.links.map((l) => (
                    <li key={l.href}>
                      <Link href={l.href} className="text-sm text-brand-200/80 transition-colors hover:text-white">
                        {l.label}
                      </Link>
                    </li>
                  ))}
                </ul>
              </div>
            ))}
          </nav>
        </div>

        <div className="mt-14 flex flex-col-reverse gap-4 border-t border-white/10 pt-8 sm:flex-row sm:items-center sm:justify-between">
          <p className="text-xs text-brand-200/60">© {new Date().getFullYear()} MentorConnect. All rights reserved.</p>
          <a
            href={REPO_URL}
            target="_blank"
            rel="noopener noreferrer"
            className="inline-flex items-center gap-2 text-xs text-brand-200/70 transition-colors hover:text-white"
          >
            <GitHubIcon className="size-4" /> Source on GitHub
          </a>
        </div>
      </div>
    </footer>
  );
}
