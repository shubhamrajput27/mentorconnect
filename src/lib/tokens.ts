import "server-only";
import { createHash, randomBytes } from "node:crypto";
import { db } from "@/lib/db";
import type { TokenType } from "@/generated/prisma/enums";

const LIFETIME_MS: Record<TokenType, number> = {
  EMAIL_VERIFY: 24 * 60 * 60 * 1000,
  PASSWORD_RESET: 60 * 60 * 1000,
};

export const hashToken = (token: string) => createHash("sha256").update(token).digest("hex");

/** Create a one-time token, replacing any unused ones of the same type. Returns the raw token. */
export async function issueToken(userId: string, type: TokenType) {
  const token = randomBytes(32).toString("base64url");
  await db.$transaction([
    db.userToken.deleteMany({ where: { userId, type, usedAt: null } }),
    db.userToken.create({
      data: { userId, type, tokenHash: hashToken(token), expiresAt: new Date(Date.now() + LIFETIME_MS[type]) },
    }),
  ]);
  return token;
}

/** Look up a valid (unused, unexpired) token without consuming it. */
export async function findToken(token: string, type: TokenType) {
  if (!token) return null;
  const record = await db.userToken.findUnique({ where: { tokenHash: hashToken(token) } });
  if (!record || record.type !== type || record.usedAt || record.expiresAt < new Date()) return null;
  return record;
}

/** Mark a token as used. Returns false if it was already used (e.g. a double submit). */
export async function consumeToken(id: string) {
  const { count } = await db.userToken.updateMany({ where: { id, usedAt: null }, data: { usedAt: new Date() } });
  return count === 1;
}
