'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { authApi } from '@/lib/api/endpoints';
import { useAuthStore } from '@/lib/store/auth';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert } from '@/components/ui/alert';

const loginSchema = z.object({
  email: z.string().email('Email invalide'),
  password: z.string().min(6, 'Mot de passe trop court'),
});

type LoginFormData = z.infer<typeof loginSchema>;

export default function LoginPage() {
  const router = useRouter();
  const setAuth = useAuthStore((s) => s.setAuth);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<LoginFormData>({
    resolver: zodResolver(loginSchema),
  });

  const onSubmit = async (data: LoginFormData) => {
    setError(null);
    setLoading(true);
    try {
      const res = await authApi.login(data);
      const { token, user } = res.data.data;

      setAuth(token, user);
      document.cookie = `bookmi_token=${token}; path=/; max-age=${60 * 60 * 24 * 7}; SameSite=Lax`;

      if (user.talentProfile) {
        router.push('/talent/dashboard');
      } else {
        router.push('/manager/talents');
      }
    } catch (err: unknown) {
      const axiosErr = err as { response?: { data?: { message?: string } } };
      setError(
        axiosErr?.response?.data?.message ||
          'Identifiants incorrects. Veuillez réessayer.'
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <div
      className="min-h-screen flex items-center justify-center px-4"
      style={{
        background:
          'linear-gradient(135deg, #FF6B35 0%, #C85A20 40%, #E87040 70%, #FF8C42 100%)',
      }}
    >
      <div className="w-full max-w-md">
        {/* Logo — bicolore adapté au fond orange */}
        <div className="text-center mb-8">
          <div className="flex items-center justify-center mb-3">
            <span className="font-extrabold text-4xl text-white tracking-tight leading-none">
              Book
            </span>
            <span
              className="font-extrabold text-4xl tracking-tight leading-none"
              style={{ color: 'rgba(255,235,180,0.95)' }}
            >
              Mi
            </span>
          </div>
          <p style={{ color: 'rgba(255,255,255,0.72)' }} className="text-sm mt-1">
            Espace talents &amp; managers
          </p>
        </div>

        {/* Card glassmorphism iOS 26 */}
        <div
          className="rounded-2xl p-8"
          style={{
            background: 'rgba(255,255,255,0.96)',
            backdropFilter: 'blur(40px) saturate(200%)',
            WebkitBackdropFilter: 'blur(40px) saturate(200%)',
            border: '1px solid rgba(255,255,255,0.85)',
            boxShadow:
              '0 24px 64px rgba(0,0,0,0.18), inset 0 1px 0 rgba(255,255,255,0.95)',
          }}
        >
          <h1 className="text-xl font-extrabold text-gray-900 mb-1">
            Connexion
          </h1>
          <p className="text-sm text-gray-500 mb-6">
            Accédez à votre espace de gestion
          </p>

          <form onSubmit={handleSubmit(onSubmit)} className="space-y-5">
            {error && (
              <Alert className="bg-red-50 border-red-200 text-red-800 text-sm">
                {error}
              </Alert>
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
              {errors.email && (
                <p className="text-xs text-red-500">{errors.email.message}</p>
              )}
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
              {errors.password && (
                <p className="text-xs text-red-500">
                  {errors.password.message}
                </p>
              )}
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full py-2.5 rounded-lg text-white text-sm font-semibold transition-all duration-150 disabled:opacity-60"
              style={{
                background: loading
                  ? 'rgba(255,107,53,0.6)'
                  : 'linear-gradient(135deg, #FF6B35, #C85A20)',
                boxShadow: loading
                  ? 'none'
                  : '0 4px 16px rgba(255,107,53,0.40)',
              }}
              onMouseEnter={(e) => {
                if (!loading)
                  (e.currentTarget as HTMLElement).style.boxShadow =
                    '0 6px 24px rgba(255,107,53,0.55)';
              }}
              onMouseLeave={(e) => {
                if (!loading)
                  (e.currentTarget as HTMLElement).style.boxShadow =
                    '0 4px 16px rgba(255,107,53,0.40)';
              }}
            >
              {loading ? 'Connexion...' : 'Se connecter'}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}
