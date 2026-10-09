import type { Metadata } from "next";
import Link from "next/link";
import { LegalPage } from "@/components/legal-page";

export const metadata: Metadata = { title: "Terms of use" };

export default function TermsPage() {
  return (
    <LegalPage title="Terms of use" updated="9 October 2026">
      <p>
        By creating an account or using MentorConnect you agree to these terms. They&apos;re written to
        be short and readable. Please also read our <Link href="/privacy">privacy policy</Link>.
      </p>

      <section>
        <h2>About the service</h2>
        <p>
          MentorConnect is a portfolio project that helps mentees find mentors, request mentorship,
          book sessions, chat and leave reviews. It is provided as is, without guarantees of availability
          or fitness for a particular purpose, and features may change.
        </p>
      </section>

      <section>
        <h2>Your account</h2>
        <ul>
          <li>Give accurate information and keep your password private. You&apos;re responsible for activity on your account.</li>
          <li>One account per person. Don&apos;t impersonate anyone or misrepresent your experience.</li>
        </ul>
      </section>

      <section>
        <h2>Being a good member</h2>
        <ul>
          <li>Be respectful in messages and sessions. Harassment, hate speech and spam aren&apos;t allowed.</li>
          <li>Turn up to sessions you confirm, or cancel in good time.</li>
          <li>Write honest reviews based on sessions you actually attended.</li>
          <li>Don&apos;t try to break, overload or access parts of the platform you shouldn&apos;t.</li>
        </ul>
        <p className="mt-3">Accounts that break these rules may be suspended.</p>
      </section>

      <section>
        <h2>Mentorship and payments</h2>
        <p>
          Mentors are independent and share their own experience and opinions. MentorConnect doesn&apos;t
          verify credentials or guarantee outcomes such as job offers. Rates shown on profiles are set by
          mentors; MentorConnect doesn&apos;t process payments, so any payment is arranged directly
          between mentor and mentee.
        </p>
      </section>

      <section>
        <h2>Your content</h2>
        <p>
          You own what you post: your profile, messages and reviews. You allow MentorConnect to store and
          display it as needed to run the service, for example showing a review on a mentor&apos;s profile.
        </p>
      </section>

      <section>
        <h2>Changes</h2>
        <p>We may update these terms. The date at the top shows when they last changed.</p>
      </section>
    </LegalPage>
  );
}
