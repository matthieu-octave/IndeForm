<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseModuleController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\SessionStepController;
use App\Http\Controllers\SessionTemplateController;
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
    Route::post('sessions/{session}/move', [TeachingSessionController::class, 'move'])->name('sessions.move');
    Route::post('sessions/{session}/duplicate', [TeachingSessionController::class, 'duplicate'])->name('sessions.duplicate');
    Route::get('sessions/{session}/animer', [TeachingSessionController::class, 'present'])->name('sessions.present');

    Route::post('sessions/{session}/steps', [SessionStepController::class, 'store'])->name('steps.store');
    Route::get('modeles', [SessionTemplateController::class, 'index'])->name('templates.index');
    Route::put('modeles/{template}', [SessionTemplateController::class, 'update'])->name('templates.update');
    Route::delete('modeles/{template}', [SessionTemplateController::class, 'destroy'])->name('templates.destroy');
    Route::post('sessions/{session}/template', [SessionTemplateController::class, 'storeFromSession'])->name('templates.fromSession');

    Route::post('modules/{module}/resources', [ResourceController::class, 'store'])->name('resources.store');
    Route::put('resources/{resource}', [ResourceController::class, 'update'])->name('resources.update');
    Route::delete('resources/{resource}', [ResourceController::class, 'destroy'])->name('resources.destroy');

    Route::put('steps/{step}', [SessionStepController::class, 'update'])->name('steps.update');
    Route::post('steps/{step}/move', [SessionStepController::class, 'move'])->name('steps.move');
    Route::delete('steps/{step}', [SessionStepController::class, 'destroy'])->name('steps.destroy');
});
