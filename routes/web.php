<?php

use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\CinemaController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FormatController;
use App\Http\Controllers\Admin\MovieController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PricingRuleController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\ScheduleGeneratorController;
use App\Http\Controllers\Admin\SeatController;
use App\Http\Controllers\Admin\ShowtimeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TicketVerificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/services', [ServiceController::class, 'index'])
    ->name('services.index');

Route::get('/services/vouchers', [ServiceController::class, 'vouchers'])
    ->name('services.vouchers');

Route::get('/services/gift-cards', [ServiceController::class, 'giftCards'])
    ->name('services.gift-cards');

Route::get('/showtimes', [BookingController::class, 'index'])
    ->name('showtimes.index');

Route::get('/movies', [HomeController::class, 'movies'])
    ->name('movies.index');

Route::get('/movies/suggestions', [HomeController::class, 'suggestions'])
    ->middleware('throttle:60,1')
    ->name('movies.suggestions');

Route::get('/tickets/{bookingCode}/verify', [TicketVerificationController::class, 'show'])
    ->middleware('throttle:30,1')
    ->name('tickets.verify');

Route::post('/tickets/{bookingCode}/check-in', [TicketVerificationController::class, 'checkIn'])
    ->middleware(['auth', 'active', 'admin', 'can:check-tickets'])
    ->name('tickets.checkin');

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])
        ->name('register');

    Route::post('/register', [AuthController::class, 'register'])
        ->name('register.store');

    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.store');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::get('/movies/{movie}', [HomeController::class, 'show'])
    ->name('movies.show');

Route::post('/movies/{movie}/reviews', [ReviewController::class, 'store'])
    ->middleware(['auth', 'active', 'throttle:10,1'])
    ->name('movies.reviews.store');

Route::post('/movies/{movie}/favorite', [FavoriteController::class, 'toggle'])
->middleware(['auth', 'active'])
->name('movies.favorite');

Route::middleware(['auth', 'active'])->group(function () {
Route::get('/profile', [AuthController::class, 'profile'])
    ->name('profile');

Route::get('/favorites', [FavoriteController::class, 'index'])
    ->name('favorites.index');

    Route::put('/profile', [AuthController::class, 'updateProfile'])
        ->name('profile.update');

    Route::put('/profile/password', [AuthController::class, 'updatePassword'])
        ->name('profile.password');

    Route::get('/bookings', [
        BookingController::class,
        'history',
    ])->name('bookings.history');

    Route::get('/showtimes/{showtime}/seats', [
        BookingController::class,
        'selectSeats',
    ])->name('bookings.seats');

    Route::post('/showtimes/{showtime}/seats/check', [
        BookingController::class,
        'checkSeatSelection',
    ])->middleware('throttle:60,1')->name('bookings.seats.check');

    Route::post('/showtimes/{showtime}/bookings', [
        BookingController::class,
        'store',
    ])->name('bookings.store');

    Route::post('/bookings/{booking}/cancel', [
        BookingController::class,
        'cancel',
    ])->name('bookings.cancel');

    Route::get('/bookings/{booking}/payment', [
        BookingController::class,
        'payment',
    ])->name('bookings.payment');

    Route::post('/bookings/{booking}/payment', [
        BookingController::class,
        'processPayment',
    ])->middleware('throttle:10,1')->name('bookings.payment.process');

    Route::get('/bookings/{booking}', [
        BookingController::class,
        'show',
    ])->name('bookings.show');
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'active', 'admin', 'admin.audit'])
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::middleware('can:access-admin')->group(function () {
            Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
        });

        Route::middleware('can:manage-cinema')->group(function () {
            Route::get('/showtimes/auto', [ScheduleGeneratorController::class, 'index'])
                ->name('showtimes.auto');
            Route::post('/showtimes/auto/preview', [ScheduleGeneratorController::class, 'preview'])
                ->name('showtimes.auto.preview');
            Route::post('/showtimes/auto', [ScheduleGeneratorController::class, 'store'])
                ->name('showtimes.auto.store');

            Route::resource('showtimes', ShowtimeController::class)
                ->except(['show']);

            Route::resource('cinemas', CinemaController::class)->except(['show']);
            Route::resource('rooms', RoomController::class)->except(['show']);

            Route::get('/rooms/{room}/seats', [SeatController::class, 'index'])
                ->name('rooms.seats.index');
            Route::post('/rooms/{room}/seats', [SeatController::class, 'store'])
                ->name('rooms.seats.store');
            Route::post('/rooms/{room}/seats/rows', [SeatController::class, 'storeRow'])
                ->name('rooms.seats.rows.store');
            Route::delete('/rooms/{room}/seats/rows/{rowNumber}', [SeatController::class, 'destroyRow'])
                ->name('rooms.seats.rows.destroy');
            Route::post('/rooms/{room}/seats/columns', [SeatController::class, 'storeColumn'])
                ->name('rooms.seats.columns.store');
            Route::delete('/rooms/{room}/seats/columns/{seatNumber}', [SeatController::class, 'destroyColumn'])
                ->name('rooms.seats.columns.destroy');
            Route::put('/rooms/{room}/seats/{seat}', [SeatController::class, 'update'])
                ->name('rooms.seats.update');
            Route::delete('/rooms/{room}/seats/{seat}', [SeatController::class, 'destroy'])
                ->name('rooms.seats.destroy');
        });

        Route::middleware('can:manage-bookings')->group(function () {
            Route::get('/bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
            Route::get('/bookings/{booking}', [AdminBookingController::class, 'show'])->name('bookings.show');
            Route::patch('/bookings/{booking}/cancel', [AdminBookingController::class, 'cancel'])->name('bookings.cancel');
        });

        Route::middleware('can:manage-payments')->group(function () {
            Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        });

        Route::middleware('can:manage-reports')->group(function () {
            Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
            Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
        });

        Route::middleware('can:manage-content')->group(function () {
            Route::resource('movies', MovieController::class)
                ->except(['show']);

            Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
            Route::delete('/reviews/{review}', [AdminReviewController::class, 'destroy'])->name('reviews.destroy');

            Route::get('/formats', [FormatController::class, 'index'])->name('formats.index');
            Route::post('/formats', [FormatController::class, 'store'])->name('formats.store');
            Route::put('/formats/{format}', [FormatController::class, 'update'])->name('formats.update');
            Route::delete('/formats/{format}', [FormatController::class, 'destroy'])->name('formats.destroy');

            Route::get('/pricing', [PricingRuleController::class, 'index'])->name('pricing.index');
            Route::post('/pricing', [PricingRuleController::class, 'store'])->name('pricing.store');
            Route::put('/pricing/{pricingRule}', [PricingRuleController::class, 'update'])->name('pricing.update');
            Route::delete('/pricing/{pricingRule}', [PricingRuleController::class, 'destroy'])->name('pricing.destroy');
        });

        Route::middleware('can:manage-users')->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
            Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.role');
            Route::patch('/users/{user}/active', [UserController::class, 'toggleActive'])->name('users.active');
        });
    });
