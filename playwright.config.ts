import "dotenv/config";
import { defineConfig, devices } from "@playwright/test";

const PORT = 3200;

// End-to-end tests run a production build against a separate PostgreSQL
// database (E2E_DATABASE_URL), wiped and seeded before every run, so your
// development data is never touched.
const E2E_DATABASE_URL = process.env.E2E_DATABASE_URL;
if (!E2E_DATABASE_URL) throw new Error("Set E2E_DATABASE_URL in .env (see .env.example).");
if (E2E_DATABASE_URL === process.env.DATABASE_URL) throw new Error("E2E_DATABASE_URL must differ from DATABASE_URL.");

export default defineConfig({
  testDir: "./e2e",
  fullyParallel: false,
  workers: 1,
  retries: process.env.CI ? 1 : 0,
  reporter: process.env.CI ? [["github"], ["html", { open: "never" }]] : "list",
  use: {
    baseURL: `http://localhost:${PORT}`,
    trace: "retain-on-failure",
    // Locally use the installed Chrome; CI installs Playwright's Chromium.
    channel: process.env.CI ? undefined : "chrome",
  },
  projects: [
    { name: "desktop", use: { ...devices["Desktop Chrome"] }, testIgnore: /mobile/ },
    { name: "mobile", use: { ...devices["Pixel 7"] }, testMatch: /mobile/ },
  ],
  webServer: {
    command: `node e2e/prepare-db.mjs && next build && next start --port ${PORT}`,
    url: `http://localhost:${PORT}/login`,
    timeout: 240_000,
    reuseExistingServer: false,
    env: {
      DATABASE_URL: E2E_DATABASE_URL,
      APP_URL: `http://localhost:${PORT}`,
      EMAIL_OUTBOX_DIR: ".e2e-emails",
    },
  },
});
