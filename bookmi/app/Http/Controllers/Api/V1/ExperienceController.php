<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ExperienceBookingStatus;
use App\Enums\ExperienceStatus;
use App\Http\Controllers\Controller;
use App\Models\ExperienceBooking;
use App\Models\PrivateExperience;
use App\Models\TalentProfile;
use App\Notifications\MeetAndGreetBookingConfirmation;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExperienceController extends Controller
{
    // ── Public ────────────────────────────────────────────────────────────

    /**
     * GET /api/v1/experiences
     * Liste des expériences publiées à venir (discovery mobile).
     */
    public function index(Request $request): JsonResponse
    {
        $query = PrivateExperience::with(['talentProfile:id,stage_name,slug,profile_photo,city'])
            ->publiclyVisible()
            ->upcoming()
            ->orderBy('event_date');

        if ($request->filled('talent_id')) {
            $query->where('talent_profile_id', $request->integer('talent_id'));
        }

        $experiences = $query->paginate(20);

        return response()->json([
            'data' => $experiences->map(fn (PrivateExperience $e) => $this->serializeList($e)),
            'meta' => [
                'current_page' => $experiences->currentPage(),
                'last_page'    => $experiences->lastPage(),
                'total'        => $experiences->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/experiences/{id}
     * Détail d'une expérience.
     * Accessible si :
     *  - expérience publiquement visible (published / full), OU
     *  - utilisateur authentifié ayant une réservation active pour cette expérience, OU
     *  - talent propriétaire de l'expérience.
     */
    public function show(int $id): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user      = auth()->user();
        $myBooking = null;

        $query = PrivateExperience::with(['talentProfile:id,stage_name,slug,profile_photo,city,category_id'])
            ->where('id', $id);

        if ($user) {
            // Récupérer la réservation éventuelle
            $myBooking = ExperienceBooking::where('private_experience_id', $id)
                ->where('client_id', $user->id)
                ->first();

            // Autoriser si : visible publiquement OU utilisateur réservé OU talent propriétaire
            $talentProfile = \App\Models\TalentProfile::where('user_id', $user->id)->first();
            $ownedIds      = $talentProfile
                ? PrivateExperience::where('talent_profile_id', $talentProfile->id)->pluck('id')
                : collect();

            $bookedIds = ExperienceBooking::where('client_id', $user->id)
                ->pluck('private_experience_id');

            $query->where(function ($q) use ($bookedIds, $ownedIds) {
                $q->whereIn('status', ExperienceStatus::visibleOnPublic())
                  ->orWhereIn('id', $bookedIds)
                  ->orWhereIn('id', $ownedIds);
            });
        } else {
            $query->whereIn('status', ExperienceStatus::visibleOnPublic());
        }

        $experience = $query->firstOrFail();

        return response()->json([
            'data' => $this->serializeDetail($experience, $myBooking),
        ]);
    }

    // ── Talent (auth) ──────────────────────────────────────────────────────

    /**
     * POST /api/v1/talent/experiences
     * Talent crée un Meet & Greet.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (! $user->hasRole('talent')) {
            return response()->json(['message' => 'Accès réservé aux talents.'], 403);
        }

        $profile = TalentProfile::where('user_id', $user->id)->firstOrFail();

        $validated = $request->validate([
            'title'         => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string', 'max:3000'],
            'event_date'    => ['required', 'date_format:Y-m-d H:i:s', 'after:today'],
            'venue_address' => ['nullable', 'string', 'max:255'],
            'total_price'   => ['required', 'integer', 'min:100'],
            'max_seats'     => ['required', 'integer', 'min:1', 'max:500'],
        ]);

        $experience = PrivateExperience::create([
            'talent_profile_id' => $profile->id,
            'title'             => $validated['title'],
            'description'       => $validated['description'] ?? null,
            'event_date'        => $validated['event_date'],
            'venue_address'     => $validated['venue_address'] ?? null,
            'total_price'       => $validated['total_price'],
            'max_seats'         => $validated['max_seats'],
            'commission_rate'   => (int) config('bookmi.commission_rate', 15),
            'status'            => ExperienceStatus::Draft->value,
        ]);

        return response()->json(['data' => $this->serializeDetail($experience)], 201);
    }

    /**
     * GET /api/v1/talent/experiences
     * Liste des expériences du talent connecté.
     */
    public function myExperiences(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (! $user->hasRole('talent')) {
            return response()->json(['message' => 'Accès réservé aux talents.'], 403);
        }

        $profile = TalentProfile::where('user_id', $user->id)->firstOrFail();

        $experiences = PrivateExperience::where('talent_profile_id', $profile->id)
            ->withCount('bookings')
            ->orderByDesc('event_date')
            ->get();

        return response()->json([
            'data' => $experiences->map(fn (PrivateExperience $e) => array_merge(
                $this->serializeList($e),
                [
                    'total_collected' => $e->total_collected,
                    'talent_net'      => $e->talent_net,
                    'bookings_count'  => $e->bookings_count,
                ]
            )),
        ]);
    }

    // ── Client booking ─────────────────────────────────────────────────────

    /**
     * POST /api/v1/experiences/{id}/book
     * Client réserve une ou plusieurs places.
     */
    public function book(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'seats_count' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        /** @var \App\Models\User $client */
        $client = auth()->user();
        $seats  = (int) $validated['seats_count'];

        try {
            $booking = DB::transaction(function () use ($id, $seats, $client): ExperienceBooking {
                // lockForUpdate INSIDE the transaction — verrou réel
                $experience = PrivateExperience::where('id', $id)
                    ->where('status', ExperienceStatus::Published->value)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (ExperienceBooking::where('private_experience_id', $id)
                    ->where('client_id', $client->id)
                    ->exists()
                ) {
                    abort(422, 'Vous êtes déjà inscrit à cet événement.');
                }

                if ($experience->seats_available < $seats) {
                    abort(422, "Seulement {$experience->seats_available} place(s) disponible(s).");
                }

                $pricePerSeat     = $experience->price_per_seat;
                $totalAmount      = $pricePerSeat * $seats;
                $commissionAmount = (int) round($totalAmount * $experience->commission_rate / 100);

                $booking = ExperienceBooking::create([
                    'private_experience_id' => $experience->id,
                    'client_id'             => $client->id,
                    'seats_count'           => $seats,
                    'price_per_seat'        => $pricePerSeat,
                    'total_amount'          => $totalAmount,
                    'commission_amount'     => $commissionAmount,
                    'status'                => ExperienceBookingStatus::Pending->value,
                ]);

                $experience->increment('booked_seats', $seats);
                $experience->refresh();

                if ($experience->booked_seats >= $experience->max_seats) {
                    $experience->update(['status' => ExperienceStatus::Full->value]);
                }

                return $booking;
            });
        } catch (QueryException $e) {
            // Contrainte unique (private_experience_id, client_id) — double-booking concurrent
            if ($e->getCode() === '23000') {
                return response()->json(['message' => 'Vous êtes déjà inscrit à cet événement.'], 422);
            }
            throw $e;
        }

        // Envoi du reçu/billet par email
        $booking->load(['experience.talentProfile', 'client']);
        $client->notify(new MeetAndGreetBookingConfirmation($booking));

        return response()->json([
            'message' => 'Inscription enregistrée. Un billet de confirmation vous a été envoyé par email.',
            'data'    => [
                'id'           => $booking->id,
                'seats_count'  => $booking->seats_count,
                'total_amount' => $booking->total_amount,
                'status'       => $booking->status->value,
            ],
        ], 201);
    }

    /**
     * DELETE /api/v1/experiences/{id}/booking
     * Client annule sa réservation.
     */
    public function cancelBooking(int $id): JsonResponse
    {
        /** @var \App\Models\User $client */
        $client = auth()->user();

        $booking = ExperienceBooking::where('private_experience_id', $id)
            ->where('client_id', $client->id)
            ->where('status', ExperienceBookingStatus::Pending->value)
            ->firstOrFail();

        DB::transaction(function () use ($booking): void {
            $booking->update([
                'status'       => ExperienceBookingStatus::Cancelled->value,
                'cancelled_at' => now(),
            ]);

            // lockForUpdate pour éviter la lecture d'un état périmé entre decrement et le SELECT
            /** @var PrivateExperience|null $exp */
            $exp = $booking->experience()->lockForUpdate()->first();

            if ($exp instanceof PrivateExperience) {
                $exp->decrement('booked_seats', $booking->seats_count);
                $exp->refresh();

                if ($exp->status === ExperienceStatus::Full) {
                    $exp->update(['status' => ExperienceStatus::Published->value]);
                }
            }
        });

        return response()->json(['message' => 'Inscription annulée.']);
    }

    // ── Client: list own bookings ──────────────────────────────────────────

    /**
     * GET /api/v1/me/experience-bookings
     * Liste des réservations M&G du client connecté.
     */
    public function myBookings(): JsonResponse
    {
        /** @var \App\Models\User $client */
        $client = auth()->user();

        $bookings = ExperienceBooking::with([
            'experience.talentProfile:id,stage_name,profile_photo',
        ])
            ->where('client_id', $client->id)
            ->orderByDesc('created_at')
            ->get();

        $data = $bookings->map(function (ExperienceBooking $b): array {
            /** @var PrivateExperience|null $exp */
            $exp    = $b->experience instanceof PrivateExperience ? $b->experience : null;
            /** @var TalentProfile|null $talent */
            $talent = $exp && $exp->talentProfile instanceof TalentProfile
                ? $exp->talentProfile
                : null;

            return [
                'booking_id'   => $b->id,
                'seats_count'  => $b->seats_count,
                'price_per_seat' => $b->price_per_seat,
                'total_amount' => $b->total_amount,
                'status'       => $b->status->value,
                'status_label' => $b->status->label(),
                'experience'   => $exp ? [
                    'id'            => $exp->id,
                    'title'         => $exp->title,
                    'event_date'    => $exp->event_date->toDateString(),
                    'event_time'    => $exp->event_date->format('H:i'),
                    'venue_address' => $exp->venue_address,
                    'cover_image'   => $exp->cover_image_url,
                    'talent'        => $talent ? [
                        'id'            => $talent->id,
                        'stage_name'    => $talent->stage_name,
                        'profile_photo' => $talent->profile_photo,
                    ] : null,
                ] : null,
            ];
        });

        return response()->json(['data' => $data]);
    }

    // ── Talent: list experience attendees ──────────────────────────────────

    /**
     * GET /api/v1/talent/experiences/{id}/attendees
     * Liste des participants à une expérience du talent connecté.
     */
    public function attendees(int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (! $user->hasRole('talent')) {
            return response()->json(['message' => 'Accès réservé aux talents.'], 403);
        }

        $profile    = TalentProfile::where('user_id', $user->id)->firstOrFail();
        $experience = PrivateExperience::where('id', $id)
            ->where('talent_profile_id', $profile->id)
            ->firstOrFail();

        $bookings = ExperienceBooking::with(['client:id,first_name,last_name'])
            ->where('private_experience_id', $experience->id)
            ->orderByDesc('created_at')
            ->get();

        $data = $bookings->map(function (ExperienceBooking $b): array {
            /** @var \App\Models\User|null $client */
            $client = $b->client instanceof \App\Models\User ? $b->client : null;

            return [
                'id'           => $b->id,
                'client_id'    => $b->client_id,
                'first_name'   => $client !== null ? $client->first_name : '',
                'last_name'    => $client !== null ? $client->last_name : '',
                'seats_count'  => $b->seats_count,
                'total_amount' => $b->total_amount,
                'status'       => $b->status->value,
                'status_label' => $b->status->label(),
                'created_at'   => $b->created_at?->toIso8601String(),
            ];
        });

        return response()->json(['data' => $data]);
    }

    // ── Cover media upload (talent) ────────────────────────────────────────

    /**
     * POST /api/v1/experiences/{id}/cover
     * Le talent upload une photo ou vidéo de couverture pour son Meet & Greet.
     */
    public function uploadCover(Request $request, int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user    = auth()->user();
        $profile = TalentProfile::where('user_id', $user->id)->first();

        $experience = PrivateExperience::findOrFail($id);

        if (! $profile || $experience->talent_profile_id !== $profile->id) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        $request->validate([
            'cover' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,mp4,mov', 'max:51200'],
        ]);

        // Supprimer l'ancien média si existant
        if ($experience->cover_image) {
            Storage::disk('public')->delete($experience->cover_image);
        }

        $file  = $request->file('cover');
        $mime  = $file->getMimeType() ?? '';

        if (str_starts_with($mime, 'image')) {
            // Optimise l'image avec GD avant stockage
            $filename    = "experience-covers/{$id}/" . uniqid() . '.jpg';
            $storagePath = storage_path('app/public/' . $filename);
            @mkdir(dirname($storagePath), 0755, true);
            $this->optimizeImage($file->getRealPath(), $storagePath, 1200, 82);
            $path = $filename;
        } else {
            // Vidéo — stockée telle quelle
            $path = $file->store("experience-covers/{$id}", 'public');
        }

        $experience->update(['cover_image' => $path]);

        return response()->json([
            'message' => 'Média de couverture mis à jour.',
            'data'    => [
                'cover_image'     => $experience->fresh()->cover_image_url,
                'is_video'        => str_starts_with($mime, 'video'),
            ],
        ]);
    }

    /**
     * Redimensionne et ré-encode une image en JPEG avec GD.
     */
    private function optimizeImage(string $source, string $dest, int $maxWidth, int $quality): void
    {
        $info = @getimagesize($source);
        if (! $info) {
            copy($source, $dest);
            return;
        }

        [$width, $height, $type] = $info;

        $src = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($source),
            IMAGETYPE_PNG  => imagecreatefrompng($source),
            IMAGETYPE_WEBP => imagecreatefromwebp($source),
            IMAGETYPE_GIF  => imagecreatefromgif($source),
            default        => null,
        };

        if (! $src) {
            copy($source, $dest);
            return;
        }

        if ($width > $maxWidth) {
            $newWidth  = $maxWidth;
            $newHeight = (int) round($height * $maxWidth / $width);
        } else {
            $newWidth  = $width;
            $newHeight = $height;
        }

        $dst   = imagecreatetruecolor($newWidth, $newHeight);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagejpeg($dst, $dest, $quality);
        imagedestroy($src);
        imagedestroy($dst);
    }

    // ── Serializers ────────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private function serializeList(PrivateExperience $e): array
    {
        /** @var TalentProfile|null $talent */
        $talent = $e->talentProfile instanceof TalentProfile ? $e->talentProfile : null;

        return [
            'id'              => $e->id,
            'title'           => $e->title,
            'event_date'      => $e->event_date->toIso8601String(),
            'event_time'      => $e->event_date->format('H:i'),
            'status'          => $e->status->value,
            'status_label'    => $e->status->label(),
            'price_per_seat'  => $e->price_per_seat,
            'max_seats'       => $e->max_seats,
            'booked_seats'    => $e->booked_seats,
            'seats_available' => $e->seats_available,
            'is_full'         => $e->is_full,
            'cover_image'     => $e->cover_image_url,
            'talent'          => $talent ? [
                'id'            => $talent->id,
                'stage_name'    => $talent->stage_name,
                'slug'          => $talent->slug,
                'city'          => $talent->city,
                'profile_photo' => $talent->profile_photo,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDetail(PrivateExperience $e, ?ExperienceBooking $myBooking = null): array
    {
        $data             = $this->serializeList($e);
        $data['description']     = $e->description;
        $data['premium_options'] = $e->premium_options ?? [];

        // Lieu : masqué si non inscrit et pas encore révélé publiquement
        $showVenue = $e->venue_revealed
            || ($myBooking && $myBooking->status->value !== 'cancelled');

        $data['venue_address'] = $showVenue ? $e->venue_address : null;
        $data['venue_revealed'] = $e->venue_revealed;

        if ($myBooking) {
            $data['my_booking'] = [
                'id'           => $myBooking->id,
                'seats_count'  => $myBooking->seats_count,
                'total_amount' => $myBooking->total_amount,
                'status'       => $myBooking->status->value,
                'status_label' => $myBooking->status->label(),
            ];
        }

        return $data;
    }
}
