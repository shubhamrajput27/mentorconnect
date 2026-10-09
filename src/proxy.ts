import { NextResponse, type NextRequest } from "next/server";

// Optimistic check only: real authorization happens in pages and actions,
// which validate the session against the database.
const PROTECTED = [
  "/dashboard",
  "/onboarding",
  "/connections",
  "/sessions",
  "/messages",
  "/notifications",
  "/settings",
  "/admin",
];

export function proxy(request: NextRequest) {
  const { pathname, search } = request.nextUrl;
  const hasSession = request.cookies.has("mc_session");

  if (!hasSession && PROTECTED.some((p) => pathname === p || pathname.startsWith(`${p}/`))) {
    const url = new URL("/login", request.url);
    url.searchParams.set("next", pathname + search);
    return NextResponse.redirect(url);
  }
  return NextResponse.next();
}

export const config = {
  matcher: ["/((?!api|_next/static|_next/image|favicon.ico).*)"],
};
