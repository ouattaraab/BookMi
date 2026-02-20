<?php
namespace App\Http\Controllers\Web\Talent;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(): View
    {
        $profile = auth()->user()->talentProfile;
        if (!$profile) return view('talent.coming-soon', ['title' => 'Messages', 'description' => 'Configurez votre profil d\'abord.']);

        $conversations = Conversation::where('talent_profile_id', $profile->id)
            ->with(['client', 'latestMessage'])
            ->orderByDesc('last_message_at')
            ->paginate(20);

        return view('talent.messages.index', compact('conversations'));
    }

    public function show(int $id): View
    {
        $profile = auth()->user()->talentProfile;
        $conversation = Conversation::where('talent_profile_id', $profile?->id)
            ->with(['client', 'messages.sender'])
            ->findOrFail($id);

        $conversation->messages()
            ->where('sender_id', '!=', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('talent.messages.show', compact('conversation'));
    }

    public function send(int $id, Request $request): RedirectResponse
    {
        $request->validate(['content' => 'required|string|max:2000']);
        $profile = auth()->user()->talentProfile;
        $conversation = Conversation::where('talent_profile_id', $profile?->id)->findOrFail($id);

        $conversation->messages()->create([
            'sender_id' => auth()->id(),
            'content'   => $request->content,
            'type'      => 'text',
        ]);
        $conversation->touch('last_message_at');
        return back();
    }
}
