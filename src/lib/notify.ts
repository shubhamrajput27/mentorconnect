import "server-only";
import { db } from "@/lib/db";

export function notify(userId: string, title: string, body: string, link?: string) {
  return db.notification.create({ data: { userId, title, body, link } });
}
