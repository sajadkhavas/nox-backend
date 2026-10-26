<?php

namespace App\Enums;

enum ProductFact: string
{
    case Name = 'name';
    case Media = 'media';
    case Price = 'price';
    case PreviousPrice = 'previous_price';
    case Colors = 'colors';
    case Sizes = 'sizes';
    case Stock = 'stock';
    case Description = 'description';
    case Material = 'material';
    case Care = 'care';
    case Fit = 'fit';
    case Sku = 'sku';
    case CollectionMembership = 'collection_membership';
    case SizeGuide = 'size_guide';

    public static function values(): array
    {
        return array_map(
            static fn (self $fact): string => $fact->value,
            self::cases(),
        );
    }
}
