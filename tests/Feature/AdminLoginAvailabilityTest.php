<?php

namespace Tests\Feature;

use App\Models\IpBlacklist;
use App\Models\MaintenanceSetting;
use App\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminLoginAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_is_available_without_unimplemented_web_subsystems(): void
    {
        $this->assertFalse(class_exists(Redirect::class));
        $this->assertFalse(class_exists(MaintenanceSetting::class));
        $this->assertFalse(class_exists(IpBlacklist::class));

        $this->get('/admin/login')
            ->assertOk();
    }

    public function test_web_pipeline_does_not_require_unimplemented_runtime_models(): void
    {
        Route::middleware('web')->get('/__web-pipeline-probe', static fn () => response()->noContent());

        $this->get('/__web-pipeline-probe')
            ->assertNoContent();
    }
}
