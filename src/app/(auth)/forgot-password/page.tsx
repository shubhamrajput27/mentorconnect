import type { Metadata } from "next";
import Link from "next/link";
import { ForgotPasswordForm } from "./forgot-password-form";

export const metadata: Metadata = { title: "Forgot password" };

export default function ForgotPasswordPage() {
  return (
    <>
      <h1 className="text-2xl font-semibold tracking-tight text-slate-900">Forgot your password?</h1>
      <p className="mt-2 text-sm text-slate-600">Enter your email and we&apos;ll send you a link to choose a new one.</p>
      <div className="mt-8">
        <ForgotPasswordForm />
      </div>
      <p className="mt-6 text-center text-sm text-slate-600">
        Remembered it?{" "}
        <Link href="/login" className="font-medium text-brand-700 hover:text-brand-800">
          Back to log in
        </Link>
      </p>
    </>
  );
}
