'use client';

import { useState, Suspense } from 'react';
import { useSearchParams, useRouter } from 'next/navigation';
import { useQuery } from '@tanstack/react-query';
import Link from 'next/link';
import Image from 'next/image';
import { publicApi } from '@/lib/api/endpoints';
import { useAuthStore } from '@/lib/store/auth';
import { Star, CheckCircle2, MapPin, SlidersHorizontal, X } from 'lucide-react';

type Category = { id: number; name: string; color_hex?: string; parent_id?: number };
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

function formatCachet(c: number) {
  return new Intl.NumberFormat('fr-FR').format(Math.round(c / 100)) + ' FCFA';
}

function Stars({ rating }: { rating?: number }) {
  const r = rating ?? 0;
  return (
    <div className="flex items-center gap-0.5">
      {[1, 2, 3, 4, 5].map((i) => (
        <Star key={i} size={11} className={i <= Math.round(r) ? 'fill-amber-400 text-amber-400' : 'fill-gray-200 text-gray-200'} />
      ))}
      {rating ? <span className="text-xs text-gray-400 ml-1">{r.toFixed(1)}</span> : null}
    </div>
  );
}

function TalentsContent() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const user = useAuthStore((s) => s.user);

  const [city, setCity] = useState(searchParams.get('city') ?? '');
  const [categoryId, setCategoryId] = useState(searchParams.get('category_id') ?? '');
  const [minCachet, setMinCachet] = useState('');
  const [maxCachet, setMaxCachet] = useState('');
  const [filtersOpen, setFiltersOpen] = useState(false);

  const { data: catData } = useQuery({
    queryKey: ['public_categories'],
    queryFn: () => publicApi.getCategories(),
  });
  const parentCategories: Category[] = catData?.data?.data ?? [];

  const queryParams: Record<string, unknown> = { per_page: 24 };
  if (city.trim()) queryParams.city = city.trim();
  if (categoryId) queryParams.category_id = categoryId;
  if (minCachet) queryParams.min_cachet = Number(minCachet) * 100;
  if (maxCachet) queryParams.max_cachet = Number(maxCachet) * 100;

  const { data: talentsData, isLoading } = useQuery({
    queryKey: ['browse_talents', city, categoryId, minCachet, maxCachet],
    queryFn: () => publicApi.getTalents(queryParams),
  });
  // API returns JSON:API format: { id, type, attributes: { stage_name, ... } }
  const rawTalents: { id: number; attributes: Omit<TalentItem, 'id'> & { average_rating?: string | number } }[] = talentsData?.data?.data ?? [];
  const talents: TalentItem[] = rawTalents.map((item) => ({
    id: item.id,
    ...item.attributes,
    average_rating: item.attributes.average_rating != null ? Number(item.attributes.average_rating) : undefined,
  }));

  const handleSearch = () => {
    const p = new URLSearchParams();
    if (city.trim()) p.set('city', city.trim());
    if (categoryId) p.set('category_id', categoryId);
    router.replace(`/talents?${p.toString()}`, { scroll: false });
  };

  const clearFilters = () => {
    setCity('');
    setCategoryId('');
    setMinCachet('');
    setMaxCachet('');
  };

  const hasFilters = city || categoryId || minCachet || maxCachet;

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
        <div className="max-w-7xl mx-auto px-4 md:px-8 h-14 flex items-center justify-between gap-4">
          <Link href="/">
            <Image src="/logo.png" alt="BookMi" width={95} height={30} />
          </Link>
          <div className="flex items-center gap-3">
            {user ? (
              <Link
                href={user.talentProfile ? '/talent/dashboard' : '/client/dashboard'}
                className="text-sm font-semibold px-4 py-1.5 rounded-lg text-white"
                style={{ background: 'linear-gradient(135deg, #FF6B35, #C85A20)' }}
              >
                Mon espace
              </Link>
            ) : (
              <>
                <Link href="/login" className="text-sm font-semibold text-gray-600 hover:text-gray-900">Se connecter</Link>
                <Link
                  href="/register"
                  className="text-sm font-bold px-4 py-1.5 rounded-lg text-white"
                  style={{ background: 'linear-gradient(135deg, #FF6B35, #C85A20)' }}
                >
                  S&apos;inscrire
                </Link>
              </>
            )}
          </div>
        </div>
      </header>

      <div className="max-w-7xl mx-auto px-4 md:px-8 py-8">
        {/* Page title */}
        <div className="mb-6">
          <h1 className="text-2xl font-extrabold text-gray-900">Tous les talents</h1>
          <p className="text-gray-500 text-sm mt-1">{isLoading ? '...' : `${talents.length} artistes trouvés`}</p>
        </div>

        <div className="flex gap-6">
          {/* Sidebar Filters — Desktop */}
          <aside className="hidden lg:block w-64 flex-shrink-0">
            <div className="rounded-2xl bg-white border border-gray-100 p-5 space-y-5 sticky top-20">
              <div className="flex items-center justify-between">
                <h3 className="font-bold text-gray-900 text-sm">Filtres</h3>
                {hasFilters && (
                  <button onClick={clearFilters} className="text-xs text-gray-400 hover:text-gray-700">Effacer</button>
                )}
              </div>

              <div>
                <label className="text-xs font-semibold text-gray-500 uppercase tracking-wide block mb-2">Ville</label>
                <input
                  type="text"
                  value={city}
                  onChange={(e) => setCity(e.target.value)}
                  placeholder="Ex: Abidjan"
                  className="w-full h-9 rounded-lg border border-gray-200 px-3 text-sm outline-none focus:border-blue-400"
                />
              </div>

              <div>
                <label className="text-xs font-semibold text-gray-500 uppercase tracking-wide block mb-2">Catégorie</label>
                <select
                  value={categoryId}
                  onChange={(e) => setCategoryId(e.target.value)}
                  className="w-full h-9 rounded-lg border border-gray-200 px-3 text-sm outline-none focus:border-blue-400 bg-white"
                >
                  <option value="">Toutes</option>
                  {parentCategories.map((c) => (
                    <option key={c.id} value={c.id}>{c.name}</option>
                  ))}
                </select>
              </div>

              <div>
                <label className="text-xs font-semibold text-gray-500 uppercase tracking-wide block mb-2">
                  Cachet (FCFA)
                </label>
                <div className="flex gap-2">
                  <input
                    type="number"
                    value={minCachet}
                    onChange={(e) => setMinCachet(e.target.value)}
                    placeholder="Min"
                    className="w-1/2 h-9 rounded-lg border border-gray-200 px-3 text-sm outline-none focus:border-blue-400"
                  />
                  <input
                    type="number"
                    value={maxCachet}
                    onChange={(e) => setMaxCachet(e.target.value)}
                    placeholder="Max"
                    className="w-1/2 h-9 rounded-lg border border-gray-200 px-3 text-sm outline-none focus:border-blue-400"
                  />
                </div>
              </div>

              <button
                onClick={handleSearch}
                className="w-full py-2 rounded-xl text-white text-sm font-bold"
                style={{ background: 'linear-gradient(135deg, #FF6B35, #C85A20)' }}
              >
                Appliquer
              </button>
            </div>
          </aside>

          {/* Main content */}
          <div className="flex-1 min-w-0">
            {/* Mobile filter toggle */}
            <div className="lg:hidden mb-4 flex gap-2">
              <button
                onClick={() => setFiltersOpen(!filtersOpen)}
                className="flex items-center gap-2 px-4 py-2 rounded-xl border text-sm font-semibold text-gray-700"
                style={{ borderColor: hasFilters ? '#2196F3' : '#E2E8F0', color: hasFilters ? '#2196F3' : '#374151' }}
              >
                <SlidersHorizontal size={15} />
                Filtres {hasFilters && '•'}
              </button>
              {hasFilters && (
                <button onClick={clearFilters} className="p-2 rounded-xl border border-gray-200">
                  <X size={15} className="text-gray-400" />
                </button>
              )}
            </div>

            {/* Mobile filter panel */}
            {filtersOpen && (
              <div className="lg:hidden rounded-2xl bg-white border border-gray-100 p-5 mb-4 space-y-4">
                <input
                  type="text"
                  value={city}
                  onChange={(e) => setCity(e.target.value)}
                  placeholder="Ville"
                  className="w-full h-9 rounded-lg border border-gray-200 px-3 text-sm outline-none"
                />
                <select
                  value={categoryId}
                  onChange={(e) => setCategoryId(e.target.value)}
                  className="w-full h-9 rounded-lg border border-gray-200 px-3 text-sm outline-none bg-white"
                >
                  <option value="">Toutes catégories</option>
                  {parentCategories.map((c) => (
                    <option key={c.id} value={c.id}>{c.name}</option>
                  ))}
                </select>
                <button
                  onClick={() => { handleSearch(); setFiltersOpen(false); }}
                  className="w-full py-2 rounded-xl text-white text-sm font-bold"
                  style={{ background: 'linear-gradient(135deg, #FF6B35, #C85A20)' }}
                >
                  Appliquer
                </button>
              </div>
            )}

            {/* Grid */}
            {isLoading ? (
              <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                {[...Array(9)].map((_, i) => (
                  <div key={i} className="rounded-2xl bg-white border border-gray-100 p-5 space-y-3 animate-pulse">
                    <div className="w-14 h-14 rounded-full bg-gray-100" />
                    <div className="h-4 bg-gray-100 rounded w-3/4" />
                    <div className="h-3 bg-gray-100 rounded w-1/2" />
                  </div>
                ))}
              </div>
            ) : talents.length === 0 ? (
              <div className="text-center py-20 text-gray-400">
                <p className="text-lg font-semibold mb-2">Aucun talent trouvé</p>
                <p className="text-sm">Essayez de modifier vos filtres</p>
              </div>
            ) : (
              <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                {talents.map((talent) => {
                  const initials = talent.stage_name.split(' ').slice(0, 2).map((w) => w[0]).join('').toUpperCase();
                  return (
                    <Link
                      key={talent.id}
                      href={`/talents/${talent.slug}`}
                      className="group block rounded-2xl border border-gray-100 bg-white p-5 hover:shadow-lg hover:border-blue-100 transition-all"
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
                            {talent.is_verified && <CheckCircle2 size={13} style={{ color: '#2196F3' }} className="flex-shrink-0" />}
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
                          <p className="flex items-center gap-1 text-xs text-gray-400">
                            <MapPin size={11} /> {talent.city}
                          </p>
                        )}
                        <Stars rating={talent.average_rating} />
                        <p className="text-sm font-bold text-gray-900">
                          À partir de <span style={{ color: '#FF6B35' }}>{formatCachet(talent.cachet_amount)}</span>
                        </p>
                      </div>
                      <div className="mt-4 pt-3 border-t border-gray-50 flex items-center justify-between">
                        <span className="text-xs text-gray-400 capitalize">{talent.talent_level}</span>
                        <span className="text-xs font-semibold" style={{ color: '#2196F3' }}>Voir le profil →</span>
                      </div>
                    </Link>
                  );
                })}
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

export default function TalentsPage() {
  return (
    <Suspense>
      <TalentsContent />
    </Suspense>
  );
}
