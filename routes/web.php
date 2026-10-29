<?php

use App\Http\Controllers\Admin\LoreEntryController;
use App\Http\Controllers\Admin\OrganisationMemberController;
use App\Http\Controllers\Admin\OrganisationRankController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CelestialBodyController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::get('/celestial-bodies/{body}', [CelestialBodyController::class, 'show'])
    ->name('celestial-bodies.show');

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegistration'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/entries')->name('index');
    Route::get('entries/search', [LoreEntryController::class, 'search'])->name('entries.search');
    Route::post('entries/check', [LoreEntryController::class, 'check'])->name('entries.check');
    Route::post('entries/resolve', [LoreEntryController::class, 'resolve'])->name('entries.resolve');
    Route::resource('entries', LoreEntryController::class)->except('show');
    Route::post('entries/{entry}/ranks', [OrganisationRankController::class, 'store'])->name('entries.ranks.store');
    Route::delete('entries/{entry}/ranks/{rank}', [OrganisationRankController::class, 'destroy'])->name('entries.ranks.destroy');
    Route::post('entries/{entry}/members', [OrganisationMemberController::class, 'store'])->name('entries.members.store');
    Route::put('entries/{entry}/members/{member}', [OrganisationMemberController::class, 'update'])->name('entries.members.update');
    Route::delete('entries/{entry}/members/{member}', [OrganisationMemberController::class, 'destroy'])->name('entries.members.destroy');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');
