import { expect, test, type Page } from "@playwright/test";
import { login, logout } from "./helpers";

async function openMentor(page: Page, name: string) {
  await page.goto(`/mentors?q=${encodeURIComponent(name)}`);
  await page.getByRole("link", { name: new RegExp(name) }).first().click();
  await expect(page.getByRole("heading", { name })).toBeVisible();
}

test("request → accept → book → confirm → join", async ({ page }) => {
  // Neha (mentee) asks Arjun (mentor) for mentorship.
  await login(page, "neha@mentorconnect.dev");
  await openMentor(page, "Arjun Nair");
  await page.getByLabel(/Message to Arjun/).fill("Hi Arjun, I'd love help learning Docker and deploying my portfolio site.");
  await page.getByRole("button", { name: "Request mentorship" }).click();
  await expect(page.getByText("Request sent", { exact: true })).toBeVisible();

  // Arjun accepts.
  await logout(page);
  await login(page, "arjun@mentorconnect.dev");
  await page.goto("/connections");
  const request = page.getByRole("listitem").filter({ hasText: "Neha Joshi" });
  await request.getByRole("button", { name: "Accept" }).click();
  await expect(page.getByRole("listitem").filter({ hasText: "Neha Joshi" }).getByRole("link", { name: "Message" })).toBeVisible();

  // Neha books the first open slot.
  await logout(page);
  await login(page, "neha@mentorconnect.dev");
  await openMentor(page, "Arjun Nair");
  const booking = page.locator("form", { hasText: "Book a session" });
  await booking.locator(".grid button").first().click();
  await booking.getByLabel("Topic").fill("Docker basics");
  await booking.getByRole("button", { name: "Request this time" }).click();
  await expect(page).toHaveURL(/\/sessions\?booked=1/);

  // Arjun confirms; a video link is attached.
  await logout(page);
  await login(page, "arjun@mentorconnect.dev");
  await page.goto("/sessions?tab=requests");
  await page.locator("div.rounded-2xl", { hasText: "Docker basics" }).getByRole("button", { name: "Confirm" }).click();
  // Wait for the server to save it: the session leaves the Requests list.
  await expect(page.locator("div.rounded-2xl", { hasText: "Docker basics" })).toHaveCount(0);
  await page.goto("/sessions?tab=upcoming");
  const card = page.locator("div.rounded-2xl", { hasText: "Docker basics" });
  await expect(card.getByText("Confirmed")).toBeVisible();
  await expect(card.getByRole("link", { name: "Join call" })).toHaveAttribute("href", /meet\.jit\.si/);

  // Neha was notified.
  await logout(page);
  await login(page, "neha@mentorconnect.dev");
  await page.goto("/notifications");
  await expect(page.getByText(/Arjun Nair confirmed "Docker basics"/)).toBeVisible();
});

test("two mentees can't book the same slot", async ({ browser }) => {
  // Aarav and Isha are both connected to Priya. They pick the same slot and submit together.
  const pages = await Promise.all(
    ["aarav@mentorconnect.dev", "isha@mentorconnect.dev"].map(async (email) => {
      const page = await (await browser.newContext()).newPage();
      await login(page, email);
      await openMentor(page, "Priya Sharma");
      const booking = page.locator("form", { hasText: "Book a session" });
      await booking.locator(".grid button").first().click();
      await booking.getByLabel("Topic").fill(`Race test (${email})`);
      return page;
    }),
  );
  const slotTexts = await Promise.all(pages.map((p) => p.locator("form .grid button.ring-2").textContent()));
  expect(slotTexts[0]).toBe(slotTexts[1]);

  await Promise.all(pages.map((p) => p.getByRole("button", { name: "Request this time" }).click()));

  const outcomes = await Promise.all(
    pages.map((p) =>
      Promise.race([
        p.waitForURL(/\/sessions\?booked=1/).then(() => "booked"),
        p
          .getByRole("alert")
          .filter({ hasText: /no longer available|Someone just booked that time/ })
          .waitFor()
          .then(() => "rejected"),
      ]),
    ),
  );
  expect(outcomes.sort()).toEqual(["booked", "rejected"]);
});
