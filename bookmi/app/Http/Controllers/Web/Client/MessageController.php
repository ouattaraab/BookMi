<?php

namespace App\Http\Controllers\Web\Client;

use App\Http\Controllers\Controller;
use App\Enums\BookingStatus;
use App\Models\BookingRequest;
use App\Models\Conversation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
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
        $request->validate(['content' => 'required|string|max:2000']);
        $conversation = Conversation::where('client_id', auth()->id())->findOrFail($id);
        $conversation->messages()->create([
            'sender_id' => auth()->id(),
            'content'   => $request->content,
            'type'      => 'text',
        ]);
        $conversation->touch('last_message_at');
        return back();
    }
}
