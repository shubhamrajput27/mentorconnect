import { describe, expect, it } from "vitest";
import { availabilitySchema, fieldErrors, profileSchema, signupSchema } from "./validation";

describe("signupSchema", () => {
  const valid = { name: "Aarav Patel", email: "  Aarav@Example.com ", password: "secret123", role: "MENTEE" };

  it("normalises email", () => {
    expect(signupSchema.parse(valid).email).toBe("aarav@example.com");
  });

  it("rejects weak passwords with a helpful message", () => {
    const result = signupSchema.safeParse({ ...valid, password: "password" });
    expect(result.success).toBe(false);
    expect(fieldErrors(result.error!)).toEqual({ password: "Include at least one number." });
  });

  it("does not allow signing up as admin", () => {
    expect(signupSchema.safeParse({ ...valid, role: "ADMIN" }).success).toBe(false);
  });
});

describe("profileSchema", () => {
  it("dedupes skills and turns empty optional fields into null", () => {
    const parsed = profileSchema.parse({
      name: "Aarav",
      headline: "Student",
      bio: "",
      location: "",
      timezone: "Asia/Kolkata",
      goals: "",
      skills: ["React", "React", "Next.js"],
    });
    expect(parsed.skills).toEqual(["React", "Next.js"]);
    expect(parsed.bio).toBeNull();
  });
});

describe("availabilitySchema", () => {
  it("rejects blocks shorter than 30 minutes", () => {
    expect(availabilitySchema.safeParse([{ dayOfWeek: 1, startMinute: 600, endMinute: 615 }]).success).toBe(false);
    expect(availabilitySchema.safeParse([{ dayOfWeek: 1, startMinute: 600, endMinute: 660 }]).success).toBe(true);
  });
});
