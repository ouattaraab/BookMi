'use client';

import { useRef, useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { portfolioApi } from '@/lib/api/endpoints';
import { useAuthStore } from '@/lib/store/auth';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert } from '@/components/ui/alert';
import { Upload, Link2, Trash2, ExternalLink, Image, Film, Music } from 'lucide-react';

type PortfolioItem = {
  id: number;
  media_type: 'image' | 'video' | 'link';
  url: string;
  link_url?: string;
  link_platform?: string;
  caption?: string;
  created_at: string;
};

const PLATFORMS = [
  { value: 'youtube', label: 'YouTube' },
  { value: 'deezer', label: 'Deezer' },
  { value: 'apple_music', label: 'Apple Music' },
  { value: 'facebook', label: 'Facebook' },
  { value: 'tiktok', label: 'TikTok' },
  { value: 'soundcloud', label: 'SoundCloud' },
];

const PLATFORM_COLORS: Record<string, string> = {
  youtube:     'bg-red-100 text-red-700',
  deezer:      'bg-purple-100 text-purple-700',
  apple_music: 'bg-pink-100 text-pink-700',
  facebook:    'bg-blue-100 text-blue-700',
  tiktok:      'bg-gray-900 text-white',
  soundcloud:  'bg-orange-100 text-orange-700',
};

function PlatformBadge({ platform }: { platform: string }) {
  const label = PLATFORMS.find((p) => p.value === platform)?.label ?? platform;
  const color = PLATFORM_COLORS[platform] ?? 'bg-gray-100 text-gray-700';
  return (
    <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold ${color}`}>
      {label}
    </span>
  );
}

export default function TalentPortfolioPage() {
  const qc = useQueryClient();
  const user = useAuthStore((s) => s.user);
  const talentProfileId = user?.talentProfile?.id;

  // ── Upload file state ─────────────────────────────────────────────────────
  const fileRef = useRef<HTMLInputElement>(null);
  const [fileCaption, setFileCaption] = useState('');
  const [uploadError, setUploadError] = useState<string | null>(null);
  const [uploadSuccess, setUploadSuccess] = useState(false);

  // ── Add link state ─────────────────────────────────────────────────────────
  const [linkUrl, setLinkUrl] = useState('');
  const [linkPlatform, setLinkPlatform] = useState('youtube');
  const [linkCaption, setLinkCaption] = useState('');
  const [linkError, setLinkError] = useState<string | null>(null);
  const [linkSuccess, setLinkSuccess] = useState(false);

  // ── Data ──────────────────────────────────────────────────────────────────
  const { data, isLoading } = useQuery({
    queryKey: ['portfolio', 'me', talentProfileId],
    queryFn: () => portfolioApi.list(talentProfileId!),
    enabled: !!talentProfileId,
  });
  const items: PortfolioItem[] = data?.data?.data ?? [];

  // ── Upload mutation ───────────────────────────────────────────────────────
  const uploadMutation = useMutation({
    mutationFn: (formData: FormData) => portfolioApi.upload(formData),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['portfolio', 'me', talentProfileId] });
      setFileCaption('');
      if (fileRef.current) fileRef.current.value = '';
      setUploadSuccess(true);
      setTimeout(() => setUploadSuccess(false), 3000);
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { message?: string } } };
      setUploadError(e?.response?.data?.message || 'Erreur lors de l\'upload.');
    },
  });

  const handleUpload = () => {
    setUploadError(null);
    const file = fileRef.current?.files?.[0];
    if (!file) { setUploadError('Veuillez sélectionner un fichier.'); return; }
    if (file.size > 20 * 1024 * 1024) { setUploadError('Le fichier ne doit pas dépasser 20 Mo.'); return; }
    const formData = new FormData();
    formData.append('file', file);
    if (fileCaption) formData.append('caption', fileCaption);
    uploadMutation.mutate(formData);
  };

  // ── Add link mutation ─────────────────────────────────────────────────────
  const linkMutation = useMutation({
    mutationFn: (data: { link_url: string; link_platform: string; caption?: string }) =>
      portfolioApi.addLink(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['portfolio', 'me', talentProfileId] });
      setLinkUrl('');
      setLinkCaption('');
      setLinkPlatform('youtube');
      setLinkSuccess(true);
      setTimeout(() => setLinkSuccess(false), 3000);
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { message?: string } } };
      setLinkError(e?.response?.data?.message || 'Lien invalide.');
    },
  });

  const handleAddLink = () => {
    setLinkError(null);
    if (!linkUrl) { setLinkError('Veuillez entrer un lien.'); return; }
    if (!linkUrl.startsWith('http')) { setLinkError('L\'URL doit commencer par http:// ou https://'); return; }
    linkMutation.mutate({ link_url: linkUrl, link_platform: linkPlatform, caption: linkCaption || undefined });
  };

  // ── Delete mutation ────────────────────────────────────────────────────────
  const deleteMutation = useMutation({
    mutationFn: (itemId: number) => portfolioApi.delete(itemId),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['portfolio', 'me', talentProfileId] });
    },
  });

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Portfolio</h1>
        <p className="text-gray-500 text-sm mt-1">
          Ajoutez vos meilleures photos, vidéos et liens pour montrer votre talent
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {/* ── Upload file ────────────────────────────────────────────────── */}
        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="flex items-center gap-2 text-base">
              <Upload size={16} className="text-[#FF6B35]" />
              Uploader une photo ou une vidéo
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {uploadSuccess && (
              <Alert className="bg-green-50 border-green-200 text-green-800 text-sm">
                Fichier uploadé avec succès !
              </Alert>
            )}
            {uploadError && (
              <Alert className="bg-red-50 border-red-200 text-red-800 text-sm">
                {uploadError}
              </Alert>
            )}
            <div className="space-y-1">
              <Label>Fichier (JPG, PNG, GIF, MP4, MOV — max 20 Mo)</Label>
              <Input ref={fileRef} type="file" accept=".jpg,.jpeg,.png,.gif,.mp4,.mov" />
            </div>
            <div className="space-y-1">
              <Label>Description (optionnel)</Label>
              <Input
                value={fileCaption}
                onChange={(e) => setFileCaption(e.target.value)}
                placeholder="Description de ce média..."
                maxLength={255}
              />
            </div>
            <Button
              onClick={handleUpload}
              disabled={uploadMutation.isPending}
              className="w-full bg-[#FF6B35] hover:bg-[#C85A20] text-white"
            >
              {uploadMutation.isPending ? 'Upload en cours...' : 'Uploader'}
            </Button>
          </CardContent>
        </Card>

        {/* ── Add link ────────────────────────────────────────────────────── */}
        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="flex items-center gap-2 text-base">
              <Link2 size={16} className="text-[#FF6B35]" />
              Ajouter un lien externe
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {linkSuccess && (
              <Alert className="bg-green-50 border-green-200 text-green-800 text-sm">
                Lien ajouté avec succès !
              </Alert>
            )}
            {linkError && (
              <Alert className="bg-red-50 border-red-200 text-red-800 text-sm">
                {linkError}
              </Alert>
            )}
            <div className="space-y-1">
              <Label>Plateforme</Label>
              <select
                value={linkPlatform}
                onChange={(e) => setLinkPlatform(e.target.value)}
                className="w-full rounded-md border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#FF6B35]"
              >
                {PLATFORMS.map((p) => (
                  <option key={p.value} value={p.value}>{p.label}</option>
                ))}
              </select>
            </div>
            <div className="space-y-1">
              <Label>URL du contenu</Label>
              <Input
                value={linkUrl}
                onChange={(e) => setLinkUrl(e.target.value)}
                placeholder="https://www.youtube.com/watch?v=..."
                type="url"
              />
            </div>
            <div className="space-y-1">
              <Label>Description (optionnel)</Label>
              <Input
                value={linkCaption}
                onChange={(e) => setLinkCaption(e.target.value)}
                placeholder="Titre ou description..."
                maxLength={255}
              />
            </div>
            <Button
              onClick={handleAddLink}
              disabled={linkMutation.isPending}
              className="w-full bg-[#FF6B35] hover:bg-[#C85A20] text-white"
            >
              {linkMutation.isPending ? 'Ajout en cours...' : 'Ajouter le lien'}
            </Button>
          </CardContent>
        </Card>
      </div>

      {/* ── Portfolio gallery ──────────────────────────────────────────────── */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Image size={18} className="text-[#FF6B35]" />
            Mon portfolio ({items.length} élément{items.length !== 1 ? 's' : ''})
          </CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
              {[...Array(6)].map((_, i) => (
                <Skeleton key={i} className="h-40 w-full rounded-lg" />
              ))}
            </div>
          ) : items.length === 0 ? (
            <p className="text-gray-400 text-sm text-center py-12">
              Votre portfolio est vide. Ajoutez des photos, vidéos ou liens !
            </p>
          ) : (
            <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
              {items.map((item) => (
                <div key={item.id} className="relative group rounded-xl overflow-hidden border border-gray-100 bg-gray-50">
                  {/* Media preview */}
                  {item.media_type === 'image' && (
                    <div className="aspect-square overflow-hidden">
                      {/* eslint-disable-next-line @next/next/no-img-element */}
                      <img
                        src={item.url}
                        alt={item.caption ?? 'Portfolio'}
                        className="w-full h-full object-cover transition-transform group-hover:scale-105"
                      />
                    </div>
                  )}

                  {item.media_type === 'video' && (
                    <div className="aspect-square flex flex-col items-center justify-center bg-gray-900 text-white gap-2">
                      <Film size={32} className="opacity-70" />
                      <span className="text-xs opacity-70">Vidéo</span>
                      <a
                        href={item.url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="text-xs underline opacity-70 hover:opacity-100"
                      >
                        Voir la vidéo
                      </a>
                    </div>
                  )}

                  {item.media_type === 'link' && (
                    <div className="aspect-square flex flex-col items-center justify-center gap-3 p-4">
                      <Music size={32} className="text-gray-400" />
                      {item.link_platform && <PlatformBadge platform={item.link_platform} />}
                      {item.caption && (
                        <p className="text-xs text-gray-600 text-center line-clamp-2">{item.caption}</p>
                      )}
                      <a
                        href={item.url ?? item.link_url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex items-center gap-1 text-xs text-[#FF6B35] hover:underline"
                      >
                        <ExternalLink size={12} /> Ouvrir
                      </a>
                    </div>
                  )}

                  {/* Caption overlay for images */}
                  {item.media_type === 'image' && item.caption && (
                    <div className="px-2 py-1 bg-white border-t border-gray-100">
                      <p className="text-xs text-gray-600 truncate">{item.caption}</p>
                    </div>
                  )}

                  {/* Delete button */}
                  <button
                    onClick={() => deleteMutation.mutate(item.id)}
                    disabled={deleteMutation.isPending}
                    className="absolute top-2 right-2 p-1.5 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600"
                    title="Supprimer"
                  >
                    <Trash2 size={12} />
                  </button>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
