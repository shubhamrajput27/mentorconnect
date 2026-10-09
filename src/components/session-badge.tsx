import { Badge } from "@/components/ui";
import type { SessionStatus } from "@/generated/prisma/enums";

const map: Record<SessionStatus, { label: string; tone: "amber" | "green" | "red" | "gray" | "blue" }> = {
  PENDING: { label: "Awaiting confirmation", tone: "amber" },
  CONFIRMED: { label: "Confirmed", tone: "green" },
  DECLINED: { label: "Declined", tone: "red" },
  CANCELLED: { label: "Cancelled", tone: "gray" },
  COMPLETED: { label: "Completed", tone: "blue" },
};

export function SessionBadge({ status }: { status: SessionStatus }) {
  const { label, tone } = map[status];
  return <Badge tone={tone}>{label}</Badge>;
}
