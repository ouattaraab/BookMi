'use client';

import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import Link from 'next/link';
import { bookingApi } from '@/lib/api/endpoints';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { ChevronRight } from 'lucide-react';

type Booking = {
  id: number;
  status: string;
  event_date: string;
  location: string;
  total_amount: number;
  client?: { email: string; first_name: string; last_name: string };
};

const STATUS_LABELS: Record<string, string> = {
  pending: 'En attente',
  confirmed: 'Confirmée',
  completed: 'Complétée',
  cancelled: 'Annulée',
  rejected: 'Refusée',
};

const STATUS_COLORS: Record<string, string> = {
  pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',
  confirmed: 'bg-blue-100 text-blue-800 border-blue-200',
  completed: 'bg-green-100 text-green-800 border-green-200',
  cancelled: 'bg-gray-100 text-gray-600 border-gray-200',
  rejected: 'bg-red-100 text-red-800 border-red-200',
};

const TABS = [
  { value: 'all', label: 'Toutes' },
  { value: 'pending', label: 'En attente' },
  { value: 'confirmed', label: 'Confirmées' },
  { value: 'completed', label: 'Complétées' },
  { value: 'cancelled', label: 'Annulées' },
];

function formatAmount(amount: number): string {
  return new Intl.NumberFormat('fr-FR').format(amount) + ' FCFA';
}

function formatDate(dateStr: string): string {
  if (!dateStr) return '—';
  return new Date(dateStr).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  });
}

export default function TalentBookingsPage() {
  const [activeTab, setActiveTab] = useState('all');

  const { data, isLoading } = useQuery({
    queryKey: ['bookings'],
    queryFn: () => bookingApi.list(),
  });

  const allBookings: Booking[] = data?.data?.data ?? [];

  const filtered =
    activeTab === 'all'
      ? allBookings
      : allBookings.filter((b) => b.status === activeTab);

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Réservations</h1>
        <p className="text-gray-500 text-sm mt-1">
          Gérez toutes vos demandes de réservation
        </p>
      </div>

      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList className="bg-gray-100">
          {TABS.map((tab) => (
            <TabsTrigger key={tab.value} value={tab.value}>
              {tab.label}
              {tab.value !== 'all' && (
                <span className="ml-1.5 text-xs bg-white rounded-full px-1.5 py-0.5 text-gray-500">
                  {allBookings.filter((b) => b.status === tab.value).length}
                </span>
              )}
            </TabsTrigger>
          ))}
        </TabsList>
      </Tabs>

      <Card>
        <CardContent className="p-0">
          {isLoading ? (
            <div className="p-6 space-y-3">
              {[...Array(6)].map((_, i) => (
                <Skeleton key={i} className="h-12 w-full" />
              ))}
            </div>
          ) : filtered.length === 0 ? (
            <div className="py-16 text-center text-gray-400 text-sm">
              Aucune réservation dans cette catégorie
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow className="bg-gray-50">
                  <TableHead className="font-semibold text-gray-600">ID</TableHead>
                  <TableHead className="font-semibold text-gray-600">Client</TableHead>
                  <TableHead className="font-semibold text-gray-600">Date</TableHead>
                  <TableHead className="font-semibold text-gray-600">Lieu</TableHead>
                  <TableHead className="font-semibold text-gray-600 text-right">
                    Montant
                  </TableHead>
                  <TableHead className="font-semibold text-gray-600 text-center">
                    Statut
                  </TableHead>
                  <TableHead className="font-semibold text-gray-600 text-right">
                    Action
                  </TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filtered.map((booking) => (
                  <TableRow key={booking.id} className="hover:bg-gray-50">
                    <TableCell className="font-mono text-gray-400 text-xs">
                      #{booking.id}
                    </TableCell>
                    <TableCell className="text-gray-700">
                      {booking.client
                        ? `${booking.client.first_name} ${booking.client.last_name}`
                        : '—'}
                      {booking.client?.email && (
                        <p className="text-xs text-gray-400">
                          {booking.client.email}
                        </p>
                      )}
                    </TableCell>
                    <TableCell className="text-gray-600">
                      {formatDate(booking.event_date)}
                    </TableCell>
                    <TableCell className="text-gray-600 max-w-[150px] truncate">
                      {booking.location ?? '—'}
                    </TableCell>
                    <TableCell className="text-right font-medium text-gray-800">
                      {formatAmount(booking.total_amount)}
                    </TableCell>
                    <TableCell className="text-center">
                      <span
                        className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border ${STATUS_COLORS[booking.status] ?? 'bg-gray-100 text-gray-600'}`}
                      >
                        {STATUS_LABELS[booking.status] ?? booking.status}
                      </span>
                    </TableCell>
                    <TableCell className="text-right">
                      <Link href={`/talent/bookings/${booking.id}`}>
                        <Button
                          variant="ghost"
                          size="sm"
                          className="text-amber-600 hover:text-amber-700 hover:bg-amber-50 gap-1"
                        >
                          Voir
                          <ChevronRight size={14} />
                        </Button>
                      </Link>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
