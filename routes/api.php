<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware([
    \Illuminate\Cookie\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user()->load(['employee.roles.role', 'employee.roles.unit']);
    })->middleware('auth');

    Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login']);
    Route::post('/logout', function (Request $request) {
        $sessionId = $request->session()->getId();
        $userId = Auth::id() ?? $request->input('user_id');

        if (Auth::check()) {
            $user = Auth::user();
            $user->last_logout = now();
            $user->save();

            \App\Services\ActivityLogger::log('LOGOUT', 'AUTH', "Pengguna {$user->username} keluar dari aplikasi.");
            
            Auth::logout();
        }

        // Always clean up session records for this session ID and user ID
        $query = \App\Models\UserSession::where('id', $sessionId);
        if ($userId) {
            $query->orWhere('user_id', $userId);
        }
        $query->delete();

        $request->session()->flush();
        return response()->json(['message' => 'Logged out']);
    });
    Route::post('/change-password', [\App\Http\Controllers\AuthController::class, 'changePassword']);

    Route::get('/users', [\App\Http\Controllers\UserController::class, 'index']);
    Route::post('/users', [\App\Http\Controllers\UserController::class, 'store']);
    Route::post('/users/reset-password', [\App\Http\Controllers\UserController::class, 'resetPassword']);
    Route::post('/users/reset-session', [\App\Http\Controllers\UserController::class, 'resetSession']);
    Route::put('/users/{id}', [\App\Http\Controllers\UserController::class, 'update']);
    Route::delete('/users', [\App\Http\Controllers\UserController::class, 'destroy']);

    Route::get('/units', [\App\Http\Controllers\UnitController::class, 'index']);
    Route::post('/units', [\App\Http\Controllers\UnitController::class, 'store']);
    Route::put('/units/{id}', [\App\Http\Controllers\UnitController::class, 'update']);
    Route::delete('/units', [\App\Http\Controllers\UnitController::class, 'destroy']);

    Route::post('/news/bulk-delete', [\App\Http\Controllers\NewsController::class, 'bulkDestroy']);
    Route::post('/news/upload-attachment', [\App\Http\Controllers\NewsController::class, 'uploadAttachment']);
    Route::post('/news/delete-attachment', [\App\Http\Controllers\NewsController::class, 'deleteSingleAttachment']);
    Route::get('/news/download-attachment', [\App\Http\Controllers\NewsController::class, 'downloadAttachment']);
    Route::apiResource('news', \App\Http\Controllers\NewsController::class);

    Route::get('/portal-settings', [\App\Http\Controllers\PortalSettingController::class, 'index']);
    Route::post('/portal-settings', [\App\Http\Controllers\PortalSettingController::class, 'store']);
    Route::post('/portal-settings/bulk', [\App\Http\Controllers\PortalSettingController::class, 'updateAll']);
    Route::post('/portal-settings/{id}', [\App\Http\Controllers\PortalSettingController::class, 'updateSingle']);
    Route::delete('/portal-settings/{id}', [\App\Http\Controllers\PortalSettingController::class, 'destroy']);

    Route::get('/dashboard/stats', [\App\Http\Controllers\DashboardController::class, 'getStats']);
    Route::post('/dashboard/track-click', [\App\Http\Controllers\DashboardController::class, 'trackClick']);
    Route::post('/dashboard/track-login', [\App\Http\Controllers\DashboardController::class, 'trackLogin']);

    Route::get('/activity-logs', [\App\Http\Controllers\ActivityLogController::class, 'getActivityLogs']);
    Route::get('/activity-logs/export', [\App\Http\Controllers\ActivityLogController::class, 'exportActivityLogs']);
    Route::delete('/activity-logs/clear', [\App\Http\Controllers\ActivityLogController::class, 'clearActivityLogs']);
    Route::get('/error-logs/stats', [\App\Http\Controllers\ActivityLogController::class, 'getErrorStats']);
    Route::get('/error-logs', [\App\Http\Controllers\ActivityLogController::class, 'getErrorLogs']);
    Route::get('/error-logs/export', [\App\Http\Controllers\ActivityLogController::class, 'exportErrorLogs']);
    Route::delete('/error-logs/clear', [\App\Http\Controllers\ActivityLogController::class, 'clearErrorLogs']);
    Route::post('/error-logs/client', [\App\Http\Controllers\ActivityLogController::class, 'logClientError']);
    Route::post('/security/scan', [\App\Http\Controllers\ActivityLogController::class, 'runSecurityScan']);
    Route::post('/security/simulate-malware', [\App\Http\Controllers\ActivityLogController::class, 'simulateMalware']);
    Route::post('/security/simulate-quarantine', [\App\Http\Controllers\ActivityLogController::class, 'simulateQuarantine']);
    Route::post('/error-logs/solve-all', [\App\Http\Controllers\ActivityLogController::class, 'solveAllErrors']);
    Route::patch('/error-logs/{id}/status', [\App\Http\Controllers\ActivityLogController::class, 'updateErrorStatus']);

    // Export & Import
    Route::get('/export-import/schedule', [\App\Http\Controllers\ExportImportController::class, 'getSchedule']);
    Route::post('/export-import/schedule', [\App\Http\Controllers\ExportImportController::class, 'saveSchedule']);
    Route::get('/export-import/history', [\App\Http\Controllers\ExportImportController::class, 'getHistory']);
    Route::get('/export-import/export/{module}/{format}', [\App\Http\Controllers\ExportImportController::class, 'export']);
    Route::get('/export-import/template/{module}/{format}', [\App\Http\Controllers\ExportImportController::class, 'template']);
    Route::post('/export-import/import/{module}/{format}', [\App\Http\Controllers\ExportImportController::class, 'import']);
});
