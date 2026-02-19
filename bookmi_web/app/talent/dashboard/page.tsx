'use client';

import { useQuery } from '@tanstack/react-query';
import { bookingApi, analyticsApi } from '@/lib/api/endpoints';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { BookCheck, TrendingUp, Star, Clock } from 'lucide-react';
import Link from 'next/link';

type Booking = {
  id: number;
  status: string;
  event_date: string;
  location: string;
  total_amount: number;
  client?: { email: string; first_name: string; last_name: string };
};

type Analytics = {
  total_revenue: number;
  total_bookings: number;
  average_rating: number;
  monthly_revenue: { month: string; amount: number }[];
};

const STATUS_LABELS: Record<string, string> = {
  pending: 'En attente',
  confirmed: 'Confirmée',
  completed: 'Complétée',
  cancelled: 'Annulée',
  rejected: 'Refusée',
};

const STATUS_COLORS: Record<string, string> = {
  pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',
  confirmed: 'bg-blue-100 text-blue-800 border-blue-200',
  completed: 'bg-green-100 text-green-800 border-green-200',
  cancelled: 'bg-gray-100 text-gray-600 border-gray-200',
  rejected: 'bg-red-100 text-red-800 border-red-200',
};

function formatAmount(amount: number): string {
  return new Intl.NumberFormat('fr-FR').format(amount) + ' FCFA';
}

function formatDate(dateStr: string): string {
  if (!dateStr) return '—';
  return new Date(dateStr).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  });
}

export default function TalentDashboardPage() {
  const { data: bookingsData, isLoading: loadingBookings } = useQuery({
    queryKey: ['bookings'],
    queryFn: () => bookingApi.list(),
  });

  const { data: analyticsData, isLoading: loadingAnalytics } = useQuery({
    queryKey: ['analytics'],
    queryFn: () => analyticsApi.getDashboard(),
  });

  const bookings: Booking[] = bookingsData?.data?.data ?? [];
  const analytics: Analytics | null = analyticsData?.data?.data ?? null;

  const pendingCount = bookings.filter((b) => b.status === 'pending').length;
  const recentBookings = [...bookings]
    .sort(
      (a, b) =>
        new Date(b.event_date).getTime() - new Date(a.event_date).getTime()
    )
    .slice(0, 5);

  // Current month revenue
  const now = new Date();
  const currentMonthKey = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
  const currentMonthRevenue =
    analytics?.monthly_revenue?.find((m) => m.month?.startsWith(currentMonthKey))
      ?.amount ?? 0;

  const isLoading = loadingBookings || loadingAnalytics;

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p className="text-gray-500 text-sm mt-1">
          Vue d&apos;ensemble de votre activité
        </p>
      </div>

      {/* Stats cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <Card>
          <CardHeader className="pb-2 flex flex-row items-center justify-between">
            <CardTitle className="text-sm font-medium text-gray-500">
              Réservations en attente
            </CardTitle>
            <Clock size={18} className="text-yellow-500" />
          </CardHeader>
          <CardContent>
            {isLoading ? (
              <Skeleton className="h-8 w-12" />
            ) : (
              <p className="text-3xl font-bold text-gray-900">{pendingCount}</p>
            )}
            <p className="text-xs text-gray-400 mt-1">À traiter</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2 flex flex-row items-center justify-between">
            <CardTitle className="text-sm font-medium text-gray-500">
              CA du mois
            </CardTitle>
            <TrendingUp size={18} className="text-[#2196F3]" />
          </CardHeader>
          <CardContent>
            {isLoading ? (
              <Skeleton className="h-8 w-28" />
            ) : (
              <p className="text-2xl font-bold text-gray-900">
                {formatAmount(currentMonthRevenue)}
              </p>
            )}
            <p className="text-xs text-gray-400 mt-1">Ce mois-ci</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2 flex flex-row items-center justify-between">
            <CardTitle className="text-sm font-medium text-gray-500">
              Note moyenne
            </CardTitle>
            <Star size={18} className="text-[#2196F3]" />
          </CardHeader>
          <CardContent>
            {isLoading ? (
              <Skeleton className="h-8 w-16" />
            ) : (
              <p className="text-3xl font-bold text-gray-900">
                {analytics?.average_rating
                  ? analytics.average_rating.toFixed(1)
                  : '—'}
              </p>
            )}
            <p className="text-xs text-gray-400 mt-1">Sur 5</p>
          </CardContent>
        </Card>
      </div>

      {/* Recent bookings */}
      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <CardTitle className="flex items-center gap-2">
            <BookCheck size={18} className="text-[#2196F3]" />
            Dernières réservations
          </CardTitle>
          <Link
            href="/talent/bookings"
            className="text-sm text-[#2196F3] hover:underline font-medium"
          >
            Voir toutes
          </Link>
        </CardHeader>
        <CardContent>
          {loadingBookings ? (
            <div className="space-y-3">
              {[...Array(5)].map((_, i) => (
                <Skeleton key={i} className="h-12 w-full" />
              ))}
            </div>
          ) : recentBookings.length === 0 ? (
            <p className="text-gray-400 text-sm text-center py-8">
              Aucune réservation pour l&apos;instant
            </p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-gray-100">
                    <th className="text-left py-3 px-2 text-gray-500 font-medium">
                      ID
                    </th>
                    <th className="text-left py-3 px-2 text-gray-500 font-medium">
                      Client
                    </th>
                    <th className="text-left py-3 px-2 text-gray-500 font-medium">
                      Date
                    </th>
                    <th className="text-right py-3 px-2 text-gray-500 font-medium">
                      Montant
                    </th>
                    <th className="text-right py-3 px-2 text-gray-500 font-medium">
                      Statut
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {recentBookings.map((booking) => (
                    <tr
                      key={booking.id}
                      className="border-b border-gray-50 hover:bg-gray-50 transition-colors"
                    >
                      <td className="py-3 px-2 font-mono text-gray-400 text-xs">
                        #{booking.id}
                      </td>
                      <td className="py-3 px-2 text-gray-700">
                        {booking.client
                          ? `${booking.client.first_name} ${booking.client.last_name}`
                          : '—'}
                      </td>
                      <td className="py-3 px-2 text-gray-600">
                        {formatDate(booking.event_date)}
                      </td>
                      <td className="py-3 px-2 text-right font-medium text-gray-800">
                        {formatAmount(booking.total_amount)}
                      </td>
                      <td className="py-3 px-2 text-right">
                        <span
                          className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border ${STATUS_COLORS[booking.status] ?? 'bg-gray-100 text-gray-600'}`}
                        >
                          {STATUS_LABELS[booking.status] ?? booking.status}
                        </span>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
