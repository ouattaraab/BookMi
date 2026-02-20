'use client';

import { useState, Suspense } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import Link from 'next/link';
import Image from 'next/image';
import { authApi, twoFactorApi } from '@/lib/api/endpoints';
import { useAuthStore } from '@/lib/store/auth';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert } from '@/components/ui/alert';
import { ShieldCheck, ArrowLeft } from 'lucide-react';

const loginSchema = z.object({
  email: z.string().email('Email invalide'),
  password: z.string().min(6, 'Mot de passe trop court'),
});

type LoginFormData = z.infer<typeof loginSchema>;
type Step = 'credentials' | 'two_factor';

function LoginForm() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const setAuth = useAuthStore((s) => s.setAuth);

  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  // 2FA state
  const [step, setStep] = useState<Step>('credentials');
  const [challengeToken, setChallengeToken] = useState('');
  const [twoFactorMethod, setTwoFactorMethod] = useState<'totp' | 'email'>('totp');
  const [twoFactorCode, setTwoFactorCode] = useState('');

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<LoginFormData>({ resolver: zodResolver(loginSchema) });

  const redirectAfterLogin = (user: { is_admin: boolean; talentProfile?: unknown; role?: string }) => {
    const redirect = searchParams.get('redirect');
    if (user.is_admin) {
      router.push(redirect || '/admin');
    } else if (user.talentProfile) {
      router.push(redirect || '/talent/dashboard');
    } else if (user.role === 'manager') {
      router.push(redirect || '/manager/talents');
    } else {
      router.push(redirect || '/client/dashboard');
    }
  };

  const onSubmit = async (data: LoginFormData) => {
    setError(null);
    setLoading(true);
    try {
      const res = await authApi.login(data);
      const result = res.data.data;

      // 2FA challenge required
      if (result.two_factor_required) {
        setChallengeToken(result.challenge_token);
        setTwoFactorMethod(result.method as 'totp' | 'email');
        setStep('two_factor');
        return;
      }

      // Normal login
      const { token, user } = result;
      setAuth(token, user);
      document.cookie = `bookmi_token=${token}; path=/; max-age=${60 * 60 * 24 * 7}; SameSite=Lax`;
      redirectAfterLogin(user);
    } catch (err: unknown) {
      const e = err as { response?: { data?: { message?: string } } };
      setError(e?.response?.data?.message || 'Identifiants incorrects. Veuillez réessayer.');
    } finally {
      setLoading(false);
    }
  };

  const onVerify2FA = async () => {
    if (!twoFactorCode || twoFactorCode.length !== 6) {
      setError('Veuillez saisir un code à 6 chiffres.');
      return;
    }
    setError(null);
    setLoading(true);
    try {
      const res = await twoFactorApi.verify({
        challenge_token: challengeToken,
        code: twoFactorCode,
      });
      const { token, user } = res.data.data;
      setAuth(token, user);
      document.cookie = `bookmi_token=${token}; path=/; max-age=${60 * 60 * 24 * 7}; SameSite=Lax`;
      redirectAfterLogin(user);
    } catch (err: unknown) {
      const e = err as { response?: { data?: { message?: string } } };
      setError(e?.response?.data?.message || 'Code invalide ou expiré.');
    } finally {
      setLoading(false);
    }
  };

  const cardStyle = {
    background: 'rgba(255,255,255,0.96)',
    backdropFilter: 'blur(40px) saturate(200%)',
    WebkitBackdropFilter: 'blur(40px) saturate(200%)',
    border: '1px solid rgba(255,255,255,0.85)',
    boxShadow: '0 24px 64px rgba(0,0,0,0.18), inset 0 1px 0 rgba(255,255,255,0.95)',
  };

  return (
    <div
      className="min-h-screen flex items-center justify-center px-4"
      style={{
        background: 'linear-gradient(135deg, #FF6B35 0%, #C85A20 40%, #E87040 70%, #FF8C42 100%)',
      }}
    >
      <div className="w-full max-w-md">
        {/* Logo */}
        <div className="text-center mb-8">
          <div className="flex items-center justify-center mb-2">
            <div className="bg-white/10 backdrop-blur-sm rounded-2xl px-6 py-3">
              <Image src="/logo.png" alt="BookMi" width={120} height={38} priority />
            </div>
          </div>
          <p className="text-sm mt-3" style={{ color: 'rgba(255,255,255,0.75)' }}>
            Espace talents, managers &amp; clients
          </p>
        </div>

        {/* ─── STEP 1: Credentials ─── */}
        {step === 'credentials' && (
          <div className="rounded-2xl p-8" style={cardStyle}>
            <h1 className="text-xl font-extrabold text-gray-900 mb-1">Connexion</h1>
            <p className="text-sm text-gray-500 mb-6">Accédez à votre espace de gestion</p>

            <form onSubmit={handleSubmit(onSubmit)} className="space-y-5">
              {error && (
                <Alert className="bg-red-50 border-red-200 text-red-800 text-sm">{error}</Alert>
              )}

              <div className="space-y-2">
                <Label htmlFor="email">Adresse email</Label>
                <Input
                  id="email"
                  type="email"
                  placeholder="votre@email.com"
                  autoComplete="email"
                  {...register('email')}
                  className={errors.email ? 'border-red-400' : ''}
                />
                {errors.email && <p className="text-xs text-red-500">{errors.email.message}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="password">Mot de passe</Label>
                <Input
                  id="password"
                  type="password"
                  placeholder="••••••••"
                  autoComplete="current-password"
                  {...register('password')}
                  className={errors.password ? 'border-red-400' : ''}
                />
                {errors.password && <p className="text-xs text-red-500">{errors.password.message}</p>}
              </div>

              <button
                type="submit"
                disabled={loading}
                className="w-full py-2.5 rounded-lg text-white text-sm font-semibold transition-all duration-150 disabled:opacity-60"
                style={{
                  background: loading ? 'rgba(255,107,53,0.6)' : 'linear-gradient(135deg, #FF6B35, #C85A20)',
                  boxShadow: loading ? 'none' : '0 4px 16px rgba(255,107,53,0.40)',
                }}
              >
                {loading ? 'Connexion...' : 'Se connecter'}
              </button>
            </form>

            <p className="text-center text-sm text-gray-500 mt-6">
              Pas encore de compte ?{' '}
              <Link href="/register" className="font-semibold" style={{ color: '#FF6B35' }}>
                Créer un compte
              </Link>
            </p>
          </div>
        )}

        {/* ─── STEP 2: 2FA Verification ─── */}
        {step === 'two_factor' && (
          <div className="rounded-2xl p-8" style={cardStyle}>
            <div className="flex items-center gap-3 mb-6">
              <div className="p-2 rounded-full" style={{ background: 'rgba(255,107,53,0.1)' }}>
                <ShieldCheck size={24} style={{ color: '#FF6B35' }} />
              </div>
              <div>
                <h1 className="text-xl font-extrabold text-gray-900">Vérification en deux étapes</h1>
                <p className="text-sm text-gray-500">
                  {twoFactorMethod === 'email'
                    ? 'Un code a été envoyé à votre adresse email.'
                    : 'Entrez le code depuis votre application d\'authentification.'}
                </p>
              </div>
            </div>

            {error && (
              <Alert className="bg-red-50 border-red-200 text-red-800 text-sm mb-4">{error}</Alert>
            )}

            <div className="space-y-2 mb-6">
              <Label htmlFor="2fa-code">Code de vérification (6 chiffres)</Label>
              <Input
                id="2fa-code"
                type="text"
                inputMode="numeric"
                maxLength={6}
                placeholder="000000"
                autoComplete="one-time-code"
                value={twoFactorCode}
                onChange={(e) => setTwoFactorCode(e.target.value.replace(/\D/g, '').slice(0, 6))}
                className="text-center text-2xl tracking-widest font-mono"
              />
            </div>

            <button
              onClick={onVerify2FA}
              disabled={loading || twoFactorCode.length !== 6}
              className="w-full py-2.5 rounded-lg text-white text-sm font-semibold transition-all duration-150 disabled:opacity-60"
              style={{
                background: (loading || twoFactorCode.length !== 6)
                  ? 'rgba(255,107,53,0.6)'
                  : 'linear-gradient(135deg, #FF6B35, #C85A20)',
                boxShadow: (loading || twoFactorCode.length !== 6)
                  ? 'none'
                  : '0 4px 16px rgba(255,107,53,0.40)',
              }}
            >
              {loading ? 'Vérification...' : 'Vérifier'}
            </button>

            <button
              onClick={() => { setStep('credentials'); setError(null); setTwoFactorCode(''); }}
              className="w-full mt-3 flex items-center justify-center gap-1 text-sm text-gray-500 hover:text-gray-700"
            >
              <ArrowLeft size={14} />
              Retour à la connexion
            </button>
          </div>
        )}
      </div>
    </div>
  );
}

export default function LoginPage() {
  return (
    <Suspense>
      <LoginForm />
    </Suspense>
  );
}
