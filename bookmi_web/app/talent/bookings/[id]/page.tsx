'use client';

import { useState } from 'react';
import { use } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useRouter } from 'next/navigation';
import { bookingApi } from '@/lib/api/endpoints';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { Alert } from '@/components/ui/alert';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog';
import {
  ArrowLeft,
  CheckCircle,
  XCircle,
  Download,
  MapPin,
  Calendar,
  User,
  DollarSign,
  MessageSquare,
} from 'lucide-react';
import Link from 'next/link';

type BookingDetail = {
  id: number;
  status: string;
  event_date: string;
  location: string;
  total_amount: number;
  message?: string;
  reject_reason?: string;
  client?: {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    phone?: string;
  };
  service_package?: {
    name: string;
    duration_minutes: number;
  };
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
  return new Date(dateStr).toLocaleString('fr-FR', {
    weekday: 'long',
    day: '2-digit',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

export default function TalentBookingDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = use(params);
  const bookingId = parseInt(id, 10);
  const queryClient = useQueryClient();
  const router = useRouter();

  const [rejectOpen, setRejectOpen] = useState(false);
  const [rejectReason, setRejectReason] = useState('');
  const [apiError, setApiError] = useState<string | null>(null);

  const { data, isLoading, isError } = useQuery({
    queryKey: ['booking', bookingId],
    queryFn: () => bookingApi.get(bookingId),
    enabled: !isNaN(bookingId),
  });

  const booking: BookingDetail | null = data?.data?.data ?? null;

  const acceptMutation = useMutation({
    mutationFn: () => bookingApi.accept(bookingId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['booking', bookingId] });
      queryClient.invalidateQueries({ queryKey: ['bookings'] });
      setApiError(null);
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { message?: string } } };
      setApiError(e?.response?.data?.message ?? 'Erreur lors de l\'acceptation');
    },
  });

  const rejectMutation = useMutation({
    mutationFn: () => bookingApi.reject(bookingId, rejectReason),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['booking', bookingId] });
      queryClient.invalidateQueries({ queryKey: ['bookings'] });
      setRejectOpen(false);
      setRejectReason('');
      setApiError(null);
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { message?: string } } };
      setApiError(e?.response?.data?.message ?? 'Erreur lors du refus');
    },
  });

  const handleDownloadContract = async () => {
    try {
      const res = await bookingApi.getContract(bookingId);
      const blob = new Blob([res.data], { type: 'application/pdf' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `contrat_reservation_${bookingId}.pdf`;
      a.click();
      URL.revokeObjectURL(url);
    } catch {
      setApiError('Impossible de télécharger le contrat');
    }
  };

  if (isLoading) {
    return (
      <div className="space-y-6">
        <Skeleton className="h-8 w-48" />
        <Skeleton className="h-64 w-full" />
      </div>
    );
  }

  if (isError || !booking) {
    return (
      <div className="space-y-4">
        <Link href="/talent/bookings">
          <Button variant="ghost" className="gap-2 text-gray-500">
            <ArrowLeft size={16} /> Retour
          </Button>
        </Link>
        <Alert className="bg-red-50 border-red-200 text-red-800">
          Réservation introuvable ou erreur de chargement.
        </Alert>
      </div>
    );
  }

  const isPending = booking.status === 'pending';

  return (
    <div className="space-y-6 max-w-3xl">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Link href="/talent/bookings">
            <Button variant="ghost" size="sm" className="gap-2 text-gray-500">
              <ArrowLeft size={15} /> Retour
            </Button>
          </Link>
          <div>
            <h1 className="text-2xl font-bold text-gray-900">
              Réservation #{booking.id}
            </h1>
          </div>
        </div>
        <span
          className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium border ${STATUS_COLORS[booking.status] ?? 'bg-gray-100 text-gray-600'}`}
        >
          {STATUS_LABELS[booking.status] ?? booking.status}
        </span>
      </div>

      {apiError && (
        <Alert className="bg-red-50 border-red-200 text-red-800 text-sm">
          {apiError}
        </Alert>
      )}

      {/* Actions */}
      {isPending && (
        <Card className="border-amber-200 bg-amber-50">
          <CardContent className="py-4 flex items-center justify-between">
            <p className="text-sm text-amber-800 font-medium">
              Cette réservation est en attente de votre validation
            </p>
            <div className="flex gap-3">
              <Button
                onClick={() => setRejectOpen(true)}
                variant="outline"
                className="border-red-300 text-red-600 hover:bg-red-50 gap-2"
              >
                <XCircle size={15} />
                Refuser
              </Button>
              <Button
                onClick={() => acceptMutation.mutate()}
                disabled={acceptMutation.isPending}
                className="bg-green-600 hover:bg-green-700 text-white gap-2"
              >
                <CheckCircle size={15} />
                {acceptMutation.isPending ? 'En cours...' : 'Accepter'}
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Main details */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <Card>
          <CardHeader>
            <CardTitle className="text-base flex items-center gap-2">
              <User size={16} className="text-amber-500" />
              Informations client
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            {booking.client ? (
              <>
                <div>
                  <p className="text-xs text-gray-400">Nom complet</p>
                  <p className="font-medium text-gray-800">
                    {booking.client.first_name} {booking.client.last_name}
                  </p>
                </div>
                <div>
                  <p className="text-xs text-gray-400">Email</p>
                  <p className="text-gray-700">{booking.client.email}</p>
                </div>
                {booking.client.phone && (
                  <div>
                    <p className="text-xs text-gray-400">Téléphone</p>
                    <p className="text-gray-700">{booking.client.phone}</p>
                  </div>
                )}
              </>
            ) : (
              <p className="text-gray-400 text-sm">
                Informations client non disponibles
              </p>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="text-base flex items-center gap-2">
              <Calendar size={16} className="text-amber-500" />
              Détails de l&apos;événement
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            <div>
              <p className="text-xs text-gray-400">Date et heure</p>
              <p className="font-medium text-gray-800">
                {formatDate(booking.event_date)}
              </p>
            </div>
            <div className="flex items-start gap-2">
              <MapPin size={14} className="text-gray-400 mt-0.5 shrink-0" />
              <div>
                <p className="text-xs text-gray-400">Lieu</p>
                <p className="text-gray-700">{booking.location ?? '—'}</p>
              </div>
            </div>
            {booking.service_package && (
              <div>
                <p className="text-xs text-gray-400">Package</p>
                <p className="text-gray-700">
                  {booking.service_package.name} (
                  {booking.service_package.duration_minutes} min)
                </p>
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="text-base flex items-center gap-2">
            <DollarSign size={16} className="text-amber-500" />
            Montant
          </CardTitle>
        </CardHeader>
        <CardContent>
          <p className="text-3xl font-bold text-gray-900">
            {formatAmount(booking.total_amount)}
          </p>
        </CardContent>
      </Card>

      {booking.message && (
        <Card>
          <CardHeader>
            <CardTitle className="text-base flex items-center gap-2">
              <MessageSquare size={16} className="text-amber-500" />
              Message du client
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-gray-700 text-sm leading-relaxed whitespace-pre-wrap">
              {booking.message}
            </p>
          </CardContent>
        </Card>
      )}

      {booking.reject_reason && (
        <Alert className="bg-red-50 border-red-200">
          <p className="text-sm text-red-800 font-medium mb-1">
            Motif du refus
          </p>
          <p className="text-sm text-red-700">{booking.reject_reason}</p>
        </Alert>
      )}

      {/* Download contract */}
      <div className="flex justify-end">
        <Button
          variant="outline"
          onClick={handleDownloadContract}
          className="gap-2 text-gray-600"
        >
          <Download size={15} />
          Télécharger le contrat (PDF)
        </Button>
      </div>

      {/* Reject modal */}
      <Dialog open={rejectOpen} onOpenChange={setRejectOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Refuser la réservation</DialogTitle>
          </DialogHeader>
          <div className="space-y-4 mt-2">
            <div className="space-y-2">
              <Label htmlFor="reason">Motif du refus</Label>
              <Textarea
                id="reason"
                placeholder="Expliquez la raison du refus au client..."
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
              onClick={() => { setRejectOpen(false); setRejectReason(''); }}
            >
              Annuler
            </Button>
            <Button
              variant="destructive"
              disabled={rejectMutation.isPending || !rejectReason.trim()}
              onClick={() => rejectMutation.mutate()}
            >
              {rejectMutation.isPending ? 'En cours...' : 'Confirmer le refus'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
