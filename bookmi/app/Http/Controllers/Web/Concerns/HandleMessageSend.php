<?php

namespace App\Http\Controllers\Web\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait HandleMessageSend
{
    /**
     * Detect Ivoirian and international phone numbers in text.
     * Returns true if a phone number is found.
     */
    protected function containsPhoneNumber(string $text): bool
    {
        // Match Ivory Coast numbers (07/05/01/27 + 8 digits) and international formats
        $pattern = '/(?:\+?225[\s\-]?)?(?:0[01257]\s?\d{2}\s?\d{2}\s?\d{2}\s?\d{2})|\+\d{1,3}[\s\-]?\(?\d{1,4}\)?[\s\-]?\d{1,4}[\s\-]?\d{2}[\s\-]?\d{2}[\s\-]?\d{2}/';
        return (bool) preg_match($pattern, preg_replace('/\s+/', ' ', $text));
    }

    /**
     * Upload a media file (image or video) and return ['path' => ..., 'type' => 'image'|'video'].
     */
    protected function uploadMedia(UploadedFile $file, int $conversationId): array
    {
        $mimeType  = $file->getMimeType();
        $mediaType = str_starts_with($mimeType, 'video/') ? 'video' : 'image';
        $extension = $file->getClientOriginalExtension();
        $filename  = uniqid('msg_', true) . '.' . $extension;
        $path      = "messages/{$conversationId}/{$filename}";

        Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));

        return ['path' => $path, 'type' => $mediaType];
    }
}
