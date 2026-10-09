import type { Metadata } from "next";
import Link from "next/link";
import { Search, SearchX } from "lucide-react";
import { MentorCard } from "@/components/mentor-card";
import { Alert, Button, EmptyState, Input, PageHeader, Select } from "@/components/ui";
import { popularSkills, searchMentors, type MentorSort } from "@/lib/mentors";

export const metadata: Metadata = {
  title: "Find a mentor",
  description: "Browse experienced mentors by skill, experience and price.",
};

const SORTS: { value: MentorSort; label: string }[] = [
  { value: "rating", label: "Top rated" },
  { value: "experience", label: "Most experienced" },
  { value: "price-low", label: "Price: low to high" },
  { value: "newest", label: "Newest" },
];

const PRICES = [
  { value: "", label: "Any price" },
  { value: "0", label: "Free only" },
  { value: "1000", label: "Up to ₹1,000/hr" },
  { value: "2500", label: "Up to ₹2,500/hr" },
];

export default async function MentorsPage({ searchParams }: PageProps<"/mentors">) {
  const params = await searchParams;
  const str = (v: string | string[] | undefined) => (typeof v === "string" ? v : "");
  const q = str(params.q);
  const skill = str(params.skill);
  const price = str(params.price);
  const sort = (SORTS.some((s) => s.value === params.sort) ? params.sort : "rating") as MentorSort;

  const [mentors, skills] = await Promise.all([
    searchMentors({ q, skill: skill || undefined, maxRate: price ? Number(price) : undefined, sort }),
    popularSkills(30),
  ]);
  const hasFilters = Boolean(q || skill || price);

  return (
    <>
      <PageHeader title="Find a mentor" description="Learn from people who have already walked the path you're on." />

      {params.welcome && (
        <div className="mb-6">
          <Alert tone="info">You&apos;re all set! Browse mentors below and send a request to the ones who fit your goals.</Alert>
        </div>
      )}

      <form className="mb-8 grid gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 md:grid-cols-[1fr_auto_auto_auto_auto]">
        <div className="relative">
          <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
          <Input name="q" defaultValue={q} placeholder="Search by name, skill, company…" className="pl-9" aria-label="Search" />
        </div>
        <Select name="skill" defaultValue={skill} aria-label="Skill">
          <option value="">All skills</option>
          {skills.map((s) => (
            <option key={s.id} value={s.slug}>{s.name}</option>
          ))}
        </Select>
        <Select name="price" defaultValue={price} aria-label="Price">
          {PRICES.map((p) => (
            <option key={p.value} value={p.value}>{p.label}</option>
          ))}
        </Select>
        <Select name="sort" defaultValue={sort} aria-label="Sort by">
          {SORTS.map((s) => (
            <option key={s.value} value={s.value}>{s.label}</option>
          ))}
        </Select>
        <Button type="submit">Search</Button>
      </form>

      <div className="mb-4 flex items-center justify-between text-sm text-slate-500">
        <span>
          {mentors.length} mentor{mentors.length === 1 ? "" : "s"} found
        </span>
        {hasFilters && (
          <Link href="/mentors" className="font-medium text-brand-700 hover:text-brand-800">
            Clear filters
          </Link>
        )}
      </div>

      {mentors.length ? (
        <div className="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
          {mentors.map((m) => (
            <MentorCard key={m.id} mentor={m} />
          ))}
        </div>
      ) : (
        <div className="rounded-2xl bg-white ring-1 ring-slate-200">
          <EmptyState
            icon={<SearchX className="size-6" />}
            title="No mentors match your search"
            description="Try a broader keyword or remove a filter."
          />
        </div>
      )}
    </>
  );
}
