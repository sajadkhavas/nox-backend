<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApparelFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_apparel_admin_resources(): void
    {
        $this->get('/admin/colors')->assertRedirect();
        $this->get('/admin/sizes')->assertRedirect();
        $this->get('/admin/collections')->assertRedirect();
        $this->get('/admin/drops')->assertRedirect();
        $this->get('/admin/size-guides')->assertRedirect();
        $this->get('/admin/product-media-assets')->assertRedirect();
    }

    public function test_super_admin_can_open_apparel_admin_resources(): void
    {
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);

        $admin = User::create([
            'name' => 'LBB Apparel Admin',
            'email' => 'apparel-admin@example.test',
            'password' => 'apparel-test-password',
        ]);
        $admin->assignRole('super_admin');

        foreach ([
            '/admin/colors',
            '/admin/sizes',
            '/admin/collections',
            '/admin/drops',
            '/admin/measurement-definitions',
            '/admin/size-guides',
            '/admin/product-media-assets',
            '/admin/products/create',
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }
}
