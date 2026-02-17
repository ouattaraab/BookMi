<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\ServicePackage
 */
class ServicePackageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'service_package',
            'attributes' => [
                'name' => $this->name,
                'description' => $this->description,
                'cachet_amount' => $this->cachet_amount,
                'duration_minutes' => $this->duration_minutes,
                'inclusions' => $this->inclusions,
                'type' => $this->type,
                'is_active' => $this->is_active,
                'sort_order' => $this->sort_order,
                'created_at' => $this->created_at?->toIso8601String(),
            ],
        ];
    }
}
