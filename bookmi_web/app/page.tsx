'use client';

import { useState } from 'react';
import Image from 'next/image';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { useQuery } from '@tanstack/react-query';
import { publicApi } from '@/lib/api/endpoints';
import { useAuthStore } from '@/lib/store/auth';
import {
  Star, CheckCircle2, MapPin, Shield, FileText,
  CreditCard, Headphones, Music, Menu, X,
} from 'lucide-react';

// ── Types ─────────────────────────────────────────────────────────────────────

type Category = {
  id: number;
  name: string;
  slug: string;
  color_hex?: string;
  parent_id?: number;
};

type TalentItem = {
  id: number;
  stage_name: string;
  slug: string;
  city?: string;
  cachet_amount: number;
  average_rating?: number;
  is_verified: boolean;
  talent_level: string;
  category?: { name: string; color_hex?: string };
};

// ── Helpers ───────────────────────────────────────────────────────────────────

function formatCachet(centimes: number) {
  return new Intl.NumberFormat('fr-FR').format(Math.round(centimes / 100)) + ' FCFA';
}

function StarRating({ rating }: { rating?: number }) {
  const r = rating ?? 0;
  return (
    <div className="flex items-center gap-0.5">
      {[1, 2, 3, 4, 5].map((i) => (
        <Star
          key={i}
          size={12}
          className={i <= Math.round(r) ? 'fill-amber-400 text-amber-400' : 'fill-gray-200 text-gray-200'}
        />
      ))}
      {rating ? <span className="text-xs text-gray-500 ml-1">{r.toFixed(1)}</span> : null}
    </div>
  );
}

// ── TalentCard ────────────────────────────────────────────────────────────────

function TalentCard({ talent }: { talent: TalentItem }) {
  const initials = talent.stage_name
    .split(' ')
    .slice(0, 2)
    .map((w) => w[0])
    .join('')
    .toUpperCase();

  return (
    <Link
      href={`/talents/${talent.slug}`}
      className="group block rounded-2xl border border-gray-100 bg-white p-5 hover:shadow-xl hover:border-blue-100 transition-all duration-200"
    >
      <div className="flex items-start gap-3 mb-4">
        <div
          className="w-14 h-14 rounded-full flex items-center justify-center text-white font-bold text-lg flex-shrink-0"
          style={{ background: 'linear-gradient(135deg, #1A2744, #2196F3)' }}
        >
          {initials}
        </div>
        <div className="flex-1 min-w-0 pt-0.5">
          <div className="flex items-center gap-1.5">
            <p className="font-bold text-gray-900 truncate text-sm">{talent.stage_name}</p>
            {talent.is_verified && (
              <CheckCircle2 size={14} className="flex-shrink-0" style={{ color: '#2196F3' }} />
            )}
          </div>
          {talent.category && (
            <span
              className="inline-block text-xs font-medium px-2 py-0.5 rounded-full mt-1"
              style={{
                background: `${talent.category.color_hex ?? '#2196F3'}18`,
                color: talent.category.color_hex ?? '#2196F3',
              }}
            >
              {talent.category.name}
            </span>
          )}
        </div>
      </div>

      <div className="space-y-1.5">
        {talent.city && (
          <p className="flex items-center gap-1.5 text-xs text-gray-400">
            <MapPin size={11} />
            {talent.city}
          </p>
        )}
        <StarRating rating={talent.average_rating} />
        <p className="text-sm font-bold text-gray-900">
          À partir de{' '}
          <span style={{ color: '#FF6B35' }}>{formatCachet(talent.cachet_amount)}</span>
        </p>
      </div>

      <div className="mt-4 pt-3 border-t border-gray-50 flex items-center justify-between">
        <span className="text-xs text-gray-400 capitalize">{talent.talent_level}</span>
        <span className="text-xs font-semibold group-hover:underline" style={{ color: '#2196F3' }}>
          Voir le profil →
        </span>
      </div>
    </Link>
  );
}

// ── Page principale ───────────────────────────────────────────────────────────

export default function LandingPage() {
  const router = useRouter();
  const user = useAuthStore((s) => s.user);

  const [activeCategoryId, setActiveCategoryId] = useState<number | null>(null);
  const [searchCity, setSearchCity] = useState('');
  const [searchCategoryId, setSearchCategoryId] = useState('');
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  const { data: catData } = useQuery({
    queryKey: ['public_categories'],
    queryFn: () => publicApi.getCategories(),
  });
  const allCategories: Category[] = catData?.data?.data ?? [];
  const parentCategories = allCategories.slice(0, 6);

  const { data: talentsData, isLoading: talentsLoading } = useQuery({
    queryKey: ['public_talents', activeCategoryId],
    queryFn: () =>
      publicApi.getTalents(
        activeCategoryId ? { category_id: activeCategoryId, per_page: 12 } : { per_page: 12 }
      ),
  });
  // API returns JSON:API format: { id, type, attributes: { stage_name, ... } }
  const rawTalents: { id: number; attributes: Omit<TalentItem, 'id'> & { average_rating?: string | number } }[] = talentsData?.data?.data ?? [];
  const talents: TalentItem[] = rawTalents.map((item) => ({
    id: item.id,
    ...item.attributes,
    average_rating: item.attributes.average_rating != null ? Number(item.attributes.average_rating) : undefined,
  }));

  const handleSearch = () => {
    const params = new URLSearchParams();
    if (searchCity.trim()) params.set('city', searchCity.trim());
    if (searchCategoryId) params.set('category_id', searchCategoryId);
    router.push(`/talents?${params.toString()}`);
  };

  return (
    <div className="min-h-screen bg-white" style={{ fontFamily: 'var(--font-nunito), Nunito, sans-serif' }}>

      {/* ══════════════════════════════════════════════════════════════════════
          HEADER
      ════════════════════════════════════════════════════════════════════════ */}
      <header
        className="sticky top-0 z-50"
        style={{
          background: 'rgba(255,255,255,0.92)',
          backdropFilter: 'blur(20px) saturate(180%)',
          WebkitBackdropFilter: 'blur(20px) saturate(180%)',
          borderBottom: '1px solid rgba(26,39,68,0.08)',
          boxShadow: '0 1px 8px rgba(26,39,68,0.06)',
        }}
      >
        <div className="max-w-7xl mx-auto px-4 md:px-8 h-16 flex items-center justify-between gap-4">
          {/* Logo PNG */}
          <Link href="/" className="flex-shrink-0">
            <Image src="/logo.png" alt="BookMi" width={110} height={34} priority />
          </Link>

          {/* Desktop nav */}
          <nav className="hidden md:flex items-center gap-7 text-sm font-semibold">
            <a href="#artistes" className="text-gray-600 hover:text-gray-900 transition-colors">Artistes</a>
            <a href="#categories" className="text-gray-600 hover:text-gray-900 transition-colors">Catégories</a>
            <a href="#pourquoi" className="text-gray-600 hover:text-gray-900 transition-colors">Pourquoi BookMi</a>
            <a href="#contact" className="text-gray-600 hover:text-gray-900 transition-colors">Contact</a>
          </nav>

          {/* CTAs */}
          <div className="hidden md:flex items-center gap-3">
            {user ? (
              <Link
                href={user.talentProfile ? '/talent/dashboard' : '/client/dashboard'}
                className="px-5 py-2 rounded-xl text-sm font-bold text-white"
                style={{ background: 'linear-gradient(135deg, #FF6B35, #C85A20)', boxShadow: '0 3px 12px rgba(255,107,53,0.35)' }}
              >
                Mon espace
              </Link>
            ) : (
              <>
                <Link
                  href="/login"
                  className="px-4 py-2 rounded-xl text-sm font-semibold border border-gray-200 text-gray-700 hover:bg-gray-50 transition-colors"
                >
                  Se connecter
                </Link>
                <Link
                  href="/register"
                  className="px-4 py-2 rounded-xl text-sm font-bold text-white transition-all"
                  style={{ background: 'linear-gradient(135deg, #FF6B35, #C85A20)', boxShadow: '0 3px 12px rgba(255,107,53,0.35)' }}
                >
                  S&apos;inscrire
                </Link>
              </>
            )}
          </div>

          {/* Mobile hamburger */}
          <button
            className="md:hidden p-2 text-gray-600 hover:text-gray-900"
            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
          >
            {mobileMenuOpen ? <X size={22} /> : <Menu size={22} />}
          </button>
        </div>

        {/* Mobile menu */}
        {mobileMenuOpen && (
          <div className="md:hidden border-t border-gray-100 bg-white px-4 py-4 space-y-3">
            {['#artistes', '#categories', '#pourquoi', '#contact'].map((href) => (
              <a
                key={href}
                href={href}
                className="block text-sm font-semibold text-gray-700 py-1"
                onClick={() => setMobileMenuOpen(false)}
              >
                {href.replace('#', '').charAt(0).toUpperCase() + href.replace('#', '').slice(1).replace('pourquoi', 'Pourquoi BookMi').replace('artistes', 'Artistes').replace('categories', 'Catégories').replace('contact', 'Contact')}
              </a>
            ))}
            <div className="flex gap-3 pt-2">
              <Link href="/login" className="flex-1 text-center px-4 py-2 rounded-lg text-sm font-semibold border border-gray-200 text-gray-700">
                Se connecter
              </Link>
              <Link
                href="/register"
                className="flex-1 text-center px-4 py-2 rounded-lg text-sm font-bold text-white"
                style={{ background: 'linear-gradient(135deg, #FF6B35, #C85A20)' }}
              >
                S&apos;inscrire
              </Link>
            </div>
          </div>
        )}
      </header>

      {/* ══════════════════════════════════════════════════════════════════════
          HERO
      ════════════════════════════════════════════════════════════════════════ */}
      <section
        className="relative pt-24 pb-32 px-4 overflow-hidden"
        style={{ background: 'linear-gradient(135deg, #1A2744 0%, #0D1B35 60%, #162340 100%)' }}
      >
        {/* Glow blobs */}
        <div
          className="absolute top-0 right-0 w-[500px] h-[500px] rounded-full pointer-events-none"
          style={{
            background: 'radial-gradient(circle, rgba(33,150,243,0.15) 0%, transparent 70%)',
            transform: 'translate(25%, -25%)',
          }}
        />
        <div
          className="absolute bottom-0 left-0 w-96 h-96 rounded-full pointer-events-none"
          style={{
            background: 'radial-gradient(circle, rgba(255,107,53,0.12) 0%, transparent 70%)',
            transform: 'translate(-25%, 25%)',
          }}
        />

        <div className="max-w-4xl mx-auto text-center relative">
          <div
            className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-semibold mb-6"
            style={{
              background: 'rgba(33,150,243,0.12)',
              border: '1px solid rgba(33,150,243,0.3)',
              color: '#64B5F6',
            }}
          >
            <span className="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse" />
            500+ talents actifs en Côte d&apos;Ivoire
          </div>

          <h1 className="text-4xl md:text-5xl lg:text-6xl font-extrabold text-white leading-tight tracking-tight mb-6">
            Réservez les meilleurs{' '}
            <span style={{ color: '#FF6B35' }}>talents</span>{' '}
            de Côte d&apos;Ivoire
          </h1>

          <p className="text-lg text-gray-300 mb-10 max-w-2xl mx-auto leading-relaxed">
            DJ, Chanteurs, Musiciens, Humoristes, Danseurs...{' '}
            Trouvez et réservez l&apos;artiste parfait pour votre événement en quelques clics.
          </p>

          {/* Search bar */}
          <div
            className="flex flex-col sm:flex-row gap-0 max-w-2xl mx-auto mb-12 rounded-2xl overflow-hidden"
            style={{ background: 'rgba(255,255,255,0.08)', border: '1px solid rgba(255,255,255,0.15)' }}
          >
            <input
              type="text"
              placeholder="Ville (ex : Abidjan)"
              value={searchCity}
              onChange={(e) => setSearchCity(e.target.value)}
              onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
              className="flex-1 bg-transparent text-white placeholder-gray-400 px-5 py-3.5 text-sm outline-none min-w-0"
            />
            <div className="h-px sm:h-auto sm:w-px bg-white/10" />
            <select
              value={searchCategoryId}
              onChange={(e) => setSearchCategoryId(e.target.value)}
              className="bg-transparent text-white text-sm outline-none px-4 py-3.5"
              style={{ background: 'rgba(0,0,0,0.25)', color: searchCategoryId ? 'white' : '#9CA3AF' }}
            >
              <option value="" style={{ background: '#1A2744' }}>Toutes catégories</option>
              {parentCategories.map((c) => (
                <option key={c.id} value={c.id} style={{ background: '#1A2744' }}>{c.name}</option>
              ))}
            </select>
            <button
              onClick={handleSearch}
              className="px-7 py-3.5 text-white text-sm font-bold transition-all"
              style={{
                background: 'linear-gradient(135deg, #FF6B35, #C85A20)',
                flexShrink: 0,
              }}
            >
              Rechercher
            </button>
          </div>

          {/* Stats */}
          <div className="flex flex-wrap justify-center gap-10">
            {[
              { value: '500+', label: 'Talents vérifiés' },
              { value: '1 000+', label: 'Réservations réussies' },
              { value: '98%', label: 'Clients satisfaits' },
            ].map((s) => (
              <div key={s.label} className="text-center">
                <p className="text-3xl font-extrabold text-white">{s.value}</p>
                <p className="text-xs text-gray-400 mt-1 font-medium">{s.label}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* ══════════════════════════════════════════════════════════════════════
          CATÉGORIES
      ════════════════════════════════════════════════════════════════════════ */}
      <section id="categories" className="py-20 px-4" style={{ background: '#F8FAFC' }}>
        <div className="max-w-7xl mx-auto">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-extrabold text-gray-900">Explorez par catégorie</h2>
            <p className="text-gray-500 mt-2">Trouvez le type d&apos;artiste qui correspond à votre événement</p>
          </div>

          {parentCategories.length === 0 ? (
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
              {[...Array(6)].map((_, i) => (
                <div key={i} className="h-28 rounded-2xl bg-gray-100 animate-pulse" />
              ))}
            </div>
          ) : (
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
              {parentCategories.map((cat) => {
                const bg = cat.color_hex ?? '#2196F3';
                return (
                  <button
                    key={cat.id}
                    onClick={() => {
                      setActiveCategoryId(cat.id);
                      document.getElementById('artistes')?.scrollIntoView({ behavior: 'smooth' });
                    }}
                    className="relative overflow-hidden rounded-2xl p-5 flex flex-col items-center gap-3 transition-all hover:scale-105 hover:shadow-lg"
                    style={{ background: `${bg}14`, border: `1.5px solid ${bg}28` }}
                  >
                    <div
                      className="w-12 h-12 rounded-xl flex items-center justify-center"
                      style={{ background: `${bg}22` }}
                    >
                      <Music size={22} style={{ color: bg }} />
                    </div>
                    <p className="text-sm font-bold text-gray-800 text-center leading-tight">{cat.name}</p>
                  </button>
                );
              })}
            </div>
          )}
        </div>
      </section>

      {/* ══════════════════════════════════════════════════════════════════════
          TALENTS
      ════════════════════════════════════════════════════════════════════════ */}
      <section id="artistes" className="py-20 px-4 bg-white">
        <div className="max-w-7xl mx-auto">
          <div className="flex items-center justify-between flex-wrap gap-4 mb-8">
            <div>
              <h2 className="text-3xl font-extrabold text-gray-900">Nos talents vedettes</h2>
              <p className="text-gray-500 mt-1">Découvrez les artistes disponibles pour votre prochain événement</p>
            </div>
            <Link
              href="/talents"
              className="text-sm font-bold px-5 py-2.5 rounded-xl border transition-colors"
              style={{ color: '#2196F3', borderColor: 'rgba(33,150,243,0.25)', background: 'rgba(33,150,243,0.04)' }}
            >
              Voir tous les artistes →
            </Link>
          </div>

          {/* Category filter tabs */}
          {parentCategories.length > 0 && (
            <div className="flex flex-wrap gap-2 mb-8">
              <button
                onClick={() => setActiveCategoryId(null)}
                className="px-4 py-1.5 rounded-full text-sm font-semibold transition-all"
                style={
                  !activeCategoryId
                    ? { background: '#1A2744', color: 'white' }
                    : { background: '#F1F5F9', color: '#64748b' }
                }
              >
                Tous
              </button>
              {parentCategories.map((cat) => (
                <button
                  key={cat.id}
                  onClick={() => setActiveCategoryId(cat.id)}
                  className="px-4 py-1.5 rounded-full text-sm font-semibold transition-all"
                  style={
                    activeCategoryId === cat.id
                      ? { background: '#1A2744', color: 'white' }
                      : { background: '#F1F5F9', color: '#64748b' }
                  }
                >
                  {cat.name}
                </button>
              ))}
            </div>
          )}

          {/* Talent grid */}
          {talentsLoading ? (
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
              {[...Array(8)].map((_, i) => (
                <div key={i} className="rounded-2xl border border-gray-100 p-5 space-y-3 animate-pulse">
                  <div className="w-14 h-14 rounded-full bg-gray-100" />
                  <div className="h-4 bg-gray-100 rounded w-3/4" />
                  <div className="h-3 bg-gray-100 rounded w-1/2" />
                </div>
              ))}
            </div>
          ) : talents.length === 0 ? (
            <div className="text-center py-16 text-gray-400">
              Aucun talent trouvé pour cette catégorie.
            </div>
          ) : (
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
              {talents.map((talent) => (
                <TalentCard key={talent.id} talent={talent} />
              ))}
            </div>
          )}
        </div>
      </section>

      {/* ══════════════════════════════════════════════════════════════════════
          POURQUOI BOOKMI
      ════════════════════════════════════════════════════════════════════════ */}
      <section id="pourquoi" className="py-20 px-4" style={{ background: '#F8FAFC' }}>
        <div className="max-w-7xl mx-auto">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-extrabold text-gray-900">Pourquoi choisir BookMi ?</h2>
            <p className="text-gray-500 mt-2">
              La plateforme de réservation d&apos;artistes la plus fiable de Côte d&apos;Ivoire
            </p>
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            {[
              {
                icon: Shield,
                color: '#2196F3',
                bg: 'rgba(33,150,243,0.08)',
                title: 'Talents vérifiés et notés',
                desc: "Chaque artiste est vérifié par notre équipe et évalué par les clients après chaque prestation.",
              },
              {
                icon: FileText,
                color: '#FF6B35',
                bg: 'rgba(255,107,53,0.08)',
                title: 'Contrat signé automatiquement',
                desc: "Un contrat de prestation est généré et signé automatiquement à chaque réservation confirmée.",
              },
              {
                icon: CreditCard,
                color: '#10B981',
                bg: 'rgba(16,185,129,0.08)',
                title: 'Paiement sécurisé',
                desc: "Votre paiement est sécurisé par séquestre. Libéré à l'artiste seulement après la prestation.",
              },
              {
                icon: Headphones,
                color: '#8B5CF6',
                bg: 'rgba(139,92,246,0.08)',
                title: 'Support 24h / 7j',
                desc: "Notre équipe est disponible à tout moment pour vous accompagner avant, pendant et après votre événement.",
              },
            ].map((item) => (
              <div
                key={item.title}
                className="rounded-2xl p-6"
                style={{
                  background: 'white',
                  border: '1px solid rgba(0,0,0,0.06)',
                  boxShadow: '0 4px 20px rgba(0,0,0,0.04)',
                }}
              >
                <div
                  className="w-12 h-12 rounded-xl flex items-center justify-center mb-4"
                  style={{ background: item.bg }}
                >
                  <item.icon size={22} style={{ color: item.color }} />
                </div>
                <h3 className="font-bold text-gray-900 mb-2">{item.title}</h3>
                <p className="text-sm text-gray-500 leading-relaxed">{item.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* ══════════════════════════════════════════════════════════════════════
          CTA BANNER
      ════════════════════════════════════════════════════════════════════════ */}
      <section
        className="py-20 px-4"
        style={{ background: 'linear-gradient(135deg, #1A2744 0%, #0D1B35 100%)' }}
      >
        <div className="max-w-3xl mx-auto text-center">
          <h2 className="text-3xl font-extrabold text-white mb-4">
            Prêt à organiser un événement inoubliable ?
          </h2>
          <p className="text-gray-300 mb-8">
            Rejoignez des milliers de clients qui font confiance à BookMi
          </p>
          <div className="flex flex-wrap justify-center gap-4">
            <Link
              href="/register"
              className="px-8 py-3.5 rounded-xl text-white font-bold text-base transition-all"
              style={{
                background: 'linear-gradient(135deg, #FF6B35, #C85A20)',
                boxShadow: '0 4px 20px rgba(255,107,53,0.4)',
              }}
            >
              Créer un compte gratuitement
            </Link>
            <Link
              href="/talents"
              className="px-8 py-3.5 rounded-xl text-white font-semibold text-base border border-white/20 hover:bg-white/10 transition-all"
            >
              Explorer les talents
            </Link>
          </div>
        </div>
      </section>

      {/* ══════════════════════════════════════════════════════════════════════
          FOOTER
      ════════════════════════════════════════════════════════════════════════ */}
      <footer id="contact" className="py-12 px-4" style={{ background: '#0D1B35' }}>
        <div className="max-w-7xl mx-auto">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-10 mb-10">
            {/* Brand */}
            <div>
              <div className="flex items-center mb-4">
                <span className="font-extrabold text-2xl text-white tracking-tight">Book</span>
                <span className="font-extrabold text-2xl tracking-tight" style={{ color: '#2196F3' }}>Mi</span>
              </div>
              <p className="text-sm text-gray-400 leading-relaxed max-w-xs">
                La première plateforme de réservation de talents artistiques en Côte d&apos;Ivoire.
              </p>
            </div>

            {/* Links */}
            <div>
              <h4 className="font-bold text-white mb-4 text-xs uppercase tracking-widest">Légal</h4>
              <ul className="space-y-2 text-sm text-gray-400">
                <li><a href="#" className="hover:text-white transition-colors">Conditions Générales d&apos;Utilisation</a></li>
                <li><a href="#" className="hover:text-white transition-colors">Politique de confidentialité</a></li>
                <li><a href="#" className="hover:text-white transition-colors">Règles d&apos;annulation</a></li>
              </ul>
            </div>

            {/* Contact */}
            <div>
              <h4 className="font-bold text-white mb-4 text-xs uppercase tracking-widest">Contact</h4>
              <ul className="space-y-2 text-sm text-gray-400">
                <li>contact@bookmi.click</li>
                <li>+225 07 00 00 00 00</li>
                <li>Abidjan, Côte d&apos;Ivoire</li>
              </ul>
            </div>
          </div>

          <div className="border-t border-white/10 pt-6 text-center text-xs text-gray-500">
            © 2026 BookMi. Tous droits réservés.
          </div>
        </div>
      </footer>
    </div>
  );
}
