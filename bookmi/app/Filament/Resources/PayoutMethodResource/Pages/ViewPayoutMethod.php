<?php

namespace App\Filament\Resources\PayoutMethodResource\Pages;

use App\Filament\Resources\PayoutMethodResource;
use App\Models\TalentProfile;
use Filament\Resources\Pages\ViewRecord;

class ViewPayoutMethod extends ViewRecord
{
    protected static string $resource = PayoutMethodResource::class;

    /**
     * Inject the eagerly-loaded user relationship into the form data so that
     * dot-notation fields (user.first_name, user.last_name, user.email) resolve
     * correctly. Without this, those fields are empty because Filament's ViewRecord
     * fills the form from the record's own attributes only.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var TalentProfile $profile */
        $profile = $this->record;
        $user = $profile->user;

        if ($user) {
            $data['user'] = $user->toArray();
        }

        return $data;
    }
}
