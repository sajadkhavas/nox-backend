<?php

use App\Http\Controllers\Api\AccountAddressController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AccountOrderController;
use App\Http\Controllers\Api\AccountStorefrontStateController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\CommerceCartController;
use App\Http\Controllers\Api\CommerceCheckoutController;
use App\Http\Controllers\Api\CommerceExchangeController;
use App\Http\Controllers\Api\CommerceRefundController;
use App\Http\Controllers\Api\CommerceReturnController;
use App\Http\Controllers\Api\DeliveryController;
use App\Http\Controllers\Api\InquiryController;
use App\Http\Controllers\Api\OtpAuthController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductMannequinModel3dController;
use App\Http\Controllers\Api\PublicCatalogController;
use App\Http\Controllers\Api\PublicOrderTrackingController;
use App\Http\Controllers\Api\PushSubscriptionController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\StoreContentController;
use App\Http\Controllers\Api\StorefrontContentController;
use App\Http\Controllers\Api\SystemController;
use Illuminate\Support\Facades\Route;

Route::prefix('system')->middleware('throttle:60,1')->group(function () {
    Route::get('health', [SystemController::class, 'health']);
    Route::get('ready', [SystemController::class, 'ready']);
    Route::get('meta', [SystemController::class, 'meta']);
    Route::get('contracts', [SystemController::class, 'contracts']);
    Route::get('openapi', [SystemController::class, 'openapi']);
});
Route::prefix('catalog')->middleware('throttle:120,1')->group(function () {
    Route::get('products', [CatalogController::class, 'products']);
    Route::get('products/{slug}', [CatalogController::class, 'product']);
    Route::get('products/{slug}/reviews', [ReviewController::class, 'index']);
    Route::get('categories', [CatalogController::class, 'categories']);
});
Route::get('delivery/options', [DeliveryController::class, 'options'])->middleware('throttle:120,1');
Route::prefix('store')->middleware('throttle:120,1')->group(function () {
    Route::get('settings', [StoreContentController::class, 'settings']);
    Route::get('pages/{slug}', [StoreContentController::class, 'page']);
    Route::get('faqs', [StoreContentController::class, 'faqs']);
    Route::get('gallery', [StoreContentController::class, 'gallery']);
    Route::get('posts', [StoreContentController::class, 'posts']);
    Route::get('posts/{slug}', [StoreContentController::class, 'post']);
});
Route::post('inquiries', [InquiryController::class, 'store'])->middleware('throttle:5,1');
Route::prefix('auth')->group(function () {
    Route::post('otp/request', [OtpAuthController::class, 'requestOtp'])->middleware('throttle:otp-request');
    Route::post('otp/verify', [OtpAuthController::class, 'verify'])->middleware('throttle:otp-verify');
    Route::middleware(['auth:customer', 'customer.active', 'throttle:60,1'])->group(function () {
        Route::get('me', [OtpAuthController::class, 'me']);
        Route::post('logout', [OtpAuthController::class, 'logout']);
    });
});

Route::prefix('web-push')->name('web-push.')->group(function (): void {
    Route::get('/config', [PushSubscriptionController::class, 'configuration'])
        ->middleware('throttle:60,1')
        ->name('config');

    Route::post('/guest/subscriptions', [PushSubscriptionController::class, 'storeGuest'])
        ->middleware('throttle:5,1')
        ->name('guest.store');
    Route::delete('/guest/subscriptions', [PushSubscriptionController::class, 'destroyGuest'])
        ->middleware('throttle:10,1')
        ->name('guest.destroy');

    Route::middleware(['auth:customer', 'customer.active'])->group(function (): void {
        Route::get('/subscriptions', [PushSubscriptionController::class, 'index'])
            ->middleware('throttle:60,1')
            ->name('subscriptions.index');
        Route::post('/subscriptions', [PushSubscriptionController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('subscriptions.store');
        Route::delete('/subscriptions', [PushSubscriptionController::class, 'destroy'])
            ->middleware('throttle:30,1')
            ->name('subscriptions.destroy');
        Route::post('/test', [PushSubscriptionController::class, 'test'])
            ->middleware('throttle:3,1')
            ->name('test');
    });
});

// Legacy compatibility surface. BE-E routes below are the versioned commerce contract.
Route::middleware(['auth:customer', 'customer.active'])->group(function () {
    Route::post('checkout', [CheckoutController::class, 'store'])->middleware('throttle:20,1');
    Route::post('orders/{orderId}/payments', [PaymentController::class, 'store'])->middleware('throttle:10,1');
    Route::post('payments/verify', [PaymentController::class, 'verify'])->middleware('throttle:20,1');
    Route::post('payments/zarinpal/verify', [PaymentController::class, 'verify'])->middleware('throttle:20,1');
    Route::prefix('account')->middleware('throttle:60,1')->group(function () {
        Route::patch('profile', [AccountController::class, 'updateProfile']);
        Route::get('addresses', [AccountAddressController::class, 'index']);
        Route::post('addresses', [AccountAddressController::class, 'store']);
        Route::put('addresses/{addressId}', [AccountAddressController::class, 'update']);
        Route::delete('addresses/{addressId}', [AccountAddressController::class, 'destroy']);
        Route::get('orders', [AccountOrderController::class, 'index']);
        Route::get('orders/{orderId}', [AccountOrderController::class, 'show']);
        Route::post('orders/{orderId}/cancel', [AccountOrderController::class, 'cancel'])->middleware('throttle:10,1');
        Route::post('orders/{orderId}/reviews', [ReviewController::class, 'store'])->middleware('throttle:10,1');
    });
});

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::post('/otp/request', [OtpAuthController::class, 'requestOtp'])->middleware('throttle:otp-request')->name('otp.request');
        Route::post('/otp/verify', [OtpAuthController::class, 'verify'])->middleware('throttle:otp-verify')->name('otp.verify');
        Route::middleware(['auth:customer', 'customer.active', 'throttle:60,1'])->group(function (): void {
            Route::get('/me', [OtpAuthController::class, 'me'])->name('me');
            Route::post('/logout', [OtpAuthController::class, 'logout'])->name('logout');
        });
    });

    Route::middleware('throttle:public-catalog')->group(function (): void {
        Route::prefix('storefront')->name('storefront.')->group(function (): void {
            Route::get('/bootstrap', [StorefrontContentController::class, 'bootstrap'])->name('bootstrap');
            Route::get('/home-products', [StorefrontContentController::class, 'homeProducts'])->name('home-products.index');
            Route::get('/pages/{slug}', [StorefrontContentController::class, 'page'])->name('pages.show');
            Route::get('/faqs', [StorefrontContentController::class, 'faqs'])->name('faqs.index');
            Route::get('/lookbook', [StorefrontContentController::class, 'lookbook'])->name('lookbook.index');
            Route::get('/journal', [StorefrontContentController::class, 'journal'])->name('journal.index');
            Route::get('/journal/{slug}', [StorefrontContentController::class, 'journalPost'])->name('journal.show');
        });

        Route::get('/categories', [PublicCatalogController::class, 'categories'])->name('categories.index');
        Route::get('/categories/{slug}', [PublicCatalogController::class, 'category'])->name('categories.show');
        Route::get('/products', [PublicCatalogController::class, 'products'])->name('products.index');
        Route::get('/products/{slug}/mannequin-3d/file', [ProductMannequinModel3dController::class, 'file'])->name('products.mannequin-3d.file');
        Route::get('/products/{slug}/mannequin-3d', [ProductMannequinModel3dController::class, 'show'])->name('products.mannequin-3d');
        Route::get('/products/{slug}', [PublicCatalogController::class, 'product'])->name('products.show');
        Route::get('/collections', [PublicCatalogController::class, 'collections'])->name('collections.index');
        Route::get('/collections/{slug}', [PublicCatalogController::class, 'collection'])->name('collections.show');
        Route::get('/drops', [PublicCatalogController::class, 'drops'])->name('drops.index');
        Route::get('/drops/{slug}', [PublicCatalogController::class, 'drop'])->name('drops.show');
        Route::get('/colors', [PublicCatalogController::class, 'colors'])->name('colors.index');
        Route::get('/sizes', [PublicCatalogController::class, 'sizes'])->name('sizes.index');
        Route::get('/catalog/facets', [PublicCatalogController::class, 'facets'])->name('catalog.facets');
        Route::get('/delivery/options', [DeliveryController::class, 'options'])->name('delivery.options');
    });

    Route::get('/search', [PublicCatalogController::class, 'search'])->middleware('throttle:public-search')->name('search');
    Route::post('/inquiries', [InquiryController::class, 'store'])->middleware('throttle:5,1')->name('inquiries.store');
    Route::post('/orders/track', PublicOrderTrackingController::class)->middleware('throttle:10,1')->name('orders.track');

    Route::middleware(['auth:customer', 'customer.active'])->group(function (): void {
        Route::get('/account/storefront-state', [AccountStorefrontStateController::class, 'show'])->middleware('throttle:60,1')->name('storefront-state.show');
        Route::put('/account/wishlist', [AccountStorefrontStateController::class, 'replaceWishlist'])->middleware('throttle:30,1')->name('wishlist.replace');
        Route::put('/account/cart', [AccountStorefrontStateController::class, 'replaceCart'])->middleware('throttle:30,1')->name('cart.replace');

        Route::post('/cart/validate', [CommerceCartController::class, 'validateCart'])->middleware('throttle:commerce-cart')->name('cart.validate');
        Route::post('/checkout/quote', [CommerceCheckoutController::class, 'quote'])->middleware('throttle:commerce-checkout')->name('checkout.quote');
        Route::post('/checkout/commit', [CommerceCheckoutController::class, 'commit'])->middleware('throttle:commerce-checkout')->name('checkout.commit');

        Route::get('/account/orders', [AccountOrderController::class, 'index'])->middleware('throttle:commerce-order')->name('orders.index');
        Route::get('/account/orders/{orderId}', [AccountOrderController::class, 'show'])->middleware('throttle:commerce-order')->name('orders.show');
        Route::post('/account/orders/{orderId}/cancel', [AccountOrderController::class, 'cancel'])->middleware('throttle:commerce-checkout')->name('orders.cancel');

        Route::post('/orders/{orderId}/payments', [PaymentController::class, 'store'])->middleware('throttle:commerce-payment')->name('payments.store');
        Route::post('/payments/verify', [PaymentController::class, 'verify'])->middleware('throttle:commerce-payment')->name('payments.verify');

        Route::get('/returns', [CommerceReturnController::class, 'index'])->middleware('throttle:commerce-order')->name('returns.index');
        Route::get('/returns/{returnId}', [CommerceReturnController::class, 'show'])->middleware('throttle:commerce-order')->name('returns.show');
        Route::post('/orders/{orderId}/returns', [CommerceReturnController::class, 'store'])->middleware('throttle:commerce-return')->name('returns.store');

        Route::get('/exchanges', [CommerceExchangeController::class, 'index'])->middleware('throttle:commerce-order')->name('exchanges.index');
        Route::get('/exchanges/{exchangeId}', [CommerceExchangeController::class, 'show'])->middleware('throttle:commerce-order')->name('exchanges.show');
        Route::post('/orders/{orderId}/exchanges', [CommerceExchangeController::class, 'store'])->middleware('throttle:commerce-return')->name('exchanges.store');

        Route::get('/refunds', [CommerceRefundController::class, 'index'])->middleware('throttle:commerce-order')->name('refunds.index');
        Route::get('/refunds/{refundId}', [CommerceRefundController::class, 'show'])->middleware('throttle:commerce-order')->name('refunds.show');
    });
});
