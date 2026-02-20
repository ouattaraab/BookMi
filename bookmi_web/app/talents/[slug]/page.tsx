'use client';

import { useState } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { useQuery, useMutation } from '@tanstack/react-query';
import Link from 'next/link';
import Image from 'next/image';
import { publicApi, bookingApi, favoriteApi, portfolioApi } from '@/lib/api/endpoints';
import { useAuthStore } from '@/lib/store/auth';
import {
  Star, CheckCircle2, MapPin, Clock, Heart, Share2,
  ArrowLeft, Calendar, X, Zap, Film, Music, ExternalLink,
} from 'lucide-react';
import { Alert } from '@/components/ui/alert';

// ── Types ──────────────────────────────────────────────────────────────────────

type Package = {
  id: number;
  name: string;
  description?: string;
  duration_minutes?: number;
  cachet_amount: number;
  type: string;
  is_active: boolean;
};

type Review = {
  id: number;
  rating: number;
  comment?: string;
  client_name?: string;
  created_at: string;
};

type PortfolioItem = {
  id: number;
  media_type: 'image' | 'video' | 'link';
  url: string;
  link_url?: string;
  link_platform?: string;
  caption?: string;
};

const PLATFORM_LABELS: Record<string, string> = {
  youtube: 'YouTube', deezer: 'Deezer', apple_music: 'Apple Music',
  facebook: 'Facebook', tiktok: 'TikTok', soundcloud: 'SoundCloud',
};

type TalentProfile = {
  id: number;
  stage_name: string;
  slug: string;
  bio?: string;
  city?: string;
  cachet_amount: number;
  average_rating?: number;
  is_verified: boolean;
  talent_level: string;
  reliability_score?: number;
  category?: { name: string; color_hex?: string };
  service_packages?: Package[];
  recent_reviews?: Review[];
};

// ── Helpers ────────────────────────────────────────────────────────────────────

function formatCachet(c: number) {
  return new Intl.NumberFormat('fr-FR').format(Math.round(c / 100)) + ' FCFA';
}

function formatDuration(min?: number) {
  if (!min) return null;
  if (min < 60) return `${min} min`;
  const h = Math.floor(min / 60);
  const m = min % 60;
  return m ? `${h}h${String(m).padStart(2, '0')}` : `${h}h`;
}

function StarRating({ rating, size = 14 }: { rating?: number; size?: number }) {
  const r = rating ?? 0;
  return (
    <div className="flex items-center gap-0.5">
      {[1, 2, 3, 4, 5].map((i) => (
        <Star
          key={i}
          size={size}
          className={i <= Math.round(r) ? 'fill-amber-400 text-amber-400' : 'fill-gray-200 text-gray-200'}
        />
      ))}
      {rating ? <span className="text-sm font-semibold text-gray-700 ml-1">{r.toFixed(1)}</span> : null}
    </div>
  );
}

const packageTypeColors: Record<string, string> = {
  essentiel: 'bg-gray-100 text-gray-700',
  standard: 'bg-blue-100 text-blue-700',
  premium: 'bg-amber-100 text-amber-700',
  micro: 'bg-purple-100 text-purple-700',
};

// ── Booking Dialog ─────────────────────────────────────────────────────────────

function BookingDialog({
  talent,
  onClose,
}: {
  talent: TalentProfile;
  onClose: () => void;
}) {
  const router = useRouter();
  const [selectedPkg, setSelectedPkg] = useState<Package | null>(null);
  const [eventDate, setEventDate] = useState('');
  const [eventLocation, setEventLocation] = useState('');
  const [message, setMessage] = useState('');
  const [isExpress, setIsExpress] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState(false);

  const activePackages = (talent.service_packages ?? []).filter((p) => p.is_active);
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  const minDate = tomorrow.toISOString().split('T')[0];

  const bookMutation = useMutation({
    mutationFn: () =>
      bookingApi.create({
        talent_profile_id: talent.id,
        service_package_id: selectedPkg!.id,
        event_date: eventDate,
        event_location: eventLocation,
        message: message || undefined,
        is_express: isExpress,
      }),
    onSuccess: () => {
      setSuccess(true);
      setTimeout(() => router.push('/client/bookings'), 2000);
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { error?: { message?: string }; message?: string } } };
      setError(e?.response?.data?.error?.message ?? e?.response?.data?.message ?? 'Erreur lors de la réservation');
    },
  });

  const canSubmit = selectedPkg && eventDate && eventLocation.trim();

  if (success) {
    return (
      <div className="text-center py-8">
        <div className="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" style={{ background: 'rgba(16,185,129,0.1)' }}>
          <CheckCircle2 size={32} style={{ color: '#10B981' }} />
        </div>
        <h3 className="text-lg font-bold text-gray-900 mb-2">Réservation envoyée !</h3>
        <p className="text-sm text-gray-500">L&apos;artiste va répondre sous 24h. Redirection vers vos réservations...</p>
      </div>
    );
  }

  return (
    <div className="space-y-5">
      <h3 className="text-lg font-extrabold text-gray-900">Réserver {talent.stage_name}</h3>

      {error && <Alert className="bg-red-50 border-red-200 text-red-800 text-sm">{error}</Alert>}

      {/* Packages */}
      <div>
        <label className="text-xs font-bold text-gray-500 uppercase tracking-wide block mb-3">
          Choisir un package
        </label>
        {activePackages.length === 0 ? (
          <p className="text-sm text-gray-400">Aucun package disponible.</p>
        ) : (
          <div className="space-y-2">
            {activePackages.map((pkg) => (
              <button
                key={pkg.id}
                onClick={() => setSelectedPkg(pkg)}
                className="w-full p-4 rounded-xl border-2 text-left transition-all"
                style={{
                  borderColor: selectedPkg?.id === pkg.id ? '#2196F3' : '#E2E8F0',
                  background: selectedPkg?.id === pkg.id ? 'rgba(33,150,243,0.04)' : 'white',
                }}
              >
                <div className="flex items-center justify-between">
                  <div>
                    <div className="flex items-center gap-2">
                      <span className="font-semibold text-sm text-gray-900">{pkg.name}</span>
                      <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${packageTypeColors[pkg.type] ?? 'bg-gray-100 text-gray-600'}`}>
                        {pkg.type}
                      </span>
                    </div>
                    {pkg.description && (
                      <p className="text-xs text-gray-400 mt-0.5">{pkg.description}</p>
                    )}
                    {pkg.duration_minutes && (
                      <p className="text-xs text-gray-400 mt-0.5 flex items-center gap-1">
                        <Clock size={10} /> {formatDuration(pkg.duration_minutes)}
                      </p>
                    )}
                  </div>
                  <span className="font-bold text-sm" style={{ color: '#FF6B35' }}>
                    {formatCachet(pkg.cachet_amount)}
                  </span>
                </div>
              </button>
            ))}
          </div>
        )}
      </div>

      {/* Date */}
      <div>
        <label className="text-xs font-bold text-gray-500 uppercase tracking-wide block mb-2">
          Date de l&apos;événement
        </label>
        <div className="relative">
          <Calendar size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <input
            type="date"
            value={eventDate}
            min={minDate}
            onChange={(e) => setEventDate(e.target.value)}
            className="w-full h-10 rounded-xl border border-gray-200 pl-9 pr-4 text-sm outline-none focus:border-blue-400"
          />
        </div>
      </div>

      {/* Location */}
      <div>
        <label className="text-xs font-bold text-gray-500 uppercase tracking-wide block mb-2">
          Lieu de l&apos;événement
        </label>
        <input
          type="text"
          value={eventLocation}
          onChange={(e) => setEventLocation(e.target.value)}
          placeholder="Ex: Hôtel Ivoire, Cocody, Abidjan"
          className="w-full h-10 rounded-xl border border-gray-200 px-4 text-sm outline-none focus:border-blue-400"
          maxLength={255}
        />
      </div>

      {/* Message */}
      <div>
        <label className="text-xs font-bold text-gray-500 uppercase tracking-wide block mb-2">
          Message (optionnel)
        </label>
        <textarea
          value={message}
          onChange={(e) => setMessage(e.target.value)}
          placeholder="Décrivez votre événement, vos attentes..."
          className="w-full rounded-xl border border-gray-200 p-3 text-sm outline-none focus:border-blue-400 resize-none"
          rows={3}
        />
      </div>

      {/* Express toggle */}
      <label className="flex items-center gap-3 p-3 rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50">
        <input
          type="checkbox"
          checked={isExpress}
          onChange={(e) => setIsExpress(e.target.checked)}
          className="w-4 h-4"
        />
        <div>
          <p className="text-sm font-semibold text-gray-800 flex items-center gap-1">
            <Zap size={13} className="text-amber-500" /> Réservation express
          </p>
          <p className="text-xs text-gray-400">Demande de confirmation en moins de 2h</p>
        </div>
      </label>

      {/* Summary */}
      {selectedPkg && eventDate && (
        <div className="p-4 rounded-xl" style={{ background: 'rgba(33,150,243,0.06)', border: '1px solid rgba(33,150,243,0.15)' }}>
          <p className="text-xs font-bold text-blue-700 uppercase tracking-wide mb-2">Récapitulatif</p>
          <div className="space-y-1 text-sm">
            <p><span className="text-gray-500">Package :</span> <span className="font-semibold">{selectedPkg.name}</span></p>
            <p><span className="text-gray-500">Cachet :</span> <span className="font-bold" style={{ color: '#FF6B35' }}>{formatCachet(selectedPkg.cachet_amount)}</span></p>
            <p><span className="text-gray-500">Date :</span> <span className="font-semibold">{new Date(eventDate + 'T12:00:00').toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' })}</span></p>
          </div>
        </div>
      )}

      <button
        onClick={() => bookMutation.mutate()}
        disabled={!canSubmit || bookMutation.isPending}
        className="w-full py-3 rounded-xl text-white font-bold text-sm disabled:opacity-50 transition-opacity"
        style={{ background: 'linear-gradient(135deg, #FF6B35, #C85A20)', boxShadow: '0 4px 16px rgba(255,107,53,0.35)' }}
      >
        {bookMutation.isPending ? 'Envoi en cours...' : 'Confirmer la réservation'}
      </button>
    </div>
  );
}

// ── Main Page ──────────────────────────────────────────────────────────────────

export default function TalentProfilePage() {
  const { slug } = useParams<{ slug: string }>();
  const router = useRouter();
  const user = useAuthStore((s) => s.user);
  const isClient = useAuthStore((s) => s.isClient());

  const [bookingOpen, setBookingOpen] = useState(false);
  const [isFav, setIsFav] = useState(false);

  const { data, isLoading } = useQuery({
    queryKey: ['public_talent', slug],
    queryFn: () => publicApi.getTalent(slug),
    enabled: !!slug,
  });

  // API may return JSON:API format { id, type, attributes: {...} } or flat
  const rawData = data?.data?.data ?? data?.data;
  const rawTalent = rawData
    ? rawData.attributes
      ? { id: rawData.id, ...rawData.attributes }
      : rawData
    : null;
  const talent: TalentProfile | null = rawTalent
    ? {
        ...rawTalent,
        average_rating: rawTalent.average_rating != null ? Number(rawTalent.average_rating) : undefined,
        service_packages: (rawTalent.service_packages ?? []).map((p: { id: number; attributes?: Record<string, unknown> }) =>
          p.attributes ? { id: p.id, ...p.attributes } : p
        ),
        recent_reviews: (rawTalent.recent_reviews ?? []).map((r: { id: number; attributes?: Record<string, unknown> }) =>
          r.attributes ? { id: r.id, ...r.attributes } : r
        ),
      }
    : null;

  // Check favorite status if authenticated client
  useQuery({
    queryKey: ['fav_check', talent?.id],
    queryFn: async () => {
      const res = await favoriteApi.check(talent!.id);
      setIsFav(res.data?.data?.is_favorite ?? false);
      return res;
    },
    enabled: !!talent?.id && !!user,
  });

  // Portfolio items
  const { data: portfolioData } = useQuery({
    queryKey: ['portfolio', 'public', talent?.id],
    queryFn: () => portfolioApi.list(talent!.id),
    enabled: !!talent?.id,
  });
  const portfolioItems: PortfolioItem[] = portfolioData?.data?.data ?? [];

  const toggleFavMutation = useMutation({
    mutationFn: () =>
      isFav ? favoriteApi.remove(talent!.id) : favoriteApi.add(talent!.id),
    onSuccess: () => setIsFav(!isFav),
  });

  const handleBooking = () => {
    if (!user) {
      router.push(`/login?redirect=/talents/${slug}`);
      return;
    }
    if (!isClient) {
      setBookingOpen(true);
      return;
    }
    setBookingOpen(true);
  };

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center" style={{ background: '#F8FAFC' }}>
        <div className="space-y-3 w-full max-w-2xl px-4">
          {[...Array(3)].map((_, i) => (
            <div key={i} className="h-20 rounded-2xl bg-gray-100 animate-pulse" />
          ))}
        </div>
      </div>
    );
  }

  if (!talent) {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center gap-4" style={{ background: '#F8FAFC' }}>
        <p className="text-gray-500">Talent introuvable.</p>
        <Link href="/talents" className="text-sm font-semibold" style={{ color: '#2196F3' }}>← Retour à la liste</Link>
      </div>
    );
  }

  const initials = talent.stage_name.split(' ').slice(0, 2).map((w) => w[0]).join('').toUpperCase();
  const activePackages = (talent.service_packages ?? []).filter((p) => p.is_active);

  return (
    <div className="min-h-screen" style={{ background: '#F8FAFC', fontFamily: 'var(--font-nunito), Nunito, sans-serif' }}>
      {/* Header */}
      <header
        className="sticky top-0 z-50"
        style={{
          background: 'rgba(255,255,255,0.92)',
          backdropFilter: 'blur(20px)',
          WebkitBackdropFilter: 'blur(20px)',
          borderBottom: '1px solid rgba(26,39,68,0.08)',
          boxShadow: '0 1px 8px rgba(26,39,68,0.06)',
        }}
      >
        <div className="max-w-7xl mx-auto px-4 md:px-8 h-14 flex items-center justify-between">
          <button
            onClick={() => router.back()}
            className="flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-gray-900"
          >
            <ArrowLeft size={16} /> Retour
          </button>
          <Link href="/">
            <Image src="/logo.png" alt="BookMi" width={90} height={28} />
          </Link>
          <div className="flex items-center gap-3">
            {user ? null : (
              <Link href="/login" className="text-sm font-semibold text-gray-600 hover:text-gray-900">Se connecter</Link>
            )}
          </div>
        </div>
      </header>

      <div className="max-w-5xl mx-auto px-4 md:px-8 py-8">
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">

          {/* ── Main content ── */}
          <div className="lg:col-span-2 space-y-6">

            {/* Hero card */}
            <div className="rounded-2xl bg-white border border-gray-100 p-6">
              <div className="flex items-start gap-5">
                <div
                  className="w-20 h-20 rounded-2xl flex items-center justify-center text-white font-extrabold text-2xl flex-shrink-0"
                  style={{ background: 'linear-gradient(135deg, #1A2744, #2196F3)' }}
                >
                  {initials}
                </div>
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2 flex-wrap">
                    <h1 className="text-2xl font-extrabold text-gray-900">{talent.stage_name}</h1>
                    {talent.is_verified && (
                      <span
                        className="flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full"
                        style={{ background: 'rgba(33,150,243,0.1)', color: '#2196F3' }}
                      >
                        <CheckCircle2 size={12} /> Vérifié
                      </span>
                    )}
                  </div>
                  {talent.category && (
                    <span
                      className="inline-block text-xs font-semibold px-3 py-1 rounded-full mt-2"
                      style={{
                        background: `${talent.category.color_hex ?? '#2196F3'}18`,
                        color: talent.category.color_hex ?? '#2196F3',
                      }}
                    >
                      {talent.category.name}
                    </span>
                  )}
                  <div className="flex items-center gap-4 mt-3 flex-wrap">
                    {talent.city && (
                      <p className="flex items-center gap-1 text-sm text-gray-500">
                        <MapPin size={13} /> {talent.city}
                      </p>
                    )}
                    <StarRating rating={talent.average_rating} />
                    {talent.reliability_score !== undefined && (
                      <span className="text-xs text-gray-500">
                        Fiabilité : <span className="font-bold text-green-600">{talent.reliability_score}%</span>
                      </span>
                    )}
                  </div>
                  <p className="text-sm font-bold text-gray-900 mt-2">
                    À partir de{' '}
                    <span style={{ color: '#FF6B35' }}>{formatCachet(talent.cachet_amount)}</span>
                  </p>
                </div>
              </div>

              {/* Action buttons */}
              <div className="flex gap-3 mt-5">
                <button
                  onClick={handleBooking}
                  className="flex-1 py-2.5 rounded-xl text-white font-bold text-sm"
                  style={{ background: 'linear-gradient(135deg, #FF6B35, #C85A20)', boxShadow: '0 4px 12px rgba(255,107,53,0.35)' }}
                >
                  Réserver maintenant
                </button>
                {user && (
                  <button
                    onClick={() => toggleFavMutation.mutate()}
                    className="p-2.5 rounded-xl border border-gray-200 transition-colors hover:border-red-200"
                  >
                    <Heart
                      size={18}
                      className={isFav ? 'fill-red-500 text-red-500' : 'text-gray-400'}
                    />
                  </button>
                )}
                <button className="p-2.5 rounded-xl border border-gray-200 text-gray-400 hover:text-gray-600 transition-colors">
                  <Share2 size={18} />
                </button>
              </div>
            </div>

            {/* Bio */}
            {talent.bio && (
              <div className="rounded-2xl bg-white border border-gray-100 p-6">
                <h2 className="text-base font-extrabold text-gray-900 mb-3">À propos</h2>
                <p className="text-sm text-gray-600 leading-relaxed whitespace-pre-line">{talent.bio}</p>
              </div>
            )}

            {/* Packages */}
            {activePackages.length > 0 && (
              <div className="rounded-2xl bg-white border border-gray-100 p-6">
                <h2 className="text-base font-extrabold text-gray-900 mb-4">Packages de services</h2>
                <div className="space-y-3">
                  {activePackages.map((pkg) => (
                    <div key={pkg.id} className="flex items-center justify-between p-4 rounded-xl border border-gray-100 hover:border-blue-100 transition-colors">
                      <div>
                        <div className="flex items-center gap-2">
                          <p className="font-semibold text-sm text-gray-900">{pkg.name}</p>
                          <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${packageTypeColors[pkg.type] ?? 'bg-gray-100 text-gray-600'}`}>
                            {pkg.type}
                          </span>
                        </div>
                        {pkg.description && <p className="text-xs text-gray-400 mt-0.5">{pkg.description}</p>}
                        {pkg.duration_minutes && (
                          <p className="text-xs text-gray-400 mt-0.5 flex items-center gap-1">
                            <Clock size={10} /> {formatDuration(pkg.duration_minutes)}
                          </p>
                        )}
                      </div>
                      <div className="text-right">
                        <p className="font-bold text-sm" style={{ color: '#FF6B35' }}>{formatCachet(pkg.cachet_amount)}</p>
                        <button
                          onClick={handleBooking}
                          className="text-xs font-semibold mt-1 px-3 py-1 rounded-lg text-white"
                          style={{ background: 'linear-gradient(135deg, #FF6B35, #C85A20)' }}
                        >
                          Réserver
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Reviews */}
            {(talent.recent_reviews ?? []).length > 0 && (
              <div className="rounded-2xl bg-white border border-gray-100 p-6">
                <h2 className="text-base font-extrabold text-gray-900 mb-4">Avis récents</h2>
                <div className="space-y-4">
                  {talent.recent_reviews!.map((review) => (
                    <div key={review.id} className="pb-4 border-b border-gray-50 last:border-0 last:pb-0">
                      <div className="flex items-center justify-between mb-1">
                        <p className="text-sm font-semibold text-gray-800">{review.client_name ?? 'Client'}</p>
                        <StarRating rating={review.rating} size={12} />
                      </div>
                      {review.comment && (
                        <p className="text-sm text-gray-500 leading-relaxed">{review.comment}</p>
                      )}
                      <p className="text-xs text-gray-300 mt-1">
                        {new Date(review.created_at).toLocaleDateString('fr-FR')}
                      </p>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Portfolio */}
            {portfolioItems.length > 0 && (
              <div className="rounded-2xl bg-white border border-gray-100 p-6">
                <h2 className="text-base font-extrabold text-gray-900 mb-4">Portfolio</h2>
                <div className="grid grid-cols-3 gap-3">
                  {portfolioItems.map((item) => (
                    <div key={item.id} className="rounded-xl overflow-hidden border border-gray-100 bg-gray-50">
                      {item.media_type === 'image' && (
                        <a href={item.url} target="_blank" rel="noopener noreferrer" className="block aspect-square overflow-hidden group">
                          {/* eslint-disable-next-line @next/next/no-img-element */}
                          <img
                            src={item.url}
                            alt={item.caption ?? 'Portfolio'}
                            className="w-full h-full object-cover transition-transform group-hover:scale-105"
                          />
                        </a>
                      )}
                      {item.media_type === 'video' && (
                        <a
                          href={item.url}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="aspect-square flex flex-col items-center justify-center bg-gray-900 text-white gap-1 hover:bg-gray-800 transition-colors"
                        >
                          <Film size={24} className="opacity-70" />
                          <span className="text-xs opacity-60">Vidéo</span>
                        </a>
                      )}
                      {item.media_type === 'link' && (
                        <a
                          href={item.url ?? item.link_url}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="aspect-square flex flex-col items-center justify-center gap-2 p-3 hover:bg-gray-100 transition-colors"
                        >
                          <Music size={22} className="text-gray-400" />
                          {item.link_platform && (
                            <span className="text-[10px] font-semibold px-2 py-0.5 rounded bg-gray-200 text-gray-700">
                              {PLATFORM_LABELS[item.link_platform] ?? item.link_platform}
                            </span>
                          )}
                          {item.caption && (
                            <p className="text-[10px] text-gray-500 text-center line-clamp-2">{item.caption}</p>
                          )}
                          <span className="flex items-center gap-1 text-[10px] text-[#FF6B35]">
                            <ExternalLink size={10} /> Ouvrir
                          </span>
                        </a>
                      )}
                      {item.caption && item.media_type === 'image' && (
                        <div className="px-2 py-1 bg-white border-t border-gray-100">
                          <p className="text-[10px] text-gray-500 truncate">{item.caption}</p>
                        </div>
                      )}
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>

          {/* ── Sidebar ── */}
          <div className="space-y-4">
            {/* Sticky booking card */}
            <div className="rounded-2xl bg-white border border-gray-100 p-5 sticky top-20">
              <p className="text-xs text-gray-400 mb-1">Cachet à partir de</p>
              <p className="text-2xl font-extrabold mb-4" style={{ color: '#FF6B35' }}>
                {formatCachet(talent.cachet_amount)}
              </p>
              <button
                onClick={handleBooking}
                className="w-full py-3 rounded-xl text-white font-bold text-sm mb-3"
                style={{ background: 'linear-gradient(135deg, #FF6B35, #C85A20)', boxShadow: '0 4px 12px rgba(255,107,53,0.35)' }}
              >
                Réserver ce talent
              </button>
              {!user && (
                <p className="text-xs text-center text-gray-400">
                  <Link href="/login" className="font-semibold" style={{ color: '#2196F3' }}>
                    Connectez-vous
                  </Link>{' '}
                  pour réserver
                </p>
              )}

              <div className="mt-4 pt-4 border-t border-gray-50 space-y-2">
                <div className="flex items-center justify-between text-sm">
                  <span className="text-gray-500">Niveau</span>
                  <span className="font-semibold capitalize text-gray-800">{talent.talent_level}</span>
                </div>
                {!!talent.average_rating && (
                  <div className="flex items-center justify-between text-sm">
                    <span className="text-gray-500">Note moyenne</span>
                    <StarRating rating={talent.average_rating} size={12} />
                  </div>
                )}
                {talent.reliability_score !== undefined && (
                  <div className="flex items-center justify-between text-sm">
                    <span className="text-gray-500">Fiabilité</span>
                    <span className="font-bold text-green-600">{talent.reliability_score}%</span>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Booking dialog overlay */}
      {bookingOpen && talent && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center p-4"
          style={{ background: 'rgba(0,0,0,0.5)', backdropFilter: 'blur(4px)' }}
          onClick={(e) => { if (e.target === e.currentTarget) setBookingOpen(false); }}
        >
          <div
            className="w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-2xl bg-white p-6"
            style={{ boxShadow: '0 24px 64px rgba(0,0,0,0.25)' }}
          >
            <div className="flex items-center justify-between mb-5">
              <div />
              <button onClick={() => setBookingOpen(false)} className="p-1.5 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                <X size={18} />
              </button>
            </div>
            <BookingDialog talent={talent} onClose={() => setBookingOpen(false)} />
          </div>
        </div>
      )}

      {/* Mobile CTA */}
      <div className="lg:hidden fixed bottom-0 left-0 right-0 p-4 bg-white border-t border-gray-100 z-40">
        <button
          onClick={handleBooking}
          className="w-full py-3 rounded-xl text-white font-bold text-sm"
          style={{ background: 'linear-gradient(135deg, #FF6B35, #C85A20)', boxShadow: '0 4px 12px rgba(255,107,53,0.35)' }}
        >
          Réserver ce talent — {formatCachet(talent.cachet_amount)}
        </button>
      </div>
    </div>
  );
}
