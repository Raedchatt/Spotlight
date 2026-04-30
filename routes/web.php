<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\EvenementController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\MediaUploadController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\LegalController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Route::has('register'),
    ]);
})->name('home');

Route::get('/organizer/{id}', [\App\Http\Controllers\OrganizerProfileController::class, 'show'])->name('organizer.profile');
Route::get('/participant/{id}', [\App\Http\Controllers\ParticipantProfileController::class, 'show'])->name('participant.profile');
Route::get('/events/{id}', [EvenementController::class, 'show'])->name('events.show');

Route::get('/discovery', function () {
    return Inertia::render('Events/Discovery');
})->name('discovery');

// Legal Pages
Route::get('/about-us', [LegalController::class, 'aboutUs'])->name('about-us');
Route::get('/privacy-policy', [LegalController::class, 'privacyPolicy'])->name('privacy-policy');
Route::get('/terms-of-service', [LegalController::class, 'termsOfService'])->name('terms-of-service');

Route::get('/login', function () {
    return Inertia::render('auth/Login');
})->name('login');

Route::get('/register', function () {
    return Inertia::render('auth/Register');
})->name('register');

// Auth Routes (moved from api.php for session support)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Google Auth Routes
Route::get('/auth/google', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

// Blocked user page (accessible while still logged in)
Route::middleware(['auth'])->get('/blocked', function () {
    $user = \Illuminate\Support\Facades\Auth::user();
    return \Inertia\Inertia::render('Blocked', [
        'blockedUntil' => $user->blocked_until ? $user->blocked_until->toISOString() : null,
    ]);
})->name('blocked');

Route::prefix('web-api')->group(function () {
    Route::get('/events', [EvenementController::class, 'index']);
    Route::get('/events/search', [EvenementController::class, 'search']);
    Route::get('/events/{id}', [EvenementController::class, 'show']);
    Route::get('/events/{id}/management-stats', [EvenementController::class, 'managementStats'])->middleware('auth');
    Route::get('/categories', [\App\Http\Controllers\CategoryController::class, 'index']);
});

Route::middleware(['auth'])->group(function () {
    // API endpoints masquerading in the web middleware for session persistence
    Route::prefix('web-api')->group(function () {
        Route::post('/events', [EvenementController::class, 'store']);
        Route::put('/events/{id}', [EvenementController::class, 'update']);
        Route::delete('/events/{id}', [EvenementController::class, 'destroy']);
        Route::patch('/events/{id}/ouvrir', [EvenementController::class, 'ouvrirReservation']);
        Route::patch('/events/{id}/fermer', [EvenementController::class, 'fermerReservation']);
        Route::post('/events/{id}/cancel', [EvenementController::class, 'cancelWithRefund']);

        Route::post('/reservations', [ReservationController::class, 'store']);
        Route::get('/my-reservations', [ReservationController::class, 'chercherReservationParParticipant']);
        Route::get('/events/{evenement}/reservations', [ReservationController::class, 'chercherReservationParEvenement']);
        Route::patch('/reservations/{reservation}/annuler', [ReservationController::class, 'annuler']);

        // Stripe Payments
        Route::post('/paiement/checkout/{reservation}', [\App\Http\Controllers\StripeController::class, 'createCheckoutSession'])->name('paiement.checkout');

        // Organizers Profile Management (via Session)
        Route::put('/organisateurs/{organisateur}', [\App\Http\Controllers\OrganisateurController::class, 'update']);

        // Stripe Connect Onboarding
        Route::post('/stripe/connect', [\App\Http\Controllers\StripeConnectController::class, 'connect'])->name('stripe.connect');
        Route::get('/stripe/connect/return', [\App\Http\Controllers\StripeConnectController::class, 'returnHandler'])->name('stripe.connect.return');
        Route::get('/stripe/connect/refresh', [\App\Http\Controllers\StripeConnectController::class, 'refreshHandler'])->name('stripe.connect.refresh');

        // Messages (Session-based for Inertia)
        Route::get('/messages/recent', [MessageController::class, 'recent']);
        Route::post('/messages', [MessageController::class, 'envoyer'])->name('messages.envoyer');

        // AI Suggestions
        Route::post('/ai/suggest-event', [\App\Http\Controllers\GenerateImagesController::class, 'generate']);
        Route::post('/ai/upload-image', [\App\Http\Controllers\GenerateImagesController::class, 'uploadSelectedImage']);

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/{id}', [NotificationController::class, 'show']);
        Route::patch('/notifications/{id}/read', [NotificationController::class, 'marquerCommeLue']);
        Route::patch('/notifications/read-all', [NotificationController::class, 'marquerToutesCommeLues']);

        // Collaboration
        Route::get('/my-collaborations', [\App\Http\Controllers\CollaborationController::class, 'myCollaborations']);
        Route::get('/organizers/search', [\App\Http\Controllers\CollaborationController::class, 'searchOrganizers']);
        Route::get('/events/{id}/collaborators', [\App\Http\Controllers\CollaborationController::class, 'index']);
        Route::post('/events/{id}/collaborators/invite', [\App\Http\Controllers\CollaborationController::class, 'invite']);
        Route::post('/events/{id}/collaborators/accept', [\App\Http\Controllers\CollaborationController::class, 'accept']);
        Route::post('/events/{id}/collaborators/reject', [\App\Http\Controllers\CollaborationController::class, 'reject']);
        Route::patch('/events/{id}/collaborators/{collaboratorId}/toggle-permission', [EvenementController::class, 'toggleCollaboratorPermission']);

        // Admin - Block/Unblock
        Route::post('/admin/block/{user}', [\App\Http\Controllers\Admin\AdminUserController::class, 'bloquerUtilisateur'])->name('admin.users.block');
        Route::post('/admin/unblock/{user}', [\App\Http\Controllers\Admin\AdminUserController::class, 'debloquerUtilisateur'])->name('admin.users.unblock');
    });

    // ─────────────────────────────────────────────────────────────────────────
    // 5. ADMIN SECTION (administrateur)
    // ─────────────────────────────────────────────────────────────────────────
    Route::middleware(['role:administrateur'])->prefix('admin')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('admin.dashboard');

        // Admin - User Management
        Route::get('/users', [\App\Http\Controllers\Admin\AdminUserController::class, 'index'])->name('admin.users.index');
        Route::post('/users', [\App\Http\Controllers\Admin\AdminUserController::class, 'store'])->name('admin.users.store');
        Route::put('/users/{user}', [\App\Http\Controllers\Admin\AdminUserController::class, 'update'])->name('admin.users.update');
        Route::delete('/users/{user}', [\App\Http\Controllers\Admin\AdminUserController::class, 'destroy'])->name('admin.users.destroy');

        // Admin - Event Management
        Route::get('/events', [\App\Http\Controllers\Admin\AdminEventController::class, 'index'])->name('admin.events.index');
        Route::get('/events/all', [\App\Http\Controllers\Admin\AdminEventController::class, 'allEvents'])->name('admin.events.all');
        Route::get('/organizers/search', [\App\Http\Controllers\Admin\AdminEventController::class, 'searchOrganizers'])->name('admin.organizers.search');
        Route::patch('/events/{event}/approve', [\App\Http\Controllers\Admin\AdminEventController::class, 'approve'])->name('admin.events.approve');
        Route::patch('/events/{event}/reject', [\App\Http\Controllers\Admin\AdminEventController::class, 'reject'])->name('admin.events.reject');
        Route::delete('/events/{event}', [\App\Http\Controllers\Admin\AdminEventController::class, 'destroy'])->name('admin.events.destroy');

        // Admin - Financial Management
        Route::get('/financials', [\App\Http\Controllers\Admin\AdminFinancialController::class, 'index'])->name('admin.financials.index');
        Route::post('/financials/organizer/{event}/pay', [\App\Http\Controllers\Admin\AdminFinancialController::class, 'pay'])->name('admin.financials.pay');
        Route::post('/financials/organizer/{event}/refund', [\App\Http\Controllers\Admin\AdminFinancialController::class, 'refund'])->name('admin.financials.refund');
        Route::post('/financials/affiliate/{commission}/approve', [\App\Http\Controllers\Admin\AdminFinancialController::class, 'approveAffiliate'])->name('admin.financials.approve');

        // Admin - Reservation Management
        Route::get('/reservations', [\App\Http\Controllers\Admin\AdminReservationController::class, 'index'])->name('admin.reservations.index');
        Route::post('/reservations', [\App\Http\Controllers\Admin\AdminReservationController::class, 'store'])->name('admin.reservations.store');
        Route::get('/reservations/search-users', [\App\Http\Controllers\Admin\AdminReservationController::class, 'searchUsers'])->name('admin.reservations.search-users');
        Route::get('/reservations/search-events', [\App\Http\Controllers\Admin\AdminReservationController::class, 'searchEvents'])->name('admin.reservations.search-events');
        Route::patch('/reservations/{reservation}/cancel', [\App\Http\Controllers\Admin\AdminReservationController::class, 'cancel'])->name('admin.reservations.cancel');
    });

    // ─────────────────────────────────────────────────────────────────────────
    // 6. ORGANIZER SECTION (organisateur)
    // ─────────────────────────────────────────────────────────────────────────
    Route::middleware(['role:organisateur'])->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

        Route::prefix('dashboard')->group(function () {
            Route::get('/events', function () {
                return Inertia::render('Events/EventsList');
            })->name('events.index');

            Route::get('/collaborations', function () {
                return Inertia::render('Events/Collaborations');
            })->name('collaborations.index');

            Route::get('/events/create', function () {
                return Inertia::render('Events/CreateEvent');
            })->name('events.create');

            Route::get('/events/{id}/edit', function ($id) {
                return Inertia::render('Events/EditEvent', ['id' => $id]);
            })->name('events.edit');
        });
    });

    // ─────────────────────────────────────────────────────────────────────────
    // 7. AFFILIATE SECTION (revendeur)
    // ─────────────────────────────────────────────────────────────────────────
    Route::middleware(['role:revendeur'])->group(function () {
        Route::get('/affiliate/dashboard', [\App\Http\Controllers\Affiliate\AffiliateDashboardController::class, 'index'])->name('affiliate.dashboard');
    });

    // ─────────────────────────────────────────────────────────────────────────
    // 8. SHARED & PARTICIPANT ROUTES
    // ─────────────────────────────────────────────────────────────────────────
    Route::get('/dashboard/reservations', function () {
        return Inertia::render('Events/MyReservations');
    })->name('reservations.index');

    Route::get('/notifications', function () {
        return Inertia::render('Notifications/Index');
    })->name('notifications.index');

    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{user}', [MessageController::class, 'show'])->name('messages.show');

    // Media Uploads
    Route::post('/upload/image', [MediaUploadController::class, 'uploadImage']);
    Route::post('/upload/video', [MediaUploadController::class, 'uploadVideo']);
    Route::delete('/upload/media', [MediaUploadController::class, 'deleteMedia']);

    // Stripe Payment Success/Cancel
    Route::get('/paiement/success', [\App\Http\Controllers\StripeController::class, 'success'])->name('paiement.success');
    Route::get('/paiement/cancel', [\App\Http\Controllers\StripeController::class, 'cancel'])->name('paiement.cancel');

    // Billet Routes
    Route::get('/tickets/{billet}', [\App\Http\Controllers\BilletController::class, 'show'])->name('billet.show');
    Route::get('/tickets/{billet}/download', [\App\Http\Controllers\BilletController::class, 'downloadPdf'])->name('billet.download');
});

require __DIR__ . '/settings.php';