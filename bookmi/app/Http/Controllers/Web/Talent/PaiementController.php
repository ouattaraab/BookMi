<?php

namespace App\Http\Controllers\Web\Talent;

use App\Enums\PaymentMethod;
use App\Enums\WithdrawalStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Notifications\PayoutMethodAddedNotification;
use App\Notifications\WithdrawalRequestedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaiementController extends Controller
{
    public function index(): View
    {
        $profile = auth()->user()->talentProfile;

        if (! $profile) {
            return view('talent.coming-soon', [
                'title'       => 'Moyens de paiement',
                'description' => 'Configurez votre profil talent pour gérer vos paiements.',
            ]);
        }

        $isVerified       = (bool) $profile->payout_method_verified_at;
        $availableBalance = $profile->available_balance ?? 0;

        $withdrawals = WithdrawalRequest::where('talent_profile_id', $profile->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        $hasActiveWithdrawal = WithdrawalRequest::where('talent_profile_id', $profile->id)
            ->whereIn('status', [
                WithdrawalStatus::Pending->value,
                WithdrawalStatus::Approved->value,
                WithdrawalStatus::Processing->value,
            ])
            ->exists();

        $paymentMethods = PaymentMethod::cases();

        return view('talent.paiement.index', compact(
            'profile',
            'isVerified',
            'availableBalance',
            'withdrawals',
            'hasActiveWithdrawal',
            'paymentMethods'
        ));
    }

    public function updatePayoutMethod(Request $request): RedirectResponse
    {
        $profile = auth()->user()->talentProfile;

        if (! $profile) {
            return back()->with('error', 'Profil talent introuvable.');
        }

        $validated = $request->validate([
            'payout_method'        => ['required', 'string', Rule::in(array_column(PaymentMethod::cases(), 'value'))],
            'payout_details'       => ['required', 'array'],
            'payout_details.phone' => [
                Rule::requiredIf(fn () => in_array($request->input('payout_method'), [
                    PaymentMethod::OrangeMoney->value,
                    PaymentMethod::Wave->value,
                    PaymentMethod::MtnMomo->value,
                    PaymentMethod::MoovMoney->value,
                ])),
                'nullable',
                'string',
                'regex:/^\+?[0-9]{8,15}$/',
            ],
            'payout_details.account_number' => [
                Rule::requiredIf(fn () => $request->input('payout_method') === PaymentMethod::BankTransfer->value),
                'nullable',
                'string',
                'max:40',
            ],
            'payout_details.bank_code' => [
                Rule::requiredIf(fn () => $request->input('payout_method') === PaymentMethod::BankTransfer->value),
                'nullable',
                'string',
                'max:20',
            ],
        ], [
            'payout_details.phone.regex'               => 'Numéro de téléphone invalide (8 à 15 chiffres).',
            'payout_details.phone.required'            => 'Le numéro de téléphone est obligatoire.',
            'payout_details.account_number.required'   => 'Le numéro de compte est obligatoire.',
            'payout_details.bank_code.required'        => 'Le code banque est obligatoire.',
        ]);

        $isNew = ! $profile->payout_method;

        $profile->update([
            'payout_method'             => $validated['payout_method'],
            'payout_details'            => $validated['payout_details'],
            'payout_method_verified_at' => null,
            'payout_method_verified_by' => null,
        ]);

        $admins = User::role('admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new PayoutMethodAddedNotification($profile));
        }

        $message = $isNew
            ? 'Compte de paiement enregistré. En attente de validation par l\'administration.'
            : 'Compte de paiement mis à jour. Revalidation par l\'administration requise.';

        return back()->with('success', $message);
    }

    public function storeWithdrawal(Request $request): RedirectResponse
    {
        $profile = auth()->user()->talentProfile;

        if (! $profile) {
            return back()->with('error', 'Profil talent introuvable.');
        }

        if (! $profile->payout_method_verified_at) {
            return back()->with('error', 'Votre compte de paiement n\'a pas encore été validé par l\'administration.');
        }

        $hasActiveRequest = WithdrawalRequest::where('talent_profile_id', $profile->id)
            ->whereIn('status', [
                WithdrawalStatus::Pending->value,
                WithdrawalStatus::Approved->value,
                WithdrawalStatus::Processing->value,
            ])
            ->exists();

        if ($hasActiveRequest) {
            return back()->with('error', 'Vous avez déjà une demande de reversement en cours. Attendez son traitement.');
        }

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1000'],
        ], [
            'amount.required' => 'Le montant est obligatoire.',
            'amount.integer'  => 'Le montant doit être un nombre entier.',
            'amount.min'      => 'Le montant minimum est de 1 000 XOF.',
        ]);

        $amount = (int) $validated['amount'];

        if ($amount > ($profile->available_balance ?? 0)) {
            return back()->with('error', sprintf(
                'Le montant demandé dépasse votre solde disponible (%s XOF).',
                number_format($profile->available_balance ?? 0, 0, ',', ' ')
            ));
        }

        $withdrawalRequest = DB::transaction(function () use ($profile, $amount) {
            $profile->decrement('available_balance', $amount);

            return WithdrawalRequest::create([
                'talent_profile_id' => $profile->id,
                'amount'            => $amount,
                'status'            => WithdrawalStatus::Pending->value,
                'payout_method'     => $profile->payout_method,
                'payout_details'    => $profile->payout_details,
            ]);
        });

        $admins = User::role('admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new WithdrawalRequestedNotification($withdrawalRequest));
        }

        return back()->with('success', 'Demande de reversement soumise avec succès.');
    }
}
