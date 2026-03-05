<?php

namespace App\Filament\Pages;

use App\Models\LoginLockoutLog;
use App\Models\TalentProfile;
use App\Models\User;
use App\Services\AuthService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class FraudDetectionPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationLabel = 'Sécurité & Fraude';

    protected static ?string $title = 'Détection fraude & doublons';

    protected static ?string $navigationGroup = 'Gestion des utilisateurs';

    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.pages.fraud-detection-page';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return ($user?->is_admin === true) || ($user?->hasAnyRole(['admin_ceo', 'admin_moderateur']) ?? false);
    }

    /** @var array<int, array<string, mixed>> */
    public array $phoneDuplicates = [];

    /** @var array<int, array<string, mixed>> */
    public array $suspectTalents = [];

    /** @var array<int, \App\Models\LoginLockoutLog> */
    public array $activeLockouts = [];

    public function mount(): void
    {
        $this->loadData();
    }

    private function loadData(): void
    {
        // Doublons de téléphone
        $duplicatePhones = User::select('phone', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('phone')
            ->groupBy('phone')
            ->having('cnt', '>', 1)
            ->pluck('phone')
            ->toArray();

        $this->phoneDuplicates = [];
        foreach ($duplicatePhones as $phone) {
            $users = User::where('phone', $phone)
                ->select('id', 'first_name', 'last_name', 'email', 'phone', 'created_at', 'is_admin')
                ->get()
                ->toArray();
            $this->phoneDuplicates[] = [
                'phone' => $phone,
                'users' => $users,
            ];
        }

        // Comptes suspects : talents avec total_bookings = 0 depuis > 90 jours + rating = 0
        $this->suspectTalents = TalentProfile::with('user:id,first_name,last_name,email')
            ->where('total_bookings', 0)
            ->where('average_rating', 0)
            ->whereHas('user', fn ($q) => $q->where('created_at', '<', now()->subDays(90)))
            ->select('id', 'user_id', 'stage_name', 'city', 'cachet_amount', 'is_verified', 'created_at')
            ->orderBy('created_at')
            ->limit(50)
            ->get()
            ->toArray();

        // Comptes bloqués (brute-force) — actifs uniquement
        $this->activeLockouts = LoginLockoutLog::with('user:id,first_name,last_name')
            ->whereNull('unlocked_at')
            ->where('locked_until', '>', now())
            ->orderByDesc('locked_at')
            ->limit(100)
            ->get()
            ->all();
    }

    public function unlockAccount(int $lockoutId): void
    {
        $log = LoginLockoutLog::find($lockoutId);
        if (! $log) {
            return;
        }

        /** @var \App\Models\User $admin */
        $admin = auth()->user();
        app(AuthService::class)->unlockAccount($log->email, $admin->id);
        $this->loadData();

        Notification::make()->title("Compte {$log->email} déverrouillé.")->success()->send();
    }

    public function suspendUser(int $userId): void
    {
        $user = User::find($userId);
        if (! $user || $user->is_admin) {
            Notification::make()->title('Utilisateur introuvable ou protégé.')->warning()->send();
            return;
        }

        $user->update(['is_active' => false]);
        $this->loadData();

        Notification::make()->title("Compte #{$userId} suspendu.")->success()->send();
    }
}
