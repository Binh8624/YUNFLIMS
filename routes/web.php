
<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\CinemaController;
use App\Http\Controllers\Admin\ConcessionOrderController;
use App\Http\Controllers\Admin\ConcessionProductController;
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
use App\Http\Controllers\PaymentCallbackController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TicketVerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/services', [ServiceController::class, 'index'])
    ->name('services.index');

Route::get('/services/vouchers', [ServiceController::class, 'vouchers'])
    ->name('services.vouchers');

Route::get('/services/gift-cards', [ServiceController::class, 'giftCards'])
    ->name('services.gift-cards');


/*
|--------------------------------------------------------------------------
| Legal and Static Pages
|--------------------------------------------------------------------------
*/

// Nhiệm vụ 9: Chính sách và pháp lý
Route::view('/terms', 'pages.terms')->name('pages.terms');

Route::view('/privacy', 'pages.privacy')->name('pages.privacy');

Route::view('/refund-policy', 'pages.refund')->name('pages.refund');

Route::view('/age-rating', 'pages.age-rating')->name('pages.age-rating');

// Nhiệm vụ 10: Trang nội dung
Route::view('/about', 'pages.about')->name('pages.about');

Route::view('/contact', 'pages.contact')->name('pages.contact');

Route::view('/support', 'pages.support')->name('pages.support');

Route::view('/careers', 'pages.careers')->name('pages.careers');


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

Route::get('/movies/{movie}', [HomeController::class, 'show'])
    ->name('movies.show');

/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])
        ->name('register');

    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:5,1')
        ->name('register.store');

    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:5,1')
            ->name('login.store');
});

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::get('/payment/return/{gateway}', [PaymentCallbackController::class, 'return'])
    ->middleware('throttle:60,1')
    ->name('payment.return');

Route::match(['get', 'post'], '/payment/webhook/{gateway}', [PaymentCallbackController::class, 'webhook'])
    ->middleware('throttle:120,1')
    ->name('payment.webhook');


Route::post('/movies/{movie}/reviews', [ReviewController::class, 'store'])
    ->middleware(['auth', 'active', 'throttle:10,1'])
    ->name('movies.reviews.store');

Route::post('/movies/{movie}/favorite', [FavoriteController::class, 'toggle'])
    ->middleware(['auth', 'active', 'throttle:30,1'])
    ->name('movies.favorite');

/*
|--------------------------------------------------------------------------
| Customer Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active'])->group(function () {

    // Profile
    Route::get('/profile', [AuthController::class, 'profile'])
        ->name('profile');

    Route::put('/profile', [AuthController::class, 'updateProfile'])
        ->middleware('throttle:10,1')
        ->name('profile.update');

    Route::put('/profile/password', [AuthController::class, 'updatePassword'])
        ->middleware('throttle:5,1')
        ->name('profile.password');

    // Favorites
    Route::get('/favorites', [FavoriteController::class, 'index'])
        ->name('favorites.index');

    // Booking history
    Route::get('/bookings', [BookingController::class, 'history'])
        ->name('bookings.history');

    // Seat selection
    Route::get('/showtimes/{showtime}/seats', [BookingController::class, 'selectSeats'])
        ->name('bookings.seats');

    Route::post('/showtimes/{showtime}/seats/check', [BookingController::class, 'checkSeatSelection'])
        ->middleware('throttle:60,1')
        ->name('bookings.seats.check');

    // Create booking
    Route::post('/showtimes/{showtime}/bookings', [BookingController::class, 'store'])
        ->name('bookings.store');

    // Concession selection - integrated into booking
    Route::get('/bookings/{booking}/concessions', [BookingController::class, 'concessions'])
        ->name('bookings.concessions');

    Route::post('/bookings/{booking}/concessions', [BookingController::class, 'saveConcessions'])
        ->middleware('throttle:30,1')
        ->name('bookings.concessions.save');

    // Booking cancellation
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])
        ->name('bookings.cancel');

    // Booking payment
    Route::get('/bookings/{booking}/payment', [BookingController::class, 'payment'])
        ->name('bookings.payment');

    Route::post('/bookings/{booking}/payment', [BookingController::class, 'processPayment'])
        ->middleware('throttle:10,1')
        ->name('bookings.payment.process');

    Route::get('/payment/mock/{payment}/checkout', [
        PaymentCallbackController::class,
        'mockCheckout',
    ])->name('payment.mock.checkout');

    Route::post('/payment/mock/{payment}/simulate', [
        PaymentCallbackController::class,
        'mockSimulate',
    ])->middleware('throttle:10,1')->name('payment.mock.simulate');

    // Booking details
    Route::get('/bookings/{booking}', [BookingController::class, 'show'])
        ->name('bookings.show');

});

/*
|--------------------------------------------------------------------------
| Ticket Check-in
|--------------------------------------------------------------------------
*/

Route::post('/tickets/{bookingCode}/check-in', [TicketVerificationController::class, 'checkIn'])
    ->middleware(['auth', 'active', 'admin', 'can:check-tickets'])
    ->name('tickets.checkin');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'active', 'admin', 'admin.audit'])
    ->group(function () {

        Route::get('/', [DashboardController::class, 'index'])
            ->name('dashboard');

        // Activity logs
        Route::middleware('can:view-audit-logs')->group(function () {
            Route::get('/activity-logs', [ActivityLogController::class, 'index'])
                ->name('activity-logs.index');
        });

        // Services and concessions
        Route::middleware('can:manage-services')->group(function () {

            Route::patch(
                '/concession-products/{concessionProduct}/toggle-status',
                [ConcessionProductController::class, 'toggleStatus']
            )->name('concession-products.toggle-status');

            Route::resource('concession-products', ConcessionProductController::class)
                ->except(['show'])
                ->parameters([
                    'concession-products' => 'concessionProduct',
                ]);

            Route::get('/concession-orders', [ConcessionOrderController::class, 'index'])
                ->name('concession-orders.index');

            Route::get('/concession-orders/{concessionOrder}', [ConcessionOrderController::class, 'show'])
                ->name('concession-orders.show');

            Route::patch('/concession-orders/{concessionOrder}/status', [ConcessionOrderController::class, 'updateStatus'])
                ->name('concession-orders.update-status');

            Route::patch('/concession-orders/{concessionOrder}/payment', [ConcessionOrderController::class, 'updatePaymentStatus'])
                ->name('concession-orders.update-payment');
        });

        // Cinema management
        Route::middleware('can:manage-cinema')->group(function () {

            Route::get('/showtimes/auto', [ScheduleGeneratorController::class, 'index'])
                ->name('showtimes.auto');

            Route::post('/showtimes/auto/preview', [ScheduleGeneratorController::class, 'preview'])
                ->name('showtimes.auto.preview');

            Route::post('/showtimes/auto', [ScheduleGeneratorController::class, 'store'])
                ->name('showtimes.auto.store');

            Route::resource('showtimes', ShowtimeController::class)
                ->except(['show']);

            Route::resource('cinemas', CinemaController::class)
                ->except(['show']);

            Route::resource('rooms', RoomController::class)
                ->except(['show']);

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

        // Booking management
        Route::middleware('can:manage-bookings')->group(function () {

            Route::get('/bookings', [AdminBookingController::class, 'index'])
                ->name('bookings.index');

            Route::get('/bookings/{booking}', [AdminBookingController::class, 'show'])
                ->name('bookings.show');

            Route::patch('/bookings/{booking}/cancel', [AdminBookingController::class, 'cancel'])
                ->name('bookings.cancel');
        });

        // Payment management
        Route::middleware('can:manage-payments')->group(function () {
            Route::get('/payments', [PaymentController::class, 'index'])
                ->name('payments.index');
        });

        // Reports
        Route::middleware('can:manage-reports')->group(function () {
            Route::get('/reports', [ReportController::class, 'index'])
                ->name('reports.index');

            Route::get('/reports/export', [ReportController::class, 'export'])
                ->name('reports.export');
        });

        // Content management
        Route::middleware('can:manage-content')->group(function () {

            Route::resource('movies', MovieController::class)
                ->except(['show']);

            Route::get('/reviews', [AdminReviewController::class, 'index'])
                ->name('reviews.index');

            Route::delete('/reviews/{review}', [AdminReviewController::class, 'destroy'])
                ->name('reviews.destroy');
        });

        Route::middleware('can:manage-formats')->group(function () {
            Route::get('/formats', [FormatController::class, 'index'])
                ->name('formats.index');

            Route::post('/formats', [FormatController::class, 'store'])
                ->name('formats.store');

            Route::put('/formats/{format}', [FormatController::class, 'update'])
                ->name('formats.update');

            Route::delete('/formats/{format}', [FormatController::class, 'destroy'])
                ->name('formats.destroy');
        });

        Route::middleware('can:manage-pricing')->group(function () {
            Route::get('/pricing', [PricingRuleController::class, 'index'])
                ->name('pricing.index');

            Route::post('/pricing', [PricingRuleController::class, 'store'])
                ->name('pricing.store');

            Route::put('/pricing/{pricingRule}', [PricingRuleController::class, 'update'])
                ->name('pricing.update');

            Route::delete('/pricing/{pricingRule}', [PricingRuleController::class, 'destroy'])
                ->name('pricing.destroy');

            Route::post('/pricing/holidays', [PricingRuleController::class, 'storeHoliday'])
                ->name('pricing.holidays.store');

            Route::delete('/pricing/holidays/{pricingHoliday}', [PricingRuleController::class, 'destroyHoliday'])
                ->name('pricing.holidays.destroy');
        });

        // User management
        Route::middleware('can:manage-users')->group(function () {

            Route::get('/users', [UserController::class, 'index'])
                ->name('users.index');

            Route::post('/users', [UserController::class, 'storeStaff'])
                ->name('users.store');

            Route::get('/users/{user}', [UserController::class, 'show'])
                ->name('users.show');

            Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])
                ->name('users.role');

            Route::patch('/users/{user}/active', [UserController::class, 'toggleActive'])
                ->name('users.active');
        });
    });
