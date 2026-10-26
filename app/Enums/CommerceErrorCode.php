<?php

namespace App\Enums;

enum CommerceErrorCode: string
{
    case ProductUnavailable = 'commerce_product_unavailable';
    case VariantUnavailable = 'commerce_variant_unavailable';
    case OutOfStock = 'commerce_out_of_stock';
    case PriceChanged = 'commerce_price_changed';
    case InvalidQuantity = 'commerce_invalid_quantity';
    case InvalidVariant = 'commerce_invalid_variant';
    case UnpublishedProduct = 'commerce_unpublished_product';
    case ArchivedProduct = 'commerce_archived_product';
    case QuoteExpired = 'commerce_quote_expired';
    case ReservationExpired = 'commerce_reservation_expired';
    case InvalidTransition = 'commerce_invalid_transition';
    case PaymentUnavailable = 'commerce_payment_unavailable';
    case DuplicateRequest = 'commerce_duplicate_request';
    case RefundUnavailable = 'commerce_refund_unavailable';
    case ReturnUnavailable = 'commerce_return_unavailable';
    case ExchangeUnavailable = 'commerce_exchange_unavailable';
}
