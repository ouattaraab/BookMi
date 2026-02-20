<x-filament-panels::page>
    <div class="flex flex-col items-center justify-center min-h-[50vh]">
        <div class="w-full max-w-sm">
            <div class="text-center mb-6">
                <x-filament::icon
                    icon="heroicon-o-shield-check"
                    class="mx-auto h-12 w-12 text-primary-500 mb-4"
                />
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Vérification à deux facteurs
                </h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    @if(auth()->user()?->two_factor_method === 'email')
                        Un code a été envoyé à votre adresse email.
                    @else
                        Entrez le code depuis votre application d'authentification.
                    @endif
                </p>
            </div>

            <x-filament-panels::form wire:submit="verify">
                {{ $this->form }}

                <div class="mt-4">
                    <x-filament::button type="submit" class="w-full">
                        Vérifier
                    </x-filament::button>
                </div>
            </x-filament-panels::form>

            @if(auth()->user()?->two_factor_method === 'email')
                <div class="mt-4 text-center">
                    <button
                        wire:click="sendEmailCode"
                        type="button"
                        class="text-sm text-primary-600 hover:text-primary-500"
                    >
                        Renvoyer le code par email
                    </button>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
