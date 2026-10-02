<?php

use App\Http\Controllers\ApplicationAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/app'));

Route::get('/up', fn () => response()->json([
    'status' => 'ok',
    'service' => 'taleed-talent',
    'release' => (string) config('app.version', 'local'),
]));

Route::get('/ready', fn () => response()->json([
    'status' => 'ready',
    'database' => 'configured-for-local-mysql',
]));

Route::prefix('auth')->group(function (): void {
    Route::post('/login', [ApplicationAuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/forgot-password', [ApplicationAuthController::class, 'forgotPassword'])->middleware('throttle:login');
    Route::post('/reset-password', [ApplicationAuthController::class, 'resetPassword']);
    Route::get('/reset-password/{token}', fn (string $token) => response()->json(['data' => ['token' => $token]]))->name('password.reset');
    Route::post('/logout', [ApplicationAuthController::class, 'logout'])->middleware('auth:app');
    Route::get('/me', [ApplicationAuthController::class, 'me'])->middleware('auth:app');
});

Route::prefix('api/v1')->middleware('auth:app')->group(function (): void {
    Route::get('/me', [ApplicationAuthController::class, 'me']);
    Route::post('/admin/organizations', [ApplicationAuthController::class, 'createOrganization']);
    Route::post('/admin/organizations/{organization}/invitations', [ApplicationAuthController::class, 'createInvitation']);
    Route::post('/invitations/accept', [ApplicationAuthController::class, 'acceptInvitation']);
});

Route::view('/help', 'help')->name('help.index');
Route::get('/app/{path?}', function () {
    $index = public_path('app/index.html');

    abort_unless(is_file($index), 503, 'The frontend build is not installed. Run npm run build:backend.');

    return response()->file($index);
})->where('path', '.*')->name('app.shell');
