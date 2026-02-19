'use client';

import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { packageApi } from '@/lib/api/endpoints';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
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
  duration_minutes: number;
  price: number;
};

const packageSchema = z.object({
  name: z.string().min(2, 'Nom requis (2 caractères minimum)'),
  description: z.string().optional(),
  duration_minutes: z
    .string()
    .min(1, 'Durée requise')
    .refine((v) => !isNaN(Number(v)) && Number(v) >= 1, 'Durée minimum 1 minute'),
  price: z
    .string()
    .min(1, 'Prix requis')
    .refine((v) => !isNaN(Number(v)) && Number(v) >= 0, 'Prix invalide'),
});

type PackageFormData = z.infer<typeof packageSchema>;

function formatAmount(amount: number): string {
  return new Intl.NumberFormat('fr-FR').format(amount) + ' FCFA';
}

function formatDuration(minutes: number): string {
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

  const packages: ServicePackage[] = data?.data?.data ?? [];

  const {
    register,
    handleSubmit,
    reset,
    setValue,
    formState: { errors },
  } = useForm<PackageFormData>({
    resolver: zodResolver(packageSchema),
    defaultValues: { name: '', description: '', duration_minutes: '60', price: '0' },
  });

  const openEdit = (pkg: ServicePackage) => {
    setEditPkg(pkg);
    setValue('name', pkg.name);
    setValue('description', pkg.description ?? '');
    setValue('duration_minutes', String(pkg.duration_minutes));
    setValue('price', String(pkg.price));
    setApiError(null);
  };

  const openAdd = () => {
    reset({ name: '', description: '', duration_minutes: '60', price: '0' });
    setEditPkg(null);
    setAddOpen(true);
    setApiError(null);
  };

  const createMutation = useMutation({
    mutationFn: (data: PackageFormData) =>
      packageApi.create({
        ...data,
        duration_minutes: Number(data.duration_minutes),
        price: Number(data.price),
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['packages'] });
      setAddOpen(false);
      reset();
      setApiError(null);
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { message?: string } } };
      setApiError(e?.response?.data?.message ?? 'Erreur lors de la création');
    },
  });

  const updateMutation = useMutation({
    mutationFn: (data: PackageFormData) =>
      packageApi.update(editPkg!.id, {
        ...data,
        duration_minutes: Number(data.duration_minutes),
        price: Number(data.price),
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['packages'] });
      setEditPkg(null);
      reset();
      setApiError(null);
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { message?: string } } };
      setApiError(e?.response?.data?.message ?? 'Erreur lors de la mise à jour');
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => packageApi.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['packages'] });
      setDeleteId(null);
    },
    onError: (err: unknown) => {
      const e = err as { response?: { data?: { message?: string } } };
      setApiError(e?.response?.data?.message ?? 'Erreur lors de la suppression');
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
          <h1 className="text-2xl font-bold text-gray-900">
            Packages de services
          </h1>
          <p className="text-gray-500 text-sm mt-1">
            Définissez vos offres et tarifs
          </p>
        </div>
        <Button
          onClick={openAdd}
          className="bg-amber-500 hover:bg-amber-600 text-white gap-2"
        >
          <Plus size={16} />
          Ajouter un package
        </Button>
      </div>

      {apiError && (
        <Alert className="bg-red-50 border-red-200 text-red-800 text-sm">
          {apiError}
        </Alert>
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
                  <TableHead className="font-semibold text-gray-600">Description</TableHead>
                  <TableHead className="font-semibold text-gray-600 text-center">
                    Durée
                  </TableHead>
                  <TableHead className="font-semibold text-gray-600 text-right">
                    Prix
                  </TableHead>
                  <TableHead className="font-semibold text-gray-600 text-right">
                    Actions
                  </TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {packages.map((pkg) => (
                  <TableRow key={pkg.id} className="hover:bg-gray-50">
                    <TableCell className="font-medium text-gray-800">
                      {pkg.name}
                    </TableCell>
                    <TableCell className="text-gray-500 text-sm max-w-[220px] truncate">
                      {pkg.description ?? '—'}
                    </TableCell>
                    <TableCell className="text-center text-gray-600">
                      {formatDuration(pkg.duration_minutes)}
                    </TableCell>
                    <TableCell className="text-right font-semibold text-gray-800">
                      {formatAmount(pkg.price)}
                    </TableCell>
                    <TableCell className="text-right">
                      <div className="flex items-center justify-end gap-2">
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => openEdit(pkg)}
                          className="text-blue-500 hover:text-blue-700 hover:bg-blue-50"
                        >
                          <Pencil size={14} />
                        </Button>
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => setDeleteId(pkg.id)}
                          className="text-red-400 hover:text-red-600 hover:bg-red-50"
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
          if (!open) {
            setAddOpen(false);
            setEditPkg(null);
            reset();
          }
        }}
      >
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>
              {editPkg ? 'Modifier le package' : 'Nouveau package'}
            </DialogTitle>
          </DialogHeader>
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-5 mt-2">
            {apiError && (
              <Alert className="bg-red-50 border-red-200 text-red-800 text-sm">
                {apiError}
              </Alert>
            )}
            <div className="space-y-2">
              <Label htmlFor="name">Nom</Label>
              <Input
                id="name"
                placeholder="Pack Soirée Premium"
                {...register('name')}
                className={errors.name ? 'border-red-400' : ''}
              />
              {errors.name && (
                <p className="text-xs text-red-500">{errors.name.message}</p>
              )}
            </div>
            <div className="space-y-2">
              <Label htmlFor="description">Description</Label>
              <Textarea
                id="description"
                placeholder="Description du package..."
                {...register('description')}
                className="resize-none"
                rows={3}
              />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="duration_minutes">Durée (minutes)</Label>
                <Input
                  id="duration_minutes"
                  type="number"
                  min="1"
                  placeholder="60"
                  {...register('duration_minutes')}
                  className={errors.duration_minutes ? 'border-red-400' : ''}
                />
                {errors.duration_minutes && (
                  <p className="text-xs text-red-500">
                    {errors.duration_minutes.message}
                  </p>
                )}
              </div>
              <div className="space-y-2">
                <Label htmlFor="price">Prix (FCFA)</Label>
                <Input
                  id="price"
                  type="number"
                  min="0"
                  placeholder="50000"
                  {...register('price')}
                  className={errors.price ? 'border-red-400' : ''}
                />
                {errors.price && (
                  <p className="text-xs text-red-500">{errors.price.message}</p>
                )}
              </div>
            </div>
            <DialogFooter>
              <Button
                type="button"
                variant="outline"
                onClick={() => {
                  setAddOpen(false);
                  setEditPkg(null);
                  reset();
                }}
              >
                Annuler
              </Button>
              <Button
                type="submit"
                disabled={isSubmitting}
                className="bg-amber-500 hover:bg-amber-600 text-white"
              >
                {isSubmitting
                  ? 'Enregistrement...'
                  : editPkg
                    ? 'Mettre à jour'
                    : 'Créer'}
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
            Êtes-vous sûr de vouloir supprimer ce package ? Les réservations
            existantes ne seront pas affectées.
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
