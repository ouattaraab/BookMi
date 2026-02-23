<?php

namespace App\Http\Controllers\Web\Manager;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\TalentProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(): View
    {
        $profileIds = TalentProfile::whereHas('managers', fn ($q) => $q->where('users.id', auth()->id()))->pluck('id');
        $conversations = Conversation::whereIn('talent_profile_id', $profileIds)
            ->with(['talentProfile.user', 'client', 'latestMessage'])
            ->orderByDesc('last_message_at')
            ->paginate(20);
        return view('manager.messages.index', compact('conversations'));
    }

    public function show(int $id): View
    {
        $profileIds = TalentProfile::whereHas('managers', fn ($q) => $q->where('users.id', auth()->id()))->pluck('id');
        $conversation = Conversation::whereIn('talent_profile_id', $profileIds)
            ->with(['talentProfile.user', 'client', 'messages.sender'])
            ->findOrFail($id);
        $conversation->messages()->where('sender_id', '!=', auth()->id())->whereNull('read_at')->update(['read_at' => now()]);
        return view('manager.messages.show', compact('conversation'));
    }

    public function send(int $id, Request $request): RedirectResponse
    {
        $request->validate(['content' => 'required|string|max:2000']);
        $profileIds = TalentProfile::whereHas('managers', fn ($q) => $q->where('users.id', auth()->id()))->pluck('id');
        $conversation = Conversation::whereIn('talent_profile_id', $profileIds)->findOrFail($id);
        $conversation->messages()->create([
            'sender_id' => auth()->id(),
            'content'   => $request->content,
            'type'      => 'text',
        ]);
        $conversation->touch('last_message_at');
        return back();
    }
}
