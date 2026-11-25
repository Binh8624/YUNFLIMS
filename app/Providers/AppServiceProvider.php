<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Gate::define('access-admin', fn (User $user) => $user->isAdminStaff());
        Gate::define('manage-cinema', fn (User $user) => $user->hasPermission('manage_cinema'));
        Gate::define('manage-content', fn (User $user) => $user->hasPermission('manage_content'));
        Gate::define('manage-bookings', fn (User $user) => $user->hasPermission('manage_bookings'));
        Gate::define('manage-payments', fn (User $user) => $user->hasPermission('manage_payments'));
        Gate::define('manage-reports', fn (User $user) => $user->hasPermission('manage_reports'));
        Gate::define('manage-users', fn (User $user) => $user->hasPermission('manage_users'));
        Gate::define('check-tickets', fn (User $user) => $user->hasPermission('check_tickets'));
        Gate::define('manage-formats', fn (User $user) => in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN], true));
        Gate::define('manage-pricing', fn (User $user) => in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN], true));
        Gate::define('view-audit-logs', fn (User $user) => $user->role === User::ROLE_SUPER_ADMIN);
        Gate::define('manage-services', fn (User $user) => $user->hasPermission('manage_services'));
        Gate::define('manage-promotions', fn (User $user) => $user->hasPermission('manage_promotions'));
        Gate::define('manage-members', fn (User $user) => $user->hasPermission('manage_members'));
        Gate::define('manage-gift-cards', fn (User $user) => $user->hasPermission('manage_gift_cards'));

        Password::defaults(fn () => Password::min(12)->mixedCase()->numbers()->symbols());

        Blade::if('admin', function () {
            return auth()->check() && auth()->user()->isAdminStaff();
        });
    }
}
