import { NextRequest, NextResponse } from "next/server";

const PUBLIC_EXACT = new Set(["/login", "/", "/register"]);

export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl;

  // Allow exact public routes
  if (PUBLIC_EXACT.has(pathname)) return NextResponse.next();

  // Allow public talent directory
  if (pathname.startsWith("/talents")) return NextResponse.next();

  // Check auth via cookie (token stored by zustand persist in localStorage,
  // but for SSR protection we use a cookie set at login)
  const token = request.cookies.get("bookmi_token")?.value;

  if (!token) {
    const loginUrl = new URL("/login", request.url);
    loginUrl.searchParams.set("redirect", pathname);
    return NextResponse.redirect(loginUrl);
  }

  return NextResponse.next();
}

export const config = {
  matcher: [
    "/((?!_next/static|_next/image|favicon.ico|.*\\.(?:svg|png|jpg|jpeg|gif|webp)$).*)",
  ],
};
