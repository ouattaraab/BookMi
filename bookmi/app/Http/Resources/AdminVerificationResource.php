<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\IdentityVerification */
class AdminVerificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'identity_verification',
            'attributes' => [
                'document_type' => $this->document_type,
                'verification_status' => $this->verification_status,
                'rejection_reason' => $this->rejection_reason,
                'submitted_at' => $this->created_at,
                'reviewed_at' => $this->reviewed_at,
                'verified_at' => $this->verified_at,
                'has_document' => $this->stored_path !== null,
                'user' => [
                    'id' => $this->user->id,
                    'first_name' => $this->user->first_name,
                    'last_name' => $this->user->last_name,
                    'email' => $this->user->email,
                ],
                'reviewer' => $this->whenLoaded('reviewer', function () {
                    return $this->reviewer !== null ? [
                        'id' => $this->reviewer->id,
                        'first_name' => $this->reviewer->first_name,
                        'last_name' => $this->reviewer->last_name,
                    ] : null;
                }),
            ],
        ];
    }
}
