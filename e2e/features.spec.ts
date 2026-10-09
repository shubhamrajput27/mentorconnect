import { crc32, deflateSync } from "node:zlib";
import { expect, test } from "@playwright/test";
import { login, logout, unique } from "./helpers";

/** A solid-color PNG of the given size, built in memory. */
function makePng(width: number, height: number, [r, g, b]: [number, number, number]) {
  const chunk = (type: string, data: Buffer) => {
    const body = Buffer.concat([Buffer.from(type), data]);
    const out = Buffer.alloc(body.length + 8);
    out.writeUInt32BE(data.length, 0);
    body.copy(out, 4);
    out.writeUInt32BE(crc32(body), body.length + 4);
    return out;
  };
  const header = Buffer.alloc(13);
  header.writeUInt32BE(width, 0);
  header.writeUInt32BE(height, 4);
  header[8] = 8; // bit depth
  header[9] = 2; // RGB
  const row = Buffer.concat([Buffer.from([0]), Buffer.from(Array.from({ length: width }, () => [r, g, b]).flat())]);
  return Buffer.concat([
    Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]),
    chunk("IHDR", header),
    chunk("IDAT", deflateSync(Buffer.concat(Array.from({ length: height }, () => row)))),
    chunk("IEND", Buffer.alloc(0)),
  ]);
}

test("chat messages reach the other person", async ({ browser }) => {
  const text = unique("Hello from the e2e test ");
  const mentee = await (await browser.newContext()).newPage();
  const mentor = await (await browser.newContext()).newPage();

  await login(mentor, "rahul@mentorconnect.dev");
  await mentor.goto("/messages");
  await mentor.getByRole("link", { name: /Aarav Patel/ }).click();

  await login(mentee, "aarav@mentorconnect.dev");
  await mentee.goto("/messages");
  await mentee.getByRole("link", { name: /Rahul Verma/ }).click();
  await mentee.getByLabel("Message", { exact: true }).fill(text);
  await mentee.keyboard.press("Enter");
  await expect(mentee.getByText(text)).toBeVisible();

  // The mentor's open chat picks it up by polling, without a reload.
  await expect(mentor.getByText(text)).toBeVisible({ timeout: 15_000 });
});

test("upload and remove a profile photo", async ({ page }) => {
  await login(page, "sneha@mentorconnect.dev");
  await page.goto("/settings/profile");

  await page.locator("input[type=file]").setInputFiles({
    name: "me.png",
    mimeType: "image/png",
    buffer: makePng(400, 300, [31, 75, 191]),
  });
  await expect(page.getByText("Photo updated.")).toBeVisible();

  const img = page.locator('img[src^="/api/photos/"]').first();
  await expect(img).toBeVisible();
  const res = await page.request.get((await img.getAttribute("src"))!);
  expect(res.status()).toBe(200);
  expect(res.headers()["content-type"]).toMatch(/image\/(webp|jpeg)/);
  expect((await res.body()).length).toBeLessThan(300 * 1024);

  // The photo shows on the public mentor profile too.
  const photoSrc = await img.getAttribute("src");
  await logout(page);
  await page.goto("/mentors?q=Sneha");
  await expect(page.locator(`img[src="${photoSrc}"]`)).toBeVisible();

  await login(page, "sneha@mentorconnect.dev");
  await page.goto("/settings/profile");
  await page.getByRole("button", { name: "Remove", exact: true }).click();
  await expect(page.getByText("Photo removed.")).toBeVisible();
  await expect(page.locator('img[src^="/api/photos/"]')).toHaveCount(0);
});

test("admin can suspend and reactivate a user", async ({ page }) => {
  await login(page, "admin@mentorconnect.dev");
  await expect(page).toHaveURL(/\/admin/);
  page.on("dialog", (d) => d.accept());

  const row = page.getByRole("row").filter({ hasText: "rohan@mentorconnect.dev" });
  await row.getByRole("button", { name: "Suspend" }).click();
  await expect(row.getByText("Suspended")).toBeVisible();

  await logout(page);
  await page.goto("/login");
  await page.getByLabel("Email").fill("rohan@mentorconnect.dev");
  await page.getByLabel("Password").fill("password123");
  await page.getByRole("button", { name: "Sign in" }).click();
  await expect(page.getByText("This account has been suspended.")).toBeVisible();

  await login(page, "admin@mentorconnect.dev");
  await page.getByRole("row").filter({ hasText: "rohan@mentorconnect.dev" }).getByRole("button", { name: "Reactivate" }).click();
  await expect(page.getByRole("row").filter({ hasText: "rohan@mentorconnect.dev" }).getByText("Active")).toBeVisible();
});
