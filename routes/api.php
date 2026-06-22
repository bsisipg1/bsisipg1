<?php

use App\Http\Controllers\App\Auth\GoogleLoginController;
use App\Http\Controllers\App\Auth\LoginController;
use App\Http\Controllers\App\Auth\RegisterController;
use App\Http\Controllers\App\EventController;
use App\Http\Controllers\App\HeroSectionController;
use App\Http\Controllers\App\LocationController;
use App\Http\Controllers\App\LocationRatingController;
use App\Http\Controllers\App\ProfileController;
use App\Http\Controllers\App\SavedLocationController;
use App\Http\Controllers\App\TripController;
use Illuminate\Support\Facades\Route;

Route::prefix('app')->name('app.')->group(function () {
    Route::get('hero-sections', [HeroSectionController::class, 'index'])->name('hero-sections.index');
    Route::get('locations', [LocationController::class, 'index'])->name('locations.index');
    Route::get('locations/{location}', [LocationController::class, 'show'])->name('locations.show');
    Route::get('locations/{location}/ratings', [LocationRatingController::class, 'index'])->name('locations.ratings.index');
    Route::get('events', [EventController::class, 'index'])->name('events.index');
    Route::get('events/{event}', [EventController::class, 'show'])->name('events.show');

    Route::post('register', [RegisterController::class, 'store'])->name('register');
    Route::post('login', [LoginController::class, 'store'])->name('login');
    Route::post('auth/google', [GoogleLoginController::class, 'store'])->name('auth.google');

    Route::middleware(['auth:sanctum', 'appuser'])->group(function () {
        Route::get('user', [ProfileController::class, 'show'])->name('user');
        Route::get('user/stats', [ProfileController::class, 'stats'])->name('user.stats');
        Route::put('user', [ProfileController::class, 'update'])->name('user.update');
        Route::put('user/home-location', [ProfileController::class, 'updateHomeLocation'])->name('user.home-location.update');
        Route::post('user', [ProfileController::class, 'update'])->name('user.update.post');
        Route::delete('user/profile-photo', [ProfileController::class, 'destroyPhoto'])->name('user.profile-photo.destroy');
        Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

        Route::get('saved-locations', [SavedLocationController::class, 'index'])->name('saved-locations.index');
        Route::post('saved-locations', [SavedLocationController::class, 'store'])->name('saved-locations.store');
        Route::put('saved-locations', [SavedLocationController::class, 'sync'])->name('saved-locations.sync');
        Route::delete('saved-locations/{location}', [SavedLocationController::class, 'destroy'])->name('saved-locations.destroy');

        Route::get('trips', [TripController::class, 'index'])->name('trips.index');
        Route::post('trips', [TripController::class, 'store'])->name('trips.store');
        Route::get('trips/{trip}', [TripController::class, 'show'])->name('trips.show');
        Route::put('trips/{trip}', [TripController::class, 'update'])->name('trips.update');
        Route::delete('trips/{trip}', [TripController::class, 'destroy'])->name('trips.destroy');
        Route::put('trips/{trip}/locations', [TripController::class, 'syncLocations'])->name('trips.locations.sync');
        Route::delete('trips/{trip}/locations/{location}', [TripController::class, 'removeLocation'])->name('trips.locations.destroy');

        Route::get('locations/{location}/my-rating', [LocationRatingController::class, 'mine'])->name('locations.ratings.mine');
        Route::post('locations/{location}/ratings', [LocationRatingController::class, 'store'])->name('locations.ratings.store');
        Route::delete('locations/{location}/ratings', [LocationRatingController::class, 'destroy'])->name('locations.ratings.destroy');
    });
});
