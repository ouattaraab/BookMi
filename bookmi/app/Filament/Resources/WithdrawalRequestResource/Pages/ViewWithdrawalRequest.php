<?php

namespace App\Filament\Resources\WithdrawalRequestResource\Pages;

use App\Filament\Resources\WithdrawalRequestResource;
use App\Models\TalentProfile;
use App\Models\WithdrawalRequest;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewWithdrawalRequest extends ViewRecord
{
    protected static string $resource = WithdrawalRequestResource::class;

    /**
     * Inject the talentProfile relationship (with its nested user) so that
     * talentProfile.stage_name and talentProfile.user.email form fields are populated.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var WithdrawalRequest $request */
        $request = $this->record;
        /** @var TalentProfile $talentProfile */
        $talentProfile = $request->talentProfile;

        if ($talentProfile) {
            $talentProfileData = $talentProfile->toArray();

            $user = $talentProfile->user;
            if ($user) {
                $talentProfileData['user'] = $user->toArray();
            }

            $data['talentProfile'] = $talentProfileData;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
