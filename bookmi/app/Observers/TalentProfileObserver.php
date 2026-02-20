<?php

namespace App\Observers;

use App\Models\TalentNotificationRequest;
use App\Models\TalentProfile;
use App\Notifications\TalentAvailableNotification;
use Illuminate\Support\Facades\Notification;

class TalentProfileObserver
{
    /**
     * Déclenché à la création d'un profil déjà vérifié.
     */
    public function created(TalentProfile $talent): void
    {
        if ($talent->is_verified) {
            $this->notifyInterestedUsers($talent);
        }
    }

    /**
     * Déclenché quand is_verified passe à true.
     */
    public function updated(TalentProfile $talent): void
    {
        if ($talent->wasChanged('is_verified') && $talent->is_verified) {
            $this->notifyInterestedUsers($talent);
        }
    }

    /**
     * Cherche toutes les demandes de notification en attente qui correspondent
     * au stage_name du talent (matching bidirectionnel, insensible à la casse).
     * Envoie un email aux abonnés et marque les demandes comme notifiées.
     */
    private function notifyInterestedUsers(TalentProfile $talent): void
    {
        $stageName = mb_strtolower($talent->stage_name ?? '');
        if (! $stageName) {
            return;
        }

        TalentNotificationRequest::whereNull('notified_at')
            ->whereNotNull('email')
            ->get()
            ->each(function (TalentNotificationRequest $req) use ($talent, $stageName) {
                $query = mb_strtolower($req->search_query);

                // Correspondance si le nom contient la requête ou la requête contient le nom
                if (str_contains($stageName, $query) || str_contains($query, $stageName)) {
                    Notification::route('mail', $req->email)
                        ->notify(new TalentAvailableNotification($talent, $req));

                    $req->update(['notified_at' => now()]);
                }
            });
    }
}
