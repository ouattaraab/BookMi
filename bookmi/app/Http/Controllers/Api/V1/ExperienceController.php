<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ExperienceBookingStatus;
use App\Enums\ExperienceStatus;
use App\Http\Controllers\Controller;
use App\Models\ExperienceBooking;
use App\Models\PrivateExperience;
use App\Models\TalentProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
     */
    public function show(int $id): JsonResponse
    {
        $experience = PrivateExperience::with(['talentProfile:id,stage_name,slug,profile_photo,city,category_id'])
            ->whereIn('status', ExperienceStatus::visibleOnPublic())
            ->findOrFail($id);

        /** @var \App\Models\User|null $user */
        $user      = auth()->user();
        $myBooking = null;

        if ($user) {
            $myBooking = ExperienceBooking::where('private_experience_id', $id)
                ->where('client_id', $user->id)
                ->first();
        }

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
        $user    = auth()->user();
        $profile = TalentProfile::where('user_id', $user->id)->firstOrFail();

        $validated = $request->validate([
            'title'         => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string', 'max:3000'],
            'event_date'    => ['required', 'date_format:Y-m-d H:i:s', 'after:today'],
            'venue_address' => ['nullable', 'string', 'max:255'],
            'total_price'   => ['required', 'integer', 'min:1000'],
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
        $user    = auth()->user();
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

        $experience = PrivateExperience::where('id', $id)
            ->where('status', ExperienceStatus::Published->value)
            ->lockForUpdate()
            ->firstOrFail();

        if (ExperienceBooking::where('private_experience_id', $id)
            ->where('client_id', $client->id)
            ->exists()
        ) {
            return response()->json(['message' => 'Vous êtes déjà inscrit à cet événement.'], 422);
        }

        $seats = (int) $validated['seats_count'];

        if ($experience->seats_available < $seats) {
            return response()->json([
                'message' => "Seulement {$experience->seats_available} place(s) disponible(s).",
            ], 422);
        }

        $booking = DB::transaction(function () use ($experience, $seats, $client): ExperienceBooking {
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

        return response()->json([
            'message' => 'Inscription enregistrée. L\'équipe BookMi vous contactera pour finaliser.',
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

            $booking->experience()->decrement('booked_seats', $booking->seats_count);

            /** @var PrivateExperience|null $exp */
            $exp = $booking->experience()->first();
            if ($exp instanceof PrivateExperience && $exp->status === ExperienceStatus::Full) {
                $exp->update(['status' => ExperienceStatus::Published->value]);
            }
        });

        return response()->json(['message' => 'Inscription annulée.']);
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
            'status'          => $e->status->value,
            'status_label'    => $e->status->label(),
            'price_per_seat'  => $e->price_per_seat,
            'max_seats'       => $e->max_seats,
            'booked_seats'    => $e->booked_seats,
            'seats_available' => $e->seats_available,
            'is_full'         => $e->is_full,
            'cover_image'     => $e->cover_image,
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
