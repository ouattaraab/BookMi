'use client';

import { useState, useRef } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useQuery } from '@tanstack/react-query';
import { authApi, publicApi } from '@/lib/api/endpoints';
import { useAuthStore } from '@/lib/store/auth';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert } from '@/components/ui/alert';
import { User, Mic2, ChevronLeft } from 'lucide-react';

type Role = 'client' | 'talent';

const formSchema = z
  .object({
    first_name: z.string().min(2, 'Prénom requis (2 car. min.)'),
    last_name: z.string().min(2, 'Nom requis (2 car. min.)'),
    email: z.string().email('Email invalide'),
    phone: z.string().regex(/^\+225\d{10}$/, 'Format : +225 suivi de 10 chiffres'),
    password: z.string().min(8, '8 caractères minimum'),
    password_confirmation: z.string().min(1, 'Confirmation requise'),
    category_id: z.string().optional(),
  })
  .refine((d) => d.password === d.password_confirmation, {
    message: 'Les mots de passe ne correspondent pas',
    path: ['password_confirmation'],
  });

type FormData = z.infer<typeof formSchema>;

// ── OTP Input ──────────────────────────────────────────────────────────────

function OtpInput({ value, onChange }: { value: string; onChange: (v: string) => void }) {
  const inputs = useRef<(HTMLInputElement | null)[]>([]);
  const digits = value.split('').concat(Array(6).fill('')).slice(0, 6);

  const handleChange = (i: number, v: string) => {
    const d = v.replace(/\D/g, '').slice(-1);
    const next = [...digits];
    next[i] = d;
    onChange(next.join(''));
    if (d && i < 5) inputs.current[i + 1]?.focus();
  };

  const handleKeyDown = (i: number, e: React.KeyboardEvent) => {
    if (e.key === 'Backspace' && !digits[i] && i > 0) {
      inputs.current[i - 1]?.focus();
    }
  };

  const handlePaste = (e: React.ClipboardEvent) => {
    const pasted = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
    onChange(pasted.padEnd(6, '').slice(0, 6));
    inputs.current[Math.min(pasted.length, 5)]?.focus();
    e.preventDefault();
  };

  return (
    <div className="flex gap-3 justify-center">
      {digits.map((d, i) => (
        <input
          key={i}
          ref={(el) => { inputs.current[i] = el; }}
          type="text"
          inputMode="numeric"
          maxLength={1}
          value={d}
          onChange={(e) => handleChange(i, e.target.value)}
          onKeyDown={(e) => handleKeyDown(i, e)}
          onPaste={handlePaste}
          className="w-12 h-14 text-center text-xl font-bold rounded-xl border-2 outline-none transition-all"
          style={{
            borderColor: d ? '#2196F3' : '#E2E8F0',
            background: d ? 'rgba(33,150,243,0.06)' : 'white',
          }}
        />
      ))}
    </div>
  );
}

// ── Main ──────────────────────────────────────────────────────────────────────

export default function RegisterPage() {
  const router = useRouter();
  const setAuth = useAuthStore((s) => s.setAuth);

  const [step, setStep] = useState<'role' | 'form' | 'otp'>('role');
  const [role, setRole] = useState<Role | null>(null);
  const [registeredPhone, setRegisteredPhone] = useState('');
  const [otp, setOtp] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [resendCooldown, setResendCooldown] = useState(0);

  const { data: catData } = useQuery({
    queryKey: ['public_categories'],
    queryFn: () => publicApi.getCategories(),
    enabled: step === 'form' && role === 'talent',
  });
  const categories: { id: number; name: string; parent_id?: number }[] =
    (catData?.data?.data ?? []).filter((c: { parent_id?: number }) => !c.parent_id);

  const {
    register,
    handleSubmit,
    watch,
    setError: setFieldError,
    formState: { errors },
  } = useForm<FormData>({
    resolver: zodResolver(formSchema),
    defaultValues: { phone: '+225' },
  });

  const phoneValue = watch('phone');

  const startCooldown = () => {
    setResendCooldown(60);
    const id = setInterval(() => {
      setResendCooldown((c) => {
        if (c <= 1) { clearInterval(id); return 0; }
        return c - 1;
      });
    }, 1000);
  };

  const handleFormSubmit = async (data: FormData) => {
    if (role === 'talent' && !data.category_id) {
      setFieldError('category_id', { message: 'Catégorie requise' });
      return;
    }
    setError(null);
    setLoading(true);
    try {
      const payload: Record<string, unknown> = {
        first_name: data.first_name,
        last_name: data.last_name,
        email: data.email,
        phone: data.phone,
        password: data.password,
        password_confirmation: data.password_confirmation,
        role,
      };
      if (role === 'talent') payload.category_id = Number(data.category_id);
      await authApi.register(payload);
      setRegisteredPhone(data.phone);
      setStep('otp');
      startCooldown();
    } catch (err: unknown) {
      const e = err as { response?: { data?: { error?: { message?: string }; message?: string } } };
      setError(
        e?.response?.data?.error?.message ??
        e?.response?.data?.message ??
        "Erreur lors de l'inscription"
      );
    } finally {
      setLoading(false);
    }
  };

  const handleResendOtp = async () => {
    if (resendCooldown > 0) return;
    setError(null);
    try {
      await authApi.resendOtp({ phone: registeredPhone });
      startCooldown();
    } catch {
      setError('Erreur lors du renvoi du code');
    }
  };

  const handleVerifyOtp = async () => {
    if (otp.replace(/\s/g, '').length < 6) return;
    setError(null);
    setLoading(true);
    try {
      const res = await authApi.verifyOtp({ phone: registeredPhone, otp });
      const { token, user } = res.data.data;
      setAuth(token, user);
      document.cookie = `bookmi_token=${token}; path=/; max-age=${60 * 60 * 24 * 7}; SameSite=Lax`;
      router.push(user.talentProfile ? '/talent/dashboard' : '/client/dashboard');
    } catch (err: unknown) {
      const e = err as { response?: { data?: { error?: { message?: string }; message?: string } } };
      setError(e?.response?.data?.error?.message ?? e?.response?.data?.message ?? 'Code incorrect');
    } finally {
      setLoading(false);
    }
  };

  const steps = ['role', 'form', 'otp'] as const;
  const stepIndex = steps.indexOf(step);

  return (
    <div
      className="min-h-screen flex items-center justify-center px-4 py-12"
      style={{
        background: 'linear-gradient(135deg, #FF6B35 0%, #C85A20 40%, #E87040 70%, #FF8C42 100%)',
      }}
    >
      <div className="w-full max-w-md">
        {/* Logo */}
        <div className="text-center mb-7">
          <div className="flex items-center justify-center mb-2">
            <span className="font-extrabold text-4xl text-white tracking-tight">Book</span>
            <span className="font-extrabold text-4xl tracking-tight" style={{ color: 'rgba(255,235,180,0.95)' }}>Mi</span>
          </div>
          <p className="text-sm" style={{ color: 'rgba(255,255,255,0.72)' }}>
            {step === 'role' && 'Créez votre compte'}
            {step === 'form' && (role === 'client' ? 'Informations personnelles' : 'Profil artiste')}
            {step === 'otp' && 'Vérification du téléphone'}
          </p>
        </div>

        {/* Step indicator */}
        <div className="flex items-center justify-center gap-2 mb-6">
          {steps.map((s, i) => (
            <div
              key={s}
              className="rounded-full transition-all duration-300"
              style={{
                height: 6,
                width: stepIndex >= i ? 28 : 8,
                background: stepIndex >= i ? 'white' : 'rgba(255,255,255,0.35)',
              }}
            />
          ))}
        </div>

        {/* Card */}
        <div
          className="rounded-2xl p-8"
          style={{
            background: 'rgba(255,255,255,0.96)',
            backdropFilter: 'blur(40px) saturate(200%)',
            WebkitBackdropFilter: 'blur(40px) saturate(200%)',
            border: '1px solid rgba(255,255,255,0.85)',
            boxShadow: '0 24px 64px rgba(0,0,0,0.18), inset 0 1px 0 rgba(255,255,255,0.95)',
          }}
        >
          {error && (
            <Alert className="bg-red-50 border-red-200 text-red-800 text-sm mb-5">{error}</Alert>
          )}

          {/* ── Étape 1 : Choix du rôle ── */}
          {step === 'role' && (
            <div>
              <h1 className="text-xl font-extrabold text-gray-900 mb-1">Choisissez votre profil</h1>
              <p className="text-sm text-gray-500 mb-6">Comment souhaitez-vous utiliser BookMi ?</p>
              <div className="space-y-4">
                <button
                  onClick={() => { setRole('client'); setStep('form'); setError(null); }}
                  className="w-full p-5 rounded-xl border-2 text-left transition-all hover:border-blue-300 hover:bg-blue-50 group"
                  style={{ borderColor: '#E2E8F0' }}
                >
                  <div className="flex items-center gap-4">
                    <div className="w-12 h-12 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform" style={{ background: 'rgba(33,150,243,0.1)' }}>
                      <User size={22} style={{ color: '#2196F3' }} />
                    </div>
                    <div>
                      <p className="font-bold text-gray-900">Je suis un client</p>
                      <p className="text-sm text-gray-500">Je veux réserver des talents</p>
                    </div>
                  </div>
                </button>
                <button
                  onClick={() => { setRole('talent'); setStep('form'); setError(null); }}
                  className="w-full p-5 rounded-xl border-2 text-left transition-all hover:border-orange-300 hover:bg-orange-50 group"
                  style={{ borderColor: '#E2E8F0' }}
                >
                  <div className="flex items-center gap-4">
                    <div className="w-12 h-12 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform" style={{ background: 'rgba(255,107,53,0.1)' }}>
                      <Mic2 size={22} style={{ color: '#FF6B35' }} />
                    </div>
                    <div>
                      <p className="font-bold text-gray-900">Je suis un artiste</p>
                      <p className="text-sm text-gray-500">Je veux proposer mes services</p>
                    </div>
                  </div>
                </button>
              </div>
              <p className="text-center text-sm text-gray-500 mt-6">
                Déjà un compte ?{' '}
                <Link href="/login" className="font-semibold" style={{ color: '#FF6B35' }}>
                  Se connecter
                </Link>
              </p>
            </div>
          )}

          {/* ── Étape 2 : Formulaire ── */}
          {step === 'form' && role && (
            <div>
              <div className="flex items-center gap-3 mb-6">
                <button
                  onClick={() => { setStep('role'); setError(null); }}
                  className="p-1.5 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors"
                >
                  <ChevronLeft size={18} />
                </button>
                <div>
                  <h1 className="text-xl font-extrabold text-gray-900">
                    {role === 'client' ? 'Infos personnelles' : 'Profil artiste'}
                  </h1>
                  <p className="text-xs text-gray-500">Créez votre compte {role === 'client' ? 'client' : 'artiste'}</p>
                </div>
              </div>

              <form onSubmit={handleSubmit(handleFormSubmit)} className="space-y-4">
                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <Label className="text-xs font-medium">Prénom</Label>
                    <Input {...register('first_name')} placeholder="Jean" className={errors.first_name ? 'border-red-400' : ''} />
                    {errors.first_name && <p className="text-xs text-red-500 mt-0.5">{errors.first_name.message}</p>}
                  </div>
                  <div>
                    <Label className="text-xs font-medium">Nom</Label>
                    <Input {...register('last_name')} placeholder="Dupont" className={errors.last_name ? 'border-red-400' : ''} />
                    {errors.last_name && <p className="text-xs text-red-500 mt-0.5">{errors.last_name.message}</p>}
                  </div>
                </div>

                <div>
                  <Label className="text-xs font-medium">Email</Label>
                  <Input type="email" {...register('email')} placeholder="jean@email.com" className={errors.email ? 'border-red-400' : ''} />
                  {errors.email && <p className="text-xs text-red-500 mt-0.5">{errors.email.message}</p>}
                </div>

                <div>
                  <Label className="text-xs font-medium">Téléphone</Label>
                  <Input
                    {...register('phone')}
                    placeholder="+225 07 00 00 00 00"
                    className={errors.phone ? 'border-red-400' : ''}
                    defaultValue="+225"
                  />
                  {errors.phone && <p className="text-xs text-red-500 mt-0.5">{errors.phone.message}</p>}
                  {!errors.phone && phoneValue && phoneValue !== '+225' && (
                    <p className="text-xs text-gray-400 mt-0.5">Ex: +225 0700000000</p>
                  )}
                </div>

                {role === 'talent' && (
                  <div>
                    <Label className="text-xs font-medium">Catégorie principale</Label>
                    <select
                      {...register('category_id')}
                      className="w-full h-10 rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-ring"
                    >
                      <option value="">Choisir une catégorie</option>
                      {categories.map((c) => (
                        <option key={c.id} value={c.id}>{c.name}</option>
                      ))}
                    </select>
                    {errors.category_id && <p className="text-xs text-red-500 mt-0.5">{errors.category_id.message}</p>}
                  </div>
                )}

                <div>
                  <Label className="text-xs font-medium">Mot de passe</Label>
                  <Input type="password" {...register('password')} placeholder="••••••••" className={errors.password ? 'border-red-400' : ''} />
                  {errors.password && <p className="text-xs text-red-500 mt-0.5">{errors.password.message}</p>}
                </div>

                <div>
                  <Label className="text-xs font-medium">Confirmer le mot de passe</Label>
                  <Input type="password" {...register('password_confirmation')} placeholder="••••••••" className={errors.password_confirmation ? 'border-red-400' : ''} />
                  {errors.password_confirmation && <p className="text-xs text-red-500 mt-0.5">{errors.password_confirmation.message}</p>}
                </div>

                <button
                  type="submit"
                  disabled={loading}
                  className="w-full py-2.5 rounded-lg text-white text-sm font-semibold disabled:opacity-60 transition-opacity"
                  style={{ background: 'linear-gradient(135deg, #FF6B35, #C85A20)', boxShadow: '0 4px 16px rgba(255,107,53,0.35)' }}
                >
                  {loading ? 'Inscription...' : 'Créer mon compte'}
                </button>
              </form>
            </div>
          )}

          {/* ── Étape 3 : OTP ── */}
          {step === 'otp' && (
            <div className="text-center">
              <h1 className="text-xl font-extrabold text-gray-900 mb-2">Vérifiez votre téléphone</h1>
              <p className="text-sm text-gray-500 mb-8">
                Entrez le code à 6 chiffres envoyé au<br />
                <span className="font-semibold text-gray-800">{registeredPhone}</span>
              </p>

              <OtpInput value={otp} onChange={setOtp} />

              <button
                onClick={handleVerifyOtp}
                disabled={loading || otp.replace(/\s/g, '').length < 6}
                className="w-full mt-8 py-2.5 rounded-lg text-white text-sm font-semibold disabled:opacity-50 transition-opacity"
                style={{ background: 'linear-gradient(135deg, #FF6B35, #C85A20)', boxShadow: '0 4px 16px rgba(255,107,53,0.35)' }}
              >
                {loading ? 'Vérification...' : 'Confirmer le code'}
              </button>

              <button
                onClick={handleResendOtp}
                disabled={resendCooldown > 0}
                className="mt-4 text-sm text-gray-400 hover:text-gray-700 disabled:opacity-50 transition-colors"
              >
                {resendCooldown > 0 ? `Renvoyer le code dans ${resendCooldown}s` : 'Renvoyer le code'}
              </button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
