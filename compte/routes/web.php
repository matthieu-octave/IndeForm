<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Route;

Route::view('mentions-legales', 'legal.mentions')->name('legal.notice');
Route::view('confidentialite', 'legal.privacy')->name('legal.privacy');

Route::middleware('guest')->group(function () {
    Route::get('mot-de-passe-oublie', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('mot-de-passe-oublie', [PasswordResetController::class, 'email'])->name('password.email')->middleware('throttle:6,1');
    Route::get('reinitialiser-mot-de-passe/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('reinitialiser-mot-de-passe', [PasswordResetController::class, 'update'])->name('password.update')->middleware('throttle:6,1');
    Route::get('connexion', [AuthController::class, 'showLogin'])->name('login');
    Route::post('connexion', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::get('inscription', [AuthController::class, 'showRegister'])->name('register');
    Route::post('inscription', [AuthController::class, 'register'])->middleware('throttle:6,1');
});

Route::middleware('auth')->group(function () {
    Route::post('deconnexion', [AuthController::class, 'logout'])->name('logout');

    Route::get('verifier-email', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('verifier-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])->name('verification.verify')->middleware(['signed', 'throttle:6,1']);
    Route::post('verifier-email/renvoyer', [EmailVerificationController::class, 'send'])->name('verification.send')->middleware('throttle:6,1');

    Route::middleware('verified')->group(function () {
        Route::redirect('/', '/compte')->name('home');
        Route::get('compte', [AccountController::class, 'show'])->name('account.show');
        Route::put('compte/profil', [AccountController::class, 'updateProfile'])->name('account.profile');
        Route::put('compte/espace', [AccountController::class, 'updateWorkspace'])->name('account.workspace');
        Route::put('compte/mot-de-passe', [AccountController::class, 'updatePassword'])->name('account.password')->middleware('throttle:6,1');
    });
});
