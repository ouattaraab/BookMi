<?php

namespace App\Livewire\Shared;

use App\Models\PushNotification;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class NotificationBell extends Component
{
    public string $accentColor = '#FF6B35';

    #[Computed]
    public function notifications(): \Illuminate\Database\Eloquent\Collection
    {
        return PushNotification::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();
    }

    #[Computed]
    public function unreadCount(): int
    {
        return PushNotification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->count();
    }

    public function markRead(int $id): void
    {
        PushNotification::where('id', $id)
            ->where('user_id', auth()->id())
            ->update(['read_at' => now()]);
    }

    public function markAllRead(): void
    {
        PushNotification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Triggered by the Firebase foreground message handler (JS dispatch).
     * Forces Livewire to re-render with the latest notification count.
     */
    #[On('bookmi:notification-received')]
    public function refresh(): void
    {
        unset($this->notifications, $this->unreadCount);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.shared.notification-bell');
    }
}
