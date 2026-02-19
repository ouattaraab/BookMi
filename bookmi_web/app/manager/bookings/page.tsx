'use client';

import { useQuery } from '@tanstack/react-query';
import { managerApi } from '@/lib/api/endpoints';
import Link from 'next/link';
import { Card, CardContent } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { ChevronRight, BookCheck } from 'lucide-react';

type TalentMini = {
  id: number;
  stage_name: string;
};

type Booking = {
  id: number;
  status: string;
  event_date: string;
  location: string;
  total_amount: number;
  client?: { first_name: string; last_name: string; email: string };
  talent?: TalentMini;
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

export default function ManagerBookingsPage() {
  // Fetch all talents then their bookings
  const { data: talentsData, isLoading: loadingTalents, isError } = useQuery({
    queryKey: ['manager_talents'],
    queryFn: () => managerApi.getMyTalents(),
  });

  const talents: TalentMini[] = talentsData?.data?.data ?? [];

  // Aggregate all bookings per talent client-side
  const { data: allBookingsPerTalent, isLoading: loadingBookings } = useQuery({
    queryKey: ['manager_all_bookings', talents.map((t) => t.id).join(',')],
    queryFn: async () => {
      if (!talents.length) return [];
      const results = await Promise.all(
        talents.map(async (t) => {
          const res = await managerApi.getTalentBookings(t.id);
          const bookings: Booking[] = res?.data?.data ?? [];
          return bookings.map((b) => ({ ...b, talent: t }));
        })
      );
      return results.flat();
    },
    enabled: talents.length > 0,
  });

  const bookings: Booking[] = allBookingsPerTalent ?? [];
  const pending = bookings.filter((b) => b.status === 'pending');

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">
            Réservations à valider
          </h1>
          <p className="text-gray-500 text-sm mt-1">
            Réservations en attente pour tous vos talents
          </p>
        </div>
        {pending.length > 0 && (
          <span className="px-3 py-1.5 bg-yellow-100 text-yellow-800 text-sm font-semibold rounded-full">
            {pending.length} en attente
          </span>
        )}
      </div>

      {isError && (
        <Alert className="bg-red-50 border-red-200 text-red-800 text-sm">
          Impossible de charger les réservations.
        </Alert>
      )}

      <Card>
        <CardContent className="p-0">
          {loadingTalents || loadingBookings ? (
            <div className="p-6 space-y-3">
              {[...Array(5)].map((_, i) => (
                <Skeleton key={i} className="h-12 w-full" />
              ))}
            </div>
          ) : bookings.length === 0 ? (
            <div className="py-16 text-center">
              <BookCheck size={40} className="text-gray-300 mx-auto mb-4" />
              <p className="text-gray-400 text-sm">
                Aucune réservation pour vos talents
              </p>
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow className="bg-gray-50">
                  <TableHead className="font-semibold text-gray-600">ID</TableHead>
                  <TableHead className="font-semibold text-gray-600">Talent</TableHead>
                  <TableHead className="font-semibold text-gray-600">Client</TableHead>
                  <TableHead className="font-semibold text-gray-600">Date</TableHead>
                  <TableHead className="font-semibold text-gray-600 text-right">
                    Montant
                  </TableHead>
                  <TableHead className="font-semibold text-gray-600 text-center">
                    Statut
                  </TableHead>
                  <TableHead className="font-semibold text-gray-600 text-right">
                    Détail
                  </TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {bookings.map((booking) => (
                  <TableRow key={`${booking.talent?.id}-${booking.id}`} className="hover:bg-gray-50">
                    <TableCell className="font-mono text-gray-400 text-xs">
                      #{booking.id}
                    </TableCell>
                    <TableCell className="text-gray-800 font-medium">
                      {booking.talent?.stage_name ?? '—'}
                    </TableCell>
                    <TableCell className="text-gray-700">
                      {booking.client
                        ? `${booking.client.first_name} ${booking.client.last_name}`
                        : '—'}
                    </TableCell>
                    <TableCell className="text-gray-600">
                      {formatDate(booking.event_date)}
                    </TableCell>
                    <TableCell className="text-right font-medium text-gray-800">
                      {formatAmount(booking.total_amount)}
                    </TableCell>
                    <TableCell className="text-center">
                      <span
                        className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border ${STATUS_COLORS[booking.status] ?? 'bg-gray-100 text-gray-600'}`}
                      >
                        {STATUS_LABELS[booking.status] ?? booking.status}
                      </span>
                    </TableCell>
                    <TableCell className="text-right">
                      {booking.talent && (
                        <Link href={`/manager/talents/${booking.talent.id}`}>
                          <Button
                            variant="ghost"
                            size="sm"
                            className="text-amber-600 hover:text-amber-700 hover:bg-amber-50 gap-1"
                          >
                            Gérer
                            <ChevronRight size={14} />
                          </Button>
                        </Link>
                      )}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
