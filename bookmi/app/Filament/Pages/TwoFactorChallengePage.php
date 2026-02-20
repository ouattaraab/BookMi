<?php

namespace App\Filament\Pages;

use App\Services\TwoFactorService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class TwoFactorChallengePage extends Page
{
    protected static ?string $navigationIcon = null;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.two-factor-challenge';

    public string $code = '';

    public function getTitle(): string | Htmlable
    {
        return 'Vérification à deux facteurs';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('code')
                    ->label('Code de vérification')
                    ->placeholder('000000')
                    ->maxLength(6)
                    ->required()
                    ->numeric()
                    ->autofocus(),
            ])
            ->statePath('data');
    }

    protected ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function verify(TwoFactorService $twoFactorService): void
    {
        $data = $this->form->getState();
        $code = $data['code'] ?? '';
        $user = auth()->user();

        $valid = false;

        if ($user->two_factor_method === 'totp') {
            $valid = $twoFactorService->verifyTotp($user->two_factor_secret, $code);
        } elseif ($user->two_factor_method === 'email') {
            $valid = $twoFactorService->verifyEmailOtp($user, $code);
        }

        if (! $valid) {
            Notification::make()
                ->title('Code invalide')
                ->body('Le code saisi est incorrect ou expiré.')
                ->danger()
                ->send();
            return;
        }

        session(['2fa_passed' => true]);

        $this->redirect(filament()->getUrl());
    }

    public function sendEmailCode(TwoFactorService $twoFactorService): void
    {
        $user = auth()->user();

        if ($user->two_factor_method === 'email') {
            $twoFactorService->sendEmailOtp($user);

            Notification::make()
                ->title('Code envoyé')
                ->body('Un nouveau code a été envoyé à votre adresse email.')
                ->success()
                ->send();
        }
    }
}
