'use client';

import { useState } from 'react';
import { use } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useRouter } from 'next/navigation';
import { bookingApi, trackingApi, reviewApi } from '@/lib/api/endpoints';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert } from '@/components/ui/alert';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
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
  Navigation,
  Star,
  Package,
} from 'lucide-react';
import Link from 'next/link';

type BookingDetail = {
  id: number;
  status: string;
  event_date: string;
  event_location: string;
  total_amount?: number;
  message?: string;
  reject_reason?: string;
  devis?: { cachet_amount: number; commission_amount: number; total_amount: number };
  client?: {
    id: number;
    name?: string;
    first_name?: string;
    last_name?: string;
    email?: string;
    phone?: string;
  };
  service_package?: {
    name: string;
    duration_minutes?: number;
  };
};

type TrackingEntry = {
  status: string;
  occurred_at: string;
};

type ReviewEntry = {
  id: number;
  type: string;
  rating: number;
  comment?: string;
};

const STATUS_LABELS: Record<string, string> = {
  pending:   'En attente',
  accepted:  'Acceptée',
  paid:      'Payée',
  confirmed: 'Confirmée',
  completed: 'Complétée',
  cancelled: 'Annulée',
  rejected:  'Refusée',
};

const STATUS_COLORS: Record<string, string> = {
  pending:   'bg-yellow-100 text-yellow-800 border-yellow-200',
  accepted:  'bg-purple-100 text-purple-800 border-purple-200',
  paid:      'bg-sky-100 text-sky-800 border-sky-200',
  confirmed: 'bg-blue-100 text-blue-800 border-blue-200',
  completed: 'bg-green-100 text-green-800 border-green-200',
  cancelled: 'bg-gray-100 text-gray-600 border-gray-200',
  rejected:  'bg-red-100 text-red-800 border-red-200',
};

const TRACKING_STEPS: { status: string; label: string; next?: string; nextLabel?: string }[] = [
  { status: 'preparing',  label: 'Je me prépare',          next: 'en_route',  nextLabel: 'Je suis en route' },
  { status: 'en_route',   label: 'En route',                next: 'arrived',   nextLabel: 'Je suis arrivé' },
  { status: 'arrived',    label: 'Arrivé sur place',        next: 'performing', nextLabel: 'Je commence ma prestation' },
  { status: 'performing', label: 'En prestation',           next: 'completed', nextLabel: 'Prestation terminée' },
  { status: 'completed',  label: 'Prestation terminée' },
];

const TRACKING_COLORS: Record<string, string> = {
  preparing:  '#F59E0B',
  en_route:   '#0EA5E9',
  arrived:    '#8B5CF6',
  performing: '#FF6B35',
  completed:  '#10B981',
};

function formatAmount(c?: number): string {
  if (!c) return '—';
  return new Intl.NumberFormat('fr-FR').format(Math.round(c / 100)) + ' FCFA';
}

function formatDate(dateStr: string): string {
  if (!dateStr) return '—';
  return new Date(dateStr + 'T12:00:00').toLocaleDateString('fr-FR', {
    weekday: 'long', day: '2-digit', month: 'long', year: 'numeric',
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
  const [reviewRating, setReviewRating] = useState(0);
  const [reviewComment, setReviewComment] = useState('');
  const [reviewSent, setReviewSent] = useState(false);
  const [apiError, setApiError] = useState<string | null>(null);

  const { data, isLoading, isError } = useQuery({
    queryKey: ['booking', bookingId],
    queryFn: () => bookingApi.get(bookingId),
    enabled: !isNaN(bookingId),
  });

  const booking: BookingDetail | null = data?.data?.data ?? null;

  // Tracking
  const { data: trackingData } = useQuery({
    queryKey: ['tracking', id],
    queryFn: () => trackingApi.get(bookingId),
    enabled: !!booking && ['paid', 'confirmed'].includes(booking.status),
    refetchInterval: 15_000,
  });
  const trackingEntries: TrackingEntry[] = trackingData?.data?.data ?? [];
  const latestTracking = trackingEntries[trackingEntries.length - 1];
  const currentTrackingStep = TRACKING_STEPS.find((s) => s.status === latestTracking?.status);

  // Reviews
  const { data: reviewsData } = useQuery({
    queryKey: ['reviews', id],
    queryFn: () => reviewApi.list(bookingId),
    enabled: !!booking && ['confirmed', 'completed'].includes(booking.status),
  });
  const reviews: ReviewEntry[] = reviewsData?.data?.data ?? [];
  const talentReview = reviews.find((r) => r.type === 'talent_to_client');

  const acceptMutation = useMutation({
    mutationFn: () => bookingApi.accept(bookingId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['booking', bookingId] });
      queryClient.invalidateQueries({ queryKey: ['bookings'] });
      setApiError(null);
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { message?: string } } };
      setApiError(e?.response?.data?.message ?? "Erreur lors de l'acceptation");
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

  const trackingMutation = useMutation({
    mutationFn: (status: string) => trackingApi.update(bookingId, { status }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['tracking', id] });
      setApiError(null);
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { message?: string } } };
      setApiError(e?.response?.data?.message ?? 'Erreur lors de la mise à jour');
    },
  });

  const reviewMutation = useMutation({
    mutationFn: () =>
      reviewApi.submit(bookingId, { type: 'talent_to_client', rating: reviewRating, comment: reviewComment }),
    onSuccess: () => {
      setReviewSent(true);
      queryClient.invalidateQueries({ queryKey: ['reviews', id] });
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { message?: string } } };
      setApiError(e?.response?.data?.message ?? "Erreur lors de l'envoi de l'avis");
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
        {[...Array(3)].map((_, i) => (
          <div key={i} className="h-24 rounded-2xl bg-white/50 animate-pulse" />
        ))}
      </div>
    );
  }

  if (isError || !booking) {
    return (
      <div className="space-y-4">
        <button onClick={() => router.back()} className="flex items-center gap-2 text-sm text-gray-500">
          <ArrowLeft size={16} /> Retour
        </button>
        <Alert className="bg-red-50 border-red-200 text-red-800">
          Réservation introuvable ou erreur de chargement.
        </Alert>
      </div>
    );
  }

  const isPending = booking.status === 'pending';
  const isActive = ['paid', 'confirmed'].includes(booking.status);
  const isCompleted = ['confirmed', 'completed'].includes(booking.status);
  const clientName = booking.client?.name ?? (`${booking.client?.first_name ?? ''} ${booking.client?.last_name ?? ''}`.trim() || '—');

  return (
    <div className="space-y-6 max-w-3xl">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Link href="/talent/bookings">
            <button className="flex items-center gap-2 text-sm text-gray-500 hover:text-gray-900">
              <ArrowLeft size={15} /> Retour
            </button>
          </Link>
          <h1 className="text-2xl font-bold text-gray-900">Réservation #{booking.id}</h1>
        </div>
        <span
          className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium border ${STATUS_COLORS[booking.status] ?? 'bg-gray-100 text-gray-600'}`}
        >
          {STATUS_LABELS[booking.status] ?? booking.status}
        </span>
      </div>

      {apiError && (
        <Alert className="bg-red-50 border-red-200 text-red-800 text-sm">{apiError}</Alert>
      )}

      {/* Actions — pending */}
      {isPending && (
        <Card className="border-amber-200 bg-amber-50">
          <CardContent className="py-4 flex items-center justify-between">
            <p className="text-sm text-amber-800 font-medium">
              Cette réservation est en attente de votre validation
            </p>
            <div className="flex gap-3">
              <button
                onClick={() => setRejectOpen(true)}
                className="flex items-center gap-1.5 px-4 py-2 rounded-lg border border-red-300 text-red-600 text-sm font-semibold hover:bg-red-50"
              >
                <XCircle size={15} /> Refuser
              </button>
              <button
                onClick={() => acceptMutation.mutate()}
                disabled={acceptMutation.isPending}
                className="flex items-center gap-1.5 px-4 py-2 rounded-lg bg-green-600 text-white text-sm font-bold disabled:opacity-60 hover:bg-green-700"
              >
                <CheckCircle size={15} />
                {acceptMutation.isPending ? 'En cours...' : 'Accepter'}
              </button>
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
              Client
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            {booking.client ? (
              <>
                <div>
                  <p className="text-xs text-gray-400">Nom</p>
                  <p className="font-medium text-gray-800">{clientName}</p>
                </div>
                {booking.client.email && (
                  <div>
                    <p className="text-xs text-gray-400">Email</p>
                    <p className="text-gray-700">{booking.client.email}</p>
                  </div>
                )}
                {booking.client.phone && (
                  <div>
                    <p className="text-xs text-gray-400">Téléphone</p>
                    <p className="text-gray-700">{booking.client.phone}</p>
                  </div>
                )}
              </>
            ) : (
              <p className="text-gray-400 text-sm">Informations client non disponibles</p>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="text-base flex items-center gap-2">
              <Calendar size={16} className="text-amber-500" />
              Événement
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            <div>
              <p className="text-xs text-gray-400">Date</p>
              <p className="font-medium text-gray-800">{formatDate(booking.event_date)}</p>
            </div>
            <div className="flex items-start gap-2">
              <MapPin size={14} className="text-gray-400 mt-0.5 shrink-0" />
              <div>
                <p className="text-xs text-gray-400">Lieu</p>
                <p className="text-gray-700">{booking.event_location ?? '—'}</p>
              </div>
            </div>
            {booking.service_package && (
              <div>
                <p className="text-xs text-gray-400">Package</p>
                <p className="text-gray-700">
                  {booking.service_package.name}
                  {booking.service_package.duration_minutes && ` · ${booking.service_package.duration_minutes} min`}
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
            {booking.devis ? formatAmount(booking.devis.cachet_amount) : formatAmount(booking.total_amount)}
          </p>
          {booking.devis && (
            <p className="text-xs text-gray-400 mt-1">Cachet artiste (hors frais plateforme)</p>
          )}
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
            <p className="text-gray-700 text-sm leading-relaxed whitespace-pre-wrap">{booking.message}</p>
          </CardContent>
        </Card>
      )}

      {/* Tracking section (talent can update) */}
      {isActive && (
        <Card>
          <CardHeader>
            <CardTitle className="text-base flex items-center gap-2">
              <Navigation size={16} className="text-amber-500" />
              Suivi Jour J
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {latestTracking ? (
              <div className="flex items-center gap-3">
                <div
                  className="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                  style={{ background: `${TRACKING_COLORS[latestTracking.status]}20` }}
                >
                  <Package size={18} style={{ color: TRACKING_COLORS[latestTracking.status] }} />
                </div>
                <div>
                  <p className="font-semibold text-gray-900">
                    {TRACKING_STEPS.find((s) => s.status === latestTracking.status)?.label ?? latestTracking.status}
                  </p>
                  <p className="text-xs text-gray-400">
                    Mis à jour à {new Date(latestTracking.occurred_at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}
                  </p>
                </div>
              </div>
            ) : (
              <p className="text-sm text-gray-500">Aucune mise à jour de suivi encore envoyée.</p>
            )}

            {/* Next step button */}
            {currentTrackingStep?.next ? (
              <button
                onClick={() => trackingMutation.mutate(currentTrackingStep.next!)}
                disabled={trackingMutation.isPending}
                className="flex items-center gap-2 px-4 py-2.5 rounded-xl text-white text-sm font-bold disabled:opacity-60 transition-all"
                style={{ background: 'linear-gradient(135deg, #FF6B35, #C85A20)' }}
              >
                <Navigation size={15} />
                {trackingMutation.isPending ? 'Mise à jour...' : currentTrackingStep.nextLabel}
              </button>
            ) : !latestTracking ? (
              <button
                onClick={() => trackingMutation.mutate('preparing')}
                disabled={trackingMutation.isPending}
                className="flex items-center gap-2 px-4 py-2.5 rounded-xl text-white text-sm font-bold disabled:opacity-60 transition-all"
                style={{ background: 'linear-gradient(135deg, #FF6B35, #C85A20)' }}
              >
                <Navigation size={15} />
                {trackingMutation.isPending ? 'Mise à jour...' : 'Commencer le suivi'}
              </button>
            ) : null}
          </CardContent>
        </Card>
      )}

      {/* Review section (completed + not yet reviewed by talent) */}
      {isCompleted && !talentReview && !reviewSent && (
        <Card>
          <CardHeader>
            <CardTitle className="text-base flex items-center gap-2">
              <Star size={16} className="text-amber-500" />
              Évaluer le client
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <p className="text-sm text-gray-600">Comment s&apos;est passée votre collaboration avec {clientName} ?</p>
            <div className="flex items-center gap-1">
              {[1, 2, 3, 4, 5].map((star) => (
                <button key={star} onClick={() => setReviewRating(star)}>
                  <Star
                    size={28}
                    className="transition-colors"
                    style={{ color: star <= reviewRating ? '#F59E0B' : '#E5E7EB' }}
                    fill={star <= reviewRating ? '#F59E0B' : '#E5E7EB'}
                  />
                </button>
              ))}
              {reviewRating > 0 && <span className="text-sm text-gray-500 ml-2">{reviewRating}/5</span>}
            </div>
            <div className="space-y-1">
              <Label className="text-xs text-gray-500">Commentaire (optionnel)</Label>
              <Textarea
                placeholder="Client ponctuel, communicatif..."
                value={reviewComment}
                onChange={(e) => setReviewComment(e.target.value)}
                className="resize-none"
                rows={3}
              />
            </div>
            <div className="flex justify-end">
              <button
                onClick={() => reviewMutation.mutate()}
                disabled={reviewRating === 0 || reviewMutation.isPending}
                className="flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-bold disabled:opacity-60"
                style={{ background: '#F59E0B' }}
              >
                <Star size={14} fill="white" />
                {reviewMutation.isPending ? 'Envoi...' : 'Envoyer mon avis'}
              </button>
            </div>
          </CardContent>
        </Card>
      )}

      {(reviewSent || talentReview) && (
        <div className="flex items-center gap-2 text-green-700 text-sm bg-green-50 px-4 py-3 rounded-2xl">
          <CheckCircle size={16} /> Merci pour votre évaluation !
        </div>
      )}

      {booking.reject_reason && (
        <Alert className="bg-red-50 border-red-200">
          <p className="text-sm text-red-800 font-medium mb-1">Motif du refus</p>
          <p className="text-sm text-red-700">{booking.reject_reason}</p>
        </Alert>
      )}

      {/* Download contract */}
      <div className="flex justify-end">
        <button
          onClick={handleDownloadContract}
          className="flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors"
        >
          <Download size={15} /> Télécharger le contrat (PDF)
        </button>
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
            <button
              onClick={() => { setRejectOpen(false); setRejectReason(''); }}
              className="flex-1 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700"
            >
              Annuler
            </button>
            <button
              disabled={rejectMutation.isPending || !rejectReason.trim()}
              onClick={() => rejectMutation.mutate()}
              className="flex-1 py-2.5 rounded-xl bg-red-600 text-white text-sm font-bold disabled:opacity-60 hover:bg-red-700"
            >
              {rejectMutation.isPending ? 'En cours...' : 'Confirmer le refus'}
            </button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
