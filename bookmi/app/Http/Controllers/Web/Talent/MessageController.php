<?php
namespace App\Http\Controllers\Web\Talent;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Concerns\HandleMessageSend;
use App\Models\Conversation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    use HandleMessageSend;

    public function index(): View
    {
        $profile = auth()->user()->talentProfile;
        if (!$profile) return view('talent.coming-soon', ['title' => 'Messages', 'description' => 'Configurez votre profil d\'abord.']);

        $conversations = Conversation::where('talent_profile_id', $profile->id)
            ->with(['client', 'latestMessage', 'bookingRequest'])
            ->orderByDesc('last_message_at')
            ->paginate(20);

        return view('talent.messages.index', compact('conversations'));
    }

    public function show(int $id): View
    {
        $profile = auth()->user()->talentProfile;
        $conversation = Conversation::where('talent_profile_id', $profile?->id)
            ->with(['client', 'messages.sender', 'bookingRequest'])
            ->findOrFail($id);

        $conversation->messages()
            ->where('sender_id', '!=', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('talent.messages.show', compact('conversation'));
    }

    public function send(int $id, Request $request): RedirectResponse
    {
        $request->validate([
            'content' => 'nullable|string|max:2000',
            'media'   => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,mp4,mov,avi,webm|max:51200',
        ]);

        $profile      = auth()->user()->talentProfile;
        $conversation = Conversation::where('talent_profile_id', $profile?->id)
            ->with('bookingRequest')
            ->findOrFail($id);

        // Block messaging on completed/cancelled/disputed bookings
        if ($conversation->bookingRequest) {
            $bookingStatus = $conversation->bookingRequest->status instanceof \BackedEnum
                ? $conversation->bookingRequest->status->value
                : (string) $conversation->bookingRequest->status;
            if (in_array($bookingStatus, ['completed', 'cancelled', 'disputed'])) {
                return back()->withErrors(['content' => 'Cette réservation est terminée. Les échanges via ce fil sont désactivés.']);
            }
        }

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
