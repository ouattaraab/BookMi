<?php

namespace App\Services;

use App\Enums\ConsentType;
use App\Models\BookingRequest;
use App\Models\User;
use App\Models\UserConsent;
use Illuminate\Http\Request;

class ConsentService
{
    /**
     * Record or update consents for a user.
     *
     * @param array<string, bool> $consents  Map of consent_type => bool
     */
    public function recordConsents(User $user, array $consents, ?Request $request = null): void
    {
        $version = config('bookmi.consent.cgu_version', '1.0');
        $ip      = $request?->ip();
        $ua      = $request?->userAgent();
        $device  = $request?->header('X-Device-Id');

        foreach ($consents as $type => $status) {
            if (! ConsentType::tryFrom((string) $type)) {
                continue;
            }

            UserConsent::create([
                'user_id'          => $user->id,
                'consent_type'     => $type,
                'status'           => (bool) $status,
                'ip_address'       => $ip,
                'user_agent'       => $ua,
                'device_id'        => $device,
                'document_version' => $version,
                'consented_at'     => now(),
                'withdrawn_at'     => (bool) $status ? null : now(),
            ]);
        }

        // Mark CGU version as accepted if cgu_privacy is in the set
        if (isset($consents[ConsentType::CguPrivacy->value]) && $consents[ConsentType::CguPrivacy->value]) {
            $user->update(['cgu_version_accepted' => $version]);
        }

        // Sync opt-in columns on users table
        $this->syncOptInColumns($user, $consents);
    }

    /**
     * Withdraw a single opt-in consent.
     */
    public function withdrawConsent(User $user, ConsentType $type, ?Request $request = null): void
    {
        UserConsent::create([
            'user_id'          => $user->id,
            'consent_type'     => $type->value,
            'status'           => false,
            'ip_address'       => $request?->ip(),
            'user_agent'       => $request?->userAgent(),
            'device_id'        => $request?->header('X-Device-Id'),
            'document_version' => config('bookmi.consent.cgu_version', '1.0'),
            'consented_at'     => now(),
            'withdrawn_at'     => now(),
        ]);

        $this->syncOptInColumns($user, [$type->value => false]);
    }

    /**
     * Update multiple opt-in consents (PATCH endpoint).
     *
     * @param array<string, bool> $updates
     */
    public function updateOptIns(User $user, array $updates, ?Request $request = null): void
    {
        $this->recordConsents($user, $updates, $request);
    }

    /**
     * Generate a full user activity export for jurisdictional download.
     *
     * @return array<string, mixed>
     */
    public function exportUserActivity(User $user): array
    {
        $user->load(['consents', 'activityLogs']);

        $bookings = BookingRequest::where('client_id', $user->id)
            ->orWhere(function ($q) use ($user): void {
                $q->whereHas('talentProfile', fn ($tp) => $tp->where('user_id', $user->id));
            })
            ->get();

        return [
            'export_date' => now()->toIso8601String(),
            'user'        => [
                'id'         => $user->id,
                'name'       => trim("{$user->first_name} {$user->last_name}"),
                'email'      => $user->email,
                'phone'      => $user->phone,
                'roles'      => $user->getRoleNames()->toArray(),
                'created_at' => $user->created_at->toIso8601String(),
            ],
            'consents' => $user->consents->map(function (UserConsent $c): array {
                /** @var ConsentType $type */
                $type = $c->consent_type;

                return [
                    'type'             => $type->value,
                    'label'            => $type->label(),
                    'status'           => $c->status,
                    'consented_at'     => $c->consented_at?->toIso8601String(),
                    'withdrawn_at'     => $c->withdrawn_at?->toIso8601String(),
                    'ip_address'       => $c->ip_address,
                    'device_id'        => $c->device_id,
                    'document_version' => $c->document_version,
                ];
            })->toArray(),
            'bookings' => $bookings->map(fn (BookingRequest $b): array => [
                'id'            => $b->id,
                'status'        => $b->status,
                'cachet_amount' => $b->cachet_amount,
                'total_amount'  => $b->total_amount,
                'event_date'    => $b->event_date?->toDateString(),
                'created_at'    => $b->created_at->toIso8601String(),
            ])->toArray(),
            'activity_logs' => $user->activityLogs->map(fn ($log): array => [
                'action'     => $log->action,
                'created_at' => $log->created_at->toIso8601String(),
            ])->toArray(),
        ];
    }

    /**
     * Sync users table opt-in boolean columns from consent map.
     *
     * @param array<string, bool> $consents
     */
    private function syncOptInColumns(User $user, array $consents): void
    {
        $mapping = [
            ConsentType::Marketing->value           => 'marketing_opt_in',
            ConsentType::ImageRights->value         => 'image_rights_opt_in',
            ConsentType::SatisfactionSurveys->value => 'survey_opt_in',
            ConsentType::Geolocation->value         => 'geolocation_opt_in',
        ];

        $updates = [];
        foreach ($mapping as $consentKey => $column) {
            if (array_key_exists($consentKey, $consents)) {
                $updates[$column] = (bool) $consents[$consentKey];
            }
        }

        if (! empty($updates)) {
            $user->update($updates);
        }
    }
}
