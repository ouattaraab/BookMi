'use client';

import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { calendarApi } from '@/lib/api/endpoints';
import { useAuthStore } from '@/lib/store/auth';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Alert } from '@/components/ui/alert';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog';
import { Plus, Trash2, Calendar } from 'lucide-react';

type CalendarSlot = {
  id: number;
  date_from: string;
  date_to: string;
  type: 'available' | 'unavailable';
};

const slotSchema = z
  .object({
    date_from: z.string().min(1, 'Date de début requise'),
    date_to: z.string().min(1, 'Date de fin requise'),
  })
  .refine((data) => new Date(data.date_to) > new Date(data.date_from), {
    message: 'La date de fin doit être après la date de début',
    path: ['date_to'],
  });

type SlotFormData = z.infer<typeof slotSchema>;

function formatDateTime(dateStr: string): string {
  if (!dateStr) return '—';
  return new Date(dateStr).toLocaleString('fr-FR', {
    weekday: 'short',
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

function groupByDate(
  slots: CalendarSlot[]
): Record<string, CalendarSlot[]> {
  const grouped: Record<string, CalendarSlot[]> = {};
  slots.forEach((slot) => {
    const dateKey = new Date(slot.date_from).toLocaleDateString('fr-FR', {
      weekday: 'long',
      day: '2-digit',
      month: 'long',
      year: 'numeric',
    });
    if (!grouped[dateKey]) grouped[dateKey] = [];
    grouped[dateKey].push(slot);
  });
  return grouped;
}

export default function TalentCalendarPage() {
  const queryClient = useQueryClient();
  const user = useAuthStore((s) => s.user);
  const talentId = user?.talentProfile?.id;

  const [addOpen, setAddOpen] = useState(false);
  const [deleteId, setDeleteId] = useState<number | null>(null);
  const [apiError, setApiError] = useState<string | null>(null);

  const { data, isLoading } = useQuery({
    queryKey: ['calendar_slots', talentId],
    queryFn: () => calendarApi.getSlots(talentId!),
    enabled: !!talentId,
  });

  const slots: CalendarSlot[] = data?.data?.data ?? [];
  const sorted = [...slots].sort(
    (a, b) => new Date(a.date_from).getTime() - new Date(b.date_from).getTime()
  );
  const grouped = groupByDate(sorted);

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<SlotFormData>({
    resolver: zodResolver(slotSchema),
  });

  const createMutation = useMutation({
    mutationFn: (formData: SlotFormData) => calendarApi.createSlot(formData),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['calendar_slots'] });
      setAddOpen(false);
      reset();
      setApiError(null);
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { message?: string } } };
      setApiError(e?.response?.data?.message ?? 'Erreur lors de la création');
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => calendarApi.deleteSlot(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['calendar_slots'] });
      setDeleteId(null);
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { message?: string } } };
      setApiError(e?.response?.data?.message ?? 'Erreur lors de la suppression');
      setDeleteId(null);
    },
  });

  const onSubmit = (data: SlotFormData) => {
    setApiError(null);
    createMutation.mutate(data);
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Calendrier</h1>
          <p className="text-gray-500 text-sm mt-1">
            Gérez vos créneaux de disponibilité
          </p>
        </div>
        <Button
          onClick={() => { setAddOpen(true); setApiError(null); }}
          className="bg-amber-500 hover:bg-amber-600 text-white gap-2"
        >
          <Plus size={16} />
          Ajouter un créneau
        </Button>
      </div>

      {apiError && (
        <Alert className="bg-red-50 border-red-200 text-red-800 text-sm">
          {apiError}
        </Alert>
      )}

      {isLoading ? (
        <div className="space-y-4">
          {[...Array(3)].map((_, i) => (
            <Skeleton key={i} className="h-24 w-full" />
          ))}
        </div>
      ) : sorted.length === 0 ? (
        <Card>
          <CardContent className="py-16 text-center">
            <Calendar size={40} className="text-gray-300 mx-auto mb-4" />
            <p className="text-gray-400">
              Aucun créneau défini. Ajoutez votre première disponibilité.
            </p>
          </CardContent>
        </Card>
      ) : (
        <div className="space-y-6">
          {Object.entries(grouped).map(([dateLabel, daySlots]) => (
            <div key={dateLabel}>
              <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3 capitalize">
                {dateLabel}
              </h3>
              <div className="space-y-2">
                {daySlots.map((slot) => (
                  <Card key={slot.id} className="hover:shadow-sm transition-shadow">
                    <CardContent className="py-4 flex items-center justify-between">
                      <div className="flex items-center gap-4">
                        <Badge
                          className={
                            slot.type === 'available'
                              ? 'bg-green-100 text-green-800 border-green-200 border'
                              : 'bg-red-100 text-red-800 border-red-200 border'
                          }
                        >
                          {slot.type === 'available' ? 'Disponible' : 'Indisponible'}
                        </Badge>
                        <div>
                          <p className="text-sm font-medium text-gray-800">
                            {formatDateTime(slot.date_from)}
                          </p>
                          <p className="text-xs text-gray-400">
                            jusqu&apos;au {formatDateTime(slot.date_to)}
                          </p>
                        </div>
                      </div>
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => setDeleteId(slot.id)}
                        className="text-red-400 hover:text-red-600 hover:bg-red-50"
                      >
                        <Trash2 size={15} />
                      </Button>
                    </CardContent>
                  </Card>
                ))}
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Add slot dialog */}
      <Dialog open={addOpen} onOpenChange={setAddOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Ajouter un créneau</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-5 mt-2">
            {apiError && (
              <Alert className="bg-red-50 border-red-200 text-red-800 text-sm">
                {apiError}
              </Alert>
            )}
            <div className="space-y-2">
              <Label htmlFor="date_from">Début</Label>
              <Input
                id="date_from"
                type="datetime-local"
                {...register('date_from')}
                className={errors.date_from ? 'border-red-400' : ''}
              />
              {errors.date_from && (
                <p className="text-xs text-red-500">{errors.date_from.message}</p>
              )}
            </div>
            <div className="space-y-2">
              <Label htmlFor="date_to">Fin</Label>
              <Input
                id="date_to"
                type="datetime-local"
                {...register('date_to')}
                className={errors.date_to ? 'border-red-400' : ''}
              />
              {errors.date_to && (
                <p className="text-xs text-red-500">{errors.date_to.message}</p>
              )}
            </div>
            <DialogFooter>
              <Button
                type="button"
                variant="outline"
                onClick={() => { setAddOpen(false); reset(); }}
              >
                Annuler
              </Button>
              <Button
                type="submit"
                disabled={createMutation.isPending}
                className="bg-amber-500 hover:bg-amber-600 text-white"
              >
                {createMutation.isPending ? 'Enregistrement...' : 'Enregistrer'}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* Delete confirmation dialog */}
      <Dialog open={deleteId !== null} onOpenChange={() => setDeleteId(null)}>
        <DialogContent className="sm:max-w-sm">
          <DialogHeader>
            <DialogTitle>Confirmer la suppression</DialogTitle>
          </DialogHeader>
          <p className="text-sm text-gray-600 mt-2">
            Êtes-vous sûr de vouloir supprimer ce créneau ? Cette action est
            irréversible.
          </p>
          <DialogFooter className="mt-4">
            <Button variant="outline" onClick={() => setDeleteId(null)}>
              Annuler
            </Button>
            <Button
              variant="destructive"
              disabled={deleteMutation.isPending}
              onClick={() => deleteId !== null && deleteMutation.mutate(deleteId)}
            >
              {deleteMutation.isPending ? 'Suppression...' : 'Supprimer'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
