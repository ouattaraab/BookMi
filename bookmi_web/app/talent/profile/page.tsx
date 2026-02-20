'use client';

import { useState, useEffect } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import { talentApi, talentProfileApi } from '@/lib/api/endpoints';
import { useAuthStore } from '@/lib/store/auth';
import { Alert } from '@/components/ui/alert';
import { Save, User, CreditCard, MessageCircle, CheckCircle } from 'lucide-react';

type SocialLinks = {
  instagram?: string;
  youtube?: string;
  tiktok?: string;
  facebook?: string;
  twitter?: string;
};

const PAYOUT_METHODS = [
  { value: 'orange_money', label: 'Orange Money' },
  { value: 'wave', label: 'Wave' },
  { value: 'mtn_momo', label: 'MTN Mobile Money' },
  { value: 'moov_money', label: 'Moov Money' },
  { value: 'bank_transfer', label: 'Virement bancaire' },
];

const MOBILE_METHODS = ['orange_money', 'wave', 'mtn_momo', 'moov_money'];

export default function TalentProfilePage() {
  const user = useAuthStore((s) => s.user);
  const profileId = user?.talentProfile?.id;

  // ── Profile form state ─────────────────────────────────────────────────────
  const [stageName, setStageName] = useState('');
  const [city, setCity] = useState('');
  const [cachet, setCachet] = useState('');
  const [bio, setBio] = useState('');
  const [socialLinks, setSocialLinks] = useState<SocialLinks>({});
  const [profileSuccess, setProfileSuccess] = useState(false);
  const [profileError, setProfileError] = useState<string | null>(null);

  // ── Payout form state ──────────────────────────────────────────────────────
  const [payoutMethod, setPayoutMethod] = useState('orange_money');
  const [payoutPhone, setPayoutPhone] = useState('');
  const [payoutIban, setPayoutIban] = useState('');
  const [payoutBank, setPayoutBank] = useState('');
  const [payoutSuccess, setPayoutSuccess] = useState(false);
  const [payoutError, setPayoutError] = useState<string | null>(null);

  // ── Auto-reply form state ──────────────────────────────────────────────────
  const [autoReplyActive, setAutoReplyActive] = useState(false);
  const [autoReplyMessage, setAutoReplyMessage] = useState('');
  const [autoReplySuccess, setAutoReplySuccess] = useState(false);
  const [autoReplyError, setAutoReplyError] = useState<string | null>(null);

  // ── Load current profile ───────────────────────────────────────────────────
  const { data, isLoading } = useQuery({
    queryKey: ['talent_my_profile'],
    queryFn: () => talentApi.getMyProfile(),
    enabled: !!profileId,
  });

  useEffect(() => {
    const p = data?.data?.data ?? data?.data;
    if (!p) return;
    setStageName(p.stage_name ?? '');
    setCity(p.city ?? '');
    setCachet(p.cachet_amount ? String(Math.round(p.cachet_amount / 100)) : '');
    setBio(p.bio ?? '');
    setSocialLinks(p.social_links ?? {});
    if (p.payout_method) setPayoutMethod(p.payout_method);
    if (p.payout_details) {
      setPayoutPhone(p.payout_details.phone ?? '');
      setPayoutIban(p.payout_details.iban ?? '');
      setPayoutBank(p.payout_details.bank_name ?? '');
    }
    if (typeof p.auto_reply_is_active === 'boolean') setAutoReplyActive(p.auto_reply_is_active);
    setAutoReplyMessage(p.auto_reply_message ?? '');
  }, [data]);

  // ── Mutations ──────────────────────────────────────────────────────────────
  const profileMutation = useMutation({
    mutationFn: () => {
      if (!profileId) throw new Error('Profil introuvable');
      return talentProfileApi.update(profileId, {
        stage_name: stageName,
        city,
        cachet_amount: Math.round(Number(cachet) * 100),
        bio,
        social_links: socialLinks,
      });
    },
    onSuccess: () => {
      setProfileSuccess(true);
      setProfileError(null);
      setTimeout(() => setProfileSuccess(false), 3000);
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { message?: string; error?: { message?: string } } } };
      setProfileError(e?.response?.data?.error?.message ?? e?.response?.data?.message ?? 'Erreur lors de la mise à jour');
    },
  });

  const payoutMutation = useMutation({
    mutationFn: () => {
      const details: Record<string, string> = MOBILE_METHODS.includes(payoutMethod)
        ? { phone: payoutPhone }
        : { iban: payoutIban, bank_name: payoutBank };
      return talentProfileApi.updatePayout({ payout_method: payoutMethod, payout_details: details });
    },
    onSuccess: () => {
      setPayoutSuccess(true);
      setPayoutError(null);
      setTimeout(() => setPayoutSuccess(false), 3000);
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { message?: string } } };
      setPayoutError(e?.response?.data?.message ?? 'Erreur lors de la mise à jour');
    },
  });

  const autoReplyMutation = useMutation({
    mutationFn: () =>
      talentProfileApi.updateAutoReply({
        auto_reply_message: autoReplyMessage,
        auto_reply_is_active: autoReplyActive,
      }),
    onSuccess: () => {
      setAutoReplySuccess(true);
      setAutoReplyError(null);
      setTimeout(() => setAutoReplySuccess(false), 3000);
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { message?: string } } };
      setAutoReplyError(e?.response?.data?.message ?? 'Erreur lors de la mise à jour');
    },
  });

  const cardStyle = {
    background: 'rgba(255,255,255,0.82)',
    backdropFilter: 'blur(12px)',
    border: '1px solid rgba(255,255,255,0.9)',
    borderRadius: '1rem',
    padding: '1.5rem',
  };

  const inputStyle = {
    width: '100%',
    padding: '0.625rem 0.875rem',
    borderRadius: '0.625rem',
    border: '1.5px solid rgba(0,0,0,0.10)',
    fontSize: '0.875rem',
    outline: 'none',
    background: 'rgba(255,255,255,0.9)',
  };

  const labelStyle = { fontSize: '0.75rem', fontWeight: 600, color: '#6B7280', marginBottom: '0.25rem', display: 'block' };

  if (isLoading) {
    return (
      <div className="space-y-4 max-w-2xl mx-auto">
        {[...Array(3)].map((_, i) => (
          <div key={i} className="h-48 rounded-2xl animate-pulse" style={{ background: 'rgba(255,255,255,0.5)' }} />
        ))}
      </div>
    );
  }

  return (
    <div className="space-y-6 max-w-2xl mx-auto">
      <div>
        <h1 className="text-2xl font-extrabold text-gray-900">Mon profil</h1>
        <p className="text-sm text-gray-500 mt-1">Gérez vos informations publiques et vos préférences</p>
      </div>

      {/* ── Section 1 — Informations du profil ── */}
      <div style={cardStyle}>
        <div className="flex items-center gap-2 mb-5">
          <div className="w-8 h-8 rounded-lg flex items-center justify-center" style={{ background: 'rgba(255,107,53,0.12)' }}>
            <User size={16} style={{ color: '#FF6B35' }} />
          </div>
          <h2 className="font-bold text-gray-900">Informations publiques</h2>
        </div>

        {profileError && <Alert className="bg-red-50 border-red-200 text-red-800 text-sm mb-4">{profileError}</Alert>}
        {profileSuccess && (
          <div className="flex items-center gap-2 text-green-700 text-sm mb-4 bg-green-50 px-3 py-2 rounded-lg">
            <CheckCircle size={15} /> Profil mis à jour avec succès
          </div>
        )}

        <div className="space-y-4">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label style={labelStyle}>Nom de scène</label>
              <input style={inputStyle} value={stageName} onChange={(e) => setStageName(e.target.value)} placeholder="Votre nom de scène" />
            </div>
            <div>
              <label style={labelStyle}>Ville</label>
              <input style={inputStyle} value={city} onChange={(e) => setCity(e.target.value)} placeholder="Ex: Abidjan" />
            </div>
          </div>

          <div>
            <label style={labelStyle}>Cachet (FCFA)</label>
            <input style={inputStyle} type="number" value={cachet} onChange={(e) => setCachet(e.target.value)} placeholder="Ex: 150000" />
          </div>

          <div>
            <label style={labelStyle}>Bio</label>
            <textarea
              style={{ ...inputStyle, minHeight: '8rem', resize: 'vertical' }}
              value={bio}
              onChange={(e) => setBio(e.target.value)}
              placeholder="Décrivez votre art, votre expérience..."
            />
          </div>

          <div>
            <label style={{ ...labelStyle, marginBottom: '0.75rem' }}>Réseaux sociaux</label>
            <div className="space-y-2">
              {(['instagram', 'youtube', 'tiktok', 'facebook', 'twitter'] as const).map((platform) => (
                <div key={platform} className="flex items-center gap-3">
                  <span className="text-xs font-semibold text-gray-500 w-20 capitalize">{platform}</span>
                  <input
                    style={{ ...inputStyle, flex: 1 }}
                    value={socialLinks[platform] ?? ''}
                    onChange={(e) => setSocialLinks((prev) => ({ ...prev, [platform]: e.target.value }))}
                    placeholder={`https://${platform}.com/...`}
                  />
                </div>
              ))}
            </div>
          </div>

          <div className="flex justify-end">
            <button
              onClick={() => profileMutation.mutate()}
              disabled={profileMutation.isPending}
              className="flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-bold disabled:opacity-60 transition-all"
              style={{ background: 'linear-gradient(135deg, #FF6B35, #C85A20)' }}
            >
              <Save size={15} />
              {profileMutation.isPending ? 'Enregistrement...' : 'Enregistrer le profil'}
            </button>
          </div>
        </div>
      </div>

      {/* ── Section 2 — Méthode de paiement ── */}
      <div style={cardStyle}>
        <div className="flex items-center gap-2 mb-5">
          <div className="w-8 h-8 rounded-lg flex items-center justify-center" style={{ background: 'rgba(33,150,243,0.12)' }}>
            <CreditCard size={16} style={{ color: '#2196F3' }} />
          </div>
          <h2 className="font-bold text-gray-900">Méthode de paiement</h2>
        </div>

        {payoutError && <Alert className="bg-red-50 border-red-200 text-red-800 text-sm mb-4">{payoutError}</Alert>}
        {payoutSuccess && (
          <div className="flex items-center gap-2 text-green-700 text-sm mb-4 bg-green-50 px-3 py-2 rounded-lg">
            <CheckCircle size={15} /> Méthode de paiement mise à jour
          </div>
        )}

        <div className="space-y-4">
          <div>
            <label style={labelStyle}>Méthode de virement</label>
            <select
              style={{ ...inputStyle, cursor: 'pointer' }}
              value={payoutMethod}
              onChange={(e) => setPayoutMethod(e.target.value)}
            >
              {PAYOUT_METHODS.map((m) => (
                <option key={m.value} value={m.value}>{m.label}</option>
              ))}
            </select>
          </div>

          {MOBILE_METHODS.includes(payoutMethod) ? (
            <div>
              <label style={labelStyle}>Numéro de téléphone</label>
              <input
                style={inputStyle}
                value={payoutPhone}
                onChange={(e) => setPayoutPhone(e.target.value)}
                placeholder="+225XXXXXXXXXX"
              />
            </div>
          ) : (
            <div className="space-y-3">
              <div>
                <label style={labelStyle}>IBAN</label>
                <input style={inputStyle} value={payoutIban} onChange={(e) => setPayoutIban(e.target.value)} placeholder="CI..." />
              </div>
              <div>
                <label style={labelStyle}>Nom de la banque</label>
                <input style={inputStyle} value={payoutBank} onChange={(e) => setPayoutBank(e.target.value)} placeholder="Ex: SGBCI" />
              </div>
            </div>
          )}

          <div className="flex justify-end">
            <button
              onClick={() => payoutMutation.mutate()}
              disabled={payoutMutation.isPending}
              className="flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-bold disabled:opacity-60 transition-all"
              style={{ background: '#2196F3' }}
            >
              <Save size={15} />
              {payoutMutation.isPending ? 'Enregistrement...' : 'Enregistrer'}
            </button>
          </div>
        </div>
      </div>

      {/* ── Section 3 — Réponse automatique ── */}
      <div style={cardStyle}>
        <div className="flex items-center gap-2 mb-5">
          <div className="w-8 h-8 rounded-lg flex items-center justify-center" style={{ background: 'rgba(16,185,129,0.12)' }}>
            <MessageCircle size={16} style={{ color: '#10B981' }} />
          </div>
          <h2 className="font-bold text-gray-900">Réponse automatique</h2>
        </div>

        {autoReplyError && <Alert className="bg-red-50 border-red-200 text-red-800 text-sm mb-4">{autoReplyError}</Alert>}
        {autoReplySuccess && (
          <div className="flex items-center gap-2 text-green-700 text-sm mb-4 bg-green-50 px-3 py-2 rounded-lg">
            <CheckCircle size={15} /> Réponse automatique mise à jour
          </div>
        )}

        <div className="space-y-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-semibold text-gray-800">Activer la réponse automatique</p>
              <p className="text-xs text-gray-400">Envoie ce message à chaque nouvelle réservation reçue</p>
            </div>
            <button
              onClick={() => setAutoReplyActive(!autoReplyActive)}
              className="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
              style={{ background: autoReplyActive ? '#10B981' : '#D1D5DB' }}
            >
              <span
                className="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                style={{ transform: autoReplyActive ? 'translateX(1.375rem)' : 'translateX(0.125rem)' }}
              />
            </button>
          </div>

          {autoReplyActive && (
            <div>
              <label style={labelStyle}>Message de réponse automatique</label>
              <textarea
                style={{ ...inputStyle, minHeight: '6rem', resize: 'vertical' }}
                value={autoReplyMessage}
                onChange={(e) => setAutoReplyMessage(e.target.value)}
                placeholder="Merci pour votre réservation ! Je vous recontacterai dans les plus brefs délais..."
              />
            </div>
          )}

          <div className="flex justify-end">
            <button
              onClick={() => autoReplyMutation.mutate()}
              disabled={autoReplyMutation.isPending}
              className="flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-bold disabled:opacity-60 transition-all"
              style={{ background: '#10B981' }}
            >
              <Save size={15} />
              {autoReplyMutation.isPending ? 'Enregistrement...' : 'Enregistrer'}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
