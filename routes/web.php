<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/admin'));
Route::get('/health', fn () => response()->json(['status' => 'ok', 'time' => now()->toIso8601String()]));

// Short URL redirect
Route::get('/s/{code}', function (string $code) {
    $shortUrl = \App\Models\ShortUrl::where('code', $code)->first();
    if (!$shortUrl || !$shortUrl->isActive()) {
        abort(404);
    }
    $shortUrl->incrementClicks();
    return redirect($shortUrl->destination_url, 301);
});
