import type { Metadata } from "next";
import { redirect } from "next/navigation";
import { Logo } from "@/components/logo";
import { ProfileForm } from "@/components/profile-form";
import { Card } from "@/components/ui";
import { getCurrentUser } from "@/lib/auth";
import { loadProfileForm } from "@/lib/profile-values";

export const metadata: Metadata = { title: "Set up your profile" };

export default async function OnboardingPage() {
  const user = await getCurrentUser();
  if (!user) redirect("/login");
  if (user.onboarded) redirect("/dashboard");

  const { values, suggestions } = await loadProfileForm(user);
  const isMentor = user.role === "MENTOR";

  return (
    <div className="min-h-screen bg-slate-50 px-4 py-10">
      <div className="mx-auto max-w-2xl">
        <Logo />
        <div className="mt-10">
          <p className="text-sm font-medium text-brand-600">Step 1 of {isMentor ? 2 : 1}</p>
          <h1 className="mt-1 text-3xl font-semibold tracking-tight text-slate-900">
            Welcome, {user.name.split(" ")[0]}! Let&apos;s set up your profile.
          </h1>
          <p className="mt-2 text-slate-600">
            {isMentor
              ? "Mentees choose mentors based on this, so be specific about what you can help with."
              : "This helps mentors understand where you are and where you want to go."}
          </p>
        </div>
        <Card className="mt-8 p-6 sm:p-8">
          <ProfileForm values={values} suggestions={suggestions} submitLabel={isMentor ? "Continue to availability" : "Find my mentor"} />
        </Card>
      </div>
    </div>
  );
}
