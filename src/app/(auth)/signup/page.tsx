import type { Metadata } from "next";
import Link from "next/link";
import { redirect } from "next/navigation";
import { getCurrentUser } from "@/lib/auth";
import { SignupForm } from "./signup-form";

export const metadata: Metadata = { title: "Create account" };

export default async function SignupPage({ searchParams }: PageProps<"/signup">) {
  if (await getCurrentUser()) redirect("/dashboard");
  const { role } = await searchParams;

  return (
    <>
      <h1 className="text-2xl font-semibold tracking-tight text-slate-900">Create your account</h1>
      <p className="mt-2 text-sm text-slate-600">
        Already have one?{" "}
        <Link href="/login" className="font-medium text-brand-700 hover:text-brand-800">
          Log in
        </Link>
      </p>
      <div className="mt-8">
        <SignupForm defaultRole={role === "MENTOR" ? "MENTOR" : "MENTEE"} />
      </div>
    </>
  );
}
