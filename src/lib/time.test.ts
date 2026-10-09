import { describe, expect, it } from "vitest";
import { generateSlots, localDate, minutesToLabel, zonedToUtc } from "./time";

describe("zonedToUtc", () => {
  it("converts India time (UTC+5:30, no DST)", () => {
    // 6:00 PM IST on 6 Oct 2026 is 12:30 UTC.
    expect(zonedToUtc(2026, 10, 6, 18 * 60, "Asia/Kolkata").toISOString()).toBe("2026-10-06T12:30:00.000Z");
  });

  it("respects daylight saving time", () => {
    // New York is UTC-4 in July and UTC-5 in January.
    expect(zonedToUtc(2026, 7, 1, 9 * 60, "America/New_York").toISOString()).toBe("2026-07-01T13:00:00.000Z");
    expect(zonedToUtc(2026, 1, 15, 9 * 60, "America/New_York").toISOString()).toBe("2026-01-15T14:00:00.000Z");
  });
});

describe("localDate", () => {
  it("returns the calendar date in the given timezone", () => {
    const instant = new Date("2026-10-06T20:00:00Z"); // already 7 Oct in India
    expect(localDate(instant, "Asia/Kolkata")).toEqual({ year: 2026, month: 10, day: 7 });
    expect(localDate(instant, "UTC")).toEqual({ year: 2026, month: 10, day: 6 });
  });
});

describe("generateSlots", () => {
  // Tuesday 6 Oct 2026, 09:00 IST.
  const now = new Date("2026-10-06T03:30:00Z");
  const tuesdayEvening = { dayOfWeek: 2, startMinute: 18 * 60, endMinute: 21 * 60 };

  it("splits availability windows into session-length slots", () => {
    const slots = generateSlots({
      windows: [tuesdayEvening],
      timeZone: "Asia/Kolkata",
      durationMinutes: 60,
      busy: [],
      now,
      days: 1,
      minNoticeHours: 0,
    });
    expect(slots.map((s) => s.toISOString())).toEqual([
      "2026-10-06T12:30:00.000Z",
      "2026-10-06T13:30:00.000Z",
      "2026-10-06T14:30:00.000Z",
    ]);
  });

  it("skips slots that overlap an existing booking", () => {
    const slots = generateSlots({
      windows: [tuesdayEvening],
      timeZone: "Asia/Kolkata",
      durationMinutes: 60,
      busy: [{ start: new Date("2026-10-06T13:00:00Z"), end: new Date("2026-10-06T14:00:00Z") }],
      now,
      days: 1,
      minNoticeHours: 0,
    });
    expect(slots.map((s) => s.toISOString())).toEqual(["2026-10-06T14:30:00.000Z"]);
  });

  it("enforces minimum notice and repeats weekly", () => {
    const slots = generateSlots({
      windows: [tuesdayEvening],
      timeZone: "Asia/Kolkata",
      durationMinutes: 90,
      busy: [],
      now,
      days: 14,
      minNoticeHours: 12, // rules out today's 6 PM slot
    });
    expect(slots.map((s) => s.toISOString())).toEqual([
      "2026-10-13T12:30:00.000Z",
      "2026-10-13T14:00:00.000Z",
    ]);
  });
});

describe("minutesToLabel", () => {
  it("formats minutes as 12-hour time", () => {
    expect(minutesToLabel(0)).toBe("12:00 AM");
    expect(minutesToLabel(18 * 60 + 30)).toBe("6:30 PM");
    expect(minutesToLabel(12 * 60)).toBe("12:00 PM");
  });
});
