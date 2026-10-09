import Link from "next/link";
import { LogOut } from "lucide-react";
import { logout } from "@/actions/auth";
import { MobileNav, SidebarNav, type NavItem } from "@/components/app-nav";
import { Logo } from "@/components/logo";
import { SiteFooter, SiteHeader } from "@/components/site-header";
import { Avatar } from "@/components/ui";
import { VerifyBanner } from "@/components/verify-banner";
import { getCurrentUser, type CurrentUser } from "@/lib/auth";
import { db } from "@/lib/db";

async function navFor(user: CurrentUser): Promise<NavItem[]> {
  const [unreadNotifications, unreadMessages, pendingRequests, pendingSessions] = await Promise.all([
    db.notification.count({ where: { userId: user.id, readAt: null } }),
    db.message.count({
      where: {
        readAt: null,
        senderId: { not: user.id },
        connection: { OR: [{ mentorId: user.id }, { menteeId: user.id }] },
      },
    }),
    user.role === "MENTOR" ? db.connection.count({ where: { mentorId: user.id, status: "PENDING" } }) : 0,
    user.role === "MENTOR" ? db.mentorSession.count({ where: { mentorId: user.id, status: "PENDING" } }) : 0,
  ]);

  const items: NavItem[] = [{ href: "/dashboard", label: "Dashboard", icon: "dashboard" }];
  if (user.role !== "MENTOR") items.push({ href: "/mentors", label: "Find mentors", icon: "search" });
  items.push(
    { href: "/connections", label: user.role === "MENTOR" ? "Mentees" : "My mentors", icon: "users", count: pendingRequests },
    { href: "/sessions", label: "Sessions", icon: "calendar", count: pendingSessions },
    { href: "/messages", label: "Messages", icon: "messages", count: unreadMessages },
    { href: "/notifications", label: "Notifications", icon: "bell", count: unreadNotifications },
  );
  if (user.role === "MENTOR") items.push({ href: "/settings/availability", label: "Availability", icon: "clock" });
  items.push({ href: "/settings/profile", label: "Profile settings", icon: "settings" });
  if (user.role === "ADMIN") items.push({ href: "/admin", label: "Admin", icon: "shield" });
  return items;
}

export default async function AppLayout({ children }: { children: React.ReactNode }) {
  const user = await getCurrentUser();

  // Public pages in this group (the mentor directory) still render for visitors.
  if (!user) {
    return (
      <>
        <SiteHeader />
        <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-10 sm:px-6">{children}</main>
        <SiteFooter />
      </>
    );
  }

  const items = await navFor(user);
  const roleLabel = { MENTOR: "Mentor", MENTEE: "Mentee", ADMIN: "Admin" }[user.role];

  const account = (
    <div className="flex items-center gap-3">
      <Avatar user={user} size="sm" />
      <div className="min-w-0 flex-1">
        <p className="truncate text-sm font-medium text-slate-900">{user.name}</p>
        <p className="text-xs text-slate-500">{roleLabel}</p>
      </div>
      <form action={logout}>
        <button type="submit" className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Log out" title="Log out">
          <LogOut className="size-4" />
        </button>
      </form>
    </div>
  );

  return (
    <div className="flex min-h-screen">
      <aside className="fixed inset-y-0 left-0 hidden w-64 flex-col border-r border-slate-200 bg-white px-4 py-5 lg:flex">
        <Logo href="/dashboard" className="px-2" />
        <nav className="mt-8 flex-1 overflow-y-auto">
          <SidebarNav items={items} />
        </nav>
        <div className="border-t border-slate-100 pt-4">{account}</div>
      </aside>

      <div className="flex min-w-0 flex-1 flex-col lg:pl-64">
        <header className="sticky top-0 z-20 flex h-14 items-center justify-between border-b border-slate-200 bg-white/90 px-4 backdrop-blur lg:hidden">
          <MobileNav items={items}>
            <Logo href="/dashboard" />
          </MobileNav>
          <Logo href="/dashboard" />
          <Link href="/settings/profile" aria-label="Profile settings">
            <Avatar user={user} size="sm" />
          </Link>
        </header>
        {!user.emailVerifiedAt && <VerifyBanner email={user.email} />}
        <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-6 lg:px-10">{children}</main>
      </div>
    </div>
  );
}
