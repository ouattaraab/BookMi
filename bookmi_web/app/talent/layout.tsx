'use client';

import { useState } from 'react';
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
  Menu,
  X,
} from 'lucide-react';
import { useAuthStore } from '@/lib/store/auth';
import { authApi } from '@/lib/api/endpoints';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
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
    : 'U';

  const displayName = user
    ? `${user.first_name} ${user.last_name}`
    : 'Utilisateur';

  const stageName = user?.talentProfile?.stage_name;

  return (
    <div
      className="flex h-screen"
      style={{
        background:
          'linear-gradient(135deg, #fff3e0 0%, #ffe8d6 25%, #fef3e2 60%, #fff8f0 100%)',
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

      {/* ── Sidebar — Brand Orange #FF6B35 iOS 26 glassmorphism ── */}
      <aside
        className={cn(
          'fixed md:relative z-50 md:z-auto w-64 flex flex-col h-full',
          'transition-transform duration-300 ease-in-out',
          sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'
        )}
        style={{
          background: 'linear-gradient(180deg, #FF6B35 0%, #C85A20 100%)',
          borderRight: '1px solid rgba(255,255,255,0.12)',
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
              style={{ color: 'rgba(255,235,180,0.95)' }}
            >
              Mi
            </span>
          </div>
          {/* Close button (mobile only) */}
          <button
            className="md:hidden text-white/70 hover:text-white"
            onClick={() => setSidebarOpen(false)}
          >
            <X size={20} />
          </button>
        </div>

        <div style={{ height: 1, background: 'rgba(255,255,255,0.15)', margin: '0 1rem 0.5rem' }} />

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
                    : 'text-white/75 hover:text-white'
                )}
                style={
                  isActive
                    ? {
                        background:
                          'linear-gradient(90deg, rgba(255,255,255,0.25), rgba(255,255,255,0.10))',
                        borderLeft: '3px solid rgba(255,255,255,0.9)',
                        padding: '0.625rem 0.75rem 0.625rem calc(0.75rem - 3px)',
                      }
                    : {
                        padding: '0.625rem 0.75rem',
                      }
                }
                onMouseEnter={(e) => {
                  if (!isActive)
                    (e.currentTarget as HTMLElement).style.background =
                      'rgba(255,255,255,0.10)';
                }}
                onMouseLeave={(e) => {
                  if (!isActive)
                    (e.currentTarget as HTMLElement).style.background = '';
                }}
              >
                <Icon
                  size={18}
                  className={isActive ? 'text-white' : 'text-white/50'}
                />
                {label}
              </Link>
            );
          })}
        </nav>

        <div style={{ height: 1, background: 'rgba(255,255,255,0.15)', margin: '0 1rem 0.75rem' }} />

        {/* User + Logout */}
        <div className="p-4 space-y-3">
          <div className="flex items-center gap-3">
            <Avatar className="h-9 w-9 flex-shrink-0">
              <AvatarFallback
                className="text-xs font-bold"
                style={{
                  background: 'rgba(255,255,255,0.25)',
                  color: '#ffffff',
                  border: '1.5px solid rgba(255,255,255,0.4)',
                }}
              >
                {initials}
              </AvatarFallback>
            </Avatar>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-semibold text-white truncate">
                {stageName ?? displayName}
              </p>
              <p className="text-xs truncate" style={{ color: 'rgba(255,255,255,0.55)' }}>
                Talent
              </p>
            </div>
          </div>
          <button
            onClick={handleLogout}
            className="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-150"
            style={{
              color: 'rgba(255,220,200,0.85)',
              border: '1px solid rgba(255,255,255,0.20)',
              background: 'rgba(255,255,255,0.06)',
            }}
            onMouseEnter={(e) => {
              (e.currentTarget as HTMLElement).style.background =
                'rgba(255,255,255,0.15)';
              (e.currentTarget as HTMLElement).style.borderColor =
                'rgba(255,255,255,0.40)';
            }}
            onMouseLeave={(e) => {
              (e.currentTarget as HTMLElement).style.background =
                'rgba(255,255,255,0.06)';
              (e.currentTarget as HTMLElement).style.borderColor =
                'rgba(255,255,255,0.20)';
            }}
          >
            <LogOut size={15} />
            Déconnexion
          </button>
        </div>
      </aside>

      {/* ── Main content ── */}
      <div className="flex-1 flex flex-col overflow-hidden min-w-0">
        {/* Header — iOS 26 glassmorphism */}
        <header
          className="flex-shrink-0 px-4 md:px-8 py-4 flex items-center justify-between"
          style={{
            background: 'rgba(255,255,255,0.82)',
            backdropFilter: 'blur(20px) saturate(180%)',
            WebkitBackdropFilter: 'blur(20px) saturate(180%)',
            borderBottom: '1px solid rgba(255,107,53,0.10)',
            boxShadow: '0 1px 8px rgba(255,107,53,0.06)',
          }}
        >
          <div className="flex items-center gap-3">
            {/* Hamburger — mobile only */}
            <button
              className="md:hidden text-gray-600 hover:text-gray-900 p-1"
              onClick={() => setSidebarOpen(true)}
            >
              <Menu size={22} />
            </button>
            <div>
              <h2 className="text-sm text-gray-500">
                Bienvenue,{' '}
                <span className="font-semibold text-gray-900">
                  {stageName ?? displayName}
                </span>
              </h2>
            </div>
          </div>
          <Avatar className="h-8 w-8">
            <AvatarFallback
              className="text-xs font-bold text-white"
              style={{ background: 'linear-gradient(135deg, #FF6B35, #C85A20)' }}
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
