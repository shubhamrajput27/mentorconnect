import type { Metadata } from "next";
import Link from "next/link";
import { LegalPage } from "@/components/legal-page";

export const metadata: Metadata = { title: "Privacy policy" };

const REPO_ISSUES = "https://github.com/shubhamrajput27/mentorconnect/issues";

export default function PrivacyPage() {
  return (
    <LegalPage title="Privacy policy" updated="9 October 2026">
      <p>
        This page explains what MentorConnect stores about you, why, and who else is involved in
        running the service. We collect only what the platform needs to connect mentors and mentees.
      </p>

      <section>
        <h2>What we store</h2>
        <ul>
          <li><strong>Account details:</strong> your name, email address, role (mentor or mentee) and a securely hashed password. We never store your password itself.</li>
          <li><strong>Profile:</strong> headline, bio, location, timezone, goals, skills and an optional profile photo. Mentors also add their job title, company, experience, rate, languages and LinkedIn link.</li>
          <li><strong>Mentorship activity:</strong> connection requests, weekly availability, session bookings, chat messages, reviews and notifications.</li>
        </ul>
      </section>

      <section>
        <h2>Who can see it</h2>
        <ul>
          <li>Mentor profiles, photos and reviews are public so mentees can choose a mentor.</li>
          <li>A mentee&apos;s profile and goals are shown to mentors they send a request to.</li>
          <li>Messages and session details are visible only to the two people involved.</li>
          <li>Platform administrators can see account details to keep the service safe, for example to suspend abusive accounts.</li>
        </ul>
      </section>

      <section>
        <h2>Cookies</h2>
        <p>
          We use a single essential cookie to keep you signed in. It contains a random token (not your
          details), cannot be read by scripts on the page, and expires after 30 days or when you log out.
          We don&apos;t use advertising or analytics cookies.
        </p>
      </section>

      <section>
        <h2>Services we rely on</h2>
        <ul>
          <li><strong>Vercel</strong> hosts the website.</li>
          <li><strong>Prisma Postgres</strong> stores the database, in Singapore.</li>
          <li><strong>Resend</strong> delivers account emails such as verification and password reset.</li>
          <li><strong>Jitsi Meet</strong> runs the video calls linked from confirmed sessions. Calls happen on Jitsi, not on MentorConnect.</li>
        </ul>
        <p className="mt-3">Your IP address is used briefly to limit repeated login and sign-up attempts. It is not stored.</p>
      </section>

      <section>
        <h2>What we don&apos;t do</h2>
        <p>We don&apos;t sell your data, show ads, or share your information with anyone except the services listed above.</p>
      </section>

      <section>
        <h2>Your choices</h2>
        <p>
          You can edit or remove your profile details and photo at any time in{" "}
          <Link href="/settings/profile">Profile settings</Link>. To delete your account entirely, or for
          any privacy question, contact the site owner through the{" "}
          <a href={REPO_ISSUES} target="_blank" rel="noopener noreferrer">project&apos;s GitHub page</a>.
        </p>
      </section>
    </LegalPage>
  );
}
