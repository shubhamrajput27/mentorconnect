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
import Aurora from "@/components/reactbits/Aurora";
import BlurText from "@/components/reactbits/BlurText";
import CountUp from "@/components/reactbits/CountUp";
import LogoLoop from "@/components/reactbits/LogoLoop";
import ShinyText from "@/components/reactbits/ShinyText";
import SpotlightCard from "@/components/reactbits/SpotlightCard";
import { Reveal } from "@/components/reveal";
import { Avatar, ButtonLink, Stars } from "@/components/ui";
import { db } from "@/lib/db";
import { popularSkills, searchMentors } from "@/lib/mentors";

const STEPS = [
  { icon: Search, title: "Find your mentor", text: "Filter by skill, experience and price. Read honest reviews from other mentees." },
  { icon: UserPlus, title: "Send a request", text: "Tell them your goals. Mentors accept people they can genuinely help." },
  { icon: CalendarCheck, title: "Book a session", text: "Pick a time from their live calendar. Video links are created automatically." },
  { icon: TrendingUp, title: "Grow & review", text: "Chat between sessions, track progress, and leave a review afterwards." },
];

const FEATURES = [
  { icon: CalendarCheck, title: "Smart scheduling", text: "Timezone-aware availability with no double bookings." },
  { icon: MessageSquare, title: "Built-in chat", text: "Message your mentor between sessions." },
  { icon: Star, title: "Verified reviews", text: "Only mentees who attended a session can review it." },
  { icon: ShieldCheck, title: "Safe & private", text: "Verified emails, hashed passwords. You control who you connect with." },
];

export default async function HomePage() {
  const [mentors, skills, mentorCount, sessionCount, reviews] = await Promise.all([
    searchMentors({ sort: "rating" }),
    popularSkills(16),
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
  const rated = mentors.filter((m) => m.rating);
  const avgRating = rated.length ? Math.round((rated.reduce((s, m) => s + m.rating!, 0) / rated.length) * 10) / 10 : null;

  return (
    <>
      {/* Hero */}
      <section className="relative isolate overflow-hidden">
        <div
          aria-hidden
          className="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[34rem] opacity-60 [mask-image:linear-gradient(to_bottom,black_30%,transparent)]"
        >
          <Aurora colorStops={["#c2d4fc", "#5f88ee", "#94b3f7"]} amplitude={0.8} blend={0.6} speed={0.6} lightMode />
        </div>

        <div className="mx-auto max-w-6xl px-4 pt-20 pb-16 text-center sm:px-6 sm:pt-28">
          <span className="inline-flex items-center gap-2 rounded-full bg-white/80 px-3 py-1 text-sm font-medium ring-1 ring-brand-100 backdrop-blur">
            <Sparkles className="size-4 text-brand-600" />
            <ShinyText text="1:1 mentorship from people who've done it" color="#1a3d9c" shineColor="#94b3f7" speed={3} />
          </span>

          <BlurText
            as="h1"
            text="Grow faster with a mentor in your corner"
            delay={90}
            animateBy="words"
            direction="top"
            className="mx-auto mt-6 max-w-3xl justify-center text-4xl font-semibold tracking-tight text-slate-900 sm:text-6xl"
          />

          <Reveal delay={0.4}>
            <p className="mx-auto mt-6 max-w-2xl text-lg text-slate-600">
              Find experienced engineers, designers and leaders. Get honest feedback, a clear plan, and
              someone to keep you accountable.
            </p>

            <form
              action="/mentors"
              className="mx-auto mt-10 flex max-w-xl items-center gap-2 rounded-2xl bg-white p-2 shadow-xl shadow-brand-900/5 ring-1 ring-slate-200"
            >
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
          </Reveal>

          {skills.length > 0 && (
            <Reveal delay={0.55} className="mx-auto mt-8 max-w-3xl">
              <LogoLoop
                logos={skills.map((s) => ({
                  title: s.name,
                  node: (
                    <Link
                      href={`/mentors?skill=${s.slug}`}
                      className="rounded-full bg-white px-3.5 py-1.5 text-sm whitespace-nowrap text-slate-600 ring-1 ring-slate-200 hover:text-brand-700 hover:ring-brand-200"
                    >
                      {s.name}
                    </Link>
                  ),
                }))}
                speed={40}
                gap={10}
                logoHeight={34}
                pauseOnHover
                fadeOut
                fadeOutColor="#ffffff"
                ariaLabel="Popular skills"
              />
            </Reveal>
          )}

          {mentorCount > 0 && (
            <Reveal delay={0.7}>
              <dl className="mx-auto mt-14 grid max-w-3xl grid-cols-3 gap-6 border-t border-slate-200 pt-10">
                <div>
                  <dt className="text-sm text-slate-500">Active mentors</dt>
                  <dd className="mt-1 text-3xl font-semibold tracking-tight text-slate-900">
                    <CountUp to={mentorCount} duration={1.5} />
                  </dd>
                </div>
                <div>
                  <dt className="text-sm text-slate-500">Sessions completed</dt>
                  <dd className="mt-1 text-3xl font-semibold tracking-tight text-slate-900">
                    <CountUp to={sessionCount} duration={1.5} />
                  </dd>
                </div>
                <div>
                  <dt className="text-sm text-slate-500">Average rating</dt>
                  <dd className="mt-1 text-3xl font-semibold tracking-tight text-slate-900">
                    {avgRating ? (
                      <>
                        <CountUp to={avgRating} duration={1.5} />★
                      </>
                    ) : (
                      "—"
                    )}
                  </dd>
                </div>
              </dl>
            </Reveal>
          )}
        </div>
      </section>

      {/* Featured mentors */}
      {featured.length > 0 && (
        <section className="bg-slate-50 py-20">
          <div className="mx-auto max-w-6xl px-4 sm:px-6">
            <Reveal className="flex items-end justify-between gap-4">
              <div>
                <h2 className="text-3xl font-semibold tracking-tight text-slate-900">Top-rated mentors</h2>
                <p className="mt-2 text-slate-600">Hand-picked professionals ready to help you level up.</p>
              </div>
              <Link href="/mentors" className="hidden items-center gap-1 text-sm font-medium text-brand-700 hover:text-brand-800 sm:flex">
                Browse all <ArrowRight className="size-4" />
              </Link>
            </Reveal>
            <div className="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
              {featured.map((m, i) => (
                <Reveal key={m.id} delay={(i % 3) * 0.08} className="h-full">
                  <MentorCard mentor={m} />
                </Reveal>
              ))}
            </div>
          </div>
        </section>
      )}

      {/* How it works */}
      <section id="how-it-works" className="scroll-mt-20 py-20">
        <div className="mx-auto max-w-6xl px-4 sm:px-6">
          <Reveal className="mx-auto max-w-2xl text-center">
            <h2 className="text-3xl font-semibold tracking-tight text-slate-900">How MentorConnect works</h2>
            <p className="mt-3 text-slate-600">From first search to your first breakthrough in four steps.</p>
          </Reveal>
          <div className="mt-14 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
            {STEPS.map((step, i) => (
              <Reveal key={step.title} delay={i * 0.1} className="h-full">
                <div className="relative h-full rounded-2xl bg-white p-6 ring-1 ring-slate-200 transition hover:-translate-y-1 hover:shadow-lg hover:shadow-brand-900/5">
                  <span className="absolute top-6 right-6 text-sm font-semibold text-slate-300">0{i + 1}</span>
                  <span className="flex size-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                    <step.icon className="size-5" />
                  </span>
                  <h3 className="mt-5 font-semibold text-slate-900">{step.title}</h3>
                  <p className="mt-2 text-sm text-slate-600">{step.text}</p>
                </div>
              </Reveal>
            ))}
          </div>
        </div>
      </section>

      {/* Features */}
      <section className="bg-brand-950 py-20 text-white">
        <div className="mx-auto grid max-w-6xl gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:items-center">
          <Reveal>
            <h2 className="text-3xl font-semibold tracking-tight">Everything a mentorship needs, in one place</h2>
            <p className="mt-4 text-brand-100">
              No more juggling email threads, calendar links and spreadsheets. MentorConnect keeps
              conversations, sessions and feedback together.
            </p>
            <ButtonLink href="/signup" size="lg" className="mt-8 bg-white text-brand-900 hover:bg-brand-50">
              Create your free account <ArrowRight className="size-4" />
            </ButtonLink>
          </Reveal>
          <div className="grid gap-4 sm:grid-cols-2">
            {FEATURES.map((f, i) => (
              <Reveal key={f.title} delay={i * 0.08} className="h-full">
                <SpotlightCard
                  theme="dark"
                  spotlightColor="#94b3f7"
                  intensity={0.35}
                  spotlightSize={220}
                  className="h-full"
                  style={{ "--spotlight-card-surface": "#122554" } as React.CSSProperties}
                >
                  <f.icon className="size-5 text-brand-300" />
                  <h3 className="mt-3 font-medium">{f.title}</h3>
                  <p className="mt-1 text-sm text-brand-200/80">{f.text}</p>
                </SpotlightCard>
              </Reveal>
            ))}
          </div>
        </div>
      </section>

      {/* Reviews */}
      {reviews.length > 0 && (
        <section className="py-20">
          <div className="mx-auto max-w-6xl px-4 sm:px-6">
            <Reveal>
              <h2 className="text-center text-3xl font-semibold tracking-tight text-slate-900">What mentees say</h2>
            </Reveal>
            <div className="mt-12 grid gap-6 md:grid-cols-3">
              {reviews.map((r, i) => (
                <Reveal key={r.id} delay={i * 0.1} className="h-full">
                  <figure className="flex h-full flex-col rounded-2xl bg-slate-50 p-6 ring-1 ring-slate-200">
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
                </Reveal>
              ))}
            </div>
          </div>
        </section>
      )}

      {/* CTA */}
      <section className="px-4 pb-20 sm:px-6">
        <Reveal>
          <div className="relative isolate mx-auto max-w-6xl overflow-hidden rounded-3xl bg-brand-950 px-6 py-16 text-center text-white sm:px-16">
            <div aria-hidden className="pointer-events-none absolute inset-0 -z-10 opacity-80">
              <Aurora colorStops={["#1a3d9c", "#5f88ee", "#18337c"]} amplitude={1} blend={0.7} speed={0.5} />
            </div>
            <h2 className="text-3xl font-semibold tracking-tight">Have experience to share?</h2>
            <p className="mx-auto mt-3 max-w-xl text-brand-100">
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
        </Reveal>
      </section>
    </>
  );
}
