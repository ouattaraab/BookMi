'use client';

import { useState } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import { twoFactorApi } from '@/lib/api/endpoints';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert } from '@/components/ui/alert';
import { ShieldCheck, ShieldOff, Mail, Smartphone, Eye, EyeOff } from 'lucide-react';

type TwoFactorStatus = { enabled: boolean; method: 'totp' | 'email' | null };
type SetupStep = null | 'choose' | 'totp_qr' | 'totp_confirm' | 'email_sent' | 'email_confirm' | 'disable_confirm';

export default function TalentSettingsPage() {
  const [setupStep, setSetupStep] = useState<SetupStep>(null);
  const [code, setCode] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [qrSvg, setQrSvg] = useState('');
  const [totpSecret, setTotpSecret] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  const { data, isLoading, refetch } = useQuery({
    queryKey: ['2fa_status'],
    queryFn: () => twoFactorApi.status(),
  });
  const status: TwoFactorStatus = data?.data?.data ?? { enabled: false, method: null };

  // ── Setup TOTP ─────────────────────────────────────────────────────────────
  const setupTotpMutation = useMutation({
    mutationFn: () => twoFactorApi.setupTotp(),
    onSuccess: (res) => {
      const d = res.data.data;
      setQrSvg(d.qr_code_svg);
      setTotpSecret(d.secret);
      setSetupStep('totp_qr');
      setError(null);
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { message?: string } } };
      setError(e?.response?.data?.message || 'Erreur lors de la configuration.');
    },
  });

  const enableTotpMutation = useMutation({
    mutationFn: (c: string) => twoFactorApi.enableTotp({ code: c }),
    onSuccess: () => {
      setSetupStep(null);
      setCode('');
      setSuccess('Authentification à deux facteurs (TOTP) activée !');
      refetch();
      setTimeout(() => setSuccess(null), 4000);
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { errors?: { code?: string[] }; message?: string } } };
      setError(e?.response?.data?.errors?.code?.[0] || e?.response?.data?.message || 'Code invalide.');
    },
  });

  // ── Setup Email ────────────────────────────────────────────────────────────
  const setupEmailMutation = useMutation({
    mutationFn: () => twoFactorApi.setupEmail(),
    onSuccess: () => {
      setSetupStep('email_confirm');
      setError(null);
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { message?: string } } };
      setError(e?.response?.data?.message || 'Erreur lors de l\'envoi.');
    },
  });

  const enableEmailMutation = useMutation({
    mutationFn: (c: string) => twoFactorApi.enableEmail({ code: c }),
    onSuccess: () => {
      setSetupStep(null);
      setCode('');
      setSuccess('Authentification à deux facteurs (Email) activée !');
      refetch();
      setTimeout(() => setSuccess(null), 4000);
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { errors?: { code?: string[] }; message?: string } } };
      setError(e?.response?.data?.errors?.code?.[0] || e?.response?.data?.message || 'Code invalide.');
    },
  });

  // ── Disable ────────────────────────────────────────────────────────────────
  const disableMutation = useMutation({
    mutationFn: (p: string) => twoFactorApi.disable({ password: p }),
    onSuccess: () => {
      setSetupStep(null);
      setPassword('');
      setSuccess('Authentification à deux facteurs désactivée.');
      refetch();
      setTimeout(() => setSuccess(null), 4000);
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { errors?: { password?: string[] }; message?: string } } };
      setError(e?.response?.data?.errors?.password?.[0] || e?.response?.data?.message || 'Mot de passe incorrect.');
    },
  });

  const reset = () => { setSetupStep(null); setCode(''); setPassword(''); setError(null); };

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Paramètres de sécurité</h1>
        <p className="text-gray-500 text-sm mt-1">Gérez l&apos;authentification à deux facteurs de votre compte</p>
      </div>

      {success && (
        <Alert className="bg-green-50 border-green-200 text-green-800">{success}</Alert>
      )}

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <ShieldCheck size={18} className="text-[#FF6B35]" />
            Authentification à deux facteurs (2FA)
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-6">
          {isLoading ? (
            <Skeleton className="h-20 w-full" />
          ) : (
            <>
              {/* ── Status banner ── */}
              <div className={`flex items-center justify-between rounded-xl p-4 ${status.enabled ? 'bg-green-50 border border-green-200' : 'bg-gray-50 border border-gray-200'}`}>
                <div className="flex items-center gap-3">
                  {status.enabled
                    ? <ShieldCheck size={20} className="text-green-600" />
                    : <ShieldOff size={20} className="text-gray-400" />
                  }
                  <div>
                    <p className={`font-semibold text-sm ${status.enabled ? 'text-green-800' : 'text-gray-600'}`}>
                      {status.enabled ? '2FA activée' : '2FA non activée'}
                    </p>
                    {status.enabled && status.method && (
                      <p className="text-xs text-green-600">
                        Méthode : {status.method === 'totp' ? 'Application d\'authentification' : 'Email'}
                      </p>
                    )}
                  </div>
                </div>
                {status.enabled && setupStep === null && (
                  <Button
                    variant="outline"
                    size="sm"
                    className="text-red-600 border-red-200 hover:bg-red-50"
                    onClick={() => { setSetupStep('disable_confirm'); setError(null); }}
                  >
                    Désactiver
                  </Button>
                )}
              </div>

              {error && (
                <Alert className="bg-red-50 border-red-200 text-red-800 text-sm">{error}</Alert>
              )}

              {/* ── Not enabled: choose method ── */}
              {!status.enabled && setupStep === null && (
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <button
                    onClick={() => { setError(null); setupTotpMutation.mutate(); }}
                    disabled={setupTotpMutation.isPending}
                    className="flex flex-col items-center gap-3 p-5 rounded-xl border-2 border-dashed border-gray-200 hover:border-[#FF6B35] hover:bg-orange-50 transition-all"
                  >
                    <Smartphone size={28} className="text-[#FF6B35]" />
                    <div className="text-center">
                      <p className="font-semibold text-sm text-gray-800">Application d&apos;authentification</p>
                      <p className="text-xs text-gray-500 mt-1">Google Authenticator, Authy, etc.</p>
                    </div>
                  </button>
                  <button
                    onClick={() => { setError(null); setupEmailMutation.mutate(); }}
                    disabled={setupEmailMutation.isPending}
                    className="flex flex-col items-center gap-3 p-5 rounded-xl border-2 border-dashed border-gray-200 hover:border-[#FF6B35] hover:bg-orange-50 transition-all"
                  >
                    <Mail size={28} className="text-[#FF6B35]" />
                    <div className="text-center">
                      <p className="font-semibold text-sm text-gray-800">Code par email</p>
                      <p className="text-xs text-gray-500 mt-1">Recevez un code à chaque connexion</p>
                    </div>
                  </button>
                </div>
              )}

              {/* ── TOTP: QR code step ── */}
              {setupStep === 'totp_qr' && (
                <div className="space-y-4">
                  <p className="text-sm text-gray-600">
                    Scannez ce QR code avec votre application d&apos;authentification (Google Authenticator, Authy, etc.)
                  </p>
                  <div
                    className="flex justify-center"
                    dangerouslySetInnerHTML={{ __html: qrSvg }}
                  />
                  {totpSecret && (
                    <div className="bg-gray-50 rounded-lg p-3 text-center">
                      <p className="text-xs text-gray-500 mb-1">Clé secrète (saisie manuelle)</p>
                      <p className="font-mono text-sm font-bold text-gray-800 tracking-widest">{totpSecret}</p>
                    </div>
                  )}
                  <div className="space-y-1">
                    <Label>Code de confirmation (6 chiffres)</Label>
                    <Input
                      value={code}
                      onChange={(e) => setCode(e.target.value.replace(/\D/g, '').slice(0, 6))}
                      placeholder="000000"
                      inputMode="numeric"
                      maxLength={6}
                      className="text-center text-xl tracking-widest font-mono"
                    />
                  </div>
                  <div className="flex gap-2">
                    <Button variant="outline" onClick={reset} className="flex-1">Annuler</Button>
                    <Button
                      onClick={() => { setError(null); enableTotpMutation.mutate(code); }}
                      disabled={code.length !== 6 || enableTotpMutation.isPending}
                      className="flex-1 bg-[#FF6B35] hover:bg-[#C85A20] text-white"
                    >
                      {enableTotpMutation.isPending ? 'Vérification...' : 'Activer'}
                    </Button>
                  </div>
                </div>
              )}

              {/* ── Email: confirm step ── */}
              {setupStep === 'email_confirm' && (
                <div className="space-y-4">
                  <Alert className="bg-blue-50 border-blue-200 text-blue-800 text-sm">
                    Un code de vérification a été envoyé à votre adresse email. Valable 10 minutes.
                  </Alert>
                  <div className="space-y-1">
                    <Label>Code reçu par email (6 chiffres)</Label>
                    <Input
                      value={code}
                      onChange={(e) => setCode(e.target.value.replace(/\D/g, '').slice(0, 6))}
                      placeholder="000000"
                      inputMode="numeric"
                      maxLength={6}
                      className="text-center text-xl tracking-widest font-mono"
                    />
                  </div>
                  <div className="flex gap-2">
                    <Button variant="outline" onClick={reset} className="flex-1">Annuler</Button>
                    <Button
                      onClick={() => { setError(null); enableEmailMutation.mutate(code); }}
                      disabled={code.length !== 6 || enableEmailMutation.isPending}
                      className="flex-1 bg-[#FF6B35] hover:bg-[#C85A20] text-white"
                    >
                      {enableEmailMutation.isPending ? 'Vérification...' : 'Activer'}
                    </Button>
                  </div>
                </div>
              )}

              {/* ── Disable: password confirm ── */}
              {setupStep === 'disable_confirm' && (
                <div className="space-y-4">
                  <p className="text-sm text-gray-600">
                    Confirmez votre mot de passe pour désactiver la 2FA.
                  </p>
                  <div className="space-y-1">
                    <Label>Mot de passe</Label>
                    <div className="relative">
                      <Input
                        type={showPassword ? 'text' : 'password'}
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        placeholder="••••••••"
                        autoComplete="current-password"
                      />
                      <button
                        type="button"
                        onClick={() => setShowPassword(!showPassword)}
                        className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"
                      >
                        {showPassword ? <EyeOff size={16} /> : <Eye size={16} />}
                      </button>
                    </div>
                  </div>
                  <div className="flex gap-2">
                    <Button variant="outline" onClick={reset} className="flex-1">Annuler</Button>
                    <Button
                      onClick={() => { setError(null); disableMutation.mutate(password); }}
                      disabled={!password || disableMutation.isPending}
                      className="flex-1 bg-red-600 hover:bg-red-700 text-white"
                    >
                      {disableMutation.isPending ? 'Désactivation...' : 'Désactiver la 2FA'}
                    </Button>
                  </div>
                </div>
              )}
            </>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
