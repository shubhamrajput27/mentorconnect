import { CheckCircle2 } from "lucide-react";
import { Logo } from "@/components/logo";
import Aurora from "@/components/reactbits/Aurora";

export default function AuthLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="grid min-h-screen lg:grid-cols-2">
      <div className="flex flex-col px-6 py-8 sm:px-12">
        <Logo />
        <div className="flex flex-1 items-center justify-center py-12">
          <div className="w-full max-w-sm">{children}</div>
        </div>
      </div>
      <div className="relative isolate hidden overflow-hidden bg-brand-950 lg:flex lg:items-center lg:justify-center">
        <div aria-hidden className="pointer-events-none absolute inset-0 -z-10 opacity-90">
          <Aurora colorStops={["#18337c", "#3864de", "#1a3d9c"]} amplitude={1.1} blend={0.6} speed={0.5} />
        </div>
        <div className="relative max-w-md px-10 text-white">
          <h2 className="text-3xl font-semibold tracking-tight">The shortest path to your next level is someone who&apos;s been there.</h2>
          <ul className="mt-10 space-y-4 text-brand-100">
            {[
              "Mentors from top product companies",
              "Timezone-aware booking and video links",
              "Chat, reviews and progress in one place",
            ].map((item) => (
              <li key={item} className="flex items-center gap-3">
                <CheckCircle2 className="size-5 text-brand-200" /> {item}
              </li>
            ))}
          </ul>
        </div>
      </div>
    </div>
  );
}
