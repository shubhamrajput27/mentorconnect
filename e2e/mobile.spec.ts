import { expect, test } from "@playwright/test";
import { login } from "./helpers";

test("mobile layout and menu", async ({ page }) => {
  await page.goto("/");
  const overflows = () => page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth);
  expect(await overflows()).toBe(false);

  await login(page, "aarav@mentorconnect.dev");
  await page.getByRole("button", { name: "Open menu" }).click();
  const menu = page.getByRole("dialog");
  await expect(menu.getByRole("link", { name: "Sessions" })).toBeVisible();
  await menu.getByRole("link", { name: "Sessions" }).click();
  await expect(page).toHaveURL(/\/sessions/);
  await expect(page.getByRole("dialog")).toHaveCount(0);
  expect(await overflows()).toBe(false);
});
