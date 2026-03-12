<x-filament-panels::page>
    <div class="flex flex-col items-center justify-center min-h-[50vh]">
        <div class="w-full max-w-sm">

            @if($this->setupRequired)
                {{-- Admin without 2FA configured — show setup required notice --}}
                <div class="text-center mb-6">
                    <x-filament::icon
                        icon="heroicon-o-exclamation-triangle"
                        class="mx-auto h-12 w-12 text-warning-500 mb-4"
                    />
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Configuration 2FA requise
                    </h2>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        La double authentification est obligatoire pour les comptes administrateurs.
                        Veuillez configurer la 2FA depuis les paramètres de votre profil avant d'accéder au panneau d'administration.
                    </p>
                </div>
                <div class="mt-4 rounded-lg bg-warning-50 dark:bg-warning-900/20 p-4 border border-warning-200 dark:border-warning-800">
                    <p class="text-sm text-warning-800 dark:text-warning-200">
                        <strong>Comment procéder :</strong><br>
                        1. Connectez-vous à votre profil utilisateur.<br>
                        2. Activez la 2FA (TOTP ou email) dans les paramètres de sécurité.<br>
                        3. Revenez sur cette page pour vous authentifier.
                    </p>
                </div>
            @else
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
            @endif

        </div>
    </div>
</x-filament-panels::page>
