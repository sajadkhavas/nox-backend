<?php

namespace App\Domain\Apparel;

use App\Enums\EvidenceState;
use App\Enums\ProductFact;
use App\Models\Product;

class ApparelPublicationGuard
{
    public function issues(Product $product): array
    {
        if (! $product->exists) {
            return ['Product must be saved as draft before it can be published.'];
        }

        $issues = [];

        $activeVariants = $product->variants()
            ->where('is_active', true)
            ->with(['color', 'size'])
            ->get();

        $validVariants = $activeVariants->filter(
            static fn ($variant): bool => $variant->color_id !== null
                && $variant->size_id !== null
                && filled($variant->sku)
                && $variant->regular_price_toman > 0
                && $variant->color?->is_active === true
                && $variant->size?->is_active === true,
        );

        if ($validVariants->isEmpty()) {
            $issues[] = 'At least one active apparel variant with active color, active size, SKU, and price is required.';
        }

        if (! $product->mediaAssets()->where('verification_state', EvidenceState::Verified->value)->exists()) {
            $issues[] = 'At least one verified apparel media asset is required.';
        }

        $requiredFacts = [
            ProductFact::Name,
            ProductFact::Media,
            ProductFact::Price,
            ProductFact::Colors,
            ProductFact::Sizes,
            ProductFact::Stock,
            ProductFact::Sku,
        ];

        if (filled($product->short_description) || filled($product->description)) {
            $requiredFacts[] = ProductFact::Description;
        }

        if (filled($product->material) || filled($product->fabric_composition)) {
            $requiredFacts[] = ProductFact::Material;
        }

        if (filled($product->care_instructions)) {
            $requiredFacts[] = ProductFact::Care;
        }

        if (filled($product->fit)) {
            $requiredFacts[] = ProductFact::Fit;
        }

        if ($validVariants->contains(static fn ($variant): bool => $variant->hasValidSalePrice())) {
            $requiredFacts[] = ProductFact::PreviousPrice;
        }

        if ($product->collections()->exists()) {
            $requiredFacts[] = ProductFact::CollectionMembership;
        }

        if ($product->size_guide_id !== null) {
            $requiredFacts[] = ProductFact::SizeGuide;
        }

        $verifiedFacts = $product->evidences()
            ->where('state', EvidenceState::Verified->value)
            ->pluck('fact_key')
            ->all();

        $requiredFactsByKey = [];

        foreach ($requiredFacts as $fact) {
            $requiredFactsByKey[$fact->value] = $fact;
        }

        foreach ($requiredFactsByKey as $fact) {
            if (! in_array($fact->value, $verifiedFacts, true)) {
                $issues[] = "Evidence for [{$fact->value}] must be verified before publication.";
            }
        }

        return $issues;
    }

    public function assertPublishable(Product $product): void
    {
        $issues = $this->issues($product);

        if ($issues !== []) {
            throw new \DomainException(implode(' ', $issues));
        }
    }
}
