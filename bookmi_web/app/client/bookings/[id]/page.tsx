'use client';

import { useState } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import Link from 'next/link';
import { bookingApi, paymentApi, escrowApi, reviewApi, trackingApi } from '@/lib/api/endpoints';
import { Alert } from '@/components/ui/alert';
import {
  ArrowLeft, MapPin, Calendar, FileText, MessageSquare, Zap,
  Download, X, Star, CreditCard, CheckCircle, Package,
} from 'lucide-react';

type BookingDetail = {
  id: number;
  status: string;
  event_date: string;
  event_location: string;
  message?: string;
  is_express?: boolean;
  created_at?: string;
  talent_profile?: {
    id: number;
    stage_name: string;
    slug: string;
  };
  service_package?: {
    id: number;
    name: string;
    type: string;
    duration_minutes?: number;
  };
  devis?: {
    cachet_amount: number;
    commission_amount: number;
    total_amount: number;
  };
  contract_available?: boolean;
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

const statusSteps = [
  { key: 'pending',   label: 'En attente',   desc: "L'artiste n'a pas encore répondu" },
  { key: 'accepted',  label: 'Acceptée',      desc: "L'artiste a accepté votre demande" },
  { key: 'paid',      label: 'Payée',         desc: 'Le paiement a été effectué' },
  { key: 'confirmed', label: 'Confirmée',     desc: 'La réservation est confirmée' },
  { key: 'completed', label: 'Terminée',      desc: 'La prestation a eu lieu' },
];

const statusConfig: Record<string, { label: string; color: string; bg: string }> = {
  pending:   { label: 'En attente',  color: '#F59E0B', bg: 'rgba(245,158,11,0.1)' },
  accepted:  { label: 'Acceptée',    color: '#8B5CF6', bg: 'rgba(139,92,246,0.1)' },
  paid:      { label: 'Payée',       color: '#0EA5E9', bg: 'rgba(14,165,233,0.1)' },
  confirmed: { label: 'Confirmée',   color: '#2196F3', bg: 'rgba(33,150,243,0.1)' },
  completed: { label: 'Terminée',    color: '#10B981', bg: 'rgba(16,185,129,0.1)' },
  cancelled: { label: 'Annulée',     color: '#EF4444', bg: 'rgba(239,68,68,0.1)' },
  rejected:  { label: 'Refusée',     color: '#6B7280', bg: 'rgba(107,114,128,0.1)' },
};

const trackingLabels: Record<string, { label: string; color: string }> = {
  preparing:  { label: 'Se prépare',         color: '#F59E0B' },
  en_route:   { label: 'En route',           color: '#0EA5E9' },
  arrived:    { label: 'Arrivé sur place',   color: '#8B5CF6' },
  performing: { label: 'En prestation',      color: '#FF6B35' },
  completed:  { label: 'Prestation terminée', color: '#10B981' },
};

const PAYMENT_METHODS = [
  { value: 'orange_money', label: 'Orange Money', color: '#FF6B35' },
  { value: 'wave',          label: 'Wave',         color: '#0099FF' },
  { value: 'mtn_momo',      label: 'MTN MoMo',     color: '#FFCC00' },
  { value: 'moov_money',    label: 'Moov Money',   color: '#00B140' },
];

function formatCachet(c: number) {
  return new Intl.NumberFormat('fr-FR').format(Math.round(c / 100)) + ' FCFA';
}

function formatDuration(min?: number) {
  if (!min) return null;
  const h = Math.floor(min / 60);
  const m = min % 60;
  return h > 0 ? (m ? `${h}h${String(m).padStart(2, '0')}` : `${h}h`) : `${min}min`;
}

export default function ClientBookingDetailPage() {
  const { id } = useParams<{ id: string }>();
  const router = useRouter();
  const qc = useQueryClient();

  const [cancelOpen, setCancelOpen] = useState(false);
  const [deliveryOpen, setDeliveryOpen] = useState(false);
  const [paymentOpen, setPaymentOpen] = useState(false);
  const [paymentStep, setPaymentStep] = useState<'method' | 'phone' | 'otp'>('method');
  const [paymentMethod, setPaymentMethod] = useState('orange_money');
  const [paymentPhone, setPaymentPhone] = useState('');
  const [paymentOtp, setPaymentOtp] = useState('');
  const [paymentReference, setPaymentReference] = useState<string | null>(null);
  const [paymentTxId, setPaymentTxId] = useState<number | null>(null);
  const [reviewRating, setReviewRating] = useState(0);
  const [reviewComment, setReviewComment] = useState('');
  const [reviewSent, setReviewSent] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const { data, isLoading } = useQuery({
    queryKey: ['client_booking', id],
    queryFn: () => bookingApi.get(Number(id)),
    enabled: !!id,
  });

  const raw = data?.data?.data ?? data?.data;
  const booking: BookingDetail | null = raw ?? null;

  // Tracking (poll 30s during active booking)
  const { data: trackingData } = useQuery({
    queryKey: ['tracking', id],
    queryFn: () => trackingApi.get(Number(id)),
    refetchInterval: 30_000,
    enabled: !!booking && ['accepted', 'paid', 'confirmed'].includes(booking.status),
  });
  const trackingEntries: TrackingEntry[] = trackingData?.data?.data ?? [];
  const latestTracking = trackingEntries[trackingEntries.length - 1];

  // Reviews
  const { data: reviewsData } = useQuery({
    queryKey: ['reviews', id],
    queryFn: () => reviewApi.list(Number(id)),
    enabled: !!booking && ['confirmed', 'completed'].includes(booking.status),
  });
  const reviews: ReviewEntry[] = reviewsData?.data?.data ?? [];
  const clientReview = reviews.find((r) => r.type === 'client_to_talent');

  const cancelMutation = useMutation({
    mutationFn: () => bookingApi.cancel(Number(id)),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['client_booking', id] });
      qc.invalidateQueries({ queryKey: ['client_bookings'] });
      setCancelOpen(false);
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { error?: { message?: string }; message?: string } } };
      setError(e?.response?.data?.error?.message ?? e?.response?.data?.message ?? "Erreur lors de l'annulation");
    },
  });

  const paymentInitiateMutation = useMutation({
    mutationFn: () =>
      paymentApi.initiate({ booking_id: Number(id), payment_method: paymentMethod, phone_number: paymentPhone }),
    onSuccess: (res) => {
      const tx = res?.data?.data ?? res?.data;
      if (tx?.id) setPaymentTxId(tx.id);
      if (tx?.reference) {
        setPaymentReference(tx.reference);
        setPaymentStep('otp');
      } else {
        // No OTP needed — start polling
        pollPaymentStatus(tx?.id);
      }
      setError(null);
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { message?: string } } };
      setError(e?.response?.data?.message ?? 'Erreur lors du paiement');
    },
  });

  const paymentOtpMutation = useMutation({
    mutationFn: () => paymentApi.submitOtp({ reference: paymentReference!, otp: paymentOtp }),
    onSuccess: () => {
      if (paymentTxId) pollPaymentStatus(paymentTxId);
      setError(null);
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { message?: string } } };
      setError(e?.response?.data?.message ?? 'OTP invalide');
    },
  });

  const pollPaymentStatus = (txId: number) => {
    const poll = setInterval(async () => {
      try {
        const res = await paymentApi.getStatus(txId);
        const tx = res?.data?.data ?? res?.data;
        if (tx?.status === 'completed' || tx?.status === 'success') {
          clearInterval(poll);
          setPaymentOpen(false);
          setPaymentStep('method');
          qc.invalidateQueries({ queryKey: ['client_booking', id] });
          qc.invalidateQueries({ queryKey: ['client_bookings'] });
        } else if (tx?.status === 'failed') {
          clearInterval(poll);
          setError('Le paiement a échoué. Veuillez réessayer.');
        }
      } catch {
        clearInterval(poll);
      }
    }, 3000);
    setTimeout(() => clearInterval(poll), 120_000); // max 2 min
  };

  const deliveryMutation = useMutation({
    mutationFn: () => escrowApi.confirmDelivery(Number(id)),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['client_booking', id] });
      qc.invalidateQueries({ queryKey: ['client_bookings'] });
      setDeliveryOpen(false);
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { message?: string } } };
      setError(e?.response?.data?.message ?? 'Erreur lors de la confirmation');
    },
  });

  const reviewMutation = useMutation({
    mutationFn: () =>
      reviewApi.submit(Number(id), { type: 'client_to_talent', rating: reviewRating, comment: reviewComment }),
    onSuccess: () => {
      setReviewSent(true);
      qc.invalidateQueries({ queryKey: ['reviews', id] });
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { message?: string } } };
      setError(e?.response?.data?.message ?? "Erreur lors de l'envoi de l'avis");
    },
  });

  const handleDownloadContract = async () => {
    try {
      const res = await bookingApi.getContract(Number(id));
      const url = window.URL.createObjectURL(new Blob([res.data]));
      const a = document.createElement('a');
      a.href = url;
      a.download = `contrat-${id}.pdf`;
      a.click();
      window.URL.revokeObjectURL(url);
    } catch {
      setError('Impossible de télécharger le contrat');
    }
  };

  if (isLoading) {
    return (
      <div className="space-y-4 max-w-3xl mx-auto">
        {[...Array(3)].map((_, i) => <div key={i} className="h-24 rounded-2xl bg-white/50 animate-pulse" />)}
      </div>
    );
  }

  if (!booking) {
    return (
      <div className="text-center py-20 text-gray-400">
        <p>Réservation introuvable.</p>
        <Link href="/client/bookings" className="text-sm font-semibold mt-3 inline-block" style={{ color: '#2196F3' }}>
          ← Retour
        </Link>
      </div>
    );
  }

  const cfg = statusConfig[booking.status] ?? statusConfig.pending;
  const currentStepIndex = statusSteps.findIndex((s) => s.key === booking.status);

  return (
    <div className="space-y-5 max-w-3xl mx-auto">
      {/* Back */}
      <button
        onClick={() => router.back()}
        className="flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-gray-900"
      >
        <ArrowLeft size={15} /> Retour aux réservations
      </button>

      <div>
        <div className="flex items-center gap-3 flex-wrap">
          <h1 className="text-xl font-extrabold text-gray-900">Réservation #{booking.id}</h1>
          <span className="text-sm font-semibold px-3 py-1 rounded-full" style={{ background: cfg.bg, color: cfg.color }}>
            {cfg.label}
          </span>
          {booking.is_express && (
            <span className="text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 flex items-center gap-1">
              <Zap size={11} /> Express
            </span>
          )}
        </div>
        {booking.created_at && (
          <p className="text-xs text-gray-400 mt-1">
            Demande envoyée le {new Date(booking.created_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })}
          </p>
        )}
      </div>

      {error && <Alert className="bg-red-50 border-red-200 text-red-800 text-sm">{error}</Alert>}

      <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
        {/* Talent card */}
        {booking.talent_profile && (
          <div
            className="rounded-2xl p-5"
            style={{ background: 'rgba(255,255,255,0.82)', backdropFilter: 'blur(12px)', border: '1px solid rgba(255,255,255,0.9)' }}
          >
            <p className="text-xs font-bold text-gray-400 uppercase tracking-wide mb-3">Artiste</p>
            <div className="flex items-center gap-3">
              <div
                className="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-base flex-shrink-0"
                style={{ background: 'linear-gradient(135deg, #1A2744, #2196F3)' }}
              >
                {booking.talent_profile.stage_name[0]}
              </div>
              <div>
                <p className="font-bold text-gray-900">{booking.talent_profile.stage_name}</p>
              </div>
            </div>
            <Link
              href={`/talents/${booking.talent_profile.slug}`}
              className="text-xs font-semibold mt-3 inline-block hover:underline"
              style={{ color: '#2196F3' }}
            >
              Voir le profil →
            </Link>
          </div>
        )}

        {/* Event details */}
        <div
          className="rounded-2xl p-5"
          style={{ background: 'rgba(255,255,255,0.82)', backdropFilter: 'blur(12px)', border: '1px solid rgba(255,255,255,0.9)' }}
        >
          <p className="text-xs font-bold text-gray-400 uppercase tracking-wide mb-3">Événement</p>
          <div className="space-y-3">
            <div className="flex items-start gap-2">
              <Calendar size={14} className="text-gray-400 mt-0.5 flex-shrink-0" />
              <div>
                <p className="text-xs text-gray-400">Date</p>
                <p className="text-sm font-semibold capitalize text-gray-800">
                  {new Date(booking.event_date + 'T12:00:00').toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}
                </p>
              </div>
            </div>
            <div className="flex items-start gap-2">
              <MapPin size={14} className="text-gray-400 mt-0.5 flex-shrink-0" />
              <div>
                <p className="text-xs text-gray-400">Lieu</p>
                <p className="text-sm font-semibold text-gray-800">{booking.event_location}</p>
              </div>
            </div>
            {booking.service_package && (
              <div className="flex items-start gap-2">
                <FileText size={14} className="text-gray-400 mt-0.5 flex-shrink-0" />
                <div>
                  <p className="text-xs text-gray-400">Package</p>
                  <p className="text-sm font-semibold text-gray-800">
                    {booking.service_package.name}
                    {booking.service_package.duration_minutes && ` · ${formatDuration(booking.service_package.duration_minutes)}`}
                  </p>
                  {booking.devis && (
                    <p className="text-sm font-bold mt-0.5" style={{ color: '#FF6B35' }}>
                      {formatCachet(booking.devis.cachet_amount)}
                    </p>
                  )}
                </div>
              </div>
            )}
            {booking.message && (
              <div className="flex items-start gap-2">
                <MessageSquare size={14} className="text-gray-400 mt-0.5 flex-shrink-0" />
                <div>
                  <p className="text-xs text-gray-400">Message</p>
                  <p className="text-sm text-gray-600">{booking.message}</p>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Status timeline */}
      {!['cancelled', 'rejected'].includes(booking.status) && (
        <div
          className="rounded-2xl p-5"
          style={{ background: 'rgba(255,255,255,0.82)', backdropFilter: 'blur(12px)', border: '1px solid rgba(255,255,255,0.9)' }}
        >
          <p className="text-xs font-bold text-gray-400 uppercase tracking-wide mb-5">Progression</p>
          <div className="flex items-center gap-0">
            {statusSteps.map((step, i) => {
              const done = i <= currentStepIndex;
              const active = i === currentStepIndex;
              return (
                <div key={step.key} className="flex items-center flex-1">
                  <div className="flex flex-col items-center flex-shrink-0">
                    <div
                      className="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all"
                      style={{
                        background: done ? (active ? '#2196F3' : '#E8F5E9') : '#F1F5F9',
                        color: done ? (active ? 'white' : '#10B981') : '#CBD5E1',
                        border: active ? '2px solid #2196F3' : '2px solid transparent',
                      }}
                    >
                      {i + 1}
                    </div>
                    <p className="text-xs font-semibold mt-1 text-center" style={{ color: done ? '#1A2744' : '#CBD5E1' }}>
                      {step.label}
                    </p>
                  </div>
                  {i < statusSteps.length - 1 && (
                    <div className="flex-1 h-0.5 mx-2" style={{ background: i < currentStepIndex ? '#10B981' : '#E2E8F0' }} />
                  )}
                </div>
              );
            })}
          </div>
          <p className="text-xs text-gray-400 mt-4 text-center">
            {statusSteps[Math.max(0, currentStepIndex)]?.desc}
          </p>
        </div>
      )}

      {/* Tracking section */}
      {latestTracking && (
        <div
          className="rounded-2xl p-5"
          style={{ background: 'rgba(255,255,255,0.82)', backdropFilter: 'blur(12px)', border: '1px solid rgba(255,255,255,0.9)' }}
        >
          <p className="text-xs font-bold text-gray-400 uppercase tracking-wide mb-3">Suivi en temps réel</p>
          <div className="flex items-center gap-3">
            <div
              className="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
              style={{ background: `${trackingLabels[latestTracking.status]?.color}20` }}
            >
              <Package size={18} style={{ color: trackingLabels[latestTracking.status]?.color }} />
            </div>
            <div>
              <p className="font-bold text-gray-900">
                {trackingLabels[latestTracking.status]?.label ?? latestTracking.status}
              </p>
              <p className="text-xs text-gray-400">
                Mis à jour à {new Date(latestTracking.occurred_at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}
              </p>
            </div>
            <span
              className="ml-auto text-xs font-semibold px-2.5 py-1 rounded-full"
              style={{
                background: `${trackingLabels[latestTracking.status]?.color}15`,
                color: trackingLabels[latestTracking.status]?.color,
              }}
            >
              En direct
            </span>
          </div>
        </div>
      )}

      {/* Review section (confirmed or completed + not yet reviewed) */}
      {['confirmed', 'completed'].includes(booking.status) && !clientReview && !reviewSent && (
        <div
          className="rounded-2xl p-5"
          style={{ background: 'rgba(255,255,255,0.82)', backdropFilter: 'blur(12px)', border: '1px solid rgba(255,255,255,0.9)' }}
        >
          <p className="text-xs font-bold text-gray-400 uppercase tracking-wide mb-3">Votre avis</p>
          <p className="text-sm text-gray-600 mb-4">Comment s&apos;est passée votre prestation avec {booking.talent_profile?.stage_name} ?</p>
          <div className="flex items-center gap-1 mb-4">
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
            {reviewRating > 0 && (
              <span className="text-sm text-gray-500 ml-2">{reviewRating}/5</span>
            )}
          </div>
          <textarea
            className="w-full rounded-xl p-3 text-sm text-gray-700 resize-none"
            style={{ border: '1.5px solid rgba(0,0,0,0.10)', minHeight: '5rem', outline: 'none' }}
            placeholder="Partagez votre expérience (optionnel)..."
            value={reviewComment}
            onChange={(e) => setReviewComment(e.target.value)}
          />
          <div className="flex justify-end mt-3">
            <button
              onClick={() => reviewMutation.mutate()}
              disabled={reviewRating === 0 || reviewMutation.isPending}
              className="flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-bold disabled:opacity-60 transition-all"
              style={{ background: '#F59E0B' }}
            >
              <Star size={14} fill="white" />
              {reviewMutation.isPending ? 'Envoi...' : 'Envoyer mon avis'}
            </button>
          </div>
        </div>
      )}

      {(reviewSent || clientReview) && (
        <div className="flex items-center gap-2 text-green-700 text-sm bg-green-50 px-4 py-3 rounded-2xl">
          <CheckCircle size={16} /> Merci pour votre avis !
        </div>
      )}

      {/* Actions */}
      <div className="flex flex-wrap gap-3">
        {/* Pay button (accepted) */}
        {booking.status === 'accepted' && (
          <button
            onClick={() => { setPaymentOpen(true); setPaymentStep('method'); setError(null); }}
            className="flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-bold transition-colors"
            style={{ background: 'linear-gradient(135deg, #FF6B35, #C85A20)' }}
          >
            <CreditCard size={15} /> Payer maintenant
          </button>
        )}

        {/* Escrow confirm (paid) */}
        {booking.status === 'paid' && (
          <button
            onClick={() => setDeliveryOpen(true)}
            className="flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-bold transition-colors"
            style={{ background: '#10B981' }}
          >
            <CheckCircle size={15} /> Confirmer la réception
          </button>
        )}

        {/* Download contract */}
        {booking.status === 'confirmed' && booking.contract_available && (
          <button
            onClick={handleDownloadContract}
            className="flex items-center gap-2 px-5 py-2.5 rounded-xl border text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors"
          >
            <Download size={15} /> Télécharger le contrat
          </button>
        )}

        {/* Message talent */}
        {booking.talent_profile && (
          <Link
            href="/client/messages"
            className="flex items-center gap-2 px-5 py-2.5 rounded-xl border text-sm font-semibold transition-colors hover:bg-gray-50"
            style={{ borderColor: 'rgba(33,150,243,0.25)', color: '#2196F3' }}
          >
            <MessageSquare size={15} /> Contacter l&apos;artiste
          </Link>
        )}

        {/* Cancel (pending only) */}
        {booking.status === 'pending' && (
          <button
            onClick={() => setCancelOpen(true)}
            className="flex items-center gap-2 px-5 py-2.5 rounded-xl border text-sm font-semibold transition-colors"
            style={{ borderColor: 'rgba(239,68,68,0.25)', color: '#EF4444', background: 'rgba(239,68,68,0.04)' }}
          >
            Annuler la réservation
          </button>
        )}
      </div>

      {/* ── Payment dialog ── */}
      {paymentOpen && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center p-4"
          style={{ background: 'rgba(0,0,0,0.5)', backdropFilter: 'blur(4px)' }}
        >
          <div className="w-full max-w-sm rounded-2xl bg-white p-6" style={{ boxShadow: '0 24px 64px rgba(0,0,0,0.2)' }}>
            <div className="flex items-center justify-between mb-5">
              <h3 className="font-extrabold text-gray-900 flex items-center gap-2">
                <CreditCard size={18} style={{ color: '#FF6B35' }} />
                {paymentStep === 'otp' ? 'Saisir le code OTP' : 'Payer la réservation'}
              </h3>
              <button onClick={() => { setPaymentOpen(false); setPaymentStep('method'); setError(null); }}>
                <X size={18} className="text-gray-400" />
              </button>
            </div>

            {error && <Alert className="bg-red-50 border-red-200 text-red-800 text-sm mb-4">{error}</Alert>}

            {paymentStep === 'method' && (
              <>
                <p className="text-xs text-gray-500 mb-3">Choisissez votre méthode de paiement</p>
                <div className="grid grid-cols-2 gap-3 mb-5">
                  {PAYMENT_METHODS.map((m) => (
                    <button
                      key={m.value}
                      onClick={() => setPaymentMethod(m.value)}
                      className="py-3 px-3 rounded-xl border text-sm font-semibold transition-all text-center"
                      style={{
                        borderColor: paymentMethod === m.value ? m.color : 'rgba(0,0,0,0.10)',
                        background: paymentMethod === m.value ? `${m.color}10` : 'white',
                        color: paymentMethod === m.value ? m.color : '#374151',
                      }}
                    >
                      {m.label}
                    </button>
                  ))}
                </div>
                <button
                  onClick={() => setPaymentStep('phone')}
                  className="w-full py-3 rounded-xl text-white text-sm font-bold transition-all"
                  style={{ background: 'linear-gradient(135deg, #FF6B35, #C85A20)' }}
                >
                  Continuer
                </button>
              </>
            )}

            {paymentStep === 'phone' && (
              <>
                <p className="text-xs text-gray-500 mb-3">Numéro {PAYMENT_METHODS.find(m => m.value === paymentMethod)?.label}</p>
                <input
                  type="tel"
                  className="w-full px-4 py-3 rounded-xl border text-sm mb-5 outline-none"
                  style={{ borderColor: 'rgba(0,0,0,0.12)' }}
                  placeholder="+225XXXXXXXXXX"
                  value={paymentPhone}
                  onChange={(e) => setPaymentPhone(e.target.value)}
                />
                {booking.devis && (
                  <div className="bg-gray-50 rounded-xl p-3 mb-5">
                    <div className="flex justify-between text-xs text-gray-500 mb-1">
                      <span>Cachet artiste</span>
                      <span>{formatCachet(booking.devis.cachet_amount)}</span>
                    </div>
                    <div className="flex justify-between text-xs text-gray-500 mb-2">
                      <span>Frais de service</span>
                      <span>{formatCachet(booking.devis.commission_amount)}</span>
                    </div>
                    <div className="flex justify-between text-sm font-extrabold text-gray-900">
                      <span>Total</span>
                      <span style={{ color: '#FF6B35' }}>{formatCachet(booking.devis.total_amount)}</span>
                    </div>
                  </div>
                )}
                <div className="flex gap-3">
                  <button
                    onClick={() => setPaymentStep('method')}
                    className="flex-1 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700"
                  >
                    Retour
                  </button>
                  <button
                    onClick={() => paymentInitiateMutation.mutate()}
                    disabled={!paymentPhone || paymentInitiateMutation.isPending}
                    className="flex-1 py-2.5 rounded-xl text-white text-sm font-bold disabled:opacity-60"
                    style={{ background: 'linear-gradient(135deg, #FF6B35, #C85A20)' }}
                  >
                    {paymentInitiateMutation.isPending ? 'Traitement...' : 'Payer'}
                  </button>
                </div>
              </>
            )}

            {paymentStep === 'otp' && (
              <>
                <p className="text-sm text-gray-600 mb-4">
                  Un code de confirmation a été envoyé à votre numéro. Veuillez le saisir ci-dessous.
                </p>
                <input
                  type="text"
                  className="w-full px-4 py-3 rounded-xl border text-center text-lg font-bold tracking-widest mb-5 outline-none"
                  style={{ borderColor: 'rgba(0,0,0,0.12)' }}
                  placeholder="000000"
                  maxLength={6}
                  value={paymentOtp}
                  onChange={(e) => setPaymentOtp(e.target.value)}
                />
                <button
                  onClick={() => paymentOtpMutation.mutate()}
                  disabled={paymentOtp.length < 4 || paymentOtpMutation.isPending}
                  className="w-full py-3 rounded-xl text-white text-sm font-bold disabled:opacity-60"
                  style={{ background: 'linear-gradient(135deg, #FF6B35, #C85A20)' }}
                >
                  {paymentOtpMutation.isPending ? 'Vérification...' : 'Confirmer le paiement'}
                </button>
              </>
            )}
          </div>
        </div>
      )}

      {/* ── Escrow confirm dialog ── */}
      {deliveryOpen && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center p-4"
          style={{ background: 'rgba(0,0,0,0.5)', backdropFilter: 'blur(4px)' }}
        >
          <div className="w-full max-w-sm rounded-2xl bg-white p-6" style={{ boxShadow: '0 24px 64px rgba(0,0,0,0.2)' }}>
            <div className="flex items-center justify-between mb-4">
              <h3 className="font-extrabold text-gray-900">Confirmer la réception ?</h3>
              <button onClick={() => setDeliveryOpen(false)}><X size={18} className="text-gray-400" /></button>
            </div>
            <p className="text-sm text-gray-500 mb-6">
              En confirmant, vous attestez que la prestation a bien eu lieu et que le paiement sera libéré vers l&apos;artiste. Cette action est irréversible.
            </p>
            <div className="flex gap-3">
              <button
                onClick={() => setDeliveryOpen(false)}
                className="flex-1 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700"
              >
                Annuler
              </button>
              <button
                onClick={() => deliveryMutation.mutate()}
                disabled={deliveryMutation.isPending}
                className="flex-1 py-2.5 rounded-xl text-white text-sm font-bold disabled:opacity-60"
                style={{ background: '#10B981' }}
              >
                {deliveryMutation.isPending ? 'Confirmation...' : 'Confirmer'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ── Cancel confirmation dialog ── */}
      {cancelOpen && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center p-4"
          style={{ background: 'rgba(0,0,0,0.5)', backdropFilter: 'blur(4px)' }}
        >
          <div className="w-full max-w-sm rounded-2xl bg-white p-6" style={{ boxShadow: '0 24px 64px rgba(0,0,0,0.2)' }}>
            <div className="flex items-center justify-between mb-4">
              <h3 className="font-extrabold text-gray-900">Annuler la réservation ?</h3>
              <button onClick={() => setCancelOpen(false)}><X size={18} className="text-gray-400" /></button>
            </div>
            <p className="text-sm text-gray-500 mb-6">
              Cette action est irréversible. Êtes-vous sûr de vouloir annuler cette réservation ?
            </p>
            <div className="flex gap-3">
              <button
                onClick={() => setCancelOpen(false)}
                className="flex-1 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700"
              >
                Non, garder
              </button>
              <button
                onClick={() => cancelMutation.mutate()}
                disabled={cancelMutation.isPending}
                className="flex-1 py-2.5 rounded-xl text-white text-sm font-bold disabled:opacity-60"
                style={{ background: '#EF4444' }}
              >
                {cancelMutation.isPending ? 'Annulation...' : 'Oui, annuler'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
