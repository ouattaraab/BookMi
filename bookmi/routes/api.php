<?php

use App\Http\Controllers\Api\V1\AdminDisputeController;
use App\Http\Controllers\Api\V1\AdminReviewModerationController;
use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Api\V1\CheckInController;
use App\Http\Controllers\Api\V1\ManagerController;
use App\Http\Controllers\Api\V1\PortfolioController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\RevenueCertificateController;
use App\Http\Controllers\Api\V1\TrackingController;
use App\Http\Controllers\Api\V1\AdminRefundController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\AdminReportController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TwoFactorController;
use App\Http\Controllers\Api\V1\FinancialDashboardController;
use App\Http\Controllers\Api\V1\EscrowController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PaystackWebhookController;
use App\Http\Controllers\Api\V1\RescheduleController;
use App\Http\Controllers\Api\V1\BookingRequestController;
use App\Http\Controllers\Api\V1\CalendarSlotController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\HealthCheckController;
use App\Http\Controllers\Api\V1\TalentController;
use App\Http\Controllers\Api\V1\ServicePackageController;
use App\Http\Controllers\Api\V1\TalentProfileController;
use App\Http\Controllers\Api\V1\VerificationController;
use App\Http\Controllers\Api\V1\IdentityVerificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('/health', HealthCheckController::class)->name('health');

    Route::post('/auth/register', [AuthController::class, 'register'])
        ->middleware('throttle:auth')
        ->name('auth.register');

    Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp'])
        ->middleware('throttle:auth')
        ->name('auth.verify-otp');

    Route::post('/auth/resend-otp', [AuthController::class, 'resendOtp'])
        ->middleware('throttle:auth')
        ->name('auth.resend-otp');

    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:auth')
        ->name('auth.login');

    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:forgot-password')
        ->name('auth.forgot-password');

    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:auth')
        ->name('auth.reset-password');

    // 2FA — public endpoint (challenge verification)
    Route::post('/auth/2fa/verify', [TwoFactorController::class, 'verify'])
        ->middleware('throttle:auth')
        ->name('auth.2fa.verify');

    // Webhook Paystack (public — signature validée par middleware)
    Route::post('/webhooks/paystack', [PaystackWebhookController::class, 'handle'])
        ->middleware('paystack-webhook')
        ->name('webhooks.paystack');

    // Callback Paystack après 3D Secure (redirect — public, pas besoin d'auth)
    Route::get('/payments/callback', [PaymentController::class, 'callback'])
        ->name('payments.callback');

    // Téléchargement du reçu PDF — public mais URL signée (30 min)
    Route::get('/booking_requests/{booking}/receipt/download', [BookingRequestController::class, 'receiptDownload'])
        ->name('booking_requests.receipt.download');

    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');

    Route::get('/talents', [TalentController::class, 'index'])->name('talents.index');
    Route::get('/talents/{slug}', [TalentController::class, 'show'])->name('talents.show');
    Route::get('/talents/{talent}/calendar', [CalendarSlotController::class, 'index'])->name('calendar.index');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout'])
            ->middleware('throttle:auth')
            ->name('auth.logout');

        // 2FA — protected management endpoints
        Route::get('/auth/2fa/status', [TwoFactorController::class, 'status'])->name('auth.2fa.status');
        Route::post('/auth/2fa/setup/totp', [TwoFactorController::class, 'setupTotp'])->name('auth.2fa.setup.totp');
        Route::post('/auth/2fa/enable/totp', [TwoFactorController::class, 'enableTotp'])->name('auth.2fa.enable.totp');
        Route::post('/auth/2fa/setup/email', [TwoFactorController::class, 'setupEmail'])->name('auth.2fa.setup.email');
        Route::post('/auth/2fa/enable/email', [TwoFactorController::class, 'enableEmail'])->name('auth.2fa.enable.email');
        Route::post('/auth/2fa/disable', [TwoFactorController::class, 'disable'])->name('auth.2fa.disable');

        Route::get('/me', [AuthController::class, 'me'])
            ->middleware('throttle:auth')
            ->name('me');

        Route::patch('/me', [AuthController::class, 'updateProfile'])->name('me.update');
        Route::delete('/me/avatar', [AuthController::class, 'deleteAvatar'])->name('me.avatar.delete');
        Route::get('/me/stats', [AuthController::class, 'stats'])->name('me.stats');

        // Identity verification
        Route::get('/me/identity/status', [IdentityVerificationController::class, 'status'])->name('me.identity.status');
        Route::post('/me/identity/document', [IdentityVerificationController::class, 'submitDocument'])->name('me.identity.document');
        Route::post('/me/identity/selfie', [IdentityVerificationController::class, 'submitSelfie'])->name('me.identity.selfie');

        Route::post('/talent_profiles', [TalentProfileController::class, 'store'])->name('talent_profiles.store');
        Route::get('/talent_profiles/me', [TalentProfileController::class, 'showOwn'])->name('talent_profiles.me');
        Route::patch('/talent_profiles/me/payout_method', [TalentProfileController::class, 'updatePayoutMethod'])->name('talent_profiles.payout_method');
        Route::put('/talent_profiles/me/auto_reply', [TalentProfileController::class, 'updateAutoReply'])->name('talent_profiles.auto_reply');
        Route::patch('/talent_profiles/{talent_profile}', [TalentProfileController::class, 'update'])->name('talent_profiles.update');
        Route::delete('/talent_profiles/{talent_profile}', [TalentProfileController::class, 'destroy'])->name('talent_profiles.destroy');

        Route::post('/verifications', [VerificationController::class, 'store'])->name('verifications.store');
        Route::get('/verifications/me', [VerificationController::class, 'showOwn'])->name('verifications.me');

        Route::apiResource('service_packages', ServicePackageController::class)->except(['show']);

        // Calendrier disponibilités (talent uniquement)
        Route::post('/calendar_slots', [CalendarSlotController::class, 'store'])->name('calendar.store');
        Route::put('/calendar_slots/{slot}', [CalendarSlotController::class, 'update'])->name('calendar.update');
        Route::delete('/calendar_slots/{slot}', [CalendarSlotController::class, 'destroy'])->name('calendar.destroy');

        // Réservations
        Route::get('/booking_requests', [BookingRequestController::class, 'index'])->name('booking_requests.index');
        Route::post('/booking_requests', [BookingRequestController::class, 'store'])->name('booking_requests.store');
        Route::get('/booking_requests/{booking}', [BookingRequestController::class, 'show'])->name('booking_requests.show');
        Route::post('/booking_requests/{booking}/accept', [BookingRequestController::class, 'accept'])->name('booking_requests.accept');
        Route::post('/booking_requests/{booking}/reject', [BookingRequestController::class, 'reject'])->name('booking_requests.reject');
        Route::post('/booking_requests/{booking}/cancel', [BookingRequestController::class, 'cancel'])->name('booking_requests.cancel');
        Route::get('/booking_requests/{booking}/contract', [BookingRequestController::class, 'contract'])->name('booking_requests.contract');
        Route::get('/booking_requests/{booking}/receipt', [BookingRequestController::class, 'receipt'])->name('booking_requests.receipt');
        Route::post('/booking_requests/{booking}/reschedule', [RescheduleController::class, 'store'])->name('reschedule_requests.store');

        Route::post('/booking_requests/{booking}/confirm_delivery', [EscrowController::class, 'confirmDelivery'])->name('booking_requests.confirm_delivery');

        // Report de réservation
        Route::post('/reschedule_requests/{reschedule}/accept', [RescheduleController::class, 'accept'])->name('reschedule_requests.accept');
        Route::post('/reschedule_requests/{reschedule}/reject', [RescheduleController::class, 'reject'])->name('reschedule_requests.reject');

        // Paiements
        Route::post('/payments/initiate', [PaymentController::class, 'initiate'])
            ->middleware('throttle:payment')
            ->name('payments.initiate');

        Route::post('/payments/submit_otp', [PaymentController::class, 'submitOtp'])
            ->middleware('throttle:payment')
            ->name('payments.submit_otp');

        Route::get('/payments/{transaction}/status', [PaymentController::class, 'status'])
            ->name('payments.status');

        Route::post('/payments/cancel', [PaymentController::class, 'cancel'])
            ->name('payments.cancel');

        // Dashboard financier talent (Story 4.8)
        Route::get('/me/financial_dashboard', [FinancialDashboardController::class, 'dashboard'])
            ->name('me.financial_dashboard');
        Route::get('/me/payouts', [FinancialDashboardController::class, 'payouts'])
            ->name('me.payouts');

        // Favoris
        Route::get('/me/favorites', [FavoriteController::class, 'index'])
            ->name('favorites.index');
        Route::post('/talents/{talentProfileId}/favorite', [FavoriteController::class, 'store'])
            ->name('favorites.store');
        Route::delete('/talents/{talentProfileId}/favorite', [FavoriteController::class, 'destroy'])
            ->name('favorites.destroy');
        Route::get('/talents/{talentProfileId}/favorite', [FavoriteController::class, 'check'])
            ->name('favorites.check');

        // Notifications push (Story 5.4)
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::get('/me/broadcasts', [NotificationController::class, 'broadcasts'])->name('me.broadcasts');
        Route::put('/me/fcm_token', [NotificationController::class, 'updateFcmToken'])->name('me.fcm_token');

        // Messagerie interne (Story 5.1)
        Route::get('/conversations', [MessageController::class, 'index'])->name('conversations.index');
        Route::post('/conversations', [MessageController::class, 'store'])->name('conversations.store');
        Route::get('/conversations/{conversation}/messages', [MessageController::class, 'messages'])->name('conversations.messages');
        Route::post('/conversations/{conversation}/messages', [MessageController::class, 'send'])->name('conversations.send');
        Route::post('/conversations/{conversation}/read', [MessageController::class, 'read'])->name('conversations.read');
        Route::delete('/conversations/{conversation}', [MessageController::class, 'destroyConversation'])->name('conversations.destroy');
        Route::delete('/conversations/{conversation}/messages/{message}', [MessageController::class, 'destroyMessage'])->name('conversations.messages.destroy');

        // Suivi Jour J (Stories 6.1 & 6.2)
        Route::get('/booking_requests/{booking}/tracking', [TrackingController::class, 'index'])->name('tracking.index');
        Route::post('/booking_requests/{booking}/tracking', [TrackingController::class, 'update'])->name('tracking.update');
        Route::post('/booking_requests/{booking}/checkin', [CheckInController::class, 'store'])->name('checkin.store');

        // Portfolio — routes "me" spécifiques AVANT les routes paramétrées (évite {talentProfile}="me")
        Route::get('/talent_profiles/me/portfolio', [PortfolioController::class, 'indexOwn'])->name('portfolio.own');
        Route::post('/talent_profiles/me/portfolio', [PortfolioController::class, 'store'])->name('portfolio.store');
        Route::get('/talent_profiles/me/portfolio/pending', [PortfolioController::class, 'pendingSubmissions'])->name('portfolio.pending');
        Route::patch('/talent_profiles/me/portfolio/{portfolioItem}', [PortfolioController::class, 'update'])->name('portfolio.update');
        Route::post('/talent_profiles/me/portfolio/{portfolioItem}/approve', [PortfolioController::class, 'approve'])->name('portfolio.approve');
        Route::post('/talent_profiles/me/portfolio/{portfolioItem}/reject', [PortfolioController::class, 'reject'])->name('portfolio.reject');
        Route::delete('/talent_profiles/me/portfolio/{portfolioItem}', [PortfolioController::class, 'destroy'])->name('portfolio.destroy');

        // Portfolio post-prestation (Story 6.7) — route paramétrée après les routes "me"
        Route::get('/talent_profiles/{talentProfile}/portfolio', [PortfolioController::class, 'index'])->name('portfolio.index');
        // Client submits photos/videos after a completed prestation (Story 6.7 — enrichissement)
        Route::post('/booking_requests/{booking}/client-portfolio', [PortfolioController::class, 'storeClientSubmission'])->name('portfolio.client_store');

        // Earnings (talent)
        Route::get('me/earnings', [FinancialDashboardController::class, 'earnings'])
             ->name('api.v1.me.earnings');

        // Signalement problème (Story 6.6)
        Route::post('/booking_requests/{booking}/reports', [ReportController::class, 'store'])->name('reports.store');

        // Évaluations (Stories 6.4 & 6.5)
        Route::get('/booking_requests/{booking}/reviews', [ReviewController::class, 'index'])->name('reviews.index');
        Route::post('/booking_requests/{booking}/reviews', [ReviewController::class, 'store'])->name('reviews.store');

        // Story 8.9 — Report a review (any authenticated user)
        Route::post('/reviews/{review}/report', [AdminReviewModerationController::class, 'report'])->name('reviews.report');

        // Story 7.1 — Manager assignment (talent-side)
        Route::post('/talent_profiles/me/manager', [ManagerController::class, 'assignManager'])->name('talent.manager.assign');
        Route::delete('/talent_profiles/me/manager', [ManagerController::class, 'unassignManager'])->name('talent.manager.unassign');

        // Story 7.3 — Overload settings (talent-side)
        Route::put('/talent_profiles/me/overload_settings', [ManagerController::class, 'updateOverloadSettings'])->name('talent.overload_settings');

        // Story 7.8 — Analytics talent
        Route::get('/me/analytics', [AnalyticsController::class, 'dashboard'])->name('me.analytics');

        // Story 7.9 — Revenue certificate
        Route::get('/me/revenue_certificate', [RevenueCertificateController::class, 'download'])->name('me.revenue_certificate');

        // Stories 7.2 / 7.4 / 7.5 / 7.6 — Manager interface (manager-side)
        Route::middleware('manager')->prefix('manager')->name('manager.')->group(function () {
            // 7.2 — Interface multi-talents
            Route::get('/talents', [ManagerController::class, 'myTalents'])->name('talents.index');
            Route::get('/talents/{talent}', [ManagerController::class, 'talentStats'])->name('talents.stats');
            Route::get('/talents/{talent}/bookings', [ManagerController::class, 'talentBookings'])->name('talents.bookings');

            // 7.4 — Calendar management
            Route::post('/talents/{talent}/calendar_slots', [ManagerController::class, 'storeCalendarSlot'])->name('calendar.store');
            Route::put('/talents/{talent}/calendar_slots/{slot}', [ManagerController::class, 'updateCalendarSlot'])->name('calendar.update');
            Route::delete('/talents/{talent}/calendar_slots/{slot}', [ManagerController::class, 'destroyCalendarSlot'])->name('calendar.destroy');

            // 7.5 — Booking validation
            Route::post('/talents/{talent}/bookings/{booking}/accept', [ManagerController::class, 'acceptBooking'])->name('bookings.accept');
            Route::post('/talents/{talent}/bookings/{booking}/reject', [ManagerController::class, 'rejectBooking'])->name('bookings.reject');

            // 7.6 — Messages as talent
            Route::post('/conversations/{conversation}/messages', [ManagerController::class, 'sendMessage'])->name('conversations.send');
        });

        // Administration
        Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
            Route::post('/booking_requests/{booking}/refund', [AdminRefundController::class, 'refund'])
                ->name('booking_requests.refund');
            Route::get('/reports/financial', [AdminReportController::class, 'financial'])
                ->name('reports.financial');
            Route::get('/disputes/{booking}/messages', [AdminDisputeController::class, 'messages'])
                ->name('disputes.messages');
            // Story 8.9 — Moderation admin
            Route::get('/reviews/reported', [AdminReviewModerationController::class, 'reported'])->name('reviews.reported');
            Route::post('/reviews/{review}/approve', [AdminReviewModerationController::class, 'approve'])->name('reviews.approve');
            Route::delete('/reviews/{review}', [AdminReviewModerationController::class, 'destroy'])->name('reviews.destroy');
            Route::put('/reviews/{review}', [AdminReviewModerationController::class, 'update'])->name('reviews.update');
        });
    });
});
