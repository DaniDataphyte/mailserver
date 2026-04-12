<?php

use App\Http\Controllers\Public\PreferencesController;
use App\Http\Controllers\Public\UnsubscribeController;
use App\Http\Controllers\Public\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Landing page
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    // If already authenticated in Statamic CP, send straight to CP
    if (app(\Statamic\Contracts\Auth\UserRepository::class)->current()) {
        return redirect('/cp');
    }

    return view('landing.index');
})->name('landing');

/*
|--------------------------------------------------------------------------
| Newsletter Public Routes
|--------------------------------------------------------------------------
*/

// Unsubscribe — signed URL, no auth required
Route::get('/unsubscribe/{token}', [UnsubscribeController::class, 'show'])
    ->name('newsletter.unsubscribe.show');

Route::post('/unsubscribe/{token}', [UnsubscribeController::class, 'process'])
    ->name('newsletter.unsubscribe.process');

// Preference center — signed URL, no auth required
Route::get('/preferences/{token}', [PreferencesController::class, 'show'])
    ->name('newsletter.preferences.show');

Route::post('/preferences/{token}', [PreferencesController::class, 'update'])
    ->name('newsletter.preferences.update');

// Elastic Email webhook endpoint — public, no CSRF (raw POST from Elastic Email)
Route::post('/webhooks/elastic-email', [WebhookController::class, 'receive'])
    ->name('newsletter.webhook.elastic-email')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
