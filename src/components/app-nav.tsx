"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useState } from "react";
import { createPortal } from "react-dom";
import {
  Bell,
  CalendarClock,
  CalendarDays,
  LayoutDashboard,
  Menu,
  MessageSquare,
  Search,
  Shield,
  UserCog,
  Users,
  X,
} from "lucide-react";
import { cn } from "@/lib/utils";

const icons = {
  dashboard: LayoutDashboard,
  search: Search,
  users: Users,
  calendar: CalendarDays,
  clock: CalendarClock,
  messages: MessageSquare,
  bell: Bell,
  settings: UserCog,
  shield: Shield,
};

export type NavItem = { href: string; label: string; icon: keyof typeof icons; count?: number };

function NavLinks({ items, onNavigate }: { items: NavItem[]; onNavigate?: () => void }) {
  const pathname = usePathname();
  return (
    <ul className="space-y-1">
      {items.map((item) => {
        const Icon = icons[item.icon];
        const active = pathname === item.href || (item.href !== "/dashboard" && pathname.startsWith(`${item.href}/`));
        return (
          <li key={item.href}>
            <Link
              href={item.href}
              onClick={onNavigate}
              aria-current={active ? "page" : undefined}
              className={cn(
                "group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors",
                active ? "bg-brand-50 text-brand-700" : "text-slate-600 hover:bg-slate-100 hover:text-slate-900",
              )}
            >
              <Icon className={cn("size-5", active ? "text-brand-600" : "text-slate-400 group-hover:text-slate-600")} />
              <span className="flex-1">{item.label}</span>
              {!!item.count && (
                <span className="rounded-full bg-brand-600 px-2 py-0.5 text-xs font-semibold text-white">
                  {item.count > 99 ? "99+" : item.count}
                </span>
              )}
            </Link>
          </li>
        );
      })}
    </ul>
  );
}

export function SidebarNav({ items }: { items: NavItem[] }) {
  return <NavLinks items={items} />;
}

export function MobileNav({ items, children }: { items: NavItem[]; children: React.ReactNode }) {
  const [open, setOpen] = useState(false);
  return (
    <>
      <button
        type="button"
        onClick={() => setOpen(true)}
        className="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden"
        aria-label="Open menu"
      >
        <Menu className="size-5" />
      </button>
      {/* Portal: the sticky header's backdrop-blur would otherwise trap this fixed overlay. */}
      {open &&
        createPortal(
          <div className="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true">
            <div className="absolute inset-0 bg-slate-900/40" onClick={() => setOpen(false)} />
            <div className="absolute inset-y-0 left-0 flex w-72 flex-col bg-white p-4 shadow-xl">
              <div className="mb-6 flex items-center justify-between">
                {children}
                <button
                  type="button"
                  onClick={() => setOpen(false)}
                  className="rounded-lg p-2 text-slate-500 hover:bg-slate-100"
                  aria-label="Close menu"
                >
                  <X className="size-5" />
                </button>
              </div>
              <NavLinks items={items} onNavigate={() => setOpen(false)} />
            </div>
          </div>,
          document.body,
        )}
    </>
  );
}
