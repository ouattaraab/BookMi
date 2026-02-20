<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TalentNotificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TalentNotificationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'search_query' => 'required|string|max:200',
            'email'        => 'nullable|email|max:255|required_without:phone',
            'phone'        => 'nullable|string|max:20|required_without:email',
        ], [
            'email.required_without' => 'Veuillez saisir un email ou un numéro de téléphone.',
            'phone.required_without' => 'Veuillez saisir un email ou un numéro de téléphone.',
            'email.email'            => 'Adresse email invalide.',
        ]);

        $email = $request->filled('email') ? trim($request->email) : null;
        $phone = $request->filled('phone') ? trim($request->phone) : null;
        $query = trim($request->search_query);

        // Éviter les doublons (même recherche + même contact)
        $alreadyExists = TalentNotificationRequest::where('search_query', $query)
            ->whereNull('notified_at')
            ->where(function ($q) use ($email, $phone) {
                if ($email) {
                    $q->orWhere('email', $email);
                }
                if ($phone) {
                    $q->orWhere('phone', $phone);
                }
            })
            ->exists();

        if (! $alreadyExists) {
            TalentNotificationRequest::create([
                'search_query' => $query,
                'email'        => $email,
                'phone'        => $phone,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Parfait ! Vous serez notifié(e) dès que cet artiste rejoint BookMi.',
        ]);
    }
}
