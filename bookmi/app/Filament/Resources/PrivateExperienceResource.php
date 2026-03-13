<?php

namespace App\Filament\Resources;

use App\Enums\ExperienceBookingStatus;
use App\Enums\ExperienceStatus;
use App\Filament\Resources\PrivateExperienceResource\Pages;
use App\Jobs\NotifyTalentFollowers;
use App\Models\ExperienceBooking;
use App\Models\PrivateExperience;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PrivateExperienceResource extends Resource
{
    protected static ?string $model = PrivateExperience::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'Meet & Greet';

    protected static ?string $modelLabel = 'Meet & Greet';

    protected static ?string $pluralModelLabel = 'Meet & Greet';

    protected static ?string $navigationGroup = 'Événements';

    protected static ?int $navigationSort = 10;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return ($user?->is_admin === true) || ($user?->hasAnyRole(['admin_ceo', 'admin_moderateur']) ?? false);
    }

    // ── Form (create / edit) ───────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informations générales')
                ->schema([
                    Forms\Components\Select::make('talent_profile_id')
                        ->label('Talent')
                        ->relationship('talentProfile', 'stage_name')
                        ->searchable()
                        ->required(),
                    Forms\Components\TextInput::make('title')
                        ->label('Titre')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->rows(4),
                    Forms\Components\DateTimePicker::make('event_date')
                        ->label('Date et heure')
                        ->required(),
                    Forms\Components\TextInput::make('venue_address')
                        ->label('Lieu')
                        ->maxLength(255),
                    Forms\Components\Toggle::make('venue_revealed')
                        ->label('Lieu révélé aux participants')
                        ->default(false),
                ])->columns(2),

            Forms\Components\Section::make('Places & Prix')
                ->schema([
                    Forms\Components\TextInput::make('total_price')
                        ->label('Prix total artiste (XOF)')
                        ->numeric()
                        ->required()
                        ->minValue(1000),
                    Forms\Components\TextInput::make('max_seats')
                        ->label('Nombre de places max')
                        ->numeric()
                        ->required()
                        ->minValue(1),
                    Forms\Components\TextInput::make('commission_rate')
                        ->label('Commission BookMi (%)')
                        ->numeric()
                        ->default(15)
                        ->minValue(0)
                        ->maxValue(100),
                ])->columns(3),

            Forms\Components\Section::make('Statut')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Statut')
                        ->options(collect(ExperienceStatus::cases())->mapWithKeys(
                            fn (ExperienceStatus $s) => [$s->value => $s->label()]
                        )->toArray())
                        ->required(),
                    Forms\Components\Textarea::make('cancelled_reason')
                        ->label("Motif d'annulation")
                        ->rows(2),
                ])->columns(2),
        ]);
    }

    // ── Table (list) ───────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('event_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('talentProfile.stage_name')
                    ->label('Artiste')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->limit(35),

                Tables\Columns\TextColumn::make('event_date')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn (ExperienceStatus $state) => $state->label())
                    ->color(fn (ExperienceStatus $state) => $state->filamentColor()),

                Tables\Columns\TextColumn::make('seats_info')
                    ->label('Participants')
                    ->getStateUsing(fn (PrivateExperience $r) => $r->booked_seats . ' / ' . $r->max_seats)
                    ->badge()
                    ->color(fn (PrivateExperience $r) => $r->is_full ? 'warning' : 'success'),

                Tables\Columns\TextColumn::make('price_per_seat')
                    ->label('Prix / place')
                    ->getStateUsing(fn (PrivateExperience $r) => number_format($r->price_per_seat, 0, ',', '.') . ' XOF')
                    ->sortable(false),

                Tables\Columns\TextColumn::make('venue_address')
                    ->label('Lieu')
                    ->limit(25)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(collect(ExperienceStatus::cases())->mapWithKeys(
                        fn (ExperienceStatus $s) => [$s->value => $s->label()]
                    )->toArray()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('publish')
                    ->label('Publier')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->visible(fn (PrivateExperience $r) => $r->status === ExperienceStatus::Draft)
                    ->requiresConfirmation()
                    ->action(function (PrivateExperience $record) {
                        $record->update(['status' => ExperienceStatus::Published->value]);
                        Notification::make()->title('Expérience publiée')->success()->send();

                        $talentProfile = $record->talentProfile;
                        if ($talentProfile instanceof \App\Models\TalentProfile && $talentProfile->followers()->exists()) {
                            NotifyTalentFollowers::dispatch(
                                $talentProfile->id,
                                $talentProfile->stage_name,
                                "{$talentProfile->stage_name} organise un Meet & Greet !",
                                $record->title . ' — Réservez vite, les places sont limitées.',
                                'meet_and_greet',
                                $talentProfile->slug ?? '',
                            );
                        }
                    }),
                Tables\Actions\Action::make('cancel')
                    ->label('Annuler')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (PrivateExperience $r) => in_array($r->status, [ExperienceStatus::Draft, ExperienceStatus::Published, ExperienceStatus::Full]))
                    ->form([
                        Forms\Components\Textarea::make('cancelled_reason')
                            ->label("Motif d'annulation")
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (PrivateExperience $record, array $data) {
                        $record->update([
                            'status'           => ExperienceStatus::Cancelled->value,
                            'cancelled_reason' => $data['cancelled_reason'],
                        ]);
                        Notification::make()->title('Expérience annulée')->warning()->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // ── Infolist (view) ────────────────────────────────────────────────────

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Détails')
                ->schema([
                    Infolists\Components\TextEntry::make('talentProfile.stage_name')
                        ->label('Artiste'),
                    Infolists\Components\TextEntry::make('title')
                        ->label('Titre'),
                    Infolists\Components\TextEntry::make('event_date')
                        ->label('Date')
                        ->dateTime('d/m/Y H:i'),
                    Infolists\Components\TextEntry::make('status')
                        ->label('Statut')
                        ->badge()
                        ->formatStateUsing(fn (ExperienceStatus $state) => $state->label())
                        ->color(fn (ExperienceStatus $state) => $state->filamentColor()),
                    Infolists\Components\TextEntry::make('venue_address')
                        ->label('Lieu')
                        ->placeholder('Non renseigné'),
                    Infolists\Components\IconEntry::make('venue_revealed')
                        ->label('Lieu révélé')
                        ->boolean(),
                    Infolists\Components\TextEntry::make('description')
                        ->label('Description')
                        ->columnSpanFull()
                        ->placeholder('—'),
                    Infolists\Components\TextEntry::make('cancelled_reason')
                        ->label("Motif d'annulation")
                        ->columnSpanFull()
                        ->placeholder('—')
                        ->visible(fn (PrivateExperience $r) => $r->cancelled_reason !== null),
                ])->columns(3),

            Infolists\Components\Section::make('Places & Finance')
                ->schema([
                    Infolists\Components\TextEntry::make('max_seats')
                        ->label('Places max'),
                    Infolists\Components\TextEntry::make('booked_seats')
                        ->label('Places réservées'),
                    Infolists\Components\TextEntry::make('seats_available')
                        ->label('Places disponibles')
                        ->getStateUsing(fn (PrivateExperience $r) => $r->seats_available),
                    Infolists\Components\TextEntry::make('price_per_seat_fmt')
                        ->label('Prix / place')
                        ->getStateUsing(fn (PrivateExperience $r) => number_format($r->price_per_seat, 0, ',', '.') . ' XOF'),
                    Infolists\Components\TextEntry::make('total_price_fmt')
                        ->label('Prix total artiste')
                        ->getStateUsing(fn (PrivateExperience $r) => number_format($r->total_price, 0, ',', '.') . ' XOF'),
                    Infolists\Components\TextEntry::make('commission_rate')
                        ->label('Commission BookMi')
                        ->suffix(' %'),
                    Infolists\Components\TextEntry::make('total_collected_fmt')
                        ->label('Total perçu (confirmés)')
                        ->getStateUsing(fn (PrivateExperience $r) => number_format($r->total_collected, 0, ',', '.') . ' XOF'),
                    Infolists\Components\TextEntry::make('talent_net_fmt')
                        ->label('Net talent')
                        ->getStateUsing(fn (PrivateExperience $r) => number_format($r->talent_net, 0, ',', '.') . ' XOF'),
                ])->columns(4),

            Infolists\Components\Section::make('Participants')
                ->schema([
                    Infolists\Components\RepeatableEntry::make('bookings')
                        ->label('')
                        ->schema([
                            Infolists\Components\TextEntry::make('client.first_name')
                                ->label('Prénom'),
                            Infolists\Components\TextEntry::make('client.last_name')
                                ->label('Nom'),
                            Infolists\Components\TextEntry::make('seats_count')
                                ->label('Places'),
                            Infolists\Components\TextEntry::make('total_amount')
                                ->label('Montant')
                                ->getStateUsing(fn (ExperienceBooking $b) => number_format($b->total_amount, 0, ',', '.') . ' XOF'),
                            Infolists\Components\TextEntry::make('status')
                                ->label('Statut')
                                ->badge()
                                ->formatStateUsing(fn (ExperienceBookingStatus $state) => $state->label())
                                ->color(fn (ExperienceBookingStatus $state) => $state->filamentColor()),
                            Infolists\Components\TextEntry::make('created_at')
                                ->label('Inscrit le')
                                ->dateTime('d/m/Y H:i'),
                        ])->columns(6),
                ]),
        ]);
    }

    // ── Pages ──────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPrivateExperiences::route('/'),
            'view'   => Pages\ViewPrivateExperience::route('/{record}'),
            'edit'   => Pages\EditPrivateExperience::route('/{record}/edit'),
            'create' => Pages\CreatePrivateExperience::route('/create'),
        ];
    }
}
