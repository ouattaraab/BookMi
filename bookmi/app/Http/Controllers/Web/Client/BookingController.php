<?php

namespace App\Http\Controllers\Web\Client;

use App\Enums\PaymentMethod;
use App\Events\BookingCreated;
use App\Exceptions\PaymentException;
use App\Http\Controllers\Controller;
use App\Models\BookingRequest;
use App\Models\TalentProfile;
use App\Models\Transaction;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService) {}

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
            'event_location'     => ['required', 'string', 'max:255'],
            'message'            => ['nullable', 'string', 'max:1000'],
        ]);

        $talent = TalentProfile::with('servicePackages')->findOrFail($validated['talent_profile_id']);

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
        $totalAmount      = $cachetAmount + $commissionAmount;

        $booking = BookingRequest::create([
            'client_id'          => auth()->id(),
            'talent_profile_id'  => $validated['talent_profile_id'],
            'service_package_id' => $validated['service_package_id'] ?? null,
            'event_date'         => $validated['event_date'],
            'event_location'     => $validated['event_location'],
            'message'            => $validated['message'] ?? null,
            'status'             => 'pending',
            'cachet_amount'      => $cachetAmount,
            'commission_amount'  => $commissionAmount,
            'total_amount'       => $totalAmount,
        ]);

        BookingCreated::dispatch($booking);

        return redirect()->route('client.bookings')
            ->with('success', 'Votre demande de réservation a été envoyée ! Le talent vous répondra sous 24h.');
    }

    public function show(int $id): View
    {
        $booking = BookingRequest::where('client_id', auth()->id())
            ->with(['talentProfile.user', 'talentProfile.category', 'servicePackage'])
            ->findOrFail($id);
        return view('client.bookings.show', compact('booking'));
    }

    public function cancel(int $id): RedirectResponse
    {
        $booking = BookingRequest::where('client_id', auth()->id())
            ->whereIn('status', ['pending', 'accepted'])
            ->findOrFail($id);
        $booking->update(['status' => 'cancelled']);
        return back()->with('success', 'Réservation annulée avec succès.');
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
