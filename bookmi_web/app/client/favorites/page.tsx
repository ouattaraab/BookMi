'use client';

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import Link from 'next/link';
import { favoriteApi } from '@/lib/api/endpoints';
import { Heart, CheckCircle2, MapPin, Star, Search } from 'lucide-react';

type FavoriteTalent = {
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

export default function ClientFavoritesPage() {
  const qc = useQueryClient();

  const { data, isLoading } = useQuery({
    queryKey: ['client_favorites'],
    queryFn: () => favoriteApi.list({ per_page: 50 }),
  });

  // API returns: { data: [{ attributes: { talent: { id, type, attributes: {...} } } }] }
  const rawFavorites: { attributes?: { talent?: { id: number; attributes: Record<string, unknown> } } }[] = data?.data?.data ?? [];
  const talents: FavoriteTalent[] = rawFavorites
    .map((item) => {
      const t = item?.attributes?.talent;
      if (!t) return null;
      const attrs = t.attributes as Omit<FavoriteTalent, 'id'> & { average_rating?: string | number };
      return {
        id: t.id,
        ...attrs,
        average_rating: attrs.average_rating != null ? Number(attrs.average_rating) : undefined,
      } as FavoriteTalent;
    })
    .filter(Boolean) as FavoriteTalent[];

  const removeMutation = useMutation({
    mutationFn: (talentId: number) => favoriteApi.remove(talentId),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['client_favorites'] }),
  });

  return (
    <div className="space-y-6 max-w-5xl mx-auto">
      <div>
        <h1 className="text-2xl font-extrabold text-gray-900">Mes favoris</h1>
        <p className="text-gray-500 text-sm mt-1">
          {isLoading ? '...' : `${talents.length} artiste${talents.length !== 1 ? 's' : ''} en favori`}
        </p>
      </div>

      {isLoading ? (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
          {[...Array(6)].map((_, i) => (
            <div key={i} className="h-40 rounded-2xl bg-white/50 animate-pulse" />
          ))}
        </div>
      ) : talents.length === 0 ? (
        <div
          className="rounded-2xl py-20 text-center"
          style={{ background: 'rgba(255,255,255,0.75)', border: '1px solid rgba(255,255,255,0.85)' }}
        >
          <Heart size={40} className="text-gray-200 mx-auto mb-4" />
          <p className="text-gray-400 font-semibold mb-1">Aucun favori pour le moment</p>
          <p className="text-gray-300 text-sm mb-5">Ajoutez des artistes à vos favoris depuis leur profil</p>
          <Link
            href="/talents"
            className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-bold"
            style={{ background: 'linear-gradient(135deg, #FF6B35, #C85A20)' }}
          >
            <Search size={15} /> Découvrir des talents
          </Link>
        </div>
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
          {talents.map((talent) => {
            const initials = talent.stage_name.split(' ').slice(0, 2).map((w) => w[0]).join('').toUpperCase();
            const catColor = talent.category?.color_hex ?? '#2196F3';
            const r = talent.average_rating ?? 0;

            return (
              <div
                key={talent.id}
                className="rounded-2xl p-5 relative group"
                style={{
                  background: 'rgba(255,255,255,0.82)',
                  backdropFilter: 'blur(12px)',
                  WebkitBackdropFilter: 'blur(12px)',
                  border: '1px solid rgba(255,255,255,0.9)',
                  boxShadow: '0 4px 20px rgba(0,0,0,0.05)',
                }}
              >
                {/* Remove button */}
                <button
                  onClick={() => removeMutation.mutate(talent.id)}
                  disabled={removeMutation.isPending}
                  className="absolute top-4 right-4 p-1.5 rounded-full transition-all opacity-60 hover:opacity-100 hover:bg-red-50"
                  title="Retirer des favoris"
                >
                  <Heart size={16} className="fill-red-500 text-red-500" />
                </button>

                <Link href={`/talents/${talent.slug}`} className="block">
                  <div className="flex items-start gap-3 mb-4">
                    <div
                      className="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-base flex-shrink-0"
                      style={{ background: 'linear-gradient(135deg, #1A2744, #2196F3)' }}
                    >
                      {initials}
                    </div>
                    <div className="flex-1 min-w-0 pr-6">
                      <div className="flex items-center gap-1.5">
                        <p className="font-bold text-gray-900 truncate text-sm">{talent.stage_name}</p>
                        {talent.is_verified && <CheckCircle2 size={13} style={{ color: '#2196F3' }} className="flex-shrink-0" />}
                      </div>
                      {talent.category && (
                        <span
                          className="inline-block text-xs font-medium px-2 py-0.5 rounded-full mt-1"
                          style={{ background: `${catColor}18`, color: catColor }}
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
                    <div className="flex items-center gap-0.5">
                      {[1, 2, 3, 4, 5].map((i) => (
                        <Star key={i} size={11} className={i <= Math.round(r) ? 'fill-amber-400 text-amber-400' : 'fill-gray-200 text-gray-200'} />
                      ))}
                      {talent.average_rating ? <span className="text-xs text-gray-400 ml-1">{r.toFixed(1)}</span> : null}
                    </div>
                    <p className="text-sm font-bold text-gray-900">
                      À partir de <span style={{ color: '#FF6B35' }}>{formatCachet(talent.cachet_amount)}</span>
                    </p>
                  </div>
                </Link>

                <div className="mt-4 pt-3 border-t border-gray-50 flex gap-2">
                  <Link
                    href={`/talents/${talent.slug}`}
                    className="flex-1 text-center py-2 rounded-lg text-xs font-semibold border border-gray-200 text-gray-700 hover:bg-gray-50 transition-colors"
                  >
                    Voir le profil
                  </Link>
                  <Link
                    href={`/talents/${talent.slug}`}
                    className="flex-1 text-center py-2 rounded-lg text-xs font-bold text-white"
                    style={{ background: 'linear-gradient(135deg, #FF6B35, #C85A20)' }}
                  >
                    Réserver
                  </Link>
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
