<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\ForgotPasswordRequest;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\ResendOtpRequest;
use App\Http\Requests\Api\ResetPasswordRequest;
use App\Http\Requests\Api\VerifyOtpRequest;
use App\Models\BookingRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AuthController extends BaseController
{
    public function __construct(
        private readonly AuthService $authService,
    ) {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $user   = $this->authService->register($request->validated());
        $result = $this->authService->buildAuthResponsePublic($user);

        return $this->successResponse($result, 201);
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $result = $this->authService->verifyOtp(
            $request->validated('phone'),
            $request->validated('code'),
        );

        return $this->successResponse($result);
    }

    public function resendOtp(ResendOtpRequest $request): JsonResponse
    {
        $this->authService->resendOtp($request->validated('phone'));

        return $this->successResponse([
            'message' => 'Un nouveau code OTP a été envoyé.',
        ]);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->validated('email'),
            $request->validated('password'),
        );

        // 2FA challenge: return 200 with two_factor_required flag (not a full token)
        if (isset($result['two_factor_required']) && $result['two_factor_required'] === true) {
            return $this->successResponse($result);
        }

        return $this->successResponse($result);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->forgotPassword($request->validated('email'));

        return $this->successResponse([
            'message' => 'Si cette adresse email est associée à un compte, un lien de réinitialisation a été envoyé.',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->authService->resetPassword(
            $request->validated('email'),
            $request->validated('token'),
            $request->validated('password'),
        );

        return $this->successResponse([
            'message' => 'Votre mot de passe a été réinitialisé avec succès.',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->successResponse([
            'message' => 'Déconnexion réussie.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $profile = $this->authService->getProfile($request->user());

        return $this->successResponse($profile);
    }

    /**
     * GET /api/v1/me/stats
     * Returns exact booking and favorite counts for the authenticated user.
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $tp   = $user->talentProfile;

        $bookingCount = BookingRequest::when(
            $tp,
            fn ($q) => $q->where('talent_profile_id', $tp->id),
            fn ($q) => $q->where('client_id', $user->id)
        )->count();

        $favoriteCount = DB::table('user_favorites')
            ->where('user_id', $user->id)
            ->count();

        // Pending booking count (talent only)
        $pendingBookingCount = 0;
        if ($tp) {
            $pendingBookingCount = \App\Models\BookingRequest::where('talent_profile_id', $tp->id)
                ->where('status', \App\Enums\BookingStatus::Pending->value)
                ->count();
        }

        // Unread notification count
        $unreadNotificationCount = DB::table('push_notifications')
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        // Profile views (talent only) — 4 counts en 1 seule requête selectRaw
        $profileViewsToday = 0;
        $profileViewsWeek  = 0;
        $profileViewsMonth = 0;
        $profileViewsTotal = 0;
        if ($tp) {
            $now   = now();
            $views = \App\Models\ProfileView::where('talent_profile_id', $tp->id)
                ->selectRaw(
                    'COUNT(*) as total,' .
                    'SUM(CASE WHEN DATE(viewed_at) = DATE(?) THEN 1 ELSE 0 END) as today,' .
                    'SUM(CASE WHEN viewed_at >= ? THEN 1 ELSE 0 END) as this_week,' .
                    'SUM(CASE WHEN viewed_at >= ? THEN 1 ELSE 0 END) as this_month',
                    [$now->toDateString(), $now->copy()->startOfWeek(), $now->copy()->startOfMonth()]
                )
                ->first();
            $profileViewsToday = (int) ($views->today ?? 0);
            $profileViewsWeek  = (int) ($views->this_week ?? 0);
            $profileViewsMonth = (int) ($views->this_month ?? 0);
            $profileViewsTotal = (int) ($views->total ?? 0);
        }

        return $this->successResponse([
            'booking_count'              => $bookingCount,
            'favorite_count'             => $favoriteCount,
            'pending_booking_count'      => $pendingBookingCount,
            'unread_notification_count'  => $unreadNotificationCount,
            'profile_views_today'        => $profileViewsToday,
            'profile_views_week'         => $profileViewsWeek,
            'profile_views_month'        => $profileViewsMonth,
            'profile_views_total'        => $profileViewsTotal,
        ]);
    }

    /**
     * PATCH /api/v1/me
     * Update the authenticated user's first name, last name and/or avatar.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:60',
            'last_name'  => 'sometimes|string|max:60',
            'avatar'     => 'sometimes|image|max:2048',
        ]);

        $user = $request->user();

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $path              = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $path;
        }

        $profile = $this->authService->updateProfile($user, $validated);

        return $this->successResponse($profile);
    }

    /**
     * POST /api/v1/me/deactivate
     * Deactivate the authenticated user's account and revoke all tokens.
     */
    public function deactivate(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->update(['is_active' => false]);
        $user->tokens()->delete();

        return $this->successResponse(['message' => 'Votre compte a été désactivé.']);
    }

    /**
     * DELETE /api/v1/me/avatar
     * Remove the authenticated user's avatar.
     */
    public function deleteAvatar(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->update(['avatar' => null]);
        }

        return $this->successResponse(['message' => 'Avatar supprimé.']);
    }
}
