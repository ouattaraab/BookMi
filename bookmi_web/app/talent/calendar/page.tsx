'use client';

import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { calendarApi } from '@/lib/api/endpoints';
import { useAuthStore } from '@/lib/store/auth';
import { Card, CardContent } from '@/components/ui/card';
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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Plus, Trash2, Calendar, ChevronLeft, ChevronRight } from 'lucide-react';

type CalendarSlot = {
  slot_id: number | null;
  date: string;
  status: 'available' | 'blocked' | 'rest' | 'confirmed';
};

const slotSchema = z.object({
  date: z.string().min(1, 'Date requise'),
  status: z.enum(['available', 'blocked', 'rest']),
});

type SlotFormData = z.infer<typeof slotSchema>;

const statusConfig = {
  available:  { label: 'Disponible', classes: 'bg-green-100 text-green-800 border border-green-200' },
  blocked:    { label: 'Bloqué',     classes: 'bg-red-100 text-red-800 border border-red-200' },
  rest:       { label: 'Repos',      classes: 'bg-gray-100 text-gray-600 border border-gray-200' },
  confirmed:  { label: 'Confirmé',   classes: 'bg-blue-100 text-blue-800 border border-blue-200' },
};

function formatDate(dateStr: string): string {
  if (!dateStr) return '—';
  return new Date(dateStr + 'T12:00:00').toLocaleDateString('fr-FR', {
    weekday: 'long',
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  });
}

function formatMonthLabel(yearMonth: string): string {
  const [year, month] = yearMonth.split('-');
  return new Date(Number(year), Number(month) - 1, 1).toLocaleDateString('fr-FR', {
    month: 'long',
    year: 'numeric',
  });
}

function addMonths(yearMonth: string, delta: number): string {
  const [year, month] = yearMonth.split('-').map(Number);
  const d = new Date(year, month - 1 + delta, 1);
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
}

function todayYearMonth(): string {
  const now = new Date();
  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
}

export default function TalentCalendarPage() {
  const queryClient = useQueryClient();
  const user = useAuthStore((s) => s.user);
  const talentId = user?.talentProfile?.id;

  const [currentMonth, setCurrentMonth] = useState(todayYearMonth);
  const [addOpen, setAddOpen] = useState(false);
  const [deleteId, setDeleteId] = useState<number | null>(null);
  const [apiError, setApiError] = useState<string | null>(null);

  const { data, isLoading } = useQuery({
    queryKey: ['calendar_slots', talentId, currentMonth],
    queryFn: () => calendarApi.getSlots(talentId!, currentMonth),
    enabled: !!talentId,
  });

  const slots: CalendarSlot[] = data?.data?.data ?? [];
  const sorted = [...slots].sort(
    (a, b) => new Date(a.date).getTime() - new Date(b.date).getTime()
  );

  const {
    register,
    handleSubmit,
    reset,
    setValue,
    watch,
    formState: { errors },
  } = useForm<SlotFormData>({
    resolver: zodResolver(slotSchema),
    defaultValues: { status: 'available' },
  });

  const statusValue = watch('status');

  const createMutation = useMutation({
    mutationFn: (formData: SlotFormData) => calendarApi.createSlot(formData),
    onSuccess: (_, formData) => {
      // Navigate to the month of the newly created slot
      const slotMonth = formData.date.substring(0, 7);
      setCurrentMonth(slotMonth);
      queryClient.invalidateQueries({ queryKey: ['calendar_slots'] });
      setAddOpen(false);
      reset();
      setApiError(null);
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { error?: { message?: string }; message?: string } } };
      setApiError(
        e?.response?.data?.error?.message ??
        e?.response?.data?.message ??
        'Erreur lors de la création'
      );
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => calendarApi.deleteSlot(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['calendar_slots'] });
      setDeleteId(null);
    },
    onError: () => {
      setApiError('Erreur lors de la suppression');
      setDeleteId(null);
    },
  });

  const onSubmit = (data: SlotFormData) => {
    setApiError(null);
    createMutation.mutate(data);
  };

  const isCurrentMonth = currentMonth === todayYearMonth();

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Calendrier</h1>
          <p className="text-gray-500 text-sm mt-1">
            Gérez vos créneaux de disponibilité
          </p>
        </div>
        <Button
          onClick={() => { setAddOpen(true); setApiError(null); }}
          className="gap-2 text-white"
          style={{ background: 'linear-gradient(135deg, #FF6B35, #C85A20)' }}
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

      {/* Month navigation */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-2">
          <button
            onClick={() => setCurrentMonth((m) => addMonths(m, -1))}
            className="p-1.5 rounded-lg text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-colors"
          >
            <ChevronLeft size={18} />
          </button>
          <span className="text-base font-semibold text-gray-800 capitalize min-w-[160px] text-center">
            {formatMonthLabel(currentMonth)}
          </span>
          <button
            onClick={() => setCurrentMonth((m) => addMonths(m, 1))}
            className="p-1.5 rounded-lg text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-colors"
          >
            <ChevronRight size={18} />
          </button>
        </div>
        {!isCurrentMonth && (
          <button
            onClick={() => setCurrentMonth(todayYearMonth())}
            className="text-xs font-medium px-3 py-1.5 rounded-lg border transition-colors"
            style={{ color: '#FF6B35', borderColor: 'rgba(255,107,53,0.3)', background: 'rgba(255,107,53,0.06)' }}
          >
            Aujourd&apos;hui
          </button>
        )}
      </div>

      {/* Legend */}
      <div className="flex flex-wrap gap-2">
        {Object.entries(statusConfig).map(([key, { label, classes }]) => (
          <span key={key} className={`text-xs px-2.5 py-1 rounded-full font-medium ${classes}`}>
            {label}
          </span>
        ))}
      </div>

      {/* Slots list */}
      {isLoading ? (
        <div className="space-y-3">
          {[...Array(4)].map((_, i) => (
            <Skeleton key={i} className="h-16 w-full rounded-xl" />
          ))}
        </div>
      ) : sorted.length === 0 ? (
        <Card>
          <CardContent className="py-16 text-center">
            <Calendar size={40} className="text-gray-300 mx-auto mb-4" />
            <p className="text-gray-400">
              Aucun créneau pour ce mois. Ajoutez votre première disponibilité.
            </p>
          </CardContent>
        </Card>
      ) : (
        <div className="space-y-2">
          {sorted.map((slot) => {
            const cfg = statusConfig[slot.status] ?? statusConfig.blocked;
            return (
              <Card key={slot.slot_id ?? slot.date} className="hover:shadow-sm transition-shadow">
                <CardContent className="py-3 px-4 flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <Badge className={`text-xs ${cfg.classes}`}>
                      {cfg.label}
                    </Badge>
                    <p className="text-sm font-medium text-gray-800 capitalize">
                      {formatDate(slot.date)}
                    </p>
                  </div>
                  {slot.slot_id !== null && slot.status !== 'confirmed' && (
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => slot.slot_id !== null && setDeleteId(slot.slot_id)}
                      className="text-red-400 hover:text-red-600 hover:bg-red-50 h-8 w-8 p-0"
                    >
                      <Trash2 size={15} />
                    </Button>
                  )}
                </CardContent>
              </Card>
            );
          })}
        </div>
      )}

      {/* Add slot dialog */}
      <Dialog open={addOpen} onOpenChange={(open) => { setAddOpen(open); if (!open) reset(); }}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Ajouter un créneau</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-4 mt-2">
            {apiError && (
              <Alert className="bg-red-50 border-red-200 text-red-800 text-sm">
                {apiError}
              </Alert>
            )}
            <div className="space-y-2">
              <Label htmlFor="date">Date</Label>
              <Input
                id="date"
                type="date"
                {...register('date')}
                className={errors.date ? 'border-red-400' : ''}
                min={new Date().toISOString().split('T')[0]}
              />
              {errors.date && (
                <p className="text-xs text-red-500">{errors.date.message}</p>
              )}
            </div>
            <div className="space-y-2">
              <Label htmlFor="status">Statut</Label>
              <Select
                value={statusValue}
                onValueChange={(val) =>
                  setValue('status', val as 'available' | 'blocked' | 'rest')
                }
              >
                <SelectTrigger id="status">
                  <SelectValue placeholder="Choisir un statut" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="available">Disponible</SelectItem>
                  <SelectItem value="blocked">Bloqué</SelectItem>
                  <SelectItem value="rest">Repos</SelectItem>
                </SelectContent>
              </Select>
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
                className="text-white"
                style={{ background: 'linear-gradient(135deg, #FF6B35, #C85A20)' }}
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
