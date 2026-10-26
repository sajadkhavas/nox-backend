<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CommerceFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_and_non_super_admin_cannot_access_sensitive_commerce_operations(): void
    {
        $this->get('/admin/inventory')->assertRedirect();
        $user = User::query()->create(['name' => 'Normal Admin', 'email' => 'normal-commerce@example.test', 'password' => 'password']);
        $this->actingAs($user)->get('/admin/inventory')->assertForbidden();
        $this->actingAs($user)->get('/admin/refund-requests')->assertForbidden();
    }

    public function test_super_admin_can_open_commerce_operations_surfaces(): void
    {
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        $admin = User::query()->create(['name' => 'Commerce Admin', 'email' => 'commerce-admin@example.test', 'password' => 'password']);
        $admin->assignRole('super_admin');
        foreach ([
            '/admin/inventory', '/admin/inventory-reservations', '/admin/inventory-ledger-entries', '/admin/shipments',
            '/admin/return-requests', '/admin/exchange-requests', '/admin/refund-requests',
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }
}
