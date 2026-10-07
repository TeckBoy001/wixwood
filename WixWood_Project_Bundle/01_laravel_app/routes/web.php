<?php

use Illuminate\Support\Facades\Route;

// The WixWood site is a single self-contained HTML/CSS/JS file (same one
// used in the static version of this project) that talks to the /api/*
// routes in routes/api.php. It's served from public/wixwood.html and
// returned directly here rather than relied on as a static-file hit, so
// behavior is identical whether this runs under `php artisan serve`,
// Nginx, or Apache.
Route::get('/', function () {
    return response()->file(public_path('wixwood.html'));
});

Route::get('/order-confirmation.html', function () {
    return response()->file(public_path('order-confirmation.html'));
});
