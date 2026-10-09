import type { ReactNode } from "react";
import CountUp from "@/components/reactbits/CountUp";
import { Card } from "@/components/ui";

// Kept out of ui.tsx so only pages with stats load the count-up animation.
export function StatCard({ label, value, icon, hint }: { label: string; value: ReactNode; icon: ReactNode; hint?: string }) {
  return (
    <Card className="p-5">
      <div className="flex items-center justify-between">
        <p className="text-sm font-medium text-slate-500">{label}</p>
        <span className="text-slate-400">{icon}</span>
      </div>
      <p className="mt-2 text-3xl font-semibold tracking-tight text-slate-900">
        {typeof value === "number" ? <CountUp to={value} duration={1.2} /> : value}
      </p>
      {hint && <p className="mt-1 text-xs text-slate-500">{hint}</p>}
    </Card>
  );
}
