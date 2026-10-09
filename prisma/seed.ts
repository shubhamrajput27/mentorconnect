// Demo data. Run with `npm run db:seed`. WARNING: deletes all existing data first.
// Every demo account uses the password "password123", except the admin when
// SEED_ADMIN_PASSWORD is set (always set it for a public site).
import "dotenv/config";
import bcrypt from "bcryptjs";
import { PrismaPg } from "@prisma/adapter-pg";
import { PrismaClient } from "../src/generated/prisma/client";
import { directDatabaseUrl } from "../src/lib/database-url";

const url = directDatabaseUrl();
if (!url) throw new Error("No postgres:// DATABASE_URL is set.");
// Refuse to wipe a remote database by accident.
const host = new URL(url).hostname;
if (!["localhost", "127.0.0.1", "::1"].includes(host) && process.env.SEED_ALLOW_REMOTE !== "1") {
  throw new Error(`Refusing to seed (and wipe) the database on ${host}. Set SEED_ALLOW_REMOTE=1 if you really mean it.`);
}
const db = new PrismaClient({ adapter: new PrismaPg({ connectionString: url }) });

const slug = (s: string) => s.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "");
const HOUR = 60 * 60 * 1000;
const DAY = 24 * HOUR;

/** A whole hour `days` from now, in UTC. */
function at(days: number, utcHour: number) {
  const d = new Date(Date.now() + days * DAY);
  d.setUTCHours(utcHour, 0, 0, 0);
  return d;
}

const mentors = [
  {
    name: "Priya Sharma",
    email: "priya@mentorconnect.dev",
    headline: "Staff engineer helping developers crack system design interviews",
    bio: "I've spent 11 years building distributed systems, most recently leading the payments platform at Finlytics. I've interviewed 300+ engineers and love helping people go from 'I don't know where to start' to confidently designing large-scale systems.\n\nI can help with system design, backend architecture, career growth to senior/staff, and interview prep.",
    location: "Bengaluru, India",
    jobTitle: "Staff Software Engineer",
    company: "Finlytics",
    years: 11,
    rate: 2000,
    skills: ["System Design", "Java", "Distributed Systems", "Interview Prep", "Career Growth"],
  },
  {
    name: "Rahul Verma",
    email: "rahul@mentorconnect.dev",
    headline: "Frontend lead • React, Next.js and design systems",
    bio: "Frontend lead at Kite Commerce, where I built our design system from scratch. I mentor developers moving from 'it works' to production-quality frontend: performance, accessibility, testing and architecture.",
    location: "Pune, India",
    jobTitle: "Frontend Lead",
    company: "Kite Commerce",
    years: 8,
    rate: 1200,
    skills: ["React", "Next.js", "TypeScript", "JavaScript", "CSS", "Accessibility"],
  },
  {
    name: "Ananya Iyer",
    email: "ananya@mentorconnect.dev",
    headline: "Data scientist turning curious students into ML practitioners",
    bio: "I work on recommendation systems at StreamBox. Before that I taught Python and statistics for 3 years. I focus on fundamentals first, then real projects you can put on your resume.",
    location: "Chennai, India",
    jobTitle: "Senior Data Scientist",
    company: "StreamBox",
    years: 7,
    rate: 1500,
    skills: ["Python", "Machine Learning", "Data Science", "SQL", "Statistics"],
  },
  {
    name: "Karan Mehta",
    email: "karan@mentorconnect.dev",
    headline: "Product manager • from engineer to PM, and how to get there",
    bio: "I switched from backend engineering to product management 6 years ago and now lead a team of PMs at Nimbus Labs. Happy to help with PM interviews, product sense, writing PRDs, and making the career switch.",
    location: "Gurugram, India",
    jobTitle: "Group Product Manager",
    company: "Nimbus Labs",
    years: 10,
    rate: 2500,
    skills: ["Product Management", "Career Growth", "Interview Prep", "Communication"],
  },
  {
    name: "Sneha Reddy",
    email: "sneha@mentorconnect.dev",
    headline: "Product designer who loves portfolio reviews",
    bio: "Senior product designer at Orbit Health. I review portfolios, run mock design challenges and help designers explain their decisions with confidence.",
    location: "Hyderabad, India",
    jobTitle: "Senior Product Designer",
    company: "Orbit Health",
    years: 6,
    rate: 0,
    skills: ["UI/UX Design", "Figma", "Portfolio Review", "Accessibility"],
  },
  {
    name: "Arjun Nair",
    email: "arjun@mentorconnect.dev",
    headline: "Cloud & DevOps engineer • AWS, Kubernetes, CI/CD",
    bio: "Platform engineer at CloudForge. I help developers understand cloud infrastructure from the ground up: Docker, Kubernetes, Terraform and setting up CI/CD pipelines that don't break on Fridays.",
    location: "Kochi, India",
    jobTitle: "Senior Platform Engineer",
    company: "CloudForge",
    years: 9,
    rate: 1800,
    skills: ["AWS", "DevOps", "Docker", "Kubernetes", "Terraform"],
  },
  {
    name: "Meera Kapoor",
    email: "meera@mentorconnect.dev",
    headline: "Android & Kotlin engineer, open-source maintainer",
    bio: "I build Android apps used by millions at PocketPay and maintain a couple of popular open-source Kotlin libraries. I mentor students on their first apps and first open-source contributions.",
    location: "Mumbai, India",
    jobTitle: "Senior Android Engineer",
    company: "PocketPay",
    years: 5,
    rate: 800,
    skills: ["Android", "Kotlin", "Open Source", "Java"],
  },
  {
    name: "Vikram Singh",
    email: "vikram@mentorconnect.dev",
    headline: "Engineering manager • leadership, hiring and growing teams",
    bio: "Engineering manager at Nimbus Labs with 14 years in the industry. I help new managers and senior ICs with leadership, giving feedback, hiring and navigating promotions.",
    location: "Delhi, India",
    jobTitle: "Engineering Manager",
    company: "Nimbus Labs",
    years: 14,
    rate: 3000,
    skills: ["Leadership", "Career Growth", "Communication", "System Design"],
  },
];

const mentees = [
  {
    name: "Aarav Patel",
    email: "aarav@mentorconnect.dev",
    headline: "Final-year CS student aiming for a frontend role",
    goals: "Land a frontend developer job within 6 months and build two production-quality portfolio projects.",
    location: "Ahmedabad, India",
    skills: ["React", "TypeScript", "Next.js", "System Design"],
  },
  {
    name: "Isha Gupta",
    email: "isha@mentorconnect.dev",
    headline: "Backend developer, 2 years in, preparing for senior interviews",
    goals: "Get comfortable with system design interviews and move to a product company.",
    location: "Noida, India",
    skills: ["System Design", "Java", "Distributed Systems"],
  },
  {
    name: "Rohan Das",
    email: "rohan@mentorconnect.dev",
    headline: "Mechanical engineer switching to data science",
    goals: "Build a strong ML portfolio and get my first data analyst role.",
    location: "Kolkata, India",
    skills: ["Python", "Machine Learning", "SQL"],
  },
  {
    name: "Neha Joshi",
    email: "neha@mentorconnect.dev",
    headline: "Graphic designer learning product design",
    goals: "Create a UX case study portfolio and transition into product design.",
    location: "Jaipur, India",
    skills: ["UI/UX Design", "Figma", "Portfolio Review"],
  },
];

async function main() {
  console.log("Clearing existing data…");
  await db.$transaction([
    db.notification.deleteMany(),
    db.review.deleteMany(),
    db.message.deleteMany(),
    db.mentorSession.deleteMany(),
    db.connection.deleteMany(),
    db.availability.deleteMany(),
    db.userSkill.deleteMany(),
    db.skill.deleteMany(),
    db.authSession.deleteMany(),
    db.userToken.deleteMany(),
    db.userPhoto.deleteMany(),
    db.mentorProfile.deleteMany(),
    db.user.deleteMany(),
  ]);

  const passwordHash = await bcrypt.hash("password123", 12);
  const skillNames = [...new Set([...mentors, ...mentees].flatMap((p) => p.skills))];
  const skills = new Map<string, string>();
  for (const name of skillNames) {
    const s = await db.skill.create({ data: { name, slug: slug(name) } });
    skills.set(name, s.id);
  }
  const skillLinks = (list: string[]) => ({ create: list.map((n) => ({ skillId: skills.get(n)! })) });

  console.log("Creating users…");
  const adminPassword = process.env.SEED_ADMIN_PASSWORD;
  await db.user.create({
    data: { name: "Admin", email: "admin@mentorconnect.dev", passwordHash: adminPassword ? await bcrypt.hash(adminPassword, 12) : passwordHash, role: "ADMIN", onboarded: true, emailVerifiedAt: new Date(), headline: "Platform administrator" },
  });

  const mentorIds: string[] = [];
  for (const [i, m] of mentors.entries()) {
    const user = await db.user.create({
      data: {
        name: m.name,
        email: m.email,
        passwordHash,
        role: "MENTOR",
        onboarded: true,
        emailVerifiedAt: new Date(),
        headline: m.headline,
        bio: m.bio,
        location: m.location,
        createdAt: new Date(Date.now() - (60 - i * 5) * DAY),
        skills: skillLinks(m.skills),
        mentorProfile: {
          create: {
            jobTitle: m.jobTitle,
            company: m.company,
            yearsExperience: m.years,
            hourlyRate: m.rate,
            sessionMinutes: i % 3 === 0 ? 45 : 60,
            languages: i % 2 ? "English, Hindi" : "English",
            linkedinUrl: i % 2 ? null : `https://www.linkedin.com/in/${slug(m.name)}-example`,
            acceptingMentees: i !== 7,
          },
        },
        // Weekday evenings plus a Saturday morning block (mentor's local time).
        availability: {
          create: [
            ...[1, 2, 3, 4, 5].filter((d) => (d + i) % 2 === 0).map((d) => ({ dayOfWeek: d, startMinute: 18 * 60, endMinute: 21 * 60 })),
            { dayOfWeek: 6, startMinute: 10 * 60, endMinute: 13 * 60 },
          ],
        },
      },
    });
    mentorIds.push(user.id);
  }

  const menteeIds: string[] = [];
  for (const m of mentees) {
    const user = await db.user.create({
      data: { ...m, passwordHash, role: "MENTEE", onboarded: true, emailVerifiedAt: new Date(), skills: skillLinks(m.skills) },
    });
    menteeIds.push(user.id);
  }

  const [priya, rahul, ananya, karan, sneha] = mentorIds as [string, string, string, string, string];
  const [aarav, isha, rohan, neha] = menteeIds as [string, string, string, string];

  console.log("Creating connections, sessions and messages…");
  const connect = (mentorId: string, menteeId: string, status: "ACCEPTED" | "PENDING", message: string, daysAgo: number) =>
    db.connection.create({
      data: {
        mentorId,
        menteeId,
        status,
        message,
        createdAt: new Date(Date.now() - daysAgo * DAY),
        respondedAt: status === "ACCEPTED" ? new Date(Date.now() - (daysAgo - 1) * DAY) : null,
      },
    });

  const aaravRahul = await connect(rahul, aarav, "ACCEPTED", "Hi Rahul! I'm a final-year student building React projects and I'd love guidance on making them production-quality and preparing for frontend interviews.", 30);
  const aaravPriya = await connect(priya, aarav, "ACCEPTED", "Hi Priya, I want to learn system design fundamentals early so I stand out in interviews. Would you be open to mentoring me?", 20);
  await connect(karan, aarav, "PENDING", "Hi Karan, I'm curious about product management as a long-term path and would love to understand what the switch looks like.", 2);
  const ishaPriya = await connect(priya, isha, "ACCEPTED", "Hi Priya! I'm a backend dev with 2 years of experience preparing for senior interviews. System design is my weakest area.", 25);
  const rohanAnanya = await connect(ananya, rohan, "ACCEPTED", "Hello Ananya, I'm switching from mechanical engineering to data science and need help structuring my learning and projects.", 18);
  await connect(priya, rohan, "PENDING", "Hi Priya, I'd like to understand how ML systems are deployed at scale. Would love your perspective!", 1);
  const nehaSneha = await connect(sneha, neha, "ACCEPTED", "Hi Sneha! I'm a graphic designer moving into product design. Could you help me build my first UX case study?", 15);

  type SeedSession = {
    mentorId: string;
    menteeId: string;
    startsAt: Date;
    topic: string;
    agenda?: string;
    status: "COMPLETED" | "CONFIRMED" | "PENDING";
    review?: { rating: number; comment: string };
  };
  const sessions: SeedSession[] = [
    { mentorId: rahul, menteeId: aarav, startsAt: at(-21, 13), topic: "Portfolio project review", status: "COMPLETED", review: { rating: 5, comment: "Rahul went through my project line by line and showed me exactly how to structure components and state. Super practical advice." } },
    { mentorId: rahul, menteeId: aarav, startsAt: at(-7, 13), topic: "Frontend interview mock", status: "COMPLETED", review: { rating: 5, comment: "The mock interview felt just like the real thing. I finally understand how to talk through my approach out loud." } },
    { mentorId: priya, menteeId: aarav, startsAt: at(-10, 14), topic: "System design basics", status: "COMPLETED", review: { rating: 4, comment: "Great intro to load balancers, caching and databases. Clear explanations and a good reading list afterwards." } },
    { mentorId: priya, menteeId: isha, startsAt: at(-14, 14), topic: "Design a URL shortener", status: "COMPLETED", review: { rating: 5, comment: "Priya is an incredible teacher. She pushed me on trade-offs I'd never thought about. Worth every rupee." } },
    { mentorId: priya, menteeId: isha, startsAt: at(-4, 14), topic: "Design a chat system", status: "COMPLETED", review: { rating: 5, comment: "Second session was even better. I can now confidently reason about consistency vs availability." } },
    { mentorId: ananya, menteeId: rohan, startsAt: at(-9, 12), topic: "Learning roadmap for data science", status: "COMPLETED", review: { rating: 5, comment: "Ananya gave me a clear 3-month roadmap and helped me pick a project that matches my mechanical background." } },
    { mentorId: sneha, menteeId: neha, startsAt: at(-6, 11), topic: "First UX case study", status: "COMPLETED", review: { rating: 4, comment: "Really helpful feedback on storytelling in my case study. Sneha is kind and very detailed." } },
    { mentorId: rahul, menteeId: aarav, startsAt: at(2, 13), topic: "Next.js app architecture", agenda: "Review my folder structure and data fetching approach for my capstone project.", status: "CONFIRMED" },
    { mentorId: priya, menteeId: isha, startsAt: at(3, 14), topic: "Mock system design interview", agenda: "Full 45-minute mock, then feedback.", status: "CONFIRMED" },
    { mentorId: priya, menteeId: aarav, startsAt: at(5, 14), topic: "Databases deep dive", agenda: "SQL vs NoSQL, indexing, and when to shard.", status: "PENDING" },
    { mentorId: ananya, menteeId: rohan, startsAt: at(4, 12), topic: "Project feedback: house price model", status: "PENDING" },
  ];

  const durations = new Map(
    (await db.mentorProfile.findMany({ select: { userId: true, sessionMinutes: true } })).map((p) => [p.userId, p.sessionMinutes]),
  );
  for (const s of sessions) {
    const created = await db.mentorSession.create({
      data: {
        mentorId: s.mentorId,
        menteeId: s.menteeId,
        startsAt: s.startsAt,
        durationMinutes: durations.get(s.mentorId) ?? 60,
        topic: s.topic,
        agenda: s.agenda,
        status: s.status,
        slotKey: s.status === "COMPLETED" ? null : `${s.mentorId}:${s.startsAt.toISOString()}`,
      },
    });
    if (s.status !== "PENDING") {
      await db.mentorSession.update({
        where: { id: created.id },
        data: { meetingUrl: `https://meet.jit.si/MentorConnect-${created.id}` },
      });
    }
    if (s.review) {
      await db.review.create({
        data: { sessionId: created.id, mentorId: s.mentorId, authorId: s.menteeId, ...s.review, createdAt: new Date(s.startsAt.getTime() + 2 * HOUR) },
      });
    }
  }

  const chats: [string, [string, string][]][] = [
    [aaravRahul.id, [
      [rahul, "Hey Aarav! Looking forward to working with you. What are you building right now?"],
      [aarav, "Hi Rahul! A job board in Next.js. I'm stuck on how to organise server and client components."],
      [rahul, "Great project for a portfolio. Let's make that the focus of our next session. Can you share the repo before then?"],
      [aarav, "Sure, I'll send it tonight. Thanks!"],
    ]],
    [aaravPriya.id, [
      [priya, "Hi Aarav, welcome! Before our next session, read the first two chapters of any system design primer you like."],
      [aarav, "Will do. Should I focus on anything specific?"],
      [priya, "Caching and database indexing. We'll build on those."],
    ]],
    [ishaPriya.id, [
      [isha, "Thank you for the chat system session! The fan-out part finally clicked."],
      [priya, "You did really well. For the mock on Thursday, think about how you'd handle presence at scale."],
    ]],
    [rohanAnanya.id, [
      [ananya, "Hi Rohan! I've shared a couple of datasets you could use for your first project."],
      [rohan, "Thank you! I started on the house price one. Should I use linear regression first?"],
      [ananya, "Yes, start simple, measure, then try tree-based models. Bring your notebook to our session."],
    ]],
    [nehaSneha.id, [
      [sneha, "Loved your first draft, Neha. The problem statement is very clear."],
      [neha, "Thank you! I'll rework the research section like you suggested."],
    ]],
  ];
  for (const [connectionId, lines] of chats) {
    for (const [i, [senderId, body]] of lines.entries()) {
      await db.message.create({
        data: {
          connectionId,
          senderId,
          body,
          createdAt: new Date(Date.now() - (lines.length - i) * 3 * HOUR),
          readAt: i < lines.length - 1 ? new Date() : null,
        },
      });
    }
  }

  await db.notification.createMany({
    data: [
      { userId: karan, title: "New mentorship request", body: "Aarav Patel would like you to mentor them.", link: "/connections" },
      { userId: priya, title: "New mentorship request", body: "Rohan Das would like you to mentor them.", link: "/connections" },
      { userId: priya, title: "New session request", body: 'Aarav Patel requested "Databases deep dive".', link: "/sessions" },
      { userId: aarav, title: "Session confirmed", body: 'Rahul Verma confirmed "Next.js app architecture".', link: "/sessions" },
      { userId: isha, title: "Session confirmed", body: 'Priya Sharma confirmed "Mock system design interview".', link: "/sessions" },
    ],
  });

  console.log("✔ Seed complete. Log in with any demo account using password: password123");
  console.log("  Mentee: aarav@mentorconnect.dev   Mentor: priya@mentorconnect.dev   Admin: admin@mentorconnect.dev");
}

main()
  .catch((e) => {
    console.error(e);
    process.exit(1);
  })
  .finally(() => db.$disconnect());
