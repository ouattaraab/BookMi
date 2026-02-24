<x-filament-panels::page>
    {{-- Formulaire d'envoi --}}
    <x-filament::section heading="Envoyer un message" icon="heroicon-o-megaphone">
        <x-filament-panels::form wire:submit="send">
            {{ $this->form }}
            <x-filament-panels::form.actions :actions="$this->getFormActions()" />
        </x-filament-panels::form>
    </x-filament::section>

    {{-- Historique --}}
    <x-filament::section heading="Historique des envois" icon="heroicon-o-clock">
        @if($this->recentMessages->isEmpty())
            <p class="text-sm text-gray-400 dark:text-gray-500">Aucun message envoyé pour l'instant.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs uppercase text-gray-500 border-b">
                        <tr>
                            <th class="py-2 pr-4">Date</th>
                            <th class="py-2 pr-4">Canal</th>
                            <th class="py-2 pr-4">Cible</th>
                            <th class="py-2 pr-4">Titre</th>
                            <th class="py-2 pr-4">Destinataires</th>
                            <th class="py-2">Admin</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($this->recentMessages as $msg)
                        <tr class="border-b border-gray-100 dark:border-gray-700">
                            <td class="py-2 pr-4 text-gray-500">{{ $msg->created_at->format('d/m H:i') }}</td>
                            <td class="py-2 pr-4">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                    {{ $msg->type === 'push' ? 'bg-blue-100 text-blue-700' :
                                       ($msg->type === 'email' ? 'bg-green-100 text-green-700' : 'bg-purple-100 text-purple-700') }}">
                                    {{ ['push' => 'Push', 'email' => 'Email', 'both' => 'Push+Email'][$msg->type] }}
                                </span>
                            </td>
                            <td class="py-2 pr-4">
                                {{ $msg->target_type === 'user'
                                    ? ($msg->targetUser?->first_name . ' ' . $msg->targetUser?->last_name)
                                    : ['all' => 'Tous', 'clients' => 'Clients', 'talents' => 'Talents'][$msg->target_type] }}
                            </td>
                            <td class="py-2 pr-4 font-medium">{{ Str::limit($msg->title, 40) }}</td>
                            <td class="py-2 pr-4 text-gray-500">{{ $msg->recipients_count }}</td>
                            <td class="py-2 text-gray-500">{{ $msg->admin?->first_name }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
