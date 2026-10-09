import { expect, test } from "@playwright/test";
import { latestEmailLink, login, logout, unique } from "./helpers";

test("sign up, verify email, and reset a forgotten password", async ({ page }) => {
  const email = `${unique("e2e")}@example.com`;

  // Sign up — validation errors keep what was typed.
  await page.goto("/signup");
  await page.getByLabel("Full name").fill("E2E Tester");
  await page.getByLabel("Email").fill(email);
  await page.getByLabel("Password").fill("short");
  await page.getByRole("button", { name: "Create account" }).click();
  await expect(page.getByText("Use at least 8 characters.")).toBeVisible();
  await expect(page.getByLabel("Email")).toHaveValue(email);
  await page.getByLabel("Password").fill("firstpass123");
  await page.getByRole("button", { name: "Create account" }).click();

  // Onboarding.
  await expect(page).toHaveURL(/\/onboarding/);
  await page.getByLabel("Headline").fill("Student testing the platform");
  await page.getByRole("button", { name: /^\+ / }).first().click();
  await page.getByRole("button", { name: "Find my mentor" }).click();
  await expect(page).toHaveURL(/\/mentors\?welcome=1/);

  // Not verified yet: banner shows. Following the emailed link verifies the account.
  await expect(page.getByText("Please verify your email address")).toBeVisible();
  await page.goto(await latestEmailLink(email, "/verify-email"));
  await expect(page.getByRole("heading", { name: "Email verified" })).toBeVisible();
  await page.goto("/dashboard");
  await expect(page.getByText("Please verify your email address")).toHaveCount(0);

  // The same link can't be used twice.
  await page.goto(await latestEmailLink(email, "/verify-email"));
  await expect(page.getByRole("heading", { name: "Link invalid or expired" })).toBeVisible();

  // Forgot password → email → choose a new one → signed in.
  await logout(page);
  await page.goto("/login");
  await page.getByRole("link", { name: "Forgot password?" }).click();
  await expect(page.getByRole("heading", { name: "Forgot your password?" })).toBeVisible();
  await page.getByLabel("Email").fill(email);
  await page.getByRole("button", { name: "Send reset link" }).click();
  await expect(page.getByText(/we've sent a link to reset your password/)).toBeVisible();

  const resetLink = await latestEmailLink(email, "/reset-password");
  await page.goto(resetLink);
  await page.getByLabel("New password", { exact: true }).fill("secondpass456");
  await page.getByLabel("Confirm new password").fill("different789");
  await page.getByRole("button", { name: "Save new password" }).click();
  await expect(page.getByText("Passwords don't match.")).toBeVisible();
  await page.getByLabel("Confirm new password").fill("secondpass456");
  await page.getByRole("button", { name: "Save new password" }).click();
  await expect(page).toHaveURL(/\/dashboard/);

  // Old password no longer works, the new one does, and the reset link is spent.
  await logout(page);
  await page.goto("/login");
  await page.getByLabel("Email").fill(email);
  await page.getByLabel("Password").fill("firstpass123");
  await page.getByRole("button", { name: "Sign in" }).click();
  await expect(page.getByText("Incorrect email or password.")).toBeVisible();
  await login(page, email, "secondpass456");
  await expect(page).toHaveURL(/\/dashboard/);
  await page.goto(resetLink);
  await expect(page.getByRole("heading", { name: "Link expired" })).toBeVisible();
});

test("forgot password doesn't reveal whether an account exists", async ({ page }) => {
  await page.goto("/forgot-password");
  await page.getByLabel("Email").fill("nobody-here@example.com");
  await page.getByRole("button", { name: "Send reset link" }).click();
  await expect(page.getByText(/If an account exists for nobody-here@example.com/)).toBeVisible();
});

test("private pages redirect visitors to login", async ({ page }) => {
  await page.goto("/sessions");
  await expect(page).toHaveURL(/\/login\?next=%2Fsessions/);
});
