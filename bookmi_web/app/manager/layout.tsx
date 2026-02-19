'use client';

import { useState } from 'react';
import Link from 'next/link';
import { usePathname, useRouter } from 'next/navigation';
import { Users, BookCheck, MessageSquare, LogOut, Menu, X } from 'lucide-react';
import { useAuthStore } from '@/lib/store/auth';
import { authApi } from '@/lib/api/endpoints';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
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
  const [sidebarOpen, setSidebarOpen] = useState(false);

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
    <div
      className="flex h-screen"
      style={{
        background:
          'linear-gradient(135deg, #dbeafe 0%, #e8e4ff 30%, #ddf4ff 65%, #d1fae5 100%)',
      }}
    >
      {/* ── Mobile overlay ── */}
      {sidebarOpen && (
        <div
          className="fixed inset-0 z-40 md:hidden"
          style={{ background: 'rgba(0,0,0,0.45)' }}
          onClick={() => setSidebarOpen(false)}
        />
      )}

      {/* ── Sidebar — Brand Navy #1A2744 ── */}
      <aside
        className={cn(
          'fixed md:relative z-50 md:z-auto w-64 flex flex-col h-full',
          'transition-transform duration-300 ease-in-out',
          sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'
        )}
        style={{
          background: 'linear-gradient(180deg, #1A2744 0%, #0F1E3A 100%)',
          borderRight: '1px solid rgba(255,255,255,0.06)',
        }}
      >
        {/* Logo */}
        <div className="px-6 py-5 flex items-center justify-between">
          <div className="flex items-center">
            <span className="font-extrabold text-2xl text-white tracking-tight leading-none">
              Book
            </span>
            <span
              className="font-extrabold text-2xl tracking-tight leading-none"
              style={{ color: '#2196F3' }}
            >
              Mi
            </span>
          </div>
          <button
            className="md:hidden text-white/70 hover:text-white"
            onClick={() => setSidebarOpen(false)}
          >
            <X size={20} />
          </button>
        </div>

        <div style={{ height: 1, background: 'rgba(255,255,255,0.10)', margin: '0 1rem' }} />

        {/* Manager badge */}
        <div className="px-6 py-2">
          <span
            className="text-xs font-semibold uppercase tracking-widest"
            style={{ color: 'rgba(33,150,243,0.75)' }}
          >
            Manager
          </span>
        </div>

        {/* Nav */}
        <nav className="flex-1 p-4 space-y-1 overflow-y-auto">
          {navItems.map(({ href, label, icon: Icon }) => {
            const isActive =
              pathname === href || pathname.startsWith(href + '/');
            return (
              <Link
                key={href}
                href={href}
                onClick={() => setSidebarOpen(false)}
                className={cn(
                  'flex items-center gap-3 rounded-lg text-sm font-medium transition-all duration-150',
                  isActive
                    ? 'text-white font-semibold'
                    : 'text-white/65 hover:text-white'
                )}
                style={
                  isActive
                    ? {
                        background:
                          'linear-gradient(90deg, rgba(33,150,243,0.22), rgba(33,150,243,0.08))',
                        borderLeft: '3px solid #2196F3',
                        padding: '0.625rem 0.75rem 0.625rem calc(0.75rem - 3px)',
                      }
                    : { padding: '0.625rem 0.75rem' }
                }
                onMouseEnter={(e) => {
                  if (!isActive)
                    (e.currentTarget as HTMLElement).style.background =
                      'rgba(255,255,255,0.06)';
                }}
                onMouseLeave={(e) => {
                  if (!isActive)
                    (e.currentTarget as HTMLElement).style.background = '';
                }}
              >
                <Icon
                  size={18}
                  style={{ color: isActive ? '#2196F3' : 'rgba(255,255,255,0.40)' }}
                />
                {label}
              </Link>
            );
          })}
        </nav>

        <div style={{ height: 1, background: 'rgba(255,255,255,0.10)', margin: '0 1rem 0.75rem' }} />

        {/* User + Logout */}
        <div className="p-4 space-y-3">
          <div className="flex items-center gap-3">
            <Avatar className="h-9 w-9 flex-shrink-0">
              <AvatarFallback
                className="text-xs font-bold"
                style={{
                  background: 'rgba(33,150,243,0.22)',
                  color: '#ffffff',
                  border: '1.5px solid rgba(33,150,243,0.4)',
                }}
              >
                {initials}
              </AvatarFallback>
            </Avatar>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-semibold text-white truncate">
                {displayName}
              </p>
              <p className="text-xs truncate" style={{ color: 'rgba(255,255,255,0.45)' }}>
                Manager
              </p>
            </div>
          </div>
          <button
            onClick={handleLogout}
            className="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-150"
            style={{
              color: 'rgba(252,165,165,0.85)',
              border: '1px solid rgba(239,68,68,0.25)',
              background: 'rgba(239,68,68,0.06)',
            }}
            onMouseEnter={(e) => {
              (e.currentTarget as HTMLElement).style.background =
                'rgba(239,68,68,0.14)';
              (e.currentTarget as HTMLElement).style.borderColor =
                'rgba(239,68,68,0.45)';
            }}
            onMouseLeave={(e) => {
              (e.currentTarget as HTMLElement).style.background =
                'rgba(239,68,68,0.06)';
              (e.currentTarget as HTMLElement).style.borderColor =
                'rgba(239,68,68,0.25)';
            }}
          >
            <LogOut size={15} />
            Déconnexion
          </button>
        </div>
      </aside>

      {/* ── Main content ── */}
      <div className="flex-1 flex flex-col overflow-hidden min-w-0">
        {/* Header — glassmorphism */}
        <header
          className="flex-shrink-0 px-4 md:px-8 py-4 flex items-center justify-between"
          style={{
            background: 'rgba(255,255,255,0.85)',
            backdropFilter: 'blur(20px) saturate(180%)',
            WebkitBackdropFilter: 'blur(20px) saturate(180%)',
            borderBottom: '1px solid rgba(26,39,68,0.08)',
            boxShadow: '0 1px 8px rgba(26,39,68,0.06)',
          }}
        >
          <div className="flex items-center gap-3">
            <button
              className="md:hidden text-gray-600 hover:text-gray-900 p-1"
              onClick={() => setSidebarOpen(true)}
            >
              <Menu size={22} />
            </button>
            <div>
              <h2 className="text-sm text-gray-500">
                Espace manager —{' '}
                <span className="font-semibold text-gray-900">{displayName}</span>
              </h2>
            </div>
          </div>
          <Avatar className="h-8 w-8">
            <AvatarFallback
              className="text-xs font-bold text-white"
              style={{ background: 'linear-gradient(135deg, #1A2744, #2196F3)' }}
            >
              {initials}
            </AvatarFallback>
          </Avatar>
        </header>

        {/* Page content */}
        <main className="flex-1 overflow-auto p-4 md:p-8">{children}</main>
      </div>
    </div>
  );
}
