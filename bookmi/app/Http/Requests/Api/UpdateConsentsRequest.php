<?php

namespace App\Http\Requests\Api;

use App\Enums\ConsentType;
use Illuminate\Foundation\Http\FormRequest;

class UpdateConsentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $optInValues = array_map(fn (ConsentType $t): string => $t->value, ConsentType::optIn());

        return [
            'consents'   => [
                'required',
                'array',
                'min:1',
                function (string $attribute, mixed $value, \Closure $fail) use ($optInValues): void {
                    if (! is_array($value)) {
                        return;
                    }
                    foreach (array_keys($value) as $key) {
                        if (! in_array($key, $optInValues, true)) {
                            $fail("Le consentement « {$key} » n'est pas modifiable.");
                        }
                    }
                },
            ],
            'consents.*' => ['boolean'],
        ];
    }
}
