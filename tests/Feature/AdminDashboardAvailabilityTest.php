<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminDashboardAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_dashboard_renders_without_legacy_widget_dependencies(): void
    {
        $user = new User;
        $user->name = 'Admin Dashboard Test';
        $user->email = 'admin-dashboard@example.test';
        $user->password = Hash::make('temporary-test-password');
        $user->email_verified_at = now();
        $user->save();

        $user->assignRole(Role::findOrCreate('super_admin', 'web'));

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk()
            ->assertSee('محصولات')
            ->assertSee('دسته‌ها')
            ->assertSee('مشتریان')
            ->assertSee('سفارش‌ها');
    }
}
