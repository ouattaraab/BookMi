<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreVerificationRequest extends FormRequest
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
        return [
            'document' => ['required', 'file', 'mimes:jpeg,jpg,png,pdf', 'max:5120'],
            'document_type' => ['required', 'string', 'in:cni,passport'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document.required' => 'Le document est obligatoire.',
            'document.file' => 'Le document doit être un fichier.',
            'document.mimes' => 'Le document doit être au format JPEG, PNG ou PDF.',
            'document.max' => 'Le document ne doit pas dépasser 5 Mo.',
            'document_type.required' => 'Le type de document est obligatoire.',
            'document_type.in' => 'Le type de document doit être cni ou passport.',
        ];
    }
}
