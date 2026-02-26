<?php

namespace App\Filament\Talent\Pages;

use App\Enums\PaymentMethod;
use App\Models\TalentProfile;
use App\Models\User;
use App\Services\AdminNotificationService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class PayoutMethodPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Mon compte de paiement';

    protected static ?string $title = 'Compte de paiement';

    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.talent.pages.payout-method-page';

    public ?array $data = [];

    public ?TalentProfile $profile = null;

    /** Controls whether the form is visible (true) or the summary card (false). */
    public bool $showingForm = false;

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $this->profile = TalentProfile::where('user_id', $user->id)->first();

        // Show form unless the account is currently verified
        $this->showingForm = ! $this->profile?->payout_method
            || $this->profile?->payout_method_status !== 'verified';

        $this->form->fill([
            'payout_method'          => $this->profile?->payout_method,
            'payout_details_phone'   => data_get($this->profile?->payout_details, 'phone'),
            'payout_details_account' => data_get($this->profile?->payout_details, 'account_number'),
            'payout_details_bank_code' => data_get($this->profile?->payout_details, 'bank_code'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Méthode de paiement')
                    ->description('Choisissez comment vous souhaitez recevoir vos reversements.')
                    ->schema([
                        Forms\Components\Select::make('payout_method')
                            ->label('Méthode')
                            ->options([
                                PaymentMethod::OrangeMoney->value => 'Orange Money',
                                PaymentMethod::Wave->value => 'Wave',
                                PaymentMethod::MtnMomo->value => 'MTN Mobile Money',
                                PaymentMethod::MoovMoney->value => 'Moov Money',
                                PaymentMethod::BankTransfer->value => 'Virement bancaire',
                            ])
                            ->required()
                            ->reactive(),

                        Forms\Components\TextInput::make('payout_details_phone')
                            ->label('Numéro de téléphone')
                            ->placeholder('+225 07 XX XX XX XX')
                            ->tel()
                            ->required(fn ($get) => in_array($get('payout_method'), [
                                PaymentMethod::OrangeMoney->value,
                                PaymentMethod::Wave->value,
                                PaymentMethod::MtnMomo->value,
                                PaymentMethod::MoovMoney->value,
                            ]))
                            ->visible(fn ($get) => in_array($get('payout_method'), [
                                PaymentMethod::OrangeMoney->value,
                                PaymentMethod::Wave->value,
                                PaymentMethod::MtnMomo->value,
                                PaymentMethod::MoovMoney->value,
                            ])),

                        Forms\Components\TextInput::make('payout_details_account')
                            ->label('Numéro de compte bancaire (IBAN/RIB)')
                            ->required(fn ($get) => $get('payout_method') === PaymentMethod::BankTransfer->value)
                            ->visible(fn ($get) => $get('payout_method') === PaymentMethod::BankTransfer->value),

                        Forms\Components\TextInput::make('payout_details_bank_code')
                            ->label('Code banque / SWIFT')
                            ->required(fn ($get) => $get('payout_method') === PaymentMethod::BankTransfer->value)
                            ->visible(fn ($get) => $get('payout_method') === PaymentMethod::BankTransfer->value),
                    ]),
            ])
            ->statePath('data');
    }

    public function showForm(): void
    {
        // Clear form for fresh account entry
        $this->form->fill([
            'payout_method'            => null,
            'payout_details_phone'     => null,
            'payout_details_account'   => null,
            'payout_details_bank_code' => null,
        ]);
        $this->showingForm = true;
    }

    public function hideForm(): void
    {
        $this->showingForm = false;
    }

    public function deletePayoutMethod(): void
    {
        if (! $this->profile) {
            Notification::make()->title('Aucun profil talent trouvé.')->danger()->send();

            return;
        }

        $this->profile->update([
            'payout_method'                  => null,
            'payout_details'                 => null,
            'payout_method_verified_at'      => null,
            'payout_method_verified_by'      => null,
            'payout_method_status'           => null,
            'payout_method_rejection_reason' => null,
        ]);

        $this->profile->refresh();
        $this->showingForm = false;
        $this->form->fill();

        Notification::make()
            ->title('Compte de paiement supprimé')
            ->success()
            ->send();
    }

    public function save(): void
    {
        if (! $this->profile) {
            Notification::make()->title('Aucun profil talent trouvé.')->danger()->send();

            return;
        }

        $data = $this->form->getState();
        $method = $data['payout_method'];

        $details = [];
        if (in_array($method, [
            PaymentMethod::OrangeMoney->value,
            PaymentMethod::Wave->value,
            PaymentMethod::MtnMomo->value,
            PaymentMethod::MoovMoney->value,
        ])) {
            $details['phone'] = $data['payout_details_phone'];
        } else {
            $details['account_number'] = $data['payout_details_account'] ?? '';
            $details['bank_code'] = $data['payout_details_bank_code'] ?? '';
        }

        $this->profile->update([
            'payout_method'                  => $method,
            'payout_details'                 => $details,
            'payout_method_verified_at'      => null,
            'payout_method_verified_by'      => null,
            'payout_method_status'           => 'pending',
            'payout_method_rejection_reason' => null,
        ]);

        $this->profile->refresh();
        $this->showingForm = false;

        // Notifier les admins (email + push in-app)
        AdminNotificationService::payoutMethodAdded($this->profile);

        Notification::make()
            ->title('Compte enregistré — en attente de validation')
            ->body('L\'administration va valider votre compte sous peu.')
            ->success()
            ->send();
    }

    /** @return array<string, mixed> */
    public function getViewData(): array
    {
        $this->profile?->refresh();

        return [
            'profile'            => $this->profile,
            'isVerified'         => $this->profile?->payout_method_status === 'verified',
            'availableBalance'   => $this->profile?->available_balance ?? 0,
            'verifiedAt'         => $this->profile?->payout_method_verified_at,
            'payoutMethodStatus' => $this->profile?->payout_method_status,
            'rejectionReason'    => $this->profile?->payout_method_rejection_reason,
        ];
    }
}
