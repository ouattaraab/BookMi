'use client';

import { useQuery } from '@tanstack/react-query';
import Link from 'next/link';
import { managerApi } from '@/lib/api/endpoints';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Alert } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { ChevronRight, Star, BookCheck, Users } from 'lucide-react';

type TalentCard = {
  id: number;
  stage_name: string;
  talent_level: string;
  is_verified: boolean;
  average_rating?: number;
  total_bookings?: number;
  user?: {
    first_name: string;
    last_name: string;
    email: string;
  };
};

const LEVEL_COLORS: Record<string, string> = {
  beginner: 'bg-gray-100 text-gray-600',
  intermediate: 'bg-blue-100 text-blue-700',
  professional: 'bg-purple-100 text-purple-700',
  star: 'bg-amber-100 text-amber-700',
};

const LEVEL_LABELS: Record<string, string> = {
  beginner: 'Débutant',
  intermediate: 'Intermédiaire',
  professional: 'Professionnel',
  star: 'Star',
};

function getInitials(stageName: string): string {
  return stageName
    .split(' ')
    .slice(0, 2)
    .map((w) => w[0])
    .join('')
    .toUpperCase();
}

export default function ManagerTalentsPage() {
  const { data, isLoading, isError } = useQuery({
    queryKey: ['manager_talents'],
    queryFn: () => managerApi.getMyTalents(),
  });

  const talents: TalentCard[] = data?.data?.data ?? [];

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Mes talents</h1>
        <p className="text-gray-500 text-sm mt-1">
          Gérez et suivez les performances de vos talents
        </p>
      </div>

      {isError && (
        <Alert className="bg-red-50 border-red-200 text-red-800 text-sm">
          Impossible de charger la liste des talents.
        </Alert>
      )}

      {isLoading ? (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {[...Array(6)].map((_, i) => (
            <Skeleton key={i} className="h-48 w-full rounded-xl" />
          ))}
        </div>
      ) : talents.length === 0 ? (
        <Card>
          <CardContent className="py-16 text-center">
            <Users size={40} className="text-gray-300 mx-auto mb-4" />
            <p className="text-gray-400">
              Aucun talent associé à votre compte manager.
            </p>
          </CardContent>
        </Card>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {talents.map((talent) => (
            <Card
              key={talent.id}
              className="hover:shadow-md transition-shadow border border-gray-200"
            >
              <CardContent className="p-6 space-y-4">
                <div className="flex items-start gap-4">
                  <Avatar className="h-12 w-12">
                    <AvatarFallback className="bg-[#2196F3]/10 text-[#2196F3] font-bold">
                      {getInitials(talent.stage_name)}
                    </AvatarFallback>
                  </Avatar>
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 flex-wrap">
                      <h3 className="font-semibold text-gray-900 truncate">
                        {talent.stage_name}
                      </h3>
                      {talent.is_verified && (
                        <span className="text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded-full font-medium">
                          Vérifié
                        </span>
                      )}
                    </div>
                    <p className="text-xs text-gray-500 mt-0.5">
                      {talent.user?.email ?? ''}
                    </p>
                    <Badge
                      className={`mt-2 text-xs ${LEVEL_COLORS[talent.talent_level] ?? 'bg-gray-100 text-gray-600'}`}
                    >
                      {LEVEL_LABELS[talent.talent_level] ?? talent.talent_level}
                    </Badge>
                  </div>
                </div>

                <div className="grid grid-cols-2 gap-3 pt-2 border-t border-gray-100">
                  <div className="flex items-center gap-1.5">
                    <Star size={14} className="text-amber-400" />
                    <span className="text-sm font-medium text-gray-700">
                      {talent.average_rating
                        ? talent.average_rating.toFixed(1)
                        : '—'}
                    </span>
                    <span className="text-xs text-gray-400">/ 5</span>
                  </div>
                  <div className="flex items-center gap-1.5">
                    <BookCheck size={14} className="text-blue-400" />
                    <span className="text-sm font-medium text-gray-700">
                      {talent.total_bookings ?? 0}
                    </span>
                    <span className="text-xs text-gray-400">réserv.</span>
                  </div>
                </div>

                <Link href={`/manager/talents/${talent.id}`}>
                  <Button
                    variant="outline"
                    size="sm"
                    className="w-full gap-1 text-[#2196F3] border-[#2196F3]/30 hover:bg-[#2196F3]/5 hover:border-[#2196F3]/50"
                  >
                    Voir le profil
                    <ChevronRight size={14} />
                  </Button>
                </Link>
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </div>
  );
}
