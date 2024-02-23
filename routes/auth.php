<?php

use App\Http\Controllers\Auth\{Login, PasswordController, SignUp, VerifyAccount};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

Route::get('/auth', [Login::class, 'show'])->name('login')->middleware('checkIfLoggedIn');
Route::get('/auth/login', [Login::class, 'show'])->name('login')->middleware('checkIfLoggedIn'); 

Route::get('/auth/sign-up', [SignUp::class, 'show'])->name('sign-up')->middleware('checkIfLoggedIn');
Route::get('/auth/verify-account/{token}', [VerifyAccount::class, 'show'])->name('verify-otp')->middleware('checkIfLoggedIn');

Route::get('/forget-password', [PasswordController::class, 'index'])->name('forget-password')->middleware('checkIfLoggedIn');

Route::get('/reset-password/{token}', [PasswordController::class, 'resetPasswordIndex'])->name('reset-password')->middleware('checkIfLoggedIn');

Route::get('/verify-email/{token}', [VerifyAccount::class, 'verifyEmailIndex'])->name('verify-email');
Route::post('/auth/logout', function (Request $request): RedirectResponse {
 Auth::logout();

 $request->session()->invalidate();

 $request->session()->regenerateToken();

 return redirect(route('login'));
})->name('logout');
