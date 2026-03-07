<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AdminAuthController;
use App\Http\Controllers\Web\AdminDashboardController;
use App\Http\Controllers\Web\AdminDramaController;
use App\Http\Controllers\Web\AdminEpisodeController;
use App\Http\Controllers\Web\AdminCategoryController;
use App\Http\Controllers\Web\AdminTagController;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

// ── Admin Auth ─────────────────────────────────────────────────
Route::prefix('admin')->group(function () {
    Route::get('login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
    Route::post('login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
});

// ── Admin Panel (protected) ────────────────────────────────────
Route::prefix('admin')->middleware(['web', \App\Http\Middleware\AdminWebMiddleware::class])->group(function () {
    // Dashboard
    Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    // Dramas CRUD
    Route::get('dramas', [AdminDramaController::class, 'index'])->name('admin.dramas.index');
    Route::get('dramas/create', [AdminDramaController::class, 'create'])->name('admin.dramas.create');
    Route::post('dramas', [AdminDramaController::class, 'store'])->name('admin.dramas.store');
    Route::get('dramas/{drama}', [AdminDramaController::class, 'show'])->name('admin.dramas.show');
    Route::get('dramas/{drama}/edit', [AdminDramaController::class, 'edit'])->name('admin.dramas.edit');
    Route::put('dramas/{drama}', [AdminDramaController::class, 'update'])->name('admin.dramas.update');
    Route::delete('dramas/{drama}', [AdminDramaController::class, 'destroy'])->name('admin.dramas.destroy');

    // Episodes (nested under drama)
    Route::get('dramas/{drama}/episodes/create', [AdminEpisodeController::class, 'create'])->name('admin.episodes.create');
    Route::post('dramas/{drama}/episodes', [AdminEpisodeController::class, 'store'])->name('admin.episodes.store');
    Route::get('dramas/{drama}/episodes/{episode}/edit', [AdminEpisodeController::class, 'edit'])->name('admin.episodes.edit');
    Route::put('dramas/{drama}/episodes/{episode}', [AdminEpisodeController::class, 'update'])->name('admin.episodes.update');
    Route::delete('dramas/{drama}/episodes/{episode}', [AdminEpisodeController::class, 'destroy'])->name('admin.episodes.destroy');

    // Categories
    Route::get('categories', [AdminCategoryController::class, 'index'])->name('admin.categories.index');
    Route::post('categories', [AdminCategoryController::class, 'store'])->name('admin.categories.store');
    Route::put('categories/{category}', [AdminCategoryController::class, 'update'])->name('admin.categories.update');
    Route::delete('categories/{category}', [AdminCategoryController::class, 'destroy'])->name('admin.categories.destroy');

    // Tags
    Route::get('tags', [AdminTagController::class, 'index'])->name('admin.tags.index');
    Route::post('tags', [AdminTagController::class, 'store'])->name('admin.tags.store');
    Route::put('tags/{tag}', [AdminTagController::class, 'update'])->name('admin.tags.update');
    Route::delete('tags/{tag}', [AdminTagController::class, 'destroy'])->name('admin.tags.destroy');
});
