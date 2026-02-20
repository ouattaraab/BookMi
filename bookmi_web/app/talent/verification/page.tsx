'use client';

import { useState, useRef } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { verificationApi } from '@/lib/api/endpoints';
import { Alert } from '@/components/ui/alert';
import { ShieldCheck, ShieldAlert, Clock, Upload, CheckCircle } from 'lucide-react';

const DOCUMENT_TYPES = [
  { value: 'national_id', label: "Carte nationale d'identité" },
  { value: 'passport', label: 'Passeport' },
  { value: 'driver_license', label: 'Permis de conduire' },
];

type Verification = {
  id: number;
  status: string;
  document_type?: string;
  reject_reason?: string;
  created_at: string;
};

export default function TalentVerificationPage() {
  const qc = useQueryClient();
  const fileRef = useRef<HTMLInputElement>(null);
  const [docType, setDocType] = useState('national_id');
  const [file, setFile] = useState<File | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState(false);

  const { data, isLoading } = useQuery({
    queryKey: ['verification_me'],
    queryFn: () => verificationApi.getMe(),
  });

  const verification: Verification | null = data?.data?.data ?? data?.data ?? null;

  const submitMutation = useMutation({
    mutationFn: () => {
      if (!file) throw new Error('Veuillez sélectionner un fichier');
      const formData = new FormData();
      formData.append('document', file);
      formData.append('document_type', docType);
      return verificationApi.submit(formData);
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['verification_me'] });
      setSuccess(true);
      setFile(null);
      setError(null);
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { message?: string; error?: { message?: string } } } };
      setError(e?.response?.data?.error?.message ?? e?.response?.data?.message ?? 'Erreur lors de la soumission');
    },
  });

  const cardStyle = {
    background: 'rgba(255,255,255,0.82)',
    backdropFilter: 'blur(12px)',
    border: '1px solid rgba(255,255,255,0.9)',
    borderRadius: '1rem',
    padding: '2rem',
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

  return (
    <div className="space-y-6 max-w-xl mx-auto">
      <div>
        <h1 className="text-2xl font-extrabold text-gray-900">Vérification d&apos;identité</h1>
        <p className="text-sm text-gray-500 mt-1">
          Soumettez un document officiel pour obtenir le badge &quot;Talent vérifié&quot;
        </p>
      </div>

      {isLoading ? (
        <div className="h-48 rounded-2xl animate-pulse" style={{ background: 'rgba(255,255,255,0.5)' }} />
      ) : verification ? (
        <>
          {/* Current status */}
          <div style={cardStyle}>
            {verification.status === 'approved' && (
              <div className="text-center space-y-3">
                <div className="w-16 h-16 rounded-full flex items-center justify-center mx-auto" style={{ background: 'rgba(16,185,129,0.12)' }}>
                  <ShieldCheck size={32} style={{ color: '#10B981' }} />
                </div>
                <p className="text-xl font-extrabold text-green-700">Compte vérifié ✓</p>
                <p className="text-sm text-gray-500">
                  Votre identité a été vérifiée avec succès. Le badge &quot;Talent vérifié&quot; est visible sur votre profil public.
                </p>
              </div>
            )}

            {verification.status === 'pending' && (
              <div className="text-center space-y-3">
                <div className="w-16 h-16 rounded-full flex items-center justify-center mx-auto" style={{ background: 'rgba(245,158,11,0.12)' }}>
                  <Clock size={32} style={{ color: '#F59E0B' }} />
                </div>
                <p className="text-xl font-extrabold text-amber-700">Vérification en cours</p>
                <p className="text-sm text-gray-500">
                  Votre document a été soumis et est en cours d&apos;examen par notre équipe. Délai estimé : 24-48h.
                </p>
                <p className="text-xs text-gray-400">
                  Soumis le {new Date(verification.created_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })}
                </p>
              </div>
            )}

            {verification.status === 'rejected' && (
              <div className="space-y-4">
                <div className="text-center space-y-3">
                  <div className="w-16 h-16 rounded-full flex items-center justify-center mx-auto" style={{ background: 'rgba(239,68,68,0.12)' }}>
                    <ShieldAlert size={32} style={{ color: '#EF4444' }} />
                  </div>
                  <p className="text-xl font-extrabold text-red-700">Document refusé</p>
                  {verification.reject_reason && (
                    <div className="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-800 text-left">
                      <p className="font-semibold mb-1">Motif du refus :</p>
                      <p>{verification.reject_reason}</p>
                    </div>
                  )}
                </div>
                {/* Allow resubmission */}
                <VerificationForm
                  docType={docType}
                  setDocType={setDocType}
                  file={file}
                  setFile={setFile}
                  fileRef={fileRef}
                  error={error}
                  success={success}
                  isPending={submitMutation.isPending}
                  onSubmit={() => submitMutation.mutate()}
                  inputStyle={inputStyle}
                />
              </div>
            )}
          </div>
        </>
      ) : (
        /* No verification yet */
        <div style={cardStyle}>
          <div className="flex items-center gap-2 mb-5">
            <div className="w-8 h-8 rounded-lg flex items-center justify-center" style={{ background: 'rgba(255,107,53,0.12)' }}>
              <Upload size={16} style={{ color: '#FF6B35' }} />
            </div>
            <h2 className="font-bold text-gray-900">Soumettre un document</h2>
          </div>
          <VerificationForm
            docType={docType}
            setDocType={setDocType}
            file={file}
            setFile={setFile}
            fileRef={fileRef}
            error={error}
            success={success}
            isPending={submitMutation.isPending}
            onSubmit={() => submitMutation.mutate()}
            inputStyle={inputStyle}
          />
        </div>
      )}
    </div>
  );
}

function VerificationForm({
  docType,
  setDocType,
  file,
  setFile,
  fileRef,
  error,
  success,
  isPending,
  onSubmit,
  inputStyle,
}: {
  docType: string;
  setDocType: (v: string) => void;
  file: File | null;
  setFile: (f: File | null) => void;
  fileRef: React.RefObject<HTMLInputElement | null>;
  error: string | null;
  success: boolean;
  isPending: boolean;
  onSubmit: () => void;
  inputStyle: React.CSSProperties;
}) {
  return (
    <div className="space-y-4">
      {error && <Alert className="bg-red-50 border-red-200 text-red-800 text-sm">{error}</Alert>}
      {success && (
        <div className="flex items-center gap-2 text-green-700 text-sm bg-green-50 px-3 py-2 rounded-lg">
          <CheckCircle size={15} /> Document soumis avec succès. Vérification en cours.
        </div>
      )}

      <div>
        <label style={{ fontSize: '0.75rem', fontWeight: 600, color: '#6B7280', marginBottom: '0.25rem', display: 'block' }}>
          Type de document
        </label>
        <select
          style={{ ...inputStyle, cursor: 'pointer' }}
          value={docType}
          onChange={(e) => setDocType(e.target.value)}
        >
          {DOCUMENT_TYPES.map((d) => (
            <option key={d.value} value={d.value}>{d.label}</option>
          ))}
        </select>
      </div>

      <div>
        <label style={{ fontSize: '0.75rem', fontWeight: 600, color: '#6B7280', marginBottom: '0.25rem', display: 'block' }}>
          Fichier (JPG, PNG — max 5 Mo)
        </label>
        <div
          className="flex flex-col items-center justify-center gap-3 p-8 rounded-xl cursor-pointer hover:bg-orange-50 transition-colors"
          style={{ border: '2px dashed rgba(255,107,53,0.3)', minHeight: '8rem' }}
          onClick={() => fileRef.current?.click()}
        >
          <Upload size={24} style={{ color: '#FF6B35' }} />
          {file ? (
            <div className="text-center">
              <p className="text-sm font-semibold text-gray-800">{file.name}</p>
              <p className="text-xs text-gray-400">{(file.size / 1024 / 1024).toFixed(2)} Mo</p>
            </div>
          ) : (
            <p className="text-sm text-gray-500">Cliquez pour sélectionner un fichier</p>
          )}
        </div>
        <input
          ref={fileRef}
          type="file"
          accept="image/jpeg,image/png,image/jpg"
          className="hidden"
          onChange={(e) => setFile(e.target.files?.[0] ?? null)}
        />
      </div>

      <button
        onClick={onSubmit}
        disabled={isPending || !file}
        className="w-full flex items-center justify-center gap-2 py-3 rounded-xl text-white text-sm font-bold disabled:opacity-60 transition-all"
        style={{ background: 'linear-gradient(135deg, #FF6B35, #C85A20)' }}
      >
        <Upload size={15} />
        {isPending ? 'Envoi en cours...' : 'Envoyer pour vérification'}
      </button>
    </div>
  );
}
