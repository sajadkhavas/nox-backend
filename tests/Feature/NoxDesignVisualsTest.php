<?php
namespace Tests\Feature;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class NoxDesignVisualsTest extends TestCase
{
    use RefreshDatabase;
    public function test_nox_visuals_are_public_backend_media_urls(): void
    {
        StoreSetting::query()->updateOrCreate(['key'=>'home.nox_visuals'],['group'=>'home','type'=>'json','label'=>'NOX visuals','is_public'=>true,'value'=>json_encode([
            'hero'=>'nox/hero.webp','painting'=>'nox/painting.webp','store'=>'nox/store.webp',
            'portraits'=>['nox/portrait-cap.webp','nox/portrait-white.webp'],'gallery'=>['nox/art-0.webp','nox/art-1.webp'],'categories'=>['pants'=>'nox/pants.webp'],
        ])]);
        $response=$this->getJson('/api/v1/storefront/bootstrap')->assertOk();
        $visuals=$response->json('data.settings.home')['home.nox_visuals'];
        $this->assertStringEndsWith('/storage/nox/hero.webp',$visuals['hero']);
        $this->assertStringEndsWith('/storage/nox/art-0.webp',$visuals['gallery'][0]);
        $this->assertStringEndsWith('/storage/nox/pants.webp',$visuals['categories']['pants']);
        $this->assertCount(2,$visuals['gallery']);
        $this->assertStringEndsWith('/storage/nox/portrait-cap.webp',$visuals['portraits'][0]);
        $this->assertCount(2,$visuals['portraits']);
    }
}
