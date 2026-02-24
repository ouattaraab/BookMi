<?php

namespace App\Filament\Pages;

use App\Jobs\SendPushNotification;
use App\Models\AdminMessage;
use App\Models\User;
use App\Notifications\AdminBroadcastNotification;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class MessageCenter extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-megaphone';
    protected static ?string $navigationGroup = 'Communication';
    protected static ?string $navigationLabel = 'Centre de messagerie';
    protected static ?int    $navigationSort  = 20;
    protected static string  $view            = 'filament.pages.message-center';

    public ?array $data = [];
    public Collection $recentMessages;

    public function mount(): void
    {
        $this->form->fill(['type' => 'both', 'target_type' => 'all']);
        $this->recentMessages = AdminMessage::with('admin', 'targetUser')
            ->latest()->limit(20)->get();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('type')
                ->label('Canal')
                ->options([
                    'push'  => 'Push notification',
                    'email' => 'Email',
                    'both'  => 'Push + Email',
                ])
                ->required()
                ->live(),

            Select::make('target_type')
                ->label('Destinataires')
                ->options([
                    'all'     => 'Tous les utilisateurs',
                    'clients' => 'Clients uniquement',
                    'talents' => 'Talents uniquement',
                    'user'    => 'Utilisateur spécifique',
                ])
                ->required()
                ->live(),

            Select::make('user_id')
                ->label('Utilisateur')
                ->searchable()
                ->getSearchResultsUsing(fn (string $search) => User::where('is_admin', false)
                    ->where(fn ($q) => $q
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"))
                    ->limit(20)
                    ->get()
                    ->mapWithKeys(fn ($u) => [$u->id => "{$u->first_name} {$u->last_name} ({$u->email})"]))
                ->visible(fn (Get $get) => $get('target_type') === 'user')
                ->requiredIf('target_type', 'user'),

            TextInput::make('title')
                ->label('Titre de la notification')
                ->maxLength(100)
                ->visible(fn (Get $get) => in_array($get('type'), ['push', 'both']))
                ->requiredIf('type', 'push'),

            Textarea::make('body')
                ->label('Message')
                ->rows(4)
                ->required()
                ->maxLength(500),

        ])->statePath('data');
    }

    private function resolveUsers(array $data): Collection
    {
        return match ($data['target_type']) {
            'all'     => User::where('is_admin', false)->where('is_active', true)->get(),
            'clients' => User::where('is_admin', false)->where('is_active', true)
                ->whereDoesntHave('talentProfile')->get(),
            'talents' => User::where('is_active', true)->whereHas('talentProfile')->get(),
            'user'    => User::where('id', $data['user_id'])->get(),
            default   => collect(),
        };
    }

    public function send(): void
    {
        $state = $this->form->getState();
        $users = $this->resolveUsers($state);

        if ($users->isEmpty()) {
            Notification::make()->title('Aucun destinataire trouvé')->warning()->send();

            return;
        }

        $title = $state['title'] ?? 'Message de BookMi';

        foreach ($users as $user) {
            if (in_array($state['type'], ['push', 'both'])) {
                SendPushNotification::dispatch(
                    $user->id,
                    $title,
                    $state['body'],
                    ['type' => 'admin_broadcast']
                );
            }

            if (in_array($state['type'], ['email', 'both'])) {
                $user->notify(new AdminBroadcastNotification($title, $state['body']));
            }
        }

        AdminMessage::create([
            'admin_id'         => auth()->id(),
            'type'             => $state['type'],
            'target_type'      => $state['target_type'],
            'target_user_id'   => $state['user_id'] ?? null,
            'title'            => $title,
            'body'             => $state['body'],
            'recipients_count' => $users->count(),
        ]);

        $this->recentMessages = AdminMessage::with('admin', 'targetUser')
            ->latest()->limit(20)->get();

        $this->form->fill(['type' => 'both', 'target_type' => 'all']);

        Notification::make()
            ->title("Message envoyé à {$users->count()} destinataire(s)")
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('send')
                ->label('Envoyer le message')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->action('send'),
        ];
    }
}
