'use client';

import { useState, useEffect, useRef } from 'react';
import Link from 'next/link';
import { usePathname, useRouter } from 'next/navigation';
import {
  LayoutDashboard, Search, BookOpen, Heart, MessageSquare,
  LogOut, Menu, X, Bell, CheckCheck, Settings,
} from 'lucide-react';
import { useAuthStore } from '@/lib/store/auth';
import { authApi, notificationApi } from '@/lib/api/endpoints';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import Image from 'next/image';
import { cn } from '@/lib/utils';

const navItems = [
  { href: '/client/dashboard', label: 'Tableau de bord', icon: LayoutDashboard },
  { href: '/talents', label: 'Découvrir', icon: Search },
  { href: '/client/bookings', label: 'Mes réservations', icon: BookOpen },
  { href: '/client/favorites', label: 'Favoris', icon: Heart },
  { href: '/client/messages', label: 'Messages', icon: MessageSquare },
  { href: '/client/settings', label: 'Paramètres', icon: Settings },
];

type Notification = {
  id: number;
  type: string;
  data: { message?: string; title?: string };
  read_at: string | null;
  created_at: string;
};

export default function ClientLayout({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();
  const router = useRouter();
  const user = useAuthStore((s) => s.user);
  const clearAuth = useAuthStore((s) => s.clearAuth);
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [notifOpen, setNotifOpen] = useState(false);
  const notifRef = useRef<HTMLDivElement>(null);
  const qc = useQueryClient();

  const initials = user
    ? `${user.first_name?.[0] ?? ''}${user.last_name?.[0] ?? ''}`.toUpperCase()
    : 'C';
  const displayName = user ? `${user.first_name} ${user.last_name}` : 'Client';

  const handleLogout = async () => {
    try { await authApi.logout(); } catch { /* ignore */ }
    clearAuth();
    document.cookie = 'bookmi_token=; path=/; expires=Thu, 01 Jan 1970 00:00:01 GMT';
    router.push('/login');
  };

  // ── Notifications (poll every 30s) ────────────────────────────────────────
  const { data: notifData } = useQuery({
    queryKey: ['client_notifications'],
    queryFn: () => notificationApi.list(),
    refetchInterval: 30_000,
  });

  const notifications: Notification[] = notifData?.data?.data ?? [];
  const unreadCount = notifications.filter((n) => !n.read_at).length;

  const markReadMutation = useMutation({
    mutationFn: (id: number) => notificationApi.markRead(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['client_notifications'] }),
  });

  const markAllReadMutation = useMutation({
    mutationFn: () => notificationApi.markAllRead(),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['client_notifications'] }),
  });

  // Close notif dropdown on outside click
  useEffect(() => {
    const handler = (e: MouseEvent) => {
      if (notifRef.current && !notifRef.current.contains(e.target as Node)) {
        setNotifOpen(false);
      }
    };
    document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }, []);

  return (
    <div
      className="flex h-screen"
      style={{
        background: 'linear-gradient(135deg, #dbeafe 0%, #e8e4ff 30%, #ddf4ff 65%, #d1fae5 100%)',
      }}
    >
      {/* Mobile overlay */}
      {sidebarOpen && (
        <div
          className="fixed inset-0 z-40 md:hidden"
          style={{ background: 'rgba(0,0,0,0.45)' }}
          onClick={() => setSidebarOpen(false)}
        />
      )}

      {/* ── Sidebar ── */}
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
          <Link href="/" className="flex items-center">
            <span className="font-extrabold text-2xl text-white tracking-tight leading-none">Book</span>
            <span className="font-extrabold text-2xl tracking-tight leading-none" style={{ color: '#64B5F6' }}>Mi</span>
          </Link>
          <button className="md:hidden text-white/70 hover:text-white" onClick={() => setSidebarOpen(false)}>
            <X size={20} />
          </button>
        </div>

        <div style={{ height: 1, background: 'rgba(255,255,255,0.10)', margin: '0 1rem' }} />

        {/* Client badge */}
        <div className="px-6 py-2">
          <span className="text-xs font-semibold uppercase tracking-widest" style={{ color: 'rgba(100,181,246,0.75)' }}>
            Espace client
          </span>
        </div>

        {/* Nav */}
        <nav className="flex-1 p-4 space-y-1 overflow-y-auto">
          {navItems.map(({ href, label, icon: Icon }) => {
            const isActive = pathname === href || (href !== '/talents' && pathname.startsWith(href + '/'));
            return (
              <Link
                key={href}
                href={href}
                onClick={() => setSidebarOpen(false)}
                className={cn(
                  'flex items-center gap-3 rounded-lg text-sm font-medium transition-all duration-150',
                  isActive ? 'text-white font-semibold' : 'text-white/65 hover:text-white'
                )}
                style={
                  isActive
                    ? {
                        background: 'linear-gradient(90deg, rgba(100,181,246,0.22), rgba(100,181,246,0.08))',
                        borderLeft: '3px solid #64B5F6',
                        padding: '0.625rem 0.75rem 0.625rem calc(0.75rem - 3px)',
                      }
                    : { padding: '0.625rem 0.75rem' }
                }
                onMouseEnter={(e) => {
                  if (!isActive) (e.currentTarget as HTMLElement).style.background = 'rgba(255,255,255,0.06)';
                }}
                onMouseLeave={(e) => {
                  if (!isActive) (e.currentTarget as HTMLElement).style.background = '';
                }}
              >
                <Icon size={18} style={{ color: isActive ? '#64B5F6' : 'rgba(255,255,255,0.40)' }} />
                {label}
              </Link>
            );
          })}
        </nav>

        <div style={{ height: 1, background: 'rgba(255,255,255,0.10)', margin: '0 1rem 0.75rem' }} />

        {/* User + Logout */}
        <div className="p-4 space-y-3">
          <div className="flex items-center gap-3">
            <div
              className="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
              style={{ background: 'rgba(100,181,246,0.25)', border: '1.5px solid rgba(100,181,246,0.4)' }}
            >
              {initials}
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-semibold text-white truncate">{displayName}</p>
              <p className="text-xs truncate" style={{ color: 'rgba(255,255,255,0.45)' }}>Client</p>
            </div>
          </div>
          <button
            onClick={handleLogout}
            className="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-all"
            style={{
              color: 'rgba(252,165,165,0.85)',
              border: '1px solid rgba(239,68,68,0.25)',
              background: 'rgba(239,68,68,0.06)',
            }}
            onMouseEnter={(e) => {
              (e.currentTarget as HTMLElement).style.background = 'rgba(239,68,68,0.14)';
            }}
            onMouseLeave={(e) => {
              (e.currentTarget as HTMLElement).style.background = 'rgba(239,68,68,0.06)';
            }}
          >
            <LogOut size={15} /> Déconnexion
          </button>
        </div>
      </aside>

      {/* ── Main content ── */}
      <div className="flex-1 flex flex-col overflow-hidden min-w-0">
        {/* Header */}
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
            <button className="md:hidden text-gray-600 hover:text-gray-900 p-1" onClick={() => setSidebarOpen(true)}>
              <Menu size={22} />
            </button>
            <Link href="/">
              <Image src="/logo.png" alt="BookMi" width={85} height={26} className="md:hidden" />
            </Link>
            <div className="hidden md:block">
              <h2 className="text-sm text-gray-500">
                Espace client —{' '}
                <span className="font-semibold text-gray-900">{displayName}</span>
              </h2>
            </div>
          </div>

          <div className="flex items-center gap-3">
            {/* Notification bell */}
            <div className="relative" ref={notifRef}>
              <button
                onClick={() => setNotifOpen(!notifOpen)}
                className="relative p-2 rounded-xl text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-colors"
              >
                <Bell size={19} />
                {unreadCount > 0 && (
                  <span
                    className="absolute -top-0.5 -right-0.5 w-4 h-4 rounded-full flex items-center justify-center text-white text-[10px] font-bold"
                    style={{ background: '#FF6B35' }}
                  >
                    {unreadCount > 9 ? '9+' : unreadCount}
                  </span>
                )}
              </button>

              {/* Notifications dropdown */}
              {notifOpen && (
                <div
                  className="absolute right-0 top-12 w-80 rounded-2xl bg-white shadow-2xl border border-gray-100 overflow-hidden z-50"
                  style={{ boxShadow: '0 16px 48px rgba(0,0,0,0.15)' }}
                >
                  <div className="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                    <p className="text-sm font-bold text-gray-900">Notifications</p>
                    {unreadCount > 0 && (
                      <button
                        onClick={() => markAllReadMutation.mutate()}
                        className="text-xs font-semibold flex items-center gap-1"
                        style={{ color: '#2196F3' }}
                      >
                        <CheckCheck size={12} /> Tout lire
                      </button>
                    )}
                  </div>
                  <div className="max-h-72 overflow-y-auto">
                    {notifications.length === 0 ? (
                      <p className="text-sm text-gray-400 text-center py-8">Aucune notification</p>
                    ) : (
                      notifications.slice(0, 15).map((n) => (
                        <button
                          key={n.id}
                          onClick={() => markReadMutation.mutate(n.id)}
                          className="w-full text-left px-4 py-3 border-b border-gray-50 hover:bg-gray-50 transition-colors"
                        >
                          <div className="flex items-start gap-2">
                            {!n.read_at && (
                              <span className="w-2 h-2 rounded-full mt-1.5 flex-shrink-0" style={{ background: '#FF6B35' }} />
                            )}
                            <div className={!n.read_at ? '' : 'ml-4'}>
                              <p className="text-xs font-semibold text-gray-800">
                                {n.data?.title ?? n.type.replace(/_/g, ' ')}
                              </p>
                              {n.data?.message && (
                                <p className="text-xs text-gray-500 mt-0.5 line-clamp-2">{n.data.message}</p>
                              )}
                              <p className="text-[10px] text-gray-300 mt-1">
                                {new Date(n.created_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })}
                              </p>
                            </div>
                          </div>
                        </button>
                      ))
                    )}
                  </div>
                </div>
              )}
            </div>

            {/* Avatar */}
            <div
              className="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white"
              style={{ background: 'linear-gradient(135deg, #1A2744, #64B5F6)' }}
            >
              {initials}
            </div>
          </div>
        </header>

        {/* Page content */}
        <main className="flex-1 overflow-auto p-4 md:p-8 client-content">{children}</main>
      </div>
    </div>
  );
}
