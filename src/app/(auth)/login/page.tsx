import type { Metadata } from "next";
import Link from "next/link";
import { redirect } from "next/navigation";
import { getCurrentUser } from "@/lib/auth";
import { LoginForm } from "./login-form";

export const metadata: Metadata = { title: "Log in" };

export default async function LoginPage({ searchParams }: PageProps<"/login">) {
  if (await getCurrentUser()) redirect("/dashboard");
  const { next } = await searchParams;

  return (
    <>
      <h1 className="text-2xl font-semibold tracking-tight text-slate-900">Welcome back</h1>
      <p className="mt-2 text-sm text-slate-600">
        New to MentorConnect?{" "}
        <Link href="/signup" className="font-medium text-brand-700 hover:text-brand-800">
          Create an account
        </Link>
      </p>
      <div className="mt-8">
        <LoginForm next={typeof next === "string" ? next : undefined} />
      </div>
    </>
  );
}
