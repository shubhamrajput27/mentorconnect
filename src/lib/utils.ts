import { clsx, type ClassValue } from "clsx";
import { extendTailwindMerge } from "tailwind-merge";

// Teach tailwind-merge about the custom `brand` color so overrides like
// `bg-white` correctly replace a variant's `bg-brand-600`.
const twMerge = extendTailwindMerge({
  extend: { theme: { color: ["brand-50", "brand-100", "brand-200", "brand-300", "brand-400", "brand-500", "brand-600", "brand-700", "brand-800", "brand-900", "brand-950"] } },
});

/** Join class names; later Tailwind classes override conflicting earlier ones. */
export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

export function initials(name: string) {
  return name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]!.toUpperCase())
    .join("");
}

export function slugify(value: string) {
  return value
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
}

export function formatRate(rate: number) {
  return rate === 0 ? "Free" : `₹${rate.toLocaleString("en-IN")}/hr`;
}

export function averageRating(ratings: number[]) {
  if (ratings.length === 0) return null;
  return Math.round((ratings.reduce((a, b) => a + b, 0) / ratings.length) * 10) / 10;
}
