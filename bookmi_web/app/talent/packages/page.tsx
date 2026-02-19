'use client';

import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { packageApi } from '@/lib/api/endpoints';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
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
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Plus, Pencil, Trash2, Package } from 'lucide-react';

type ServicePackage = {
  id: number;
  name: string;
  description?: string;
  duration_minutes: number | null;
  cachet_amount: number;
  type: 'essentiel' | 'standard' | 'premium' | 'micro';
  is_active: boolean;
};

const packageTypes = [
  { value: 'essentiel', label: 'Essentiel' },
  { value: 'standard', label: 'Standard' },
  { value: 'premium', label: 'Premium' },
  { value: 'micro', label: 'Micro' },
] as const;

const typeColors: Record<string, string> = {
  essentiel: 'bg-gray-100 text-gray-700 border border-gray-200',
  standard:  'bg-blue-100 text-blue-700 border border-blue-200',
  premium:   'bg-amber-100 text-amber-700 border border-amber-200',
  micro:     'bg-purple-100 text-purple-700 border border-purple-200',
};

const packageSchema = z.object({
  name: z.string().min(2, 'Nom requis (2 caractères minimum)'),
  description: z.string().optional(),
  type: z.enum(['essentiel', 'standard', 'premium', 'micro']),
  cachet_amount_fcfa: z
    .string()
    .min(1, 'Cachet requis')
    .refine((v) => !isNaN(Number(v)) && Number(v) >= 10, 'Minimum 10 FCFA'),
  duration_minutes: z.string().optional(),
});

type PackageFormData = z.infer<typeof packageSchema>;

function formatAmount(centimes: number): string {
  return new Intl.NumberFormat('fr-FR').format(Math.round(centimes / 100)) + ' FCFA';
}

function formatDuration(minutes: number | null): string {
  if (!minutes) return '—';
  if (minutes < 60) return `${minutes} min`;
  const h = Math.floor(minutes / 60);
  const m = minutes % 60;
  return m > 0 ? `${h}h${String(m).padStart(2, '0')}` : `${h}h`;
}

export default function TalentPackagesPage() {
  const queryClient = useQueryClient();

  const [addOpen, setAddOpen] = useState(false);
  const [editPkg, setEditPkg] = useState<ServicePackage | null>(null);
  const [deleteId, setDeleteId] = useState<number | null>(null);
  const [apiError, setApiError] = useState<string | null>(null);

  const { data, isLoading } = useQuery({
    queryKey: ['packages'],
    queryFn: () => packageApi.list(),
  });

  const rawPackages: { id: number; attributes: Omit<ServicePackage, 'id'> }[] =
    data?.data?.data ?? [];
  const packages: ServicePackage[] = rawPackages.map((item) => ({
    id: item.id,
    ...item.attributes,
  }));

  const {
    register,
    handleSubmit,
    reset,
    setValue,
    watch,
    formState: { errors },
  } = useForm<PackageFormData>({
    resolver: zodResolver(packageSchema),
    defaultValues: { name: '', description: '', type: 'standard', cachet_amount_fcfa: '', duration_minutes: '60' },
  });

  const typeValue = watch('type');

  const openEdit = (pkg: ServicePackage) => {
    setEditPkg(pkg);
    setValue('name', pkg.name);
    setValue('description', pkg.description ?? '');
    setValue('type', pkg.type);
    setValue('cachet_amount_fcfa', String(Math.round(pkg.cachet_amount / 100)));
    setValue('duration_minutes', pkg.duration_minutes ? String(pkg.duration_minutes) : '');
    setApiError(null);
  };

  const openAdd = () => {
    reset({ name: '', description: '', type: 'standard', cachet_amount_fcfa: '', duration_minutes: '60' });
    setEditPkg(null);
    setAddOpen(true);
    setApiError(null);
  };

  function buildPayload(data: PackageFormData) {
    const payload: Record<string, unknown> = {
      name: data.name,
      description: data.description || undefined,
      type: data.type,
      cachet_amount: Math.round(Number(data.cachet_amount_fcfa) * 100),
    };
    if (data.type !== 'micro' && data.duration_minutes) {
      payload.duration_minutes = Number(data.duration_minutes);
    }
    return payload;
  }

  const createMutation = useMutation({
    mutationFn: (data: PackageFormData) => packageApi.create(buildPayload(data)),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['packages'] });
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

  const updateMutation = useMutation({
    mutationFn: (data: PackageFormData) =>
      packageApi.update(editPkg!.id, buildPayload(data)),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['packages'] });
      setEditPkg(null);
      reset();
      setApiError(null);
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { error?: { message?: string }; message?: string } } };
      setApiError(
        e?.response?.data?.error?.message ??
        e?.response?.data?.message ??
        'Erreur lors de la mise à jour'
      );
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => packageApi.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['packages'] });
      setDeleteId(null);
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { error?: { message?: string }; message?: string } } };
      setApiError(
        e?.response?.data?.error?.message ??
        e?.response?.data?.message ??
        'Erreur lors de la suppression'
      );
      setDeleteId(null);
    },
  });

  const onSubmit = (data: PackageFormData) => {
    setApiError(null);
    if (editPkg) {
      updateMutation.mutate(data);
    } else {
      createMutation.mutate(data);
    }
  };

  const isSubmitting = createMutation.isPending || updateMutation.isPending;
  const isDialogOpen = addOpen || editPkg !== null;

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Packages de services</h1>
          <p className="text-gray-500 text-sm mt-1">Définissez vos offres et tarifs</p>
        </div>
        <Button
          onClick={openAdd}
          className="gap-2 text-white"
          style={{ background: 'linear-gradient(135deg, #FF6B35, #C85A20)' }}
        >
          <Plus size={16} />
          Ajouter un package
        </Button>
      </div>

      {apiError && (
        <Alert className="bg-red-50 border-red-200 text-red-800 text-sm">{apiError}</Alert>
      )}

      <Card>
        <CardContent className="p-0">
          {isLoading ? (
            <div className="p-6 space-y-3">
              {[...Array(4)].map((_, i) => (
                <Skeleton key={i} className="h-14 w-full" />
              ))}
            </div>
          ) : packages.length === 0 ? (
            <div className="py-16 text-center">
              <Package size={40} className="text-gray-300 mx-auto mb-4" />
              <p className="text-gray-400 text-sm">
                Aucun package créé. Ajoutez votre premier package.
              </p>
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow className="bg-gray-50">
                  <TableHead className="font-semibold text-gray-600">Nom</TableHead>
                  <TableHead className="font-semibold text-gray-600">Type</TableHead>
                  <TableHead className="font-semibold text-gray-600 text-center">Durée</TableHead>
                  <TableHead className="font-semibold text-gray-600 text-right">Cachet</TableHead>
                  <TableHead className="font-semibold text-gray-600 text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {packages.map((pkg) => (
                  <TableRow key={pkg.id} className="hover:bg-gray-50">
                    <TableCell className="font-medium text-gray-800">
                      <div>
                        <p>{pkg.name}</p>
                        {pkg.description && (
                          <p className="text-xs text-gray-400 truncate max-w-[200px]">
                            {pkg.description}
                          </p>
                        )}
                      </div>
                    </TableCell>
                    <TableCell>
                      <Badge className={`text-xs ${typeColors[pkg.type] ?? typeColors.essentiel}`}>
                        {packageTypes.find((t) => t.value === pkg.type)?.label ?? pkg.type}
                      </Badge>
                    </TableCell>
                    <TableCell className="text-center text-gray-600">
                      {formatDuration(pkg.duration_minutes)}
                    </TableCell>
                    <TableCell className="text-right font-semibold text-gray-800">
                      {formatAmount(pkg.cachet_amount)}
                    </TableCell>
                    <TableCell className="text-right">
                      <div className="flex items-center justify-end gap-2">
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => openEdit(pkg)}
                          className="text-blue-500 hover:text-blue-700 hover:bg-blue-50 h-8 w-8 p-0"
                        >
                          <Pencil size={14} />
                        </Button>
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => setDeleteId(pkg.id)}
                          className="text-red-400 hover:text-red-600 hover:bg-red-50 h-8 w-8 p-0"
                        >
                          <Trash2 size={14} />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      {/* Add / Edit dialog */}
      <Dialog
        open={isDialogOpen}
        onOpenChange={(open) => {
          if (!open) { setAddOpen(false); setEditPkg(null); reset(); }
        }}
      >
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>{editPkg ? 'Modifier le package' : 'Nouveau package'}</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-4 mt-2">
            {apiError && (
              <Alert className="bg-red-50 border-red-200 text-red-800 text-sm">{apiError}</Alert>
            )}
            <div className="space-y-2">
              <Label htmlFor="name">Nom</Label>
              <Input
                id="name"
                placeholder="Pack Soirée Premium"
                {...register('name')}
                className={errors.name ? 'border-red-400' : ''}
              />
              {errors.name && <p className="text-xs text-red-500">{errors.name.message}</p>}
            </div>

            <div className="space-y-2">
              <Label htmlFor="type">Type de package</Label>
              <Select
                value={typeValue}
                onValueChange={(val) => setValue('type', val as PackageFormData['type'])}
              >
                <SelectTrigger id="type">
                  <SelectValue placeholder="Choisir un type" />
                </SelectTrigger>
                <SelectContent>
                  {packageTypes.map((t) => (
                    <SelectItem key={t.value} value={t.value}>{t.label}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="description">Description</Label>
              <Textarea
                id="description"
                placeholder="Description du package..."
                {...register('description')}
                className="resize-none"
                rows={2}
              />
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="cachet_amount_fcfa">Cachet (FCFA)</Label>
                <Input
                  id="cachet_amount_fcfa"
                  type="number"
                  min="10"
                  placeholder="150000"
                  {...register('cachet_amount_fcfa')}
                  className={errors.cachet_amount_fcfa ? 'border-red-400' : ''}
                />
                {errors.cachet_amount_fcfa && (
                  <p className="text-xs text-red-500">{errors.cachet_amount_fcfa.message}</p>
                )}
              </div>
              {typeValue !== 'micro' && (
                <div className="space-y-2">
                  <Label htmlFor="duration_minutes">Durée (min)</Label>
                  <Input
                    id="duration_minutes"
                    type="number"
                    min="1"
                    placeholder="60"
                    {...register('duration_minutes')}
                  />
                </div>
              )}
            </div>

            <DialogFooter>
              <Button
                type="button"
                variant="outline"
                onClick={() => { setAddOpen(false); setEditPkg(null); reset(); }}
              >
                Annuler
              </Button>
              <Button
                type="submit"
                disabled={isSubmitting}
                className="text-white"
                style={{ background: 'linear-gradient(135deg, #FF6B35, #C85A20)' }}
              >
                {isSubmitting ? 'Enregistrement...' : editPkg ? 'Mettre à jour' : 'Créer'}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* Delete confirmation */}
      <Dialog open={deleteId !== null} onOpenChange={() => setDeleteId(null)}>
        <DialogContent className="sm:max-w-sm">
          <DialogHeader>
            <DialogTitle>Supprimer le package</DialogTitle>
          </DialogHeader>
          <p className="text-sm text-gray-600 mt-2">
            Êtes-vous sûr de vouloir supprimer ce package ? Les réservations existantes ne seront
            pas affectées.
          </p>
          <DialogFooter className="mt-4">
            <Button variant="outline" onClick={() => setDeleteId(null)}>Annuler</Button>
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
