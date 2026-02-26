<?php

namespace App\Services;

use App\Jobs\SendPushNotification;
use App\Models\TalentProfile;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Notifications\PayoutMethodAddedNotification;
use App\Notifications\WithdrawalRequestedNotification;

/**
 * Centralise les notifications envoyées aux administrateurs.
 * Chaque méthode envoie à la fois un e-mail et une notification push
 * in-app (cloche + FCM si l'admin a un token enregistré).
 */
class AdminNotificationService
{
    /**
     * Notifie tous les admins qu'un talent a soumis ou modifié son
     * compte de paiement en attente de validation.
     */
    public static function payoutMethodAdded(TalentProfile $profile): void
    {
        $user = $profile->user;
        $talentName = $profile->stage_name
            ?? trim((($user->first_name ?? '').' '.($user->last_name ?? '')))
            ?: 'Talent';

        $method = $profile->payout_method ?? '—';
        $details = data_get(
            $profile->payout_details,
            'phone',
            data_get($profile->payout_details, 'account_number', '—')
        );

        try {
            $admins = User::role('admin')->get();
        } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist) {
            return;
        }

        foreach ($admins as $admin) {
            // E-mail
            $admin->notify(new PayoutMethodAddedNotification($profile));

            // Push in-app (cloche + FCM)
            SendPushNotification::dispatch(
                $admin->id,
                'Compte bancaire à valider',
                "{$talentName} a renseigné ses coordonnées ({$method} : {$details})",
                [
                    'type' => 'payout_method_added',
                    'talent_profile_id' => $profile->id,
                    'url' => '/admin/payout-methods',
                ],
            );
        }
    }

    /**
     * Notifie tous les admins qu'un talent a soumis une demande de
     * reversement nécessitant un traitement rapide.
     */
    public static function withdrawalRequested(WithdrawalRequest $request): void
    {
        /** @var TalentProfile $profile */
        $profile = $request->talentProfile;
        $profileUser = $profile->user;
        $talentName = $profile->stage_name
            ?? trim(($profileUser->first_name.' '.$profileUser->last_name))
            ?: 'Talent';

        $amount = number_format($request->amount, 0, ',', ' ');

        try {
            $admins = User::role('admin')->get();
        } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist) {
            return;
        }

        foreach ($admins as $admin) {
            // E-mail
            $admin->notify(new WithdrawalRequestedNotification($request));

            // Push in-app (cloche + FCM)
            SendPushNotification::dispatch(
                $admin->id,
                'Nouvelle demande de reversement',
                "{$talentName} demande {$amount} XOF",
                [
                    'type' => 'withdrawal_requested',
                    'withdrawal_id' => $request->id,
                    'url' => '/admin/withdrawal-requests/'.$request->id,
                ],
            );
        }
    }
}
