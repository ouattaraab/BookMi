<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Comptes bloqués (brute-force) --}}
        <x-filament::card>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                Comptes bloqués (brute-force)
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                Comptes verrouillés suite à trop de tentatives de connexion incorrectes.
            </p>

            @if(empty($activeLockouts))
            <p class="text-sm text-green-600 dark:text-green-400 font-medium flex items-center gap-2">
                <x-heroicon-o-check-circle class="w-4 h-4" /> Aucun compte bloqué actuellement.
            </p>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Email ciblé</th>
                            <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Utilisateur</th>
                            <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Canal</th>
                            <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">IP</th>
                            <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Tentatives</th>
                            <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Bloqué jusqu'au</th>
                            <th class="py-2 px-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($activeLockouts as $log)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="py-2.5 px-3 font-mono text-xs text-gray-900 dark:text-white">{{ $log->email }}</td>
                            <td class="py-2.5 px-3 text-gray-600 dark:text-gray-400">
                                {{ $log->user ? $log->user->first_name . ' ' . $log->user->last_name : '—' }}
                            </td>
                            <td class="py-2.5 px-3">
                                @php
                                    $badgeColor = match($log->client_type) { 'web' => 'bg-blue-100 text-blue-700', 'mobile' => 'bg-amber-100 text-amber-700', default => 'bg-gray-100 text-gray-600' };
                                    $badgeLabel = match($log->client_type) { 'web' => 'Web', 'mobile' => 'Mobile', default => 'API' };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badgeColor }}">{{ $badgeLabel }}</span>
                            </td>
                            <td class="py-2.5 px-3 font-mono text-xs text-gray-500">{{ $log->ip_address ?? '—' }}</td>
                            <td class="py-2.5 px-3 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">{{ $log->attempts_count }}</span>
                            </td>
                            <td class="py-2.5 px-3 text-gray-500 text-xs">{{ $log->locked_until->format('d/m/Y H:i') }}</td>
                            <td class="py-2.5 px-3">
                                <button
                                    wire:click="unlockAccount({{ $log->id }})"
                                    wire:confirm="Déverrouiller le compte {{ $log->email }} ?"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold bg-green-100 text-green-700 hover:bg-green-200 transition-colors dark:bg-green-900/40 dark:text-green-300">
                                    Déverrouiller
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </x-filament::card>

        {{-- Doublons téléphone --}}
        <x-filament::card>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Doublons de numéro de téléphone</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Numéros utilisés par plusieurs comptes.</p>

            @if(empty($phoneDuplicates))
            <p class="text-sm text-green-600 dark:text-green-400 font-medium flex items-center gap-2">
                <x-heroicon-o-check-circle class="w-4 h-4" /> Aucun doublon détecté.
            </p>
            @else
            <div class="space-y-4">
                @foreach($phoneDuplicates as $group)
                <div class="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-4">
                    <p class="text-sm font-bold text-red-700 dark:text-red-400 mb-3">
                        📞 {{ $group['phone'] }} — {{ count($group['users']) }} comptes
                    </p>
                    <div class="space-y-2">
                        @foreach($group['users'] as $user)
                        <div class="flex items-center justify-between gap-4 bg-white dark:bg-gray-800 rounded-lg p-3">
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                    #{{ $user['id'] }} — {{ $user['first_name'] }} {{ $user['last_name'] }}
                                </p>
                                <p class="text-xs text-gray-500">{{ $user['email'] }} · Créé {{ \Carbon\Carbon::parse($user['created_at'])->format('d/m/Y') }}</p>
                            </div>
                            @if(!$user['is_admin'])
                            <button
                                wire:click="suspendUser({{ $user['id'] }})"
                                wire:confirm="Suspendre ce compte ?"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold bg-red-100 text-red-700 hover:bg-red-200 transition-colors dark:bg-red-900/40 dark:text-red-300">
                                Suspendre
                            </button>
                            @else
                            <span class="text-xs text-gray-400">Admin protégé</span>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </x-filament::card>

        {{-- Comptes suspects --}}
        <x-filament::card>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Profils talents suspects</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                Talents avec 0 réservation, note = 0, inscrits depuis plus de 90 jours.
            </p>

            @if(empty($suspectTalents))
            <p class="text-sm text-green-600 dark:text-green-400 font-medium flex items-center gap-2">
                <x-heroicon-o-check-circle class="w-4 h-4" /> Aucun profil suspect détecté.
            </p>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">#</th>
                            <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Nom de scène</th>
                            <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Email</th>
                            <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Ville</th>
                            <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Vérifié</th>
                            <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Créé le</th>
                            <th class="py-2 px-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($suspectTalents as $talent)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="py-2.5 px-3 text-gray-500">{{ $talent['id'] }}</td>
                            <td class="py-2.5 px-3 font-medium text-gray-900 dark:text-white">{{ $talent['stage_name'] ?? '—' }}</td>
                            <td class="py-2.5 px-3 text-gray-500">{{ $talent['user']['email'] ?? '—' }}</td>
                            <td class="py-2.5 px-3 text-gray-500">{{ $talent['city'] ?? '—' }}</td>
                            <td class="py-2.5 px-3">
                                @if($talent['is_verified'])
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Oui</span>
                                @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Non</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-gray-500">{{ \Carbon\Carbon::parse($talent['created_at'])->format('d/m/Y') }}</td>
                            <td class="py-2.5 px-3">
                                <button
                                    wire:click="suspendUser({{ $talent['user_id'] }})"
                                    wire:confirm="Suspendre ce compte talent ?"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold bg-red-100 text-red-700 hover:bg-red-200 transition-colors dark:bg-red-900/40 dark:text-red-300">
                                    Suspendre
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </x-filament::card>

    </div>
</x-filament-panels::page>
