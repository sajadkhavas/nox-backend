<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminSettingsPagesAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_render_storefront_control_center(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/admin/storefront-control-center')
            ->assertOk()
            ->assertSee('کنترل کامل ویترین و محتوای عمومی');
    }

    public function test_super_admin_can_render_legacy_site_settings(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/admin/site-settings')
            ->assertOk()
            ->assertSee('تنظیمات عمومی و صفحه اصلی');
    }

    private function superAdmin(): User
    {
        $user = new User;
        $user->name = 'Admin Settings Test';
        $user->email = fake()->unique()->safeEmail();
        $user->password = Hash::make('temporary-test-password');
        $user->email_verified_at = now();
        $user->save();

        $user->assignRole(Role::findOrCreate('super_admin', 'web'));

        return $user;
    }
}
