<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AdminAuthController;
use App\Http\Controllers\Web\AdminDashboardController;
use App\Http\Controllers\Web\AdminDramaController;
use App\Http\Controllers\Web\AdminEpisodeController;
use App\Http\Controllers\Web\AdminCategoryController;
use App\Http\Controllers\Web\AdminTagController;
use App\Http\Controllers\Web\AdminUserController;
use App\Http\Controllers\Web\AdminTransactionController;
use App\Http\Controllers\Web\AdminSettingsController;
use App\Http\Controllers\Web\AdminSubscriptionController;
use App\Http\Controllers\Web\AdminCoinPackageController;
use App\Http\Controllers\Web\AdminBannerController;

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

    // Users
    Route::get('users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::get('users/{user}', [AdminUserController::class, 'show'])->name('admin.users.show');
    Route::patch('users/{user}/toggle-active', [AdminUserController::class, 'toggleActive'])->name('admin.users.toggle-active');
    Route::patch('users/{user}/toggle-vip', [AdminUserController::class, 'toggleVip'])->name('admin.users.toggle-vip');
    Route::post('users/{user}/grant-coins', [AdminUserController::class, 'grantCoins'])->name('admin.users.grant-coins');
    Route::post('users/{user}/deduct-coins', [AdminUserController::class, 'deductCoins'])->name('admin.users.deduct-coins');

    // Transactions
    Route::get('transactions', [AdminTransactionController::class, 'index'])->name('admin.transactions.index');
    Route::get('transactions/{transaction}', [AdminTransactionController::class, 'show'])->name('admin.transactions.show');

    // Subscriptions & Plans
    Route::get('subscriptions', [AdminSubscriptionController::class, 'subscriptions'])->name('admin.subscriptions.index');
    Route::patch('subscriptions/{subscription}/cancel', [AdminSubscriptionController::class, 'cancelSubscription'])->name('admin.subscriptions.cancel');
    Route::get('subscriptions/plans', [AdminSubscriptionController::class, 'plans'])->name('admin.subscriptions.plans');
    Route::get('subscriptions/plans/create', [AdminSubscriptionController::class, 'createPlan'])->name('admin.subscriptions.plans.create');
    Route::post('subscriptions/plans', [AdminSubscriptionController::class, 'storePlan'])->name('admin.subscriptions.plans.store');
    Route::get('subscriptions/plans/{plan}/edit', [AdminSubscriptionController::class, 'editPlan'])->name('admin.subscriptions.plans.edit');
    Route::put('subscriptions/plans/{plan}', [AdminSubscriptionController::class, 'updatePlan'])->name('admin.subscriptions.plans.update');
    Route::delete('subscriptions/plans/{plan}', [AdminSubscriptionController::class, 'destroyPlan'])->name('admin.subscriptions.plans.destroy');

    // Coin Packages
    Route::get('coin-packages', [AdminCoinPackageController::class, 'index'])->name('admin.coin-packages.index');
    Route::post('coin-packages', [AdminCoinPackageController::class, 'store'])->name('admin.coin-packages.store');
    Route::put('coin-packages/{coinPackage}', [AdminCoinPackageController::class, 'update'])->name('admin.coin-packages.update');
    Route::delete('coin-packages/{coinPackage}', [AdminCoinPackageController::class, 'destroy'])->name('admin.coin-packages.destroy');

    // Banners
    Route::get('banners', [AdminBannerController::class, 'index'])->name('admin.banners.index');
    Route::post('banners', [AdminBannerController::class, 'store'])->name('admin.banners.store');
    Route::put('banners/{banner}', [AdminBannerController::class, 'update'])->name('admin.banners.update');
    Route::delete('banners/{banner}', [AdminBannerController::class, 'destroy'])->name('admin.banners.destroy');

    // Settings
    Route::get('settings', [AdminSettingsController::class, 'index'])->name('admin.settings.index');
    Route::put('settings', [AdminSettingsController::class, 'update'])->name('admin.settings.update');
    Route::post('settings', [AdminSettingsController::class, 'store'])->name('admin.settings.store');
    Route::get('settings/payment', [AdminSettingsController::class, 'paymentGateway'])->name('admin.settings.payment');
    Route::put('settings/payment', [AdminSettingsController::class, 'updatePaymentGateway'])->name('admin.settings.payment.update');
});
