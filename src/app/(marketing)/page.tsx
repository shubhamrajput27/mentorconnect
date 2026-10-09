import Link from "next/link";
import {
  ArrowRight,
  CalendarCheck,
  MessageSquare,
  Search,
  ShieldCheck,
  Sparkles,
  Star,
  TrendingUp,
  UserPlus,
} from "lucide-react";
import { MentorCard } from "@/components/mentor-card";
import { Avatar, ButtonLink, Stars } from "@/components/ui";
import { db } from "@/lib/db";
import { popularSkills, searchMentors } from "@/lib/mentors";

export default async function HomePage() {
  const [mentors, skills, mentorCount, sessionCount, reviews] = await Promise.all([
    searchMentors({ sort: "rating" }),
    popularSkills(10),
    db.user.count({ where: { role: "MENTOR", status: "ACTIVE", onboarded: true } }),
    db.mentorSession.count({ where: { status: "COMPLETED" } }),
    db.review.findMany({
      where: { rating: { gte: 4 } },
      include: { author: true, mentor: true },
      orderBy: { createdAt: "desc" },
      take: 3,
    }),
  ]);
  const featured = mentors.slice(0, 6);
  const avgRating =
    mentors.filter((m) => m.rating).reduce((sum, m, _, arr) => sum + m.rating! / arr.length, 0) || null;

  return (
    <>
      {/* Hero */}
      <section className="relative overflow-hidden">
        <div aria-hidden className="pointer-events-none absolute inset-x-0 -top-40 -z-10 flex justify-center blur-3xl">
          <div className="aspect-[3/2] w-[60rem] bg-linear-to-tr from-brand-100 via-slate-100 to-brand-200 opacity-70 [clip-path:polygon(25%_0,100%_20%,85%_80%,10%_100%,0_40%)]" />
        </div>
        <div className="mx-auto max-w-6xl px-4 pt-20 pb-16 text-center sm:px-6 sm:pt-28">
          <span className="inline-flex items-center gap-2 rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-700 ring-1 ring-brand-100">
            <Sparkles className="size-4" /> 1:1 mentorship from people who&apos;ve done it
          </span>
          <h1 className="mx-auto mt-6 max-w-3xl text-4xl font-semibold tracking-tight text-slate-900 sm:text-6xl">
            Grow faster with a mentor <span className="text-brand-600">in your corner</span>
          </h1>
          <p className="mx-auto mt-6 max-w-2xl text-lg text-slate-600">
            Find experienced engineers, designers and leaders. Get honest feedback, a clear plan, and
            someone to keep you accountable.
          </p>

          <form action="/mentors" className="mx-auto mt-10 flex max-w-xl items-center gap-2 rounded-2xl bg-white p-2 shadow-lg ring-1 ring-slate-200">
            <Search className="ml-2 size-5 shrink-0 text-slate-400" />
            <input
              name="q"
              placeholder="Try “React”, “System design” or “Product management”"
              className="h-11 min-w-0 flex-1 bg-transparent text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none"
              aria-label="Search mentors"
            />
            <button type="submit" className="h-11 shrink-0 rounded-xl bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700">
              Search
            </button>
          </form>

          {skills.length > 0 && (
            <div className="mx-auto mt-6 flex max-w-2xl flex-wrap justify-center gap-2">
              {skills.map((s) => (
                <Link
                  key={s.id}
                  href={`/mentors?skill=${s.slug}`}
                  className="rounded-full bg-white px-3 py-1 text-sm text-slate-600 ring-1 ring-slate-200 hover:text-brand-700 hover:ring-brand-200"
                >
                  {s.name}
                </Link>
              ))}
            </div>
          )}

          <dl className="mx-auto mt-16 grid max-w-3xl grid-cols-3 gap-6 border-t border-slate-200 pt-10">
            {[
              { label: "Active mentors", value: mentorCount },
              { label: "Sessions completed", value: sessionCount },
              { label: "Average rating", value: avgRating ? `${avgRating.toFixed(1)}★` : "—" },
            ].map((stat) => (
              <div key={stat.label}>
                <dt className="text-sm text-slate-500">{stat.label}</dt>
                <dd className="mt-1 text-3xl font-semibold tracking-tight text-slate-900">{stat.value}</dd>
              </div>
            ))}
          </dl>
        </div>
      </section>

      {/* Featured mentors */}
      {featured.length > 0 && (
        <section className="bg-slate-50 py-20">
          <div className="mx-auto max-w-6xl px-4 sm:px-6">
            <div className="flex items-end justify-between gap-4">
              <div>
                <h2 className="text-3xl font-semibold tracking-tight text-slate-900">Top-rated mentors</h2>
                <p className="mt-2 text-slate-600">Hand-picked professionals ready to help you level up.</p>
              </div>
              <Link href="/mentors" className="hidden items-center gap-1 text-sm font-medium text-brand-700 hover:text-brand-800 sm:flex">
                Browse all <ArrowRight className="size-4" />
              </Link>
            </div>
            <div className="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
              {featured.map((m) => (
                <MentorCard key={m.id} mentor={m} />
              ))}
            </div>
          </div>
        </section>
      )}

      {/* How it works */}
      <section id="how-it-works" className="scroll-mt-20 py-20">
        <div className="mx-auto max-w-6xl px-4 sm:px-6">
          <div className="mx-auto max-w-2xl text-center">
            <h2 className="text-3xl font-semibold tracking-tight text-slate-900">How MentorConnect works</h2>
            <p className="mt-3 text-slate-600">From first search to your first breakthrough in four steps.</p>
          </div>
          <div className="mt-14 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
            {[
              { icon: Search, title: "Find your mentor", text: "Filter by skill, experience and price. Read honest reviews from other mentees." },
              { icon: UserPlus, title: "Send a request", text: "Tell them your goals. Mentors accept people they can genuinely help." },
              { icon: CalendarCheck, title: "Book a session", text: "Pick a time from their live calendar. Video links are created automatically." },
              { icon: TrendingUp, title: "Grow & review", text: "Chat between sessions, track progress, and leave a review afterwards." },
            ].map((step, i) => (
              <div key={step.title} className="relative rounded-2xl p-6 ring-1 ring-slate-200">
                <span className="absolute top-6 right-6 text-sm font-semibold text-slate-300">0{i + 1}</span>
                <span className="flex size-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                  <step.icon className="size-5" />
                </span>
                <h3 className="mt-5 font-semibold text-slate-900">{step.title}</h3>
                <p className="mt-2 text-sm text-slate-600">{step.text}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Features */}
      <section className="bg-brand-950 py-20 text-white">
        <div className="mx-auto grid max-w-6xl gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:items-center">
          <div>
            <h2 className="text-3xl font-semibold tracking-tight">Everything a mentorship needs, in one place</h2>
            <p className="mt-4 text-brand-100">
              No more juggling email threads, calendar links and spreadsheets. MentorConnect keeps
              conversations, sessions and feedback together.
            </p>
            <ButtonLink href="/signup" size="lg" className="mt-8 bg-white text-brand-900 hover:bg-brand-50">
              Create your free account <ArrowRight className="size-4" />
            </ButtonLink>
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            {[
              { icon: CalendarCheck, title: "Smart scheduling", text: "Timezone-aware availability with no double bookings." },
              { icon: MessageSquare, title: "Built-in chat", text: "Message your mentor between sessions." },
              { icon: Star, title: "Verified reviews", text: "Only mentees who attended a session can review it." },
              { icon: ShieldCheck, title: "Safe & private", text: "Secure sessions and hashed passwords. You control who you connect with." },
            ].map((f) => (
              <div key={f.title} className="rounded-2xl bg-white/5 p-5 ring-1 ring-white/10">
                <f.icon className="size-5 text-brand-300" />
                <h3 className="mt-3 font-medium">{f.title}</h3>
                <p className="mt-1 text-sm text-brand-200/80">{f.text}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Reviews */}
      {reviews.length > 0 && (
        <section className="py-20">
          <div className="mx-auto max-w-6xl px-4 sm:px-6">
            <h2 className="text-center text-3xl font-semibold tracking-tight text-slate-900">What mentees say</h2>
            <div className="mt-12 grid gap-6 md:grid-cols-3">
              {reviews.map((r) => (
                <figure key={r.id} className="flex flex-col rounded-2xl bg-slate-50 p-6 ring-1 ring-slate-200">
                  <Stars rating={r.rating} />
                  <blockquote className="mt-4 flex-1 text-slate-700">&ldquo;{r.comment}&rdquo;</blockquote>
                  <figcaption className="mt-6 flex items-center gap-3">
                    <Avatar user={r.author} size="sm" />
                    <div className="text-sm">
                      <p className="font-medium text-slate-900">{r.author.name}</p>
                      <p className="text-slate-500">mentored by {r.mentor.name}</p>
                    </div>
                  </figcaption>
                </figure>
              ))}
            </div>
          </div>
        </section>
      )}

      {/* CTA */}
      <section className="px-4 pb-20 sm:px-6">
        <div className="mx-auto max-w-6xl overflow-hidden rounded-3xl bg-brand-900 px-6 py-16 text-center text-white sm:px-16">
          <h2 className="text-3xl font-semibold tracking-tight">Have experience to share?</h2>
          <p className="mx-auto mt-3 max-w-xl text-brand-200">
            Mentoring sharpens your own skills and builds your reputation. Set your own hours and rate.
          </p>
          <div className="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
            <ButtonLink href="/signup?role=MENTOR" size="lg" className="bg-white text-brand-700 hover:bg-brand-50">
              Become a mentor
            </ButtonLink>
            <ButtonLink href="/mentors" size="lg" variant="ghost" className="text-white hover:bg-white/10">
              Find a mentor
            </ButtonLink>
          </div>
        </div>
      </section>
    </>
  );
}
