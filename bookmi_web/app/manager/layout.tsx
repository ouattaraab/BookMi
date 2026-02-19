'use client';

import Link from 'next/link';
import { usePathname, useRouter } from 'next/navigation';
import { Users, BookCheck, MessageSquare, LogOut } from 'lucide-react';
import { useAuthStore } from '@/lib/store/auth';
import { authApi } from '@/lib/api/endpoints';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';

const navItems = [
  { href: '/manager/talents', label: 'Mes talents', icon: Users },
  { href: '/manager/bookings', label: 'Réservations', icon: BookCheck },
  { href: '/manager/messages', label: 'Messages', icon: MessageSquare },
];

export default function ManagerLayout({
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
    : 'M';

  const displayName = user
    ? `${user.first_name} ${user.last_name}`
    : 'Manager';

  return (
    <div className="flex h-screen bg-gray-50">
      {/* Sidebar */}
      <aside className="w-64 bg-white border-r border-gray-200 flex flex-col">
        {/* Brand */}
        <div className="p-6 flex items-center gap-3">
          <div className="w-9 h-9 rounded-xl bg-amber-500 flex items-center justify-center">
            <span className="text-white font-bold text-lg">B</span>
          </div>
          <div>
            <span className="font-bold text-gray-900 text-lg">BookMi</span>
            <p className="text-xs text-gray-400 -mt-0.5">Manager</p>
          </div>
        </div>

        <Separator />

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
                    ? 'bg-amber-50 text-amber-700 border border-amber-200'
                    : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
                )}
              >
                <Icon
                  size={18}
                  className={isActive ? 'text-amber-600' : 'text-gray-400'}
                />
                {label}
              </Link>
            );
          })}
        </nav>

        <Separator />

        {/* User + Logout */}
        <div className="p-4 space-y-3">
          <div className="flex items-center gap-3">
            <Avatar className="h-9 w-9">
              <AvatarFallback className="bg-amber-100 text-amber-700 text-xs font-semibold">
                {initials}
              </AvatarFallback>
            </Avatar>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium text-gray-900 truncate">
                {displayName}
              </p>
              <p className="text-xs text-gray-500 truncate">{user?.email}</p>
            </div>
          </div>
          <Button
            variant="outline"
            size="sm"
            onClick={handleLogout}
            className="w-full gap-2 text-gray-600 hover:text-red-600 hover:border-red-200"
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
              Espace manager —{' '}
              <span className="font-semibold text-gray-900">{displayName}</span>
            </h2>
          </div>
          <Avatar className="h-8 w-8">
            <AvatarFallback className="bg-amber-100 text-amber-700 text-xs font-semibold">
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
