'use client';

import Link from 'next/link';
import { usePathname, useRouter } from 'next/navigation';
import {
  LayoutDashboard,
  Calendar,
  BookCheck,
  Package,
  MessageSquare,
  BarChart3,
  FileText,
  LogOut,
} from 'lucide-react';
import { useAuthStore } from '@/lib/store/auth';
import { authApi } from '@/lib/api/endpoints';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

const navItems = [
  { href: '/talent/dashboard', label: 'Dashboard', icon: LayoutDashboard },
  { href: '/talent/calendar', label: 'Calendrier', icon: Calendar },
  { href: '/talent/bookings', label: 'Réservations', icon: BookCheck },
  { href: '/talent/packages', label: 'Packages', icon: Package },
  { href: '/talent/messages', label: 'Messages', icon: MessageSquare },
  { href: '/talent/analytics', label: 'Analytiques', icon: BarChart3 },
  { href: '/talent/certificate', label: 'Attestation', icon: FileText },
];

export default function TalentLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const pathname = usePathname();
  const router = useRouter();
  const user = useAuthStore((s) => s.user);
  const clearAuth = useAuthStore((s) => s.clearAuth);

  const handleLogout = async () => {
    try {
      await authApi.logout();
    } catch {
      // ignore error, always clear local auth
    }
    clearAuth();
    document.cookie =
      'bookmi_token=; path=/; expires=Thu, 01 Jan 1970 00:00:01 GMT';
    router.push('/login');
  };

  const initials = user
    ? `${user.first_name?.[0] ?? ''}${user.last_name?.[0] ?? ''}`.toUpperCase()
    : 'U';

  const displayName = user
    ? `${user.first_name} ${user.last_name}`
    : 'Utilisateur';

  const stageName = user?.talentProfile?.stage_name;

  return (
    <div className="flex h-screen bg-gray-50">
      {/* Sidebar — Brand Navy #1A2744 */}
      <aside className="w-64 bg-[#1A2744] flex flex-col">
        {/* Brand — logo textuel calqué sur le logo officiel */}
        <div className="px-6 py-5 flex items-center">
          <span className="font-extrabold text-2xl text-white tracking-tight">Book</span>
          <span className="font-extrabold text-2xl text-[#2196F3] tracking-tight">Mi</span>
        </div>

        <div className="mx-4 border-t border-white/10" />

        {/* Nav */}
        <nav className="flex-1 p-4 space-y-1">
          {navItems.map(({ href, label, icon: Icon }) => {
            const isActive =
              pathname === href || pathname.startsWith(href + '/');
            return (
              <Link
                key={href}
                href={href}
                className={cn(
                  'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                  isActive
                    ? 'bg-[#2196F3]/15 text-white'
                    : 'text-white/60 hover:bg-white/5 hover:text-white'
                )}
              >
                <Icon
                  size={18}
                  className={isActive ? 'text-[#2196F3]' : 'text-white/40'}
                />
                {label}
              </Link>
            );
          })}
        </nav>

        <div className="mx-4 border-t border-white/10" />

        {/* User + Logout */}
        <div className="p-4 space-y-3">
          <div className="flex items-center gap-3">
            <Avatar className="h-9 w-9">
              <AvatarFallback className="bg-[#FF6B35]/20 text-[#2196F3] text-xs font-semibold">
                {initials}
              </AvatarFallback>
            </Avatar>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium text-white truncate">
                {stageName ?? displayName}
              </p>
              <p className="text-xs text-white/50 truncate">{user?.email}</p>
            </div>
          </div>
          <Button
            variant="ghost"
            size="sm"
            onClick={handleLogout}
            className="w-full gap-2 text-white/60 hover:text-red-400 hover:bg-white/5"
          >
            <LogOut size={15} />
            Déconnexion
          </Button>
        </div>
      </aside>

      {/* Main content */}
      <div className="flex-1 flex flex-col overflow-hidden">
        {/* Header */}
        <header className="bg-white border-b border-gray-200 px-8 py-4 flex items-center justify-between">
          <div>
            <h2 className="text-sm text-gray-500">
              Bienvenue,{' '}
              <span className="font-semibold text-gray-900">
                {stageName ?? displayName}
              </span>
            </h2>
          </div>
          <Avatar className="h-8 w-8">
            <AvatarFallback className="bg-[#FF6B35]/10 text-[#2196F3] text-xs font-semibold">
              {initials}
            </AvatarFallback>
          </Avatar>
        </header>

        {/* Page content */}
        <main className="flex-1 overflow-auto p-8">{children}</main>
      </div>
    </div>
  );
}
