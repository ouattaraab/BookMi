'use client';

import { useState } from 'react';
import { analyticsApi } from '@/lib/api/endpoints';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Alert } from '@/components/ui/alert';
import { FileText, Download, CheckCircle } from 'lucide-react';

export default function TalentCertificatePage() {
  const [loading, setLoading] = useState(false);
  const [success, setSuccess] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleDownload = async () => {
    setLoading(true);
    setError(null);
    setSuccess(false);
    try {
      const res = await analyticsApi.downloadCertificate();
      const blob = new Blob([res.data], { type: 'application/pdf' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `attestation_revenus_bookmi_${new Date().getFullYear()}.pdf`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
      setSuccess(true);
    } catch (err: unknown) {
      const e = err as { response?: { data?: { message?: string } } };
      setError(
        e?.response?.data?.message ??
          'Impossible de télécharger l\'attestation. Veuillez réessayer.'
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="space-y-6 max-w-xl">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">
          Attestation de revenus
        </h1>
        <p className="text-gray-500 text-sm mt-1">
          Téléchargez votre attestation officielle de revenus BookMi
        </p>
      </div>

      {error && (
        <Alert className="bg-red-50 border-red-200 text-red-800 text-sm">
          {error}
        </Alert>
      )}

      {success && (
        <Alert className="bg-green-50 border-green-200 text-green-800 text-sm flex items-center gap-2">
          <CheckCircle size={15} />
          Attestation téléchargée avec succès.
        </Alert>
      )}

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-3 text-base">
            <div className="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
              <FileText size={20} className="text-[#2196F3]" />
            </div>
            Attestation de revenus (PDF)
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-6">
          <p className="text-sm text-gray-600 leading-relaxed">
            Ce document officiel certifie vos revenus générés sur la plateforme
            BookMi. Il peut être utilisé pour des démarches administratives,
            fiscales ou bancaires.
          </p>

          <ul className="space-y-2 text-sm text-gray-600">
            <li className="flex items-center gap-2">
              <span className="w-1.5 h-1.5 rounded-full bg-[#2196F3] shrink-0" />
              Revenus totaux perçus sur la plateforme
            </li>
            <li className="flex items-center gap-2">
              <span className="w-1.5 h-1.5 rounded-full bg-[#2196F3] shrink-0" />
              Détail mensuel des encaissements
            </li>
            <li className="flex items-center gap-2">
              <span className="w-1.5 h-1.5 rounded-full bg-[#2196F3] shrink-0" />
              Informations de votre profil talent
            </li>
            <li className="flex items-center gap-2">
              <span className="w-1.5 h-1.5 rounded-full bg-[#2196F3] shrink-0" />
              Signature numérique BookMi
            </li>
          </ul>

          <Button
            onClick={handleDownload}
            disabled={loading}
            className="w-full bg-[#2196F3] hover:bg-[#1976D2] text-white gap-2 py-5"
            size="lg"
          >
            <Download size={18} />
            {loading
              ? 'Génération en cours...'
              : 'Télécharger mon attestation de revenus (PDF)'}
          </Button>
        </CardContent>
      </Card>
    </div>
  );
}
