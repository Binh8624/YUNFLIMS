<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    public function test_guest_is_redirected_from_admin_dashboard(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_admin_dashboard(): void
    {
        $customer = new User([
            'name' => 'Test Customer',
            'email' => 'customer@example.test',
            'role' => 'customer',
        ]);
        $customer->forceFill(['is_active' => true]);

        $this->actingAs($customer)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }
}
