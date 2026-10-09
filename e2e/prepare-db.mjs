// Wipe the e2e database and email outbox, then migrate and load demo data.
import "dotenv/config";
import { execSync } from "node:child_process";
import { rmSync } from "node:fs";

const url = process.env.E2E_DATABASE_URL;
if (!url) throw new Error("Set E2E_DATABASE_URL (see .env.example).");
// This script wipes the database, so refuse anything that doesn't look like a test database.
const dbName = new URL(url).pathname.slice(1);
if (!/e2e|test/i.test(dbName)) throw new Error(`Refusing to wipe "${dbName}": the e2e database name must contain "e2e" or "test".`);

const env = { ...process.env, DATABASE_URL: url };
rmSync(".e2e-emails", { recursive: true, force: true });
execSync("npx prisma db execute --stdin", {
  input: "DROP SCHEMA IF EXISTS public CASCADE; CREATE SCHEMA public;",
  stdio: ["pipe", "inherit", "inherit"],
  env,
});
execSync("npx prisma migrate deploy", { stdio: "inherit", env });
execSync("npx prisma db seed", { stdio: "inherit", env });
