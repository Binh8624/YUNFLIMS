<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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

        Blade::if('admin', function () {
            return auth()->check() && auth()->user()->isAdminStaff();
        });
    }
}
