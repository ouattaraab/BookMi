'use client';

import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import Link from 'next/link';
import { bookingApi } from '@/lib/api/endpoints';
import { BookOpen, ChevronRight } from 'lucide-react';

type Booking = {
  id: number;
  status: string;
  event_date: string;
  event_location?: string;
  talent_profile?: { stage_name: string };
  service_package?: { name: string };
  devis?: { cachet_amount: number };
  created_at: string;
};

const tabs = [
  { key: 'all',       label: 'Toutes' },
  { key: 'pending',   label: 'En attente' },
  { key: 'confirmed', label: 'Confirmées' },
  { key: 'completed', label: 'Terminées' },
  { key: 'cancelled', label: 'Annulées' },
] as const;

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

export default function ClientBookingsPage() {
  const [activeTab, setActiveTab] = useState<string>('all');

  const { data, isLoading } = useQuery({
    queryKey: ['client_bookings', activeTab],
    queryFn: () => {
      const params: Record<string, unknown> = { per_page: 50, sort: '-created_at' };
      if (activeTab !== 'all') params.status = activeTab;
      return bookingApi.list(params);
    },
  });

  const bookings: Booking[] = data?.data?.data ?? [];

  return (
    <div className="space-y-6 max-w-4xl mx-auto">
      <div>
        <h1 className="text-2xl font-extrabold text-gray-900">Mes réservations</h1>
        <p className="text-gray-500 text-sm mt-1">Suivez l&apos;état de vos demandes de réservation</p>
      </div>

      {/* Tabs */}
      <div className="flex gap-2 overflow-x-auto pb-1">
        {tabs.map((t) => (
          <button
            key={t.key}
            onClick={() => setActiveTab(t.key)}
            className="flex-shrink-0 px-4 py-2 rounded-xl text-sm font-semibold transition-all"
            style={
              activeTab === t.key
                ? { background: '#1A2744', color: 'white' }
                : { background: 'rgba(255,255,255,0.7)', color: '#64748b', border: '1px solid rgba(0,0,0,0.08)' }
            }
          >
            {t.label}
          </button>
        ))}
      </div>

      {/* List */}
      {isLoading ? (
        <div className="space-y-3">
          {[...Array(4)].map((_, i) => (
            <div key={i} className="h-20 rounded-2xl bg-white/50 animate-pulse" />
          ))}
        </div>
      ) : bookings.length === 0 ? (
        <div
          className="rounded-2xl py-16 text-center"
          style={{ background: 'rgba(255,255,255,0.75)', border: '1px solid rgba(255,255,255,0.85)' }}
        >
          <BookOpen size={40} className="text-gray-200 mx-auto mb-4" />
          <p className="text-gray-400 text-sm">
            {activeTab === 'all' ? 'Aucune réservation pour le moment.' : `Aucune réservation "${tabs.find(t => t.key === activeTab)?.label}".`}
          </p>
          <Link href="/talents" className="text-sm font-semibold mt-3 inline-block" style={{ color: '#FF6B35' }}>
            Réserver un talent →
          </Link>
        </div>
      ) : (
        <div className="space-y-3">
          {bookings.map((b) => {
            const cfg = statusConfig[b.status] ?? statusConfig.pending;
            return (
              <Link
                key={b.id}
                href={`/client/bookings/${b.id}`}
                className="block rounded-2xl p-5 hover:shadow-md transition-all"
                style={{
                  background: 'rgba(255,255,255,0.82)',
                  backdropFilter: 'blur(12px)',
                  WebkitBackdropFilter: 'blur(12px)',
                  border: '1px solid rgba(255,255,255,0.9)',
                }}
              >
                <div className="flex items-center justify-between gap-4">
                  <div className="flex items-center gap-4 min-w-0">
                    <div
                      className="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-base flex-shrink-0"
                      style={{ background: 'linear-gradient(135deg, #1A2744, #2196F3)' }}
                    >
                      {b.talent_profile?.stage_name?.[0] ?? '?'}
                    </div>
                    <div className="min-w-0">
                      <div className="flex items-center gap-2 flex-wrap">
                        <p className="font-bold text-gray-900 text-sm">{b.talent_profile?.stage_name ?? '—'}</p>
                      </div>
                      <p className="text-xs text-gray-400 mt-0.5">
                        {new Date(b.event_date + 'T12:00:00').toLocaleDateString('fr-FR', {
                          weekday: 'long', day: 'numeric', month: 'long',
                        })}
                      </p>
                      {b.event_location && (
                        <p className="text-xs text-gray-400 truncate max-w-[240px]">{b.event_location}</p>
                      )}
                    </div>
                  </div>

                  <div className="flex items-center gap-3 flex-shrink-0">
                    <div className="text-right hidden sm:block">
                      {b.service_package && (
                        <p className="text-xs text-gray-400">{b.service_package.name}</p>
                      )}
                      {b.devis && (
                        <p className="text-sm font-bold" style={{ color: '#FF6B35' }}>
                          {formatCachet(b.devis.cachet_amount)}
                        </p>
                      )}
                    </div>
                    <span
                      className="text-xs font-semibold px-3 py-1 rounded-full whitespace-nowrap"
                      style={{ background: cfg.bg, color: cfg.color }}
                    >
                      {cfg.label}
                    </span>
                    <ChevronRight size={15} className="text-gray-300" />
                  </div>
                </div>
              </Link>
            );
          })}
        </div>
      )}
    </div>
  );
}
