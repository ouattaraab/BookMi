'use client';

import { useQuery } from '@tanstack/react-query';
import Link from 'next/link';
import { bookingApi, favoriteApi } from '@/lib/api/endpoints';
import { useAuthStore } from '@/lib/store/auth';
import { BookOpen, Heart, Clock, ChevronRight, Search, Star } from 'lucide-react';

type Booking = {
  id: number;
  status: string;
  event_date: string;
  event_location?: string;
  talent?: { stage_name: string; category?: { name: string } };
  service_package?: { name: string; cachet_amount: number };
};

const statusConfig: Record<string, { label: string; color: string; bg: string }> = {
  pending:   { label: 'En attente',  color: '#F59E0B', bg: 'rgba(245,158,11,0.1)' },
  confirmed: { label: 'Confirmée',   color: '#2196F3', bg: 'rgba(33,150,243,0.1)' },
  completed: { label: 'Terminée',    color: '#10B981', bg: 'rgba(16,185,129,0.1)' },
  cancelled: { label: 'Annulée',     color: '#EF4444', bg: 'rgba(239,68,68,0.1)' },
  rejected:  { label: 'Refusée',     color: '#6B7280', bg: 'rgba(107,114,128,0.1)' },
};

function formatCachet(c: number) {
  return new Intl.NumberFormat('fr-FR').format(Math.round(c / 100)) + ' FCFA';
}

export default function ClientDashboardPage() {
  const user = useAuthStore((s) => s.user);

  const { data: bookingsData, isLoading: bookingsLoading } = useQuery({
    queryKey: ['client_bookings_dashboard'],
    queryFn: () => bookingApi.list({ per_page: 10, sort: '-created_at' }),
  });

  const { data: favsData } = useQuery({
    queryKey: ['client_favorites_count'],
    queryFn: () => favoriteApi.list({ per_page: 1 }),
  });

  const allBookings: Booking[] = bookingsData?.data?.data ?? [];
  const pending = allBookings.filter((b) => b.status === 'pending');
  const upcoming = allBookings.filter((b) => b.status === 'confirmed');
  const favCount = favsData?.data?.meta?.total ?? favsData?.data?.data?.length ?? 0;
  const nextBookings = upcoming.slice(0, 3);

  const today = new Date().toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' });

  return (
    <div className="space-y-6 max-w-5xl mx-auto">
      {/* Greeting */}
      <div>
        <h1 className="text-2xl font-extrabold text-gray-900">
          Bonjour, {user?.first_name} 👋
        </h1>
        <p className="text-gray-500 text-sm mt-1 capitalize">{today}</p>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        {[
          {
            icon: Clock,
            color: '#F59E0B',
            bg: 'rgba(245,158,11,0.08)',
            border: 'rgba(245,158,11,0.15)',
            value: pending.length,
            label: 'En attente de réponse',
          },
          {
            icon: BookOpen,
            color: '#2196F3',
            bg: 'rgba(33,150,243,0.08)',
            border: 'rgba(33,150,243,0.15)',
            value: upcoming.length,
            label: 'Réservations confirmées',
          },
          {
            icon: Heart,
            color: '#EC4899',
            bg: 'rgba(236,72,153,0.08)',
            border: 'rgba(236,72,153,0.15)',
            value: favCount,
            label: 'Talents favoris',
          },
        ].map((s) => (
          <div
            key={s.label}
            className="rounded-2xl p-5 transition-all hover:-translate-y-0.5"
            style={{
              background: 'rgba(255,255,255,0.75)',
              backdropFilter: 'blur(16px)',
              WebkitBackdropFilter: 'blur(16px)',
              border: `1px solid ${s.border}`,
              boxShadow: '0 4px 20px rgba(0,0,0,0.05)',
            }}
          >
            <div className="flex items-center justify-between mb-3">
              <div className="w-10 h-10 rounded-xl flex items-center justify-center" style={{ background: s.bg }}>
                <s.icon size={18} style={{ color: s.color }} />
              </div>
            </div>
            <p className="text-3xl font-extrabold text-gray-900">{s.value}</p>
            <p className="text-sm text-gray-500 mt-1">{s.label}</p>
          </div>
        ))}
      </div>

      {/* Discover banner */}
      <div
        className="rounded-2xl p-6 flex items-center justify-between gap-4"
        style={{
          background: 'linear-gradient(135deg, #1A2744 0%, #2196F3 100%)',
          boxShadow: '0 8px 32px rgba(33,150,243,0.25)',
        }}
      >
        <div>
          <p className="text-lg font-extrabold text-white">Découvrez de nouveaux talents</p>
          <p className="text-sm text-blue-200 mt-1">500+ artistes vérifiés disponibles</p>
        </div>
        <Link
          href="/talents"
          className="flex-shrink-0 flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-white/20 hover:bg-white/30 transition-colors"
        >
          <Search size={15} /> Explorer
        </Link>
      </div>

      {/* Next bookings */}
      <div
        className="rounded-2xl"
        style={{
          background: 'rgba(255,255,255,0.75)',
          backdropFilter: 'blur(16px)',
          WebkitBackdropFilter: 'blur(16px)',
          border: '1px solid rgba(255,255,255,0.85)',
          boxShadow: '0 4px 20px rgba(0,0,0,0.05)',
        }}
      >
        <div className="flex items-center justify-between px-6 py-4 border-b border-gray-100">
          <h2 className="font-extrabold text-gray-900">Prochaines réservations</h2>
          <Link
            href="/client/bookings"
            className="text-xs font-semibold flex items-center gap-1 hover:underline"
            style={{ color: '#2196F3' }}
          >
            Voir tout <ChevronRight size={13} />
          </Link>
        </div>

        {bookingsLoading ? (
          <div className="p-6 space-y-3">
            {[...Array(3)].map((_, i) => (
              <div key={i} className="h-14 rounded-xl bg-gray-100 animate-pulse" />
            ))}
          </div>
        ) : nextBookings.length === 0 ? (
          <div className="py-12 text-center">
            <BookOpen size={36} className="text-gray-200 mx-auto mb-3" />
            <p className="text-gray-400 text-sm">Aucune réservation confirmée.</p>
            <Link
              href="/talents"
              className="text-sm font-semibold mt-3 inline-block"
              style={{ color: '#FF6B35' }}
            >
              Réserver un talent →
            </Link>
          </div>
        ) : (
          <div className="divide-y divide-gray-50">
            {nextBookings.map((b) => {
              const cfg = statusConfig[b.status] ?? statusConfig.pending;
              return (
                <Link
                  key={b.id}
                  href={`/client/bookings/${b.id}`}
                  className="flex items-center justify-between px-6 py-4 hover:bg-gray-50/50 transition-colors"
                >
                  <div className="flex items-center gap-4">
                    <div
                      className="w-10 h-10 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                      style={{ background: 'linear-gradient(135deg, #1A2744, #2196F3)' }}
                    >
                      {b.talent?.stage_name?.[0] ?? '?'}
                    </div>
                    <div>
                      <p className="font-semibold text-sm text-gray-900">{b.talent?.stage_name ?? '—'}</p>
                      <p className="text-xs text-gray-400">
                        {new Date(b.event_date + 'T12:00:00').toLocaleDateString('fr-FR', {
                          weekday: 'short', day: 'numeric', month: 'short',
                        })}
                        {b.service_package && ` · ${b.service_package.name}`}
                      </p>
                    </div>
                  </div>
                  <div className="flex items-center gap-3">
                    <span
                      className="text-xs font-semibold px-2.5 py-1 rounded-full"
                      style={{ background: cfg.bg, color: cfg.color }}
                    >
                      {cfg.label}
                    </span>
                    <ChevronRight size={15} className="text-gray-300" />
                  </div>
                </Link>
              );
            })}
          </div>
        )}
      </div>

      {/* Recent bookings table */}
      {allBookings.filter((b) => b.status === 'completed').length > 0 && (
        <div
          className="rounded-2xl"
          style={{
            background: 'rgba(255,255,255,0.75)',
            backdropFilter: 'blur(16px)',
            WebkitBackdropFilter: 'blur(16px)',
            border: '1px solid rgba(255,255,255,0.85)',
            boxShadow: '0 4px 20px rgba(0,0,0,0.05)',
          }}
        >
          <div className="px-6 py-4 border-b border-gray-100">
            <h2 className="font-extrabold text-gray-900">Prestations récentes</h2>
          </div>
          <div className="divide-y divide-gray-50">
            {allBookings
              .filter((b) => b.status === 'completed')
              .slice(0, 3)
              .map((b) => (
                <Link
                  key={b.id}
                  href={`/client/bookings/${b.id}`}
                  className="flex items-center justify-between px-6 py-4 hover:bg-gray-50/50 transition-colors"
                >
                  <div>
                    <p className="font-semibold text-sm text-gray-900">{b.talent?.stage_name ?? '—'}</p>
                    <p className="text-xs text-gray-400">
                      {new Date(b.event_date + 'T12:00:00').toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })}
                    </p>
                  </div>
                  <div className="flex items-center gap-3">
                    <Star size={14} className="text-amber-400 fill-amber-400" />
                    <span className="text-xs font-semibold px-2.5 py-1 rounded-full" style={{ background: 'rgba(16,185,129,0.1)', color: '#10B981' }}>
                      Terminée
                    </span>
                  </div>
                </Link>
              ))}
          </div>
        </div>
      )}
    </div>
  );
}
