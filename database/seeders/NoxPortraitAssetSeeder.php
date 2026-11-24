<?php
namespace Database\Seeders;
use App\Models\StoreSetting;
use App\Models\StorefrontMediaAsset;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class NoxPortraitAssetSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::connection()->getDatabaseName() !== 'nox') throw new \RuntimeException('Run only on the NOX database named nox.');
        $paths=[];
        foreach (['portrait-cap','portrait-white'] as $key) {
            $asset=StorefrontMediaAsset::query()->where('title','NOX design / '.$key)->first();
            if (! $asset || ! $asset->isReady()) {
                $temporary=tempnam(sys_get_temp_dir(),'nox-portrait-');
                if (! $temporary || ! copy(__DIR__.'/nox-assets/'.$key.'.webp',$temporary)) throw new \RuntimeException('Cannot prepare NOX portrait.');
                try {
                    $asset=StorefrontMediaAsset::createFromUpload(new UploadedFile($temporary,$key.'.webp','image/webp',null,true),[
                        'title'=>'NOX design / '.$key,'alt_text'=>$key==='portrait-cap' ? 'پرتره نقاشی با کلاه مشکی و زنجیر طلایی؛ هنر دیواری ناکس' : 'پرتره نقاشی با تیشرت سفید و گردنبند؛ هنر دیواری ناکس',
                        'usage'=>'home','notes'=>'AI-assisted restoration of client supplied portrait artwork.',
                    ]);
                } finally { if (is_file($temporary)) unlink($temporary); }
            }
            $path=$asset->previewPath();
            if (! $path) throw new \RuntimeException('Optimized portrait is not ready: '.$key);
            $paths[]=$path;
        }
        DB::transaction(function () use ($paths): void {
            $setting=StoreSetting::query()->where('key','home.nox_visuals')->lockForUpdate()->first();
            $visuals=$setting?->typedValue() ?? [];
            $visuals['portraits']=$paths;
            StoreSetting::query()->updateOrCreate(['key'=>'home.nox_visuals'],['group'=>'home','type'=>'json','label'=>'تصاویر طراحی ناکس','is_public'=>true,'value'=>json_encode($visuals,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)]);
        });
        $this->command?->info('Two NOX portraits imported. Existing hero, store, category images and product data preserved.');
    }
}
