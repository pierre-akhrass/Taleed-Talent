<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\BookmarkController;
use App\Http\Controllers\Api\CommitmentScheduleController;
use App\Http\Controllers\Api\CustomActivityController;
use App\Http\Controllers\Api\MonthlyReportController;
use App\Http\Controllers\Api\PlanClosureController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\PlanDraftController;
use App\Http\Controllers\ApplicationAuthController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/app'));

Route::get('/up', fn () => response()->json([
    'status' => 'ok',
    'service' => 'taleed-talent',
    'release' => (string) config('app.version', 'local'),
]));

Route::get('/ready', function () {
    try {
        DB::select('SELECT 1');
    } catch (Throwable) {
        return response()->json([
            'status' => 'unavailable',
            'service' => 'taleed-talent',
            'database' => 'unavailable',
        ], 503);
    }

    return response()->json([
        'status' => 'ready',
        'service' => 'taleed-talent',
        'database' => 'ok',
    ]);
});

Route::prefix('auth')->group(function (): void {
    Route::post('/login', [ApplicationAuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/forgot-password', [ApplicationAuthController::class, 'forgotPassword'])->middleware('throttle:login');
    Route::post('/reset-password', [ApplicationAuthController::class, 'resetPassword']);
    Route::get('/reset-password/{token}', fn (string $token) => response()->json(['data' => ['token' => $token]]))->name('password.reset');
    Route::post('/logout', [ApplicationAuthController::class, 'logout'])->middleware('auth:app');
    Route::get('/me', [ApplicationAuthController::class, 'me'])->middleware('auth:app');
});

Route::prefix('api/v1')->middleware(['auth:app', 'verified'])->group(function (): void {
    Route::get('/me', [ApplicationAuthController::class, 'me']);
    Route::post('/admin/organizations', [ApplicationAuthController::class, 'createOrganization']);
    Route::post('/admin/organizations/{organization}/invitations', [ApplicationAuthController::class, 'createInvitation']);
    Route::post('/invitations/accept', [ApplicationAuthController::class, 'acceptInvitation']);

    Route::get('/activities', [ActivityController::class, 'index']);
    Route::get('/activities/{activityId}', [ActivityController::class, 'show']);
    Route::post('/custom-activities', [CustomActivityController::class, 'store']);
    Route::patch('/custom-activities/{activityId}', [CustomActivityController::class, 'update']);
    Route::post('/custom-activities/{activityId}/retire', [CustomActivityController::class, 'retire']);
    Route::get('/bookmarks', [BookmarkController::class, 'index']);
    Route::put('/activities/{activityId}/bookmark', [BookmarkController::class, 'store']);
    Route::delete('/activities/{activityId}/bookmark', [BookmarkController::class, 'destroy']);
    Route::get('/plan-drafts', [PlanDraftController::class, 'index']);
    Route::post('/plan-drafts', [PlanDraftController::class, 'store']);
    Route::get('/plan-drafts/{draftId}', [PlanDraftController::class, 'show']);
    Route::patch('/plan-drafts/{draftId}', [PlanDraftController::class, 'update']);
    Route::post('/plan-drafts/{draftId}/activate', [PlanDraftController::class, 'activate']);
    Route::get('/plans', [PlanController::class, 'index']);
    Route::get('/plans/{planId}', [PlanController::class, 'show']);
    Route::post('/plans/{planId}/commitments', [PlanController::class, 'addCommitment']);
    Route::post('/commitments/{commitmentId}/schedule-versions', [CommitmentScheduleController::class, 'store']);
    Route::get('/calendar', [CommitmentScheduleController::class, 'calendar']);
    Route::patch('/occurrences/{occurrenceId}', [CommitmentScheduleController::class, 'transition']);
    Route::put('/occurrences/{occurrenceId}/note', [CommitmentScheduleController::class, 'updateNote']);
    Route::post('/occurrences/{occurrenceId}/reschedule', [CommitmentScheduleController::class, 'reschedule']);
    Route::post('/plans/{planId}/close', [PlanClosureController::class, 'close']);
    Route::post('/plans/{planId}/reopen', [PlanClosureController::class, 'reopen']);
    Route::get('/plans/{planId}/closures', [PlanClosureController::class, 'index']);
    Route::get('/portfolio/reports', [MonthlyReportController::class, 'index']);
});

Route::statamic('help', 'help')->name('help.index');
Route::get('/app/{path?}', function () {
    $index = public_path('app/index.html');

    abort_unless(is_file($index), 503, 'The frontend build is not installed. Run npm run build:backend.');

    return response()->file($index);
})->where('path', '.*')->name('app.shell');
