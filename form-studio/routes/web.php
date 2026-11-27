<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseModuleController;
use App\Http\Controllers\SessionStepController;
use App\Http\Controllers\TeachingSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('connexion', [AuthController::class, 'showLogin'])->name('login');
    Route::post('connexion', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::get('inscription', [AuthController::class, 'showRegister'])->name('register');
    Route::post('inscription', [AuthController::class, 'register'])->middleware('throttle:6,1');
});

Route::middleware('auth')->group(function () {
    Route::post('deconnexion', [AuthController::class, 'logout'])->name('logout');
    Route::redirect('/', '/modules');

    Route::resource('modules', CourseModuleController::class)->parameters(['modules' => 'module']);
    Route::post('modules/{module}/duplicate', [CourseModuleController::class, 'duplicate'])->name('modules.duplicate');

    Route::post('modules/{module}/sessions', [TeachingSessionController::class, 'store'])->name('sessions.store');
    Route::put('sessions/{session}', [TeachingSessionController::class, 'update'])->name('sessions.update');
    Route::delete('sessions/{session}', [TeachingSessionController::class, 'destroy'])->name('sessions.destroy');
    Route::get('sessions/{session}/animer', [TeachingSessionController::class, 'present'])->name('sessions.present');

    Route::post('sessions/{session}/steps', [SessionStepController::class, 'store'])->name('steps.store');
    Route::delete('steps/{step}', [SessionStepController::class, 'destroy'])->name('steps.destroy');
});
