<?php

namespace App\Filament\Talent\Pages;

use App\Enums\WithdrawalStatus;
use App\Models\TalentProfile;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Notifications\WithdrawalRequestedNotification;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WithdrawalRequestTalentPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Mes reversements';
    protected static ?string $title           = 'Demandes de reversement';
    protected static ?int    $navigationSort  = 20;
    protected static string  $view            = 'filament.talent.pages.withdrawal-request-talent-page';

    public ?array $data = [];

    public ?TalentProfile $profile     = null;
    public bool           $canRequest  = false;
    public Collection     $history;

    public function mount(): void
    {
        /** @var User $user */
        $user          = Auth::user();
        $this->profile = TalentProfile::where('user_id', $user->id)->first();
        $this->history = collect();

        if ($this->profile) {
            $this->canRequest = $this->profile->payout_method_verified_at !== null
                && $this->profile->available_balance > 0
                && ! WithdrawalRequest::where('talent_profile_id', $this->profile->id)
                    ->whereIn('status', [
                        WithdrawalStatus::Pending->value,
                        WithdrawalStatus::Approved->value,
                        WithdrawalStatus::Processing->value,
                    ])->exists();

            $this->history = WithdrawalRequest::where('talent_profile_id', $this->profile->id)
                ->orderByDesc('created_at')
                ->get();
        }

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        $max = $this->profile?->available_balance ?? 0;

        return $form
            ->schema([
                Forms\Components\Section::make('Nouvelle demande de reversement')
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label('Montant à retirer (XOF)')
                            ->numeric()
                            ->minValue(1000)
                            ->maxValue($max)
                            ->required()
                            ->helperText("Solde disponible : " . number_format($max, 0, ',', ' ') . " XOF"),
                    ]),
            ])
            ->statePath('data');
    }

    public function request(): void
    {
        if (! $this->profile || ! $this->canRequest) {
            Notification::make()->title('Impossible d\'effectuer cette demande.')->danger()->send();

            return;
        }

        $data   = $this->form->getState();
        $amount = (int) $data['amount'];

        if ($amount > $this->profile->available_balance) {
            Notification::make()
                ->title('Solde insuffisant')
                ->body('Le montant demandé dépasse votre solde disponible.')
                ->danger()
                ->send();

            return;
        }

        $withdrawalRequest = DB::transaction(function () use ($amount) {
            $this->profile->decrement('available_balance', $amount);

            return WithdrawalRequest::create([
                'talent_profile_id' => $this->profile->id,
                'amount'            => $amount,
                'status'            => WithdrawalStatus::Pending->value,
                'payout_method'     => $this->profile->payout_method,
                'payout_details'    => $this->profile->payout_details,
            ]);
        });

        // Notifier les admins
        $admins = User::role('admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new WithdrawalRequestedNotification($withdrawalRequest));
        }

        // Rafraîchir l'état
        $this->profile->refresh();
        $this->history = WithdrawalRequest::where('talent_profile_id', $this->profile->id)
            ->orderByDesc('created_at')
            ->get();
        $this->canRequest = false;

        $this->form->fill();

        Notification::make()
            ->title('Demande soumise avec succès')
            ->body('L\'administration traitera votre demande prochainement.')
            ->success()
            ->send();
    }

    /** @return array<string, mixed> */
    public function getViewData(): array
    {
        return [
            'profile'          => $this->profile,
            'isVerified'       => $this->profile?->payout_method_verified_at !== null,
            'availableBalance' => $this->profile?->available_balance ?? 0,
            'canRequest'       => $this->canRequest,
            'history'          => $this->history,
        ];
    }
}
