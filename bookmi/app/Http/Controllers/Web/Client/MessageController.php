<?php

namespace App\Http\Controllers\Web\Client;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Concerns\HandleMessageSend;
use App\Enums\BookingStatus;
use App\Models\BookingRequest;
use App\Models\Conversation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    use HandleMessageSend;
    public function index(): View
    {
        $conversations = Conversation::where('client_id', auth()->id())
            ->with(['talentProfile.user', 'talentProfile.category', 'latestMessage'])
            ->orderByDesc('last_message_at')
            ->paginate(20);
        return view('client.messages.index', compact('conversations'));
    }

    public function show(int $id): View
    {
        $conversation = Conversation::where('client_id', auth()->id())
            ->with(['talentProfile.user', 'messages.sender'])
            ->findOrFail($id);

        $conversation->messages()
            ->where('sender_id', '!=', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('client.messages.show', compact('conversation'));
    }

    public function startFromBooking(int $bookingId): RedirectResponse
    {
        $booking = BookingRequest::where('client_id', auth()->id())
            ->whereIn('status', [
                BookingStatus::Paid->value,
                BookingStatus::Confirmed->value,
                BookingStatus::Completed->value,
            ])
            ->findOrFail($bookingId);

        $conversation = Conversation::firstOrCreate(
            [
                'client_id'         => auth()->id(),
                'talent_profile_id' => $booking->talent_profile_id,
            ],
            [
                'booking_request_id' => $booking->id,
                'last_message_at'    => now(),
            ]
        );

        return redirect()->route('client.messages.show', $conversation->id);
    }

    public function send(int $id, Request $request): RedirectResponse
    {
        $request->validate([
            'content' => 'nullable|string|max:2000',
            'media'   => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,mp4,mov,avi,webm|max:51200',
        ]);

        $conversation = Conversation::where('client_id', auth()->id())->findOrFail($id);

        // Block phone number exchanges
        if ($request->content && $this->containsPhoneNumber($request->content)) {
            return back()->withErrors(['content' => 'Pour protéger votre sécurité, le partage de numéros de téléphone n\'est pas autorisé via la messagerie. Utilisez la plateforme pour vos échanges.'])->withInput();
        }

        $messageData = [
            'sender_id' => auth()->id(),
            'content'   => $request->content ?? '',
            'type'      => 'text',
        ];

        if ($request->hasFile('media')) {
            $media = $this->uploadMedia($request->file('media'), $id);
            $messageData['media_path'] = $media['path'];
            $messageData['media_type'] = $media['type'];
            $messageData['type']       = $media['type'] === 'video' ? 'video' : 'image';
        }

        if (empty($messageData['content']) && empty($messageData['media_path'] ?? null)) {
            return back()->withErrors(['content' => 'Le message ne peut pas être vide.']);
        }

        $conversation->messages()->create($messageData);
        $conversation->touch('last_message_at');
        return back();
    }
}
