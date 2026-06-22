<?php

use App\Http\Controllers\Admin\Auth\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\SearchController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Public site and admin panel (session auth). Mobile app API lives in api.php.
|
*/

Route::get('/', HomeController::class)->name('public.home');
Route::get('/search', SearchController::class)->name('public.search');

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', fn () => redirect()->route('login'))->name('login');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', fn () => redirect()->route('admin.dashboard'))->name('index');
        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::get('locations', [LocationController::class, 'index'])->name('locations.index');
        Route::get('locations/create', [LocationController::class, 'create'])->name('locations.create');
        Route::post('locations', [LocationController::class, 'store'])->name('locations.store');
        Route::get('locations/{location}', [LocationController::class, 'show'])->name('locations.show');
        Route::get('locations/{location}/edit', [LocationController::class, 'edit'])->name('locations.edit');
        Route::put('locations/{location}', [LocationController::class, 'update'])->name('locations.update');
        Route::delete('locations/{location}', [LocationController::class, 'destroy'])->name('locations.destroy');
        Route::get('events', [EventController::class, 'index'])->name('events.index');
        Route::get('events/create', [EventController::class, 'create'])->name('events.create');
        Route::post('events', [EventController::class, 'store'])->name('events.store');
        Route::get('events/{event}', [EventController::class, 'show'])->name('events.show');
        Route::get('events/{event}/edit', [EventController::class, 'edit'])->name('events.edit');
        Route::put('events/{event}', [EventController::class, 'update'])->name('events.update');
        Route::delete('events/{event}', [EventController::class, 'destroy'])->name('events.destroy');
        Route::get('users', [UserController::class, 'index'])->name('users');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::get('reviews', [ReviewController::class, 'index'])->name('reviews');
        Route::get('settings', [SettingsController::class, 'index'])->name('settings');
        Route::put('settings/app', [SettingsController::class, 'updateAppSettings'])->name('settings.app.update');
        Route::post('settings/hero-sections', [SettingsController::class, 'storeHeroSection'])->name('settings.hero-sections.store');
        Route::get('settings/hero-sections/{heroSection}/edit', [SettingsController::class, 'editHeroSection'])->name('settings.hero-sections.edit');
        Route::put('settings/hero-sections/{heroSection}', [SettingsController::class, 'updateHeroSection'])->name('settings.hero-sections.update');
        Route::delete('settings/hero-sections/{heroSection}', [SettingsController::class, 'destroyHeroSection'])->name('settings.hero-sections.destroy');
        Route::post('logout', [AdminLoginController::class, 'destroy'])->name('logout');
    });
});
