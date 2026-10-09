import { readdirSync, readFileSync } from "node:fs";
import path from "node:path";
import { expect, type Page } from "@playwright/test";

export const PASSWORD = "password123";

export const users = {
  mentee: "aarav@mentorconnect.dev",
  mentor: "rahul@mentorconnect.dev",
  busyMentor: "priya@mentorconnect.dev",
  admin: "admin@mentorconnect.dev",
};

export async function login(page: Page, email: string, password = PASSWORD) {
  await page.goto("/login");
  await page.getByLabel("Email").fill(email);
  await page.getByLabel("Password").fill(password);
  await page.getByRole("button", { name: "Sign in" }).click();
  await expect(page).not.toHaveURL(/\/login/);
}

export async function logout(page: Page) {
  await page.context().clearCookies();
}

/** The link from the newest email sent to `to` (emails are written to .e2e-emails/). */
export async function latestEmailLink(to: string, pathPrefix: string) {
  const dir = path.resolve(".e2e-emails");
  let link: string | undefined;
  await expect
    .poll(() => {
      const files = readdirSync(dir).filter((f) => f.endsWith(`${to}.html`)).sort();
      const html = files.length ? readFileSync(path.join(dir, files.at(-1)!), "utf8") : "";
      link = html.match(new RegExp(`href="(http[^"]*${pathPrefix}[^"]*)"`))?.[1]?.replace(/&amp;/g, "&");
      return link;
    })
    .toBeTruthy();
  return link!;
}

export const unique = (prefix: string) => `${prefix}${Date.now()}${Math.floor(Math.random() * 1000)}`;
