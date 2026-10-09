import type { Metadata } from "next";
import Link from "next/link";
import { ExternalLink } from "lucide-react";
import { PhotoUpload } from "@/components/photo-upload";
import { ProfileForm } from "@/components/profile-form";
import { Card, CardHeader, PageHeader } from "@/components/ui";
import { requireUser } from "@/lib/auth";
import { loadProfileForm } from "@/lib/profile-values";

export const metadata: Metadata = { title: "Profile settings" };

export default async function ProfileSettingsPage() {
  const user = await requireUser();
  const { values, suggestions } = await loadProfileForm(user);

  return (
    <>
      <PageHeader
        title="Profile settings"
        description="Keep your profile up to date so the right people find you."
        action={
          user.role === "MENTOR" && (
            <Link href={`/mentors/${user.id}`} className="inline-flex items-center gap-1.5 text-sm font-medium text-brand-700 hover:text-brand-800">
              View public profile <ExternalLink className="size-4" />
            </Link>
          )
        }
      />
      <Card className="mb-6 max-w-3xl">
        <CardHeader title="Profile photo" />
        <div className="p-6 sm:px-8">
          <PhotoUpload user={user} />
        </div>
      </Card>
      <Card className="max-w-3xl p-6 sm:p-8">
        <ProfileForm values={values} suggestions={suggestions} submitLabel="Save changes" />
      </Card>
    </>
  );
}
