<?php

namespace App\Http\Controllers\Web\Client;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Events\BookingCreated;
use App\Exceptions\BookingException;
use App\Exceptions\PaymentException;
use App\Http\Controllers\Controller;
use App\Models\BookingRequest;
use App\Models\TalentProfile;
use App\Models\Transaction;
use App\Services\BookingService;
use App\Services\CalendarService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly CalendarService $calendarService,
        private readonly BookingService $bookingService,
    ) {
    }

    public function index(Request $request): View
    {
        $query = BookingRequest::where('client_id', auth()->id())
            ->with(['talentProfile.user', 'talentProfile.category', 'servicePackage'])
            ->orderByDesc('created_at');

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        $bookings = $query->paginate(10)->withQueryString();
        return view('client.bookings.index', compact('bookings'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $slug = $request->string('talent')->trim()->value();

        $talent = TalentProfile::with(['user', 'category', 'servicePackages' => function ($q) {
            $q->where('is_active', true)->orderBy('sort_order')->orderBy('cachet_amount');
        }])->where('slug', $slug)->orWhere('id', $slug)->first();

        if (! $talent) {
            return redirect()->route('talents.index')
                ->with('error', 'Talent introuvable. Veuillez sélectionner un talent depuis la liste.');
        }

        return view('client.bookings.create', compact('talent'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'talent_profile_id'  => ['required', 'integer', 'exists:talent_profiles,id'],
            'service_package_id' => ['nullable', 'integer', 'exists:service_packages,id'],
            'event_date'         => ['required', 'date', 'after:today'],
            'start_time'         => ['nullable', 'date_format:H:i'],
            'event_location'     => ['required', 'string', 'max:255'],
            'message'            => ['nullable', 'string', 'max:1000'],
            'is_express'         => ['nullable', 'boolean'],
            'travel_cost'        => ['nullable', 'integer', 'min:0', 'max:999999'],
        ]);

        $talent = TalentProfile::with('servicePackages')->findOrFail($validated['talent_profile_id']);

        if (! $this->calendarService->isDateAvailable($talent, $validated['event_date'], $validated['start_time'] ?? null)) {
            return back()->withInput()->with('error', 'Cette date n\'est pas disponible pour ce talent. Veuillez choisir une autre date ou heure.');
        }

        // Calcul du montant
        $cachetAmount = $talent->cachet_amount ?? 0;
        if (! empty($validated['service_package_id'])) {
            $pkg = $talent->servicePackages->firstWhere('id', $validated['service_package_id']);
            if ($pkg) {
                $cachetAmount = $pkg->cachet_amount;
            }
        }

        $commissionRate   = (int) config('bookmi.commission_rate', 15);
        $commissionAmount = (int) round($cachetAmount * $commissionRate / 100);

        $isExpress  = $request->boolean('is_express') && $talent->enable_express_booking;
        $travelCost = (int) ($validated['travel_cost'] ?? 0);
        $expressFee = $isExpress ? (int) round($cachetAmount * 0.10) : 0;
        $totalAmount = $cachetAmount + $commissionAmount + $expressFee + $travelCost;

        $booking = BookingRequest::create([
            'client_id'          => auth()->id(),
            'talent_profile_id'  => $validated['talent_profile_id'],
            'service_package_id' => $validated['service_package_id'] ?? null,
            'event_date'         => $validated['event_date'],
            'start_time'         => $validated['start_time'] ?? null,
            'event_location'     => $validated['event_location'],
            'message'            => $validated['message'] ?? null,
            'status'             => 'pending',
            'cachet_amount'      => $cachetAmount,
            'commission_amount'  => $commissionAmount,
            'is_express'         => $isExpress,
            'travel_cost'        => $travelCost ?: null,
            'express_fee'        => $expressFee ?: null,
            'total_amount'       => $totalAmount,
        ]);

        BookingCreated::dispatch($booking);

        return redirect()->route('client.bookings')
            ->with('success', 'Votre demande de réservation a été envoyée ! Le talent vous répondra sous 24h.');
    }

    public function show(int $id): View
    {
        $booking = BookingRequest::where('client_id', auth()->id())
            ->with(['talentProfile.user', 'talentProfile.category', 'servicePackage', 'trackingEvents'])
            ->findOrFail($id);

        $hasReview = \App\Models\Review::where('booking_request_id', $id)
            ->where('reviewer_id', auth()->id())
            ->exists();

        return view('client.bookings.show', compact('booking', 'hasReview'));
    }

    public function receipt(int $id): RedirectResponse
    {
        $booking = BookingRequest::where('client_id', auth()->id())
            ->whereIn('status', [
                BookingStatus::Paid->value,
                BookingStatus::Confirmed->value,
                BookingStatus::Completed->value,
            ])
            ->findOrFail($id);

        $token = Str::uuid()->toString();
        Cache::put("pdf_download:{$token}", [
            'type'       => 'receipt',
            'booking_id' => $booking->id,
        ], now()->addMinutes(10));

        return redirect()->away(url("/api/v1/dl/{$token}"));
    }

    public function contract(int $id): RedirectResponse
    {
        $booking = BookingRequest::where('client_id', auth()->id())
            ->whereIn('status', [
                BookingStatus::Paid->value,
                BookingStatus::Confirmed->value,
                BookingStatus::Completed->value,
            ])
            ->findOrFail($id);

        if (! $booking->contract_path || ! Storage::disk('local')->exists($booking->contract_path)) {
            return back()->with('error', 'Le contrat n\'est pas encore disponible. Veuillez réessayer dans quelques minutes.');
        }

        $token = Str::uuid()->toString();
        Cache::put("pdf_download:{$token}", [
            'type'       => 'contract',
            'booking_id' => $booking->id,
        ], now()->addMinutes(10));

        return redirect()->away(url("/api/v1/dl/{$token}"));
    }

    public function cancel(int $id): RedirectResponse
    {
        $booking = BookingRequest::where('client_id', auth()->id())
            ->whereIn('status', ['pending', 'accepted', 'paid', 'confirmed'])
            ->findOrFail($id);

        $status = $booking->status instanceof BookingStatus
            ? $booking->status
            : BookingStatus::from((string) $booking->status);

        // Pre-payment: just cancel directly, no refund
        if (in_array($status, [BookingStatus::Pending, BookingStatus::Accepted], strict: true)) {
            $booking->update(['status' => 'cancelled']);
            return back()->with('success', 'Réservation annulée avec succès.');
        }

        // Post-payment: apply graduated refund policy via BookingService
        try {
            $this->bookingService->cancelBooking($booking);

            $policy = $booking->fresh()->cancellation_policy_applied;
            $refund = $booking->fresh()->refund_amount ?? 0;

            $message = match ($policy) {
                'full_refund'    => 'Réservation annulée. Remboursement intégral de ' . number_format($refund, 0, ',', ' ') . ' FCFA en cours.',
                'partial_refund' => 'Réservation annulée. Remboursement partiel de ' . number_format($refund, 0, ',', ' ') . ' FCFA (50%) en cours.',
                default          => 'Réservation annulée.',
            };

            return back()->with('success', $message);
        } catch (BookingException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * POST /client/bookings/{booking}/dispute
     *
     * Ouvre un litige pour la réservation (client seulement, statut paid ou confirmed).
     */
    public function dispute(BookingRequest $booking): RedirectResponse
    {
        if ($booking->client_id !== auth()->id()) {
            abort(403);
        }

        $status = $booking->status instanceof BookingStatus
            ? $booking->status
            : BookingStatus::from((string) $booking->status);

        if ($status === BookingStatus::Disputed) {
            return back()->with('info', 'Un litige est déjà ouvert pour cette réservation.');
        }

        if (! in_array($status, [BookingStatus::Paid, BookingStatus::Confirmed])) {
            return back()->with('error', 'Un litige ne peut être ouvert que pour une réservation payée ou confirmée.');
        }

        $booking->update(['status' => 'disputed']);

        // Notify talent
        \App\Jobs\SendPushNotification::dispatch(
            $booking->talentProfile->user_id,
            'Litige ouvert',
            'Un client a ouvert un litige sur votre réservation #' . $booking->id . '.',
            ['type' => 'dispute_opened', 'booking_id' => $booking->id],
        );

        return back()->with('success', 'Litige ouvert. L\'équipe BookMi va examiner votre demande.');
    }

    public function pay(int $id): View
    {
        $booking = BookingRequest::where('client_id', auth()->id())
            ->where('status', 'accepted')
            ->with(['talentProfile.user', 'servicePackage'])
            ->findOrFail($id);
        return view('client.bookings.pay', compact('booking'));
    }

    public function processPayment(int $id, Request $request): RedirectResponse
    {
        $request->validate([
            'payment_method' => ['required', Rule::in(array_column(PaymentMethod::cases(), 'value'))],
            'phone_number'   => ['nullable', 'regex:/^\+?[0-9]{8,15}$/'],
        ]);

        $booking = BookingRequest::where('client_id', auth()->id())
            ->where('status', 'accepted')
            ->with('client')
            ->findOrFail($id);

        $method = PaymentMethod::from($request->payment_method);

        // For card/bank transfer, override callback URL to the web callback route
        if (! $method->isMobileMoney()) {
            config(['bookmi.payment.callback_url' => route('client.bookings.payment.callback')]);
        }

        try {
            $transaction = $this->paymentService->initiatePayment($booking, [
                'payment_method' => $request->payment_method,
                'phone_number'   => $request->phone_number ?? '',
            ]);

            if ($method->isMobileMoney()) {
                session(['_pay_ref' => $transaction->idempotency_key, '_pay_booking' => $id]);
                return redirect()->route('client.bookings.pay', $id)
                    ->with('payment_pending', true)
                    ->with('payment_method_label', $this->methodLabel($method));
            }

            // Card / Bank Transfer → redirect to Paystack hosted checkout
            $authUrl = $transaction->gateway_response['data']['authorization_url']
                ?? $transaction->gateway_response['authorization_url']
                ?? null;

            if ($authUrl) {
                return redirect()->away($authUrl);
            }

            return back()->with('error', 'Impossible d\'initialiser le paiement. Veuillez réessayer.');
        } catch (PaymentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception) {
            return back()->with('error', 'Erreur de paiement inattendue. Veuillez réessayer.');
        }
    }

    public function submitOtp(int $id, Request $request): RedirectResponse
    {
        $request->validate(['otp' => 'required|string|min:4|max:8']);

        $reference = session('_pay_ref');
        if (! $reference) {
            return redirect()->route('client.bookings.pay', $id)
                ->with('error', 'Session expirée. Veuillez recommencer le paiement.');
        }

        try {
            $this->paymentService->submitOtp($reference, $request->otp);
            session()->forget(['_pay_ref', '_pay_booking']);
            return redirect()->route('client.bookings.show', $id)
                ->with('success', 'Code OTP validé ! Votre paiement est en cours de traitement.');
        } catch (PaymentException) {
            return back()->with('error', 'Code OTP invalide ou expiré. Veuillez réessayer.');
        }
    }

    public function paymentCallback(Request $request): RedirectResponse
    {
        $reference = $request->string('trxref')->value()
            ?: $request->string('reference')->value();

        if (! $reference) {
            return redirect()->route('client.bookings')
                ->with('error', 'Référence de paiement manquante.');
        }

        $transaction = Transaction::where('gateway_reference', $reference)
            ->orWhere('idempotency_key', $reference)
            ->first();

        if (! $transaction) {
            return redirect()->route('client.bookings')
                ->with('info', 'Paiement en cours de vérification. Votre réservation sera mise à jour sous peu.');
        }

        $status   = $transaction->status instanceof \BackedEnum
            ? $transaction->status->value
            : (string) $transaction->status;
        $bookingId = $transaction->booking_request_id;

        return match ($status) {
            'succeeded' => redirect()->route('client.bookings.show', $bookingId)
                ->with('success', 'Paiement effectué avec succès ! Votre réservation est confirmée.'),
            'failed'    => redirect()->route('client.bookings.pay', $bookingId)
                ->with('error', 'Le paiement a échoué. Veuillez réessayer avec une autre méthode.'),
            default     => redirect()->route('client.bookings.show', $bookingId)
                ->with('info', 'Paiement en cours de traitement, votre réservation sera mise à jour.'),
        };
    }

    private function methodLabel(PaymentMethod $method): string
    {
        return match ($method) {
            PaymentMethod::OrangeMoney  => 'Orange Money',
            PaymentMethod::Wave         => 'Wave',
            PaymentMethod::MtnMomo      => 'MTN MoMo',
            PaymentMethod::MoovMoney    => 'Moov Money',
            PaymentMethod::Card         => 'Carte bancaire',
            PaymentMethod::BankTransfer => 'Virement bancaire',
        };
    }
}
