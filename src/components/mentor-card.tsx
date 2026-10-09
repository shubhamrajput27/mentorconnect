import Link from "next/link";
import { Briefcase, Star } from "lucide-react";
import { Avatar, Badge, Card } from "@/components/ui";
import type { MentorCardData } from "@/lib/mentors";
import { formatRate } from "@/lib/utils";

export function MentorCard({ mentor }: { mentor: MentorCardData }) {
  const profile = mentor.mentorProfile;
  const skills = mentor.skills.map((s) => s.skill);
  return (
    <Link href={`/mentors/${mentor.id}`} className="group block h-full min-w-0">
      <Card className="flex h-full flex-col p-5 transition-shadow group-hover:shadow-md group-hover:ring-brand-200">
        <div className="flex items-start gap-4">
          <Avatar user={mentor} size="lg" />
          <div className="min-w-0 flex-1">
            <h3 className="truncate font-semibold text-slate-900 group-hover:text-brand-700">{mentor.name}</h3>
            {(profile?.jobTitle || profile?.company) && (
              <p className="mt-0.5 flex items-center gap-1.5 truncate text-sm text-slate-600">
                <Briefcase className="size-3.5 shrink-0 text-slate-400" />
                {[profile.jobTitle, profile.company].filter(Boolean).join(" at ")}
              </p>
            )}
            <div className="mt-1.5 flex items-center gap-1.5 text-sm">
              {mentor.rating ? (
                <>
                  <Star className="size-4 fill-amber-400 text-amber-400" />
                  <span className="font-medium text-slate-900">{mentor.rating.toFixed(1)}</span>
                  <span className="text-slate-500">({mentor.reviewCount})</span>
                </>
              ) : (
                <Badge tone="brand">New mentor</Badge>
              )}
            </div>
          </div>
        </div>
        {mentor.headline && <p className="mt-4 line-clamp-2 text-sm text-slate-600">{mentor.headline}</p>}
        <div className="mt-4 flex flex-wrap gap-1.5">
          {skills.slice(0, 4).map((s) => (
            <Badge key={s.id}>{s.name}</Badge>
          ))}
          {skills.length > 4 && <Badge>+{skills.length - 4}</Badge>}
        </div>
        <div className="mt-auto pt-5">
          <div className="flex items-center justify-between border-t border-slate-100 pt-4 text-sm">
            <span className="text-slate-500">{profile?.yearsExperience ?? 0}+ yrs experience</span>
            <span className="font-semibold text-slate-900">{formatRate(profile?.hourlyRate ?? 0)}</span>
          </div>
        </div>
      </Card>
    </Link>
  );
}
