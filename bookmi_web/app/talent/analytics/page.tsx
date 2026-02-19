'use client';

import { useQuery } from '@tanstack/react-query';
import { analyticsApi } from '@/lib/api/endpoints';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Alert } from '@/components/ui/alert';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
} from 'recharts';
import {
  TrendingUp,
  DollarSign,
  BookCheck,
  Star,
  Wallet,
  Clock,
} from 'lucide-react';

type MonthlyRevenue = {
  month: string;
  amount: number;
};

type AnalyticsData = {
  monthly_revenue: MonthlyRevenue[];
  total_revenue: number;
  total_bookings: number;
  average_rating: number;
};

type FinancialData = {
  balance: number;
  pending_payouts: number;
  total_earned: number;
};

function formatAmount(amount: number): string {
  return new Intl.NumberFormat('fr-FR').format(amount) + ' FCFA';
}

function formatMonthLabel(monthStr: string): string {
  if (!monthStr) return '';
  const [year, month] = monthStr.split('-');
  const date = new Date(parseInt(year), parseInt(month) - 1);
  return date.toLocaleDateString('fr-FR', { month: 'short', year: '2-digit' });
}

const CustomTooltip = ({
  active,
  payload,
  label,
}: {
  active?: boolean;
  payload?: { value: number }[];
  label?: string;
}) => {
  if (active && payload && payload.length) {
    return (
      <div className="bg-white border border-gray-200 rounded-lg shadow-lg px-4 py-3">
        <p className="text-sm font-semibold text-gray-700">{label}</p>
        <p className="text-[#2196F3] font-bold text-sm mt-1">
          {formatAmount(payload[0].value)}
        </p>
      </div>
    );
  }
  return null;
};

export default function TalentAnalyticsPage() {
  const { data: analyticsRes, isLoading: loadingAnalytics, isError: errorAnalytics } = useQuery({
    queryKey: ['analytics'],
    queryFn: () => analyticsApi.getDashboard(),
  });

  const { data: financialRes, isLoading: loadingFinancial } = useQuery({
    queryKey: ['financial'],
    queryFn: () => analyticsApi.getFinancialDashboard(),
  });

  const analytics: AnalyticsData | null = analyticsRes?.data?.data ?? null;
  const financial: FinancialData | null = financialRes?.data?.data ?? null;

  const chartData =
    analytics?.monthly_revenue?.map((item) => ({
      name: formatMonthLabel(item.month),
      amount: item.amount,
    })) ?? [];

  const now = new Date();
  const currentMonthKey = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
  const currentMonthRevenue =
    analytics?.monthly_revenue?.find((m) =>
      m.month?.startsWith(currentMonthKey)
    )?.amount ?? 0;

  const isLoading = loadingAnalytics || loadingFinancial;

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Analytiques</h1>
        <p className="text-gray-500 text-sm mt-1">
          Suivez vos performances et revenus
        </p>
      </div>

      {errorAnalytics && (
        <Alert className="bg-red-50 border-red-200 text-red-800 text-sm">
          Impossible de charger les données analytiques.
        </Alert>
      )}

      {/* KPI Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card>
          <CardHeader className="pb-2 flex flex-row items-center justify-between">
            <CardTitle className="text-sm font-medium text-gray-500">
              CA Total
            </CardTitle>
            <DollarSign size={18} className="text-[#2196F3]" />
          </CardHeader>
          <CardContent>
            {isLoading ? (
              <Skeleton className="h-8 w-28" />
            ) : (
              <p className="text-xl font-bold text-gray-900">
                {formatAmount(analytics?.total_revenue ?? 0)}
              </p>
            )}
            <p className="text-xs text-gray-400 mt-1">Cumul total</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2 flex flex-row items-center justify-between">
            <CardTitle className="text-sm font-medium text-gray-500">
              CA ce mois
            </CardTitle>
            <TrendingUp size={18} className="text-[#2196F3]" />
          </CardHeader>
          <CardContent>
            {isLoading ? (
              <Skeleton className="h-8 w-28" />
            ) : (
              <p className="text-xl font-bold text-gray-900">
                {formatAmount(currentMonthRevenue)}
              </p>
            )}
            <p className="text-xs text-gray-400 mt-1">Mois en cours</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2 flex flex-row items-center justify-between">
            <CardTitle className="text-sm font-medium text-gray-500">
              Réservations
            </CardTitle>
            <BookCheck size={18} className="text-[#2196F3]" />
          </CardHeader>
          <CardContent>
            {isLoading ? (
              <Skeleton className="h-8 w-12" />
            ) : (
              <p className="text-3xl font-bold text-gray-900">
                {analytics?.total_bookings ?? 0}
              </p>
            )}
            <p className="text-xs text-gray-400 mt-1">Complétées</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2 flex flex-row items-center justify-between">
            <CardTitle className="text-sm font-medium text-gray-500">
              Note moyenne
            </CardTitle>
            <Star size={18} className="text-[#2196F3]" />
          </CardHeader>
          <CardContent>
            {isLoading ? (
              <Skeleton className="h-8 w-12" />
            ) : (
              <p className="text-3xl font-bold text-gray-900">
                {analytics?.average_rating
                  ? analytics.average_rating.toFixed(1)
                  : '—'}
              </p>
            )}
            <p className="text-xs text-gray-400 mt-1">Sur 5 étoiles</p>
          </CardContent>
        </Card>
      </div>

      {/* Financial summary */}
      {financial && (
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
          <Card className="border-blue-100 bg-blue-50">
            <CardHeader className="pb-2 flex flex-row items-center justify-between">
              <CardTitle className="text-sm font-medium text-[#1A2744]">
                Solde disponible
              </CardTitle>
              <Wallet size={18} className="text-[#2196F3]" />
            </CardHeader>
            <CardContent>
              <p className="text-xl font-bold text-[#1A2744]">
                {formatAmount(Number(financial.balance) || 0)}
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-2 flex flex-row items-center justify-between">
              <CardTitle className="text-sm font-medium text-gray-500">
                En attente de paiement
              </CardTitle>
              <Clock size={18} className="text-yellow-500" />
            </CardHeader>
            <CardContent>
              <p className="text-xl font-bold text-gray-800">
                {formatAmount(Number(financial.pending_payouts) || 0)}
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-2 flex flex-row items-center justify-between">
              <CardTitle className="text-sm font-medium text-gray-500">
                Total encaissé
              </CardTitle>
              <TrendingUp size={18} className="text-green-500" />
            </CardHeader>
            <CardContent>
              <p className="text-xl font-bold text-gray-800">
                {formatAmount(Number(financial.total_earned) || 0)}
              </p>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Revenue chart */}
      <Card>
        <CardHeader>
          <CardTitle className="text-base font-semibold text-gray-800">
            Chiffre d&apos;affaires mensuel
          </CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <Skeleton className="h-64 w-full" />
          ) : chartData.length === 0 ? (
            <div className="h-64 flex items-center justify-center text-gray-400 text-sm">
              Aucune donnée disponible
            </div>
          ) : (
            <ResponsiveContainer width="100%" height={280}>
              <BarChart
                data={chartData}
                margin={{ top: 8, right: 16, left: 0, bottom: 0 }}
              >
                <CartesianGrid strokeDasharray="3 3" stroke="#f3f4f6" />
                <XAxis
                  dataKey="name"
                  tick={{ fontSize: 12, fill: '#9ca3af' }}
                  axisLine={false}
                  tickLine={false}
                />
                <YAxis
                  tick={{ fontSize: 11, fill: '#9ca3af' }}
                  axisLine={false}
                  tickLine={false}
                  tickFormatter={(v) =>
                    v >= 1000 ? `${(v / 1000).toFixed(0)}k` : String(v)
                  }
                />
                <Tooltip content={<CustomTooltip />} />
                <Bar
                  dataKey="amount"
                  fill="#2196F3"
                  radius={[6, 6, 0, 0]}
                  maxBarSize={48}
                />
              </BarChart>
            </ResponsiveContainer>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
