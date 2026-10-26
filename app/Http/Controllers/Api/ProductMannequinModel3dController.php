<?php

namespace App\Http\Controllers\Api;

use App\Domain\Catalog\MannequinModel3d;
use App\Domain\Catalog\PublicCatalogQuery;
use App\Domain\Catalog\PublicCatalogTransformer;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ProductMannequinModel3dController extends Controller
{
    public function __construct(
        private readonly PublicCatalogQuery $catalog,
        private readonly PublicCatalogTransformer $transformer,
    ) {}

    public function show(string $slug): JsonResponse
    {
        [$product, $media] = $this->resolve($slug);

        return ApiResponse::success([
            // Stream through the API origin so the viewer inherits the normal public API
            // CORS/security policy instead of depending on static storage CORS headers.
            'url' => route('api.v1.products.mannequin-3d.file', [
                'slug' => $product->slug,
                'v' => $media->updated_at?->getTimestamp(),
            ]),
            'format' => 'glb',
            'bytes' => (int) $media->size,
        ]);
    }

    public function file(string $slug): BinaryFileResponse
    {
        [, $media] = $this->resolve($slug);

        return response()->file($media->getPath(), [
            'Content-Type' => 'model/gltf-binary',
            'Cache-Control' => 'public, max-age=3600, stale-while-revalidate=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** @return array{0: Product, 1: Media} */
    private function resolve(string $slug): array
    {
        $product = $this->catalog->product($slug);
        $mannequin = $this->transformer->productSummary($product)['mannequin'] ?? null;

        // 3D is an enhancement to the existing 2D mannequin contract. Requiring the
        // lightweight fallback means mobile, weak hardware and renderer/load failures
        // can always return to a valid product presentation.
        if (! is_array($mannequin) || ($mannequin['enabled'] ?? false) !== true) {
            throw new NotFoundHttpException;
        }

        // PublicCatalogQuery intentionally eager-loads only mannequin-front media.
        // Clear that scoped relation before resolving the separate 3D collection.
        $product->unsetRelation('media');
        $media = MannequinModel3d::media($product);

        if ($media === null) {
            throw new NotFoundHttpException;
        }

        return [$product, $media];
    }
}
