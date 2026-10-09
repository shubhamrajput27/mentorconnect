# MentorConnect

A mentorship platform that connects mentees with experienced mentors. Mentees find a mentor, send a request, book 1:1 sessions from the mentor's live availability, chat between sessions, and leave reviews afterwards.

## Features

- **Accounts and roles.** Mentee, mentor and admin roles. Sessions are stored in the database and passwords are hashed with bcrypt.
- **Email verification and password reset.** One-time links that expire (24 hours to verify, 1 hour to reset). Resetting a password signs the user out on every device. The forgot-password form never reveals whether an email is registered.
- **Profile photos.** Cropped and resized to 256px in the browser, checked on the server, and stored in the database.
- **Onboarding and profiles.** Headline, bio, skills, goals and timezone. Mentors also set role, company, experience, rate, session length and languages.
- **Mentor directory.** Search by name, skill or company, filter by skill and price, and sort by rating, experience or price.
- **Mentorship requests.** Mentees send a request with a message. Mentors accept or decline, and either side can end the mentorship.
- **Scheduling.** Mentors set weekly availability in their own timezone. Mentees see open slots in theirs, and slots that are already booked are hidden. A unique database index stops two people booking the same slot, even at the same instant.
- **Sessions.** Request → confirm/decline → join the video call (a Jitsi link is created automatically) → mark complete. Either side can cancel.
- **Messaging.** Chat between connected users, with unread counts. The chat window checks for new messages every few seconds.
- **Reviews.** Only a mentee who attended a completed session can review it. Ratings appear on mentor profiles.
- **Notifications.** Sent for requests, bookings, confirmations, cancellations, messages and reviews.
- **Dashboards.** Mentees see stats, upcoming sessions and recommended mentors. Mentors see stats, new requests and a profile checklist.
- **Admin panel.** Platform stats, user search, and suspending or reactivating users. Suspending a user signs them out everywhere.

## Tech stack

| Layer | Choice |
| --- | --- |
| Framework | [Next.js 16](https://nextjs.org) (App Router, Server Components, Server Actions) |
| Language | TypeScript |
| Styling | Tailwind CSS v4, lucide-react icons |
| Database | PostgreSQL, accessed with Prisma ORM 7 |
| Validation | Zod |
| Email | [Resend](https://resend.com) (saved to a local folder in development) |
| Tests | Vitest (unit), Playwright (end-to-end) |
| CI | GitHub Actions: lint, typecheck, unit tests, build, end-to-end tests |

## Getting started

Requires Node.js 20.9 or newer and PostgreSQL 14 or newer.

1. Create a database user and two databases (one for the app, one for the end-to-end tests):

   ```bash
   psql -U postgres -c "CREATE ROLE mentorconnect LOGIN CREATEDB PASSWORD 'change-me';"
   psql -U postgres -c "CREATE DATABASE mentorconnect OWNER mentorconnect;"
   psql -U postgres -c "CREATE DATABASE mentorconnect_e2e OWNER mentorconnect;"
   ```

   On Windows, `psql` is in `C:\Program Files\PostgreSQL\<version>\bin`.

2. Install and run:

   ```bash
   npm install                 # also generates the Prisma client
   cp .env.example .env        # on Windows: copy .env.example .env, then set your password in it
   npm run db:deploy           # create the tables
   npm run db:seed             # load demo data
   npm run dev                 # http://localhost:3000
   ```

### Demo accounts

Every demo account uses the password **`password123`**.

| Role | Email |
| --- | --- |
| Mentee | `aarav@mentorconnect.dev` |
| Mentor | `priya@mentorconnect.dev` |
| Admin | `admin@mentorconnect.dev` |

Other mentors include `rahul@`, `ananya@` and `sneha@`. Other mentees include `isha@`, `rohan@` and `neha@` (all `@mentorconnect.dev`).

### Emails in development

Without `RESEND_API_KEY`, nothing is sent. Each email is saved as an HTML file in `.dev-emails/` and its link is printed in the terminal running `npm run dev`. Open the link to verify an account or reset a password.

To send real emails, create a free [Resend](https://resend.com) API key and set `RESEND_API_KEY` in `.env`. To send from your own domain, verify it in Resend and set `EMAIL_FROM`.

## Scripts

| Command | What it does |
| --- | --- |
| `npm run dev` | Start the development server |
| `npm run build` / `npm start` | Production build and server |
| `npm run lint` | ESLint |
| `npm run typecheck` | TypeScript, with Next.js route types |
| `npm test` | Unit tests |
| `npm run test:e2e` | Browser tests (builds the app and uses the separate `E2E_DATABASE_URL` database) |
| `npm run db:migrate` | Create or apply migrations during development |
| `npm run db:deploy` | Apply migrations in production |
| `npm run db:seed` | Reset all data to the demo data |
| `npm run db:studio` | Browse the database in Prisma Studio |

## End-to-end tests

`npm run test:e2e` builds the app, starts it on port 3200, wipes and re-seeds the database in `E2E_DATABASE_URL`, and runs the Playwright tests in `e2e/`. Your development database is never touched, and the script refuses to wipe any database whose name doesn't contain `e2e` or `test`. The tests cover:

- sign-up, email verification and password reset
- request → accept → book → confirm → join call
- two mentees racing for the same slot
- chat delivery between two users
- photo upload and removal
- admin suspension
- the mobile menu

Locally the tests use your installed Chrome. CI installs Playwright's Chromium and runs PostgreSQL as a service.

## Project structure

```
e2e/                   Playwright tests
prisma/
  schema.prisma        data model
  migrations/          SQL migrations
  seed.ts              demo data
src/
  actions/             server actions (auth, profile, connections, sessions, messages, admin)
  app/
    (marketing)/       landing page
    (auth)/            login and signup
    (app)/             signed-in app: dashboard, mentors, connections, sessions, messages, settings, admin
    api/               chat polling and profile photos
    onboarding/
  components/          shared UI
  lib/                 auth, db, validation, timezone and booking logic, queries
  proxy.ts             redirects signed-out visitors away from private pages
```

## Deploying to production

1. **Create a hosted PostgreSQL database.** [Neon](https://neon.tech) and [Supabase](https://supabase.com) both have free tiers. Copy the connection string.
2. **Deploy.** Vercel works with no extra configuration. Set `DATABASE_URL`, `APP_URL`, `RESEND_API_KEY` and `EMAIL_FROM`, and run `npm run db:deploy` as part of the release.
3. **Possible upgrades:**
   - Real-time chat with Pusher, Ably or Supabase Realtime instead of polling.
   - Rate limiting shared across instances, using Upstash Redis.
   - Email notifications for bookings and messages, reusing `src/lib/email.ts`.
   - Payments for paid sessions with Razorpay or Stripe.
   - Profile photos in object storage (Vercel Blob, S3 or Cloudinary) once there are many users.
