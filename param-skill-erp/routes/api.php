<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\StudentController;
use App\Http\Middleware\EnsureMobilizerApiAccess;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware(['auth:sanctum', EnsureMobilizerApiAccess::class, 'throttle:60,1'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('change-password', [AuthController::class, 'changePassword']);

        Route::get('dashboard', [StudentController::class, 'dashboard']);
        Route::get('students', [StudentController::class, 'index']);
        Route::post('students', [StudentController::class, 'store']);
        Route::get('students/{student}', [StudentController::class, 'show']);
        Route::put('students/{student}', [StudentController::class, 'update']);
        Route::post('students/{student}/submit', [StudentController::class, 'submit']);
        Route::get('students/{student}/documents', [StudentController::class, 'documents']);
        Route::post('students/{student}/documents', [StudentController::class, 'uploadDocument']);
        Route::delete('students/{student}/documents/{document}', [StudentController::class, 'deleteDocument']);
        Route::get('students/{student}/follow-ups', [StudentController::class, 'followUps']);
        Route::post('students/{student}/follow-ups', [StudentController::class, 'storeFollowUp']);
        Route::get('students/{student}/centre-visits', [StudentController::class, 'centreVisits']);
        Route::post('students/{student}/centre-visits', [StudentController::class, 'storeCentreVisit']);
    });
});
