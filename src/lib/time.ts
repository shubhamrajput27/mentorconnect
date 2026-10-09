// Timezone-aware helpers built on Intl, so no date library is needed.

export const DAYS = [
  "Sunday",
  "Monday",
  "Tuesday",
  "Wednesday",
  "Thursday",
  "Friday",
  "Saturday",
] as const;

export const TIMEZONES = [
  "Asia/Kolkata",
  "Asia/Dubai",
  "Asia/Singapore",
  "Asia/Tokyo",
  "Europe/London",
  "Europe/Berlin",
  "America/New_York",
  "America/Chicago",
  "America/Los_Angeles",
  "Australia/Sydney",
  "UTC",
];

/** Milliseconds that `timeZone` is ahead of UTC at the given instant. */
export function tzOffsetMs(date: Date, timeZone: string) {
  const parts = new Intl.DateTimeFormat("en-US", {
    timeZone,
    hourCycle: "h23",
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  }).formatToParts(date);
  const get = (type: string) => Number(parts.find((p) => p.type === type)?.value);
  const asUtc = Date.UTC(
    get("year"),
    get("month") - 1,
    get("day"),
    get("hour"),
    get("minute"),
    get("second"),
  );
  return asUtc - Math.floor(date.getTime() / 1000) * 1000;
}

/** The UTC instant of a wall-clock time (minutes after midnight) on a local date in `timeZone`. */
export function zonedToUtc(
  year: number,
  month: number,
  day: number,
  minute: number,
  timeZone: string,
) {
  const guess = Date.UTC(year, month - 1, day, 0, minute);
  let result = guess - tzOffsetMs(new Date(guess), timeZone);
  // Re-check once so times next to a DST change land on the right offset.
  result = guess - tzOffsetMs(new Date(result), timeZone);
  return new Date(result);
}

/** Today's calendar date in `timeZone`. */
export function localDate(date: Date, timeZone: string) {
  const [year, month, day] = new Intl.DateTimeFormat("en-CA", {
    timeZone,
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  })
    .format(date)
    .split("-")
    .map(Number);
  return { year, month, day };
}

export type AvailabilityWindow = {
  dayOfWeek: number;
  startMinute: number;
  endMinute: number;
};

export type BusyRange = { start: Date; end: Date };

/**
 * Bookable session start times for a mentor over the next `days` days.
 * Windows are in the mentor's timezone; results are UTC instants.
 */
export function generateSlots({
  windows,
  timeZone,
  durationMinutes,
  busy,
  now = new Date(),
  days = 14,
  minNoticeHours = 12,
}: {
  windows: AvailabilityWindow[];
  timeZone: string;
  durationMinutes: number;
  busy: BusyRange[];
  now?: Date;
  days?: number;
  minNoticeHours?: number;
}) {
  const earliest = now.getTime() + minNoticeHours * 60 * 60 * 1000;
  const today = localDate(now, timeZone);
  const slots: Date[] = [];

  for (let offset = 0; offset < days; offset++) {
    const cal = new Date(Date.UTC(today.year, today.month - 1, today.day + offset));
    const weekday = cal.getUTCDay();
    const dayWindows = windows
      .filter((w) => w.dayOfWeek === weekday)
      .sort((a, b) => a.startMinute - b.startMinute);

    for (const w of dayWindows) {
      for (let m = w.startMinute; m + durationMinutes <= w.endMinute; m += durationMinutes) {
        const start = zonedToUtc(
          cal.getUTCFullYear(),
          cal.getUTCMonth() + 1,
          cal.getUTCDate(),
          m,
          timeZone,
        );
        const end = new Date(start.getTime() + durationMinutes * 60 * 1000);
        if (start.getTime() < earliest) continue;
        if (busy.some((b) => start < b.end && end > b.start)) continue;
        slots.push(start);
      }
    }
  }
  return slots;
}

export function minutesToLabel(minutes: number) {
  const h = Math.floor(minutes / 60);
  const m = minutes % 60;
  const suffix = h >= 12 ? "PM" : "AM";
  const hour12 = h % 12 === 0 ? 12 : h % 12;
  return `${hour12}:${String(m).padStart(2, "0")} ${suffix}`;
}

export function formatDateTime(date: Date, timeZone: string) {
  return new Intl.DateTimeFormat("en-IN", {
    timeZone,
    weekday: "short",
    day: "numeric",
    month: "short",
    hour: "numeric",
    minute: "2-digit",
  }).format(date);
}

export function formatDay(date: Date, timeZone: string) {
  return new Intl.DateTimeFormat("en-IN", {
    timeZone,
    weekday: "long",
    day: "numeric",
    month: "long",
  }).format(date);
}

export function formatTime(date: Date, timeZone: string) {
  return new Intl.DateTimeFormat("en-IN", {
    timeZone,
    hour: "numeric",
    minute: "2-digit",
  }).format(date);
}

export function timeAgo(date: Date, now = new Date()) {
  const seconds = Math.round((now.getTime() - date.getTime()) / 1000);
  if (seconds < 60) return "just now";
  const minutes = Math.round(seconds / 60);
  if (minutes < 60) return `${minutes}m ago`;
  const hours = Math.round(minutes / 60);
  if (hours < 24) return `${hours}h ago`;
  const days = Math.round(hours / 24);
  if (days < 30) return `${days}d ago`;
  return date.toLocaleDateString("en-IN", { day: "numeric", month: "short", year: "numeric" });
}
