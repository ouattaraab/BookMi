'use client';

import { useState } from 'react';
import { use } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { managerApi } from '@/lib/api/endpoints';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { Alert } from '@/components/ui/alert';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Separator } from '@/components/ui/separator';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  ArrowLeft,
  Star,
  BookCheck,
  DollarSign,
  CheckCircle,
  XCircle,
  TrendingUp,
} from 'lucide-react';
import Link from 'next/link';

type TalentStats = {
  id: number;
  stage_name: string;
  talent_level: string;
  is_verified: boolean;
  average_rating?: number;
  total_bookings?: number;
  total_revenue?: number;
  user?: {
    first_name: string;
    last_name: string;
    email: string;
    phone?: string;
  };
};

type Booking = {
  id: number;
  status: string;
  event_date: string;
  location: string;
  total_amount: number;
  client?: { first_name: string; last_name: string; email: string };
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

function getInitials(stageName: string): string {
  return stageName
    .split(' ')
    .slice(0, 2)
    .map((w) => w[0])
    .join('')
    .toUpperCase();
}

export default function ManagerTalentDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = use(params);
  const talentId = parseInt(id, 10);
  const queryClient = useQueryClient();

  const [rejectBookingId, setRejectBookingId] = useState<number | null>(null);
  const [rejectReason, setRejectReason] = useState('');
  const [apiError, setApiError] = useState<string | null>(null);

  const { data: statsData, isLoading: loadingStats } = useQuery({
    queryKey: ['manager_talent', talentId],
    queryFn: () => managerApi.getTalentStats(talentId),
    enabled: !isNaN(talentId),
  });

  const { data: bookingsData, isLoading: loadingBookings } = useQuery({
    queryKey: ['manager_talent_bookings', talentId],
    queryFn: () => managerApi.getTalentBookings(talentId),
    enabled: !isNaN(talentId),
  });

  const talent: TalentStats | null = statsData?.data?.data ?? null;
  const bookings: Booking[] = bookingsData?.data?.data ?? [];

  const acceptMutation = useMutation({
    mutationFn: (bookingId: number) =>
      managerApi.acceptBooking(talentId, bookingId),
    onSuccess: () => {
      queryClient.invalidateQueries({
        queryKey: ['manager_talent_bookings', talentId],
      });
      setApiError(null);
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { message?: string } } };
      setApiError(e?.response?.data?.message ?? 'Erreur lors de l\'acceptation');
    },
  });

  const rejectMutation = useMutation({
    mutationFn: (bookingId: number) =>
      managerApi.rejectBooking(talentId, bookingId, rejectReason),
    onSuccess: () => {
      queryClient.invalidateQueries({
        queryKey: ['manager_talent_bookings', talentId],
      });
      setRejectBookingId(null);
      setRejectReason('');
      setApiError(null);
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { message?: string } } };
      setApiError(e?.response?.data?.message ?? 'Erreur lors du refus');
    },
  });

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center gap-4">
        <Link href="/manager/talents">
          <Button variant="ghost" size="sm" className="gap-2 text-gray-500">
            <ArrowLeft size={15} /> Retour
          </Button>
        </Link>
        {loadingStats ? (
          <Skeleton className="h-8 w-48" />
        ) : (
          <h1 className="text-2xl font-bold text-gray-900">
            {talent?.stage_name ?? 'Talent'}
          </h1>
        )}
      </div>

      {apiError && (
        <Alert className="bg-red-50 border-red-200 text-red-800 text-sm">
          {apiError}
        </Alert>
      )}

      {/* Talent info */}
      {loadingStats ? (
        <Card>
          <CardContent className="p-6">
            <Skeleton className="h-32 w-full" />
          </CardContent>
        </Card>
      ) : talent ? (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {/* Profile card */}
          <Card className="md:col-span-1">
            <CardContent className="p-6 space-y-4">
              <div className="flex items-center gap-4">
                <Avatar className="h-14 w-14">
                  <AvatarFallback className="bg-amber-100 text-amber-700 font-bold text-lg">
                    {getInitials(talent.stage_name)}
                  </AvatarFallback>
                </Avatar>
                <div>
                  <div className="flex items-center gap-2">
                    <h3 className="font-semibold text-gray-900">
                      {talent.stage_name}
                    </h3>
                    {talent.is_verified && (
                      <span className="text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded-full">
                        Vérifié
                      </span>
                    )}
                  </div>
                  <p className="text-sm text-gray-500 capitalize">
                    {talent.talent_level}
                  </p>
                </div>
              </div>
              <Separator />
              {talent.user && (
                <div className="space-y-2 text-sm">
                  <div>
                    <p className="text-xs text-gray-400">Nom légal</p>
                    <p className="text-gray-700">
                      {talent.user.first_name} {talent.user.last_name}
                    </p>
                  </div>
                  <div>
                    <p className="text-xs text-gray-400">Email</p>
                    <p className="text-gray-700">{talent.user.email}</p>
                  </div>
                  {talent.user.phone && (
                    <div>
                      <p className="text-xs text-gray-400">Téléphone</p>
                      <p className="text-gray-700">{talent.user.phone}</p>
                    </div>
                  )}
                </div>
              )}
            </CardContent>
          </Card>

          {/* Stats */}
          <div className="md:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <Card>
              <CardHeader className="pb-2 flex flex-row items-center justify-between">
                <CardTitle className="text-xs font-medium text-gray-500">
                  Note moyenne
                </CardTitle>
                <Star size={16} className="text-amber-400" />
              </CardHeader>
              <CardContent>
                <p className="text-2xl font-bold text-gray-900">
                  {talent.average_rating
                    ? talent.average_rating.toFixed(1)
                    : '—'}
                </p>
                <p className="text-xs text-gray-400">/ 5 étoiles</p>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="pb-2 flex flex-row items-center justify-between">
                <CardTitle className="text-xs font-medium text-gray-500">
                  Total réservations
                </CardTitle>
                <BookCheck size={16} className="text-blue-400" />
              </CardHeader>
              <CardContent>
                <p className="text-2xl font-bold text-gray-900">
                  {talent.total_bookings ?? 0}
                </p>
                <p className="text-xs text-gray-400">Complétées</p>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="pb-2 flex flex-row items-center justify-between">
                <CardTitle className="text-xs font-medium text-gray-500">
                  CA Total
                </CardTitle>
                <TrendingUp size={16} className="text-green-500" />
              </CardHeader>
              <CardContent>
                <p className="text-xl font-bold text-gray-900">
                  {talent.total_revenue
                    ? formatAmount(talent.total_revenue)
                    : '—'}
                </p>
              </CardContent>
            </Card>
          </div>
        </div>
      ) : null}

      {/* Bookings table */}
      <Card>
        <CardHeader>
          <CardTitle className="text-base font-semibold text-gray-800">
            Réservations
          </CardTitle>
        </CardHeader>
        <CardContent className="p-0">
          {loadingBookings ? (
            <div className="p-6 space-y-3">
              {[...Array(4)].map((_, i) => (
                <Skeleton key={i} className="h-12 w-full" />
              ))}
            </div>
          ) : bookings.length === 0 ? (
            <div className="py-12 text-center text-gray-400 text-sm">
              Aucune réservation pour ce talent
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow className="bg-gray-50">
                  <TableHead className="font-semibold text-gray-600">ID</TableHead>
                  <TableHead className="font-semibold text-gray-600">Client</TableHead>
                  <TableHead className="font-semibold text-gray-600">Date</TableHead>
                  <TableHead className="font-semibold text-gray-600">Lieu</TableHead>
                  <TableHead className="font-semibold text-gray-600 text-right">
                    Montant
                  </TableHead>
                  <TableHead className="font-semibold text-gray-600 text-center">
                    Statut
                  </TableHead>
                  <TableHead className="font-semibold text-gray-600 text-right">
                    Actions
                  </TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {bookings.map((booking) => (
                  <TableRow key={booking.id} className="hover:bg-gray-50">
                    <TableCell className="font-mono text-gray-400 text-xs">
                      #{booking.id}
                    </TableCell>
                    <TableCell className="text-gray-700">
                      {booking.client
                        ? `${booking.client.first_name} ${booking.client.last_name}`
                        : '—'}
                      {booking.client?.email && (
                        <p className="text-xs text-gray-400">
                          {booking.client.email}
                        </p>
                      )}
                    </TableCell>
                    <TableCell className="text-gray-600">
                      {formatDate(booking.event_date)}
                    </TableCell>
                    <TableCell className="text-gray-600 max-w-[120px] truncate">
                      {booking.location ?? '—'}
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
                      {booking.status === 'pending' ? (
                        <div className="flex items-center justify-end gap-2">
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => setRejectBookingId(booking.id)}
                            className="text-red-400 hover:text-red-600 hover:bg-red-50 gap-1"
                          >
                            <XCircle size={14} />
                            Refuser
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => acceptMutation.mutate(booking.id)}
                            disabled={acceptMutation.isPending}
                            className="text-green-600 hover:text-green-700 hover:bg-green-50 gap-1"
                          >
                            <CheckCircle size={14} />
                            Accepter
                          </Button>
                        </div>
                      ) : (
                        <span className="text-xs text-gray-400">—</span>
                      )}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      {/* Reject modal */}
      <Dialog
        open={rejectBookingId !== null}
        onOpenChange={() => {
          setRejectBookingId(null);
          setRejectReason('');
        }}
      >
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Refuser la réservation #{rejectBookingId}</DialogTitle>
          </DialogHeader>
          <div className="space-y-4 mt-2">
            <div className="space-y-2">
              <Label htmlFor="reject_reason">Motif du refus</Label>
              <Textarea
                id="reject_reason"
                placeholder="Expliquez la raison du refus..."
                value={rejectReason}
                onChange={(e) => setRejectReason(e.target.value)}
                className="resize-none"
                rows={4}
              />
            </div>
          </div>
          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => {
                setRejectBookingId(null);
                setRejectReason('');
              }}
            >
              Annuler
            </Button>
            <Button
              variant="destructive"
              disabled={
                rejectMutation.isPending || !rejectReason.trim()
              }
              onClick={() =>
                rejectBookingId !== null &&
                rejectMutation.mutate(rejectBookingId)
              }
            >
              {rejectMutation.isPending ? 'En cours...' : 'Confirmer le refus'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
