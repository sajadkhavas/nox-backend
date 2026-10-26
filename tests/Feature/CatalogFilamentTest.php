<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CatalogFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_open_catalog_catalog_create_forms(): void
    {
        Role::create([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        $admin = User::create([
            'name' => 'LBB Admin',
            'email' => 'admin@example.test',
            'password' => 'catalog-test-password',
        ]);
        $admin->assignRole('super_admin');

        $this->actingAs($admin)
            ->get('/admin/categories/create')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/products/create')
            ->assertOk();
    }
}
