<?php

namespace App\Filament\Resources\TalentProfileResource\Pages;

use App\Filament\Resources\TalentProfileResource;
use App\Models\User;
use Filament\Resources\Pages\ViewRecord;

class ViewTalentProfile extends ViewRecord
{
    protected static string $resource = TalentProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * Flatten the eagerly-loaded talentProfile relationship into the form data
     * so that dot-notation fields (e.g. talentProfile.stage_name) resolve correctly.
     *
     * Without this, $record->toArray() exposes the relation as 'talent_profile'
     * (snake_case) while the form fields expect 'talentProfile' (camelCase).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var User $user */
        $user = $this->record;
        $profile = $user->talentProfile;

        if ($profile) {
            $data['talentProfile'] = $profile->toArray();
        }

        return $data;
    }
}
