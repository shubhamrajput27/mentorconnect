import type { Metadata } from "next";
import { Alert, Card, PageHeader } from "@/components/ui";
import { requireUser } from "@/lib/auth";
import { db } from "@/lib/db";
import { AvailabilityEditor } from "./availability-editor";

export const metadata: Metadata = { title: "Availability" };

export default async function AvailabilityPage({ searchParams }: PageProps<"/settings/availability">) {
  const user = await requireUser(["MENTOR"]);
  const { welcome } = await searchParams;
  const windows = await db.availability.findMany({
    where: { mentorId: user.id },
    orderBy: [{ dayOfWeek: "asc" }, { startMinute: "asc" }],
    select: { dayOfWeek: true, startMinute: true, endMinute: true },
  });

  return (
    <>
      <PageHeader
        title="Availability"
        description={`Set the weekly hours when mentees can book ${user.mentorProfile?.sessionMinutes ?? 60}-minute sessions with you.`}
      />
      {welcome && (
        <div className="mb-6 max-w-3xl">
          <Alert tone="info">
            Your profile is live! Last step: add a few time blocks so mentees can book sessions with you.
          </Alert>
        </div>
      )}
      <Card className="max-w-3xl p-6">
        <AvailabilityEditor initial={windows} timezone={user.timezone} />
      </Card>
    </>
  );
}
