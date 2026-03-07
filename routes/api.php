<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\DramaController;
use App\Http\Controllers\Api\EpisodeController;
use App\Http\Controllers\Api\WatchHistoryController;
use App\Http\Controllers\Api\WatchlistController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Api\CoinController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DramaManagementController;
use App\Http\Controllers\Admin\EpisodeManagementController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\ContentManagementController;
use App\Http\Controllers\Admin\ReportManagementController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\PaymentController;

/*
|--------------------------------------------------------------------------
| API Routes - DramaBox Backend
|--------------------------------------------------------------------------
*/

// ── Payment Gateway Callback (no auth — called by payin.co.tz) ────
Route::post('payments/callback', [PaymentController::class, 'callback'])
    ->middleware('throttle:30,1');

// ── Guest Payment Routes (no auth — user identified by phone number) ──
Route::prefix('v1')->middleware('throttle:10,1')->group(function () {
    Route::post('guest/payments/purchase-coins', [PaymentController::class, 'purchaseCoins']);
    Route::post('guest/payments/purchase-subscription', [PaymentController::class, 'purchaseSubscription']);
    Route::get('guest/payments/{reference}/status', [PaymentController::class, 'status']);
});

// ── Public Auth Routes ─────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])
        ->middleware('throttle:5,1');
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');
    Route::post('social-login', [AuthController::class, 'socialLogin'])
        ->middleware('throttle:10,1');
});

// ── Public Content Routes (no auth required) ───────────────────────
Route::prefix('v1')->group(function () {
    // Home page
    Route::get('home', [DramaController::class, 'home']);

    // Dramas
    Route::get('dramas', [DramaController::class, 'index']);
    Route::get('dramas/featured', [DramaController::class, 'featured']);
    Route::get('dramas/trending', [DramaController::class, 'trending']);
    Route::get('dramas/new-releases', [DramaController::class, 'newReleases']);
    Route::get('dramas/{drama}', [DramaController::class, 'show']);

    // Categories
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{category}', [CategoryController::class, 'show']);

    // Episode (public details)
    Route::get('episodes/{episode}', [EpisodeController::class, 'show']);
    Route::get('episodes/{episode}/next', [EpisodeController::class, 'next']);

    // Comments (read only)
    Route::get('episodes/{episode}/comments', [CommentController::class, 'index']);

    // Ratings (read only)
    Route::get('dramas/{drama}/ratings', [RatingController::class, 'index']);
});

// ── Authenticated User Routes ──────────────────────────────────────
Route::prefix('v1')->middleware(['auth:sanctum', 'active'])->group(function () {
    // Auth
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::put('auth/fcm-token', [AuthController::class, 'updateFcmToken']);

    // Profile
    Route::put('profile', [ProfileController::class, 'update']);
    Route::put('profile/password', [ProfileController::class, 'changePassword']);
    Route::delete('profile', [ProfileController::class, 'destroy']);

    // Episode actions
    Route::post('episodes/{episode}/unlock', [EpisodeController::class, 'unlock']);
    Route::post('episodes/{episode}/progress', [EpisodeController::class, 'updateProgress']);

    // Watch History
    Route::get('watch-history', [WatchHistoryController::class, 'index']);
    Route::get('continue-watching', [WatchHistoryController::class, 'continueWatching']);
    Route::delete('watch-history/{watchHistory}', [WatchHistoryController::class, 'destroy']);
    Route::delete('watch-history', [WatchHistoryController::class, 'clearAll']);

    // Watchlist
    Route::get('watchlist', [WatchlistController::class, 'index']);
    Route::post('watchlist/{drama}', [WatchlistController::class, 'store']);
    Route::delete('watchlist/{drama}', [WatchlistController::class, 'destroy']);
    Route::get('watchlist/{drama}/check', [WatchlistController::class, 'check']);

    // Likes
    Route::post('dramas/{drama}/like', [LikeController::class, 'toggleDramaLike']);
    Route::post('episodes/{episode}/like', [LikeController::class, 'toggleEpisodeLike']);

    // Comments
    Route::post('episodes/{episode}/comments', [CommentController::class, 'store']);
    Route::delete('comments/{comment}', [CommentController::class, 'destroy']);
    Route::post('comments/{comment}/like', [CommentController::class, 'toggleLike']);

    // Ratings
    Route::post('dramas/{drama}/rate', [RatingController::class, 'store']);
    Route::delete('dramas/{drama}/rate', [RatingController::class, 'destroy']);

    // Subscriptions
    Route::get('subscriptions/plans', [SubscriptionController::class, 'plans']);
    Route::get('subscriptions/current', [SubscriptionController::class, 'current']);
    Route::get('subscriptions/history', [SubscriptionController::class, 'history']);
    Route::post('subscriptions/subscribe', [SubscriptionController::class, 'subscribe']);
    Route::post('subscriptions/cancel', [SubscriptionController::class, 'cancel']);
    Route::post('subscriptions/restore', [SubscriptionController::class, 'restore']);
    Route::post('subscriptions/verify-receipt', [SubscriptionController::class, 'verifyReceipt']);

    // Mobile Payments (authenticated — also available as guest below)
    Route::post('payments/purchase-coins', [PaymentController::class, 'purchaseCoins']);
    Route::post('payments/purchase-subscription', [PaymentController::class, 'purchaseSubscription']);
    Route::get('payments/{reference}/status', [PaymentController::class, 'status']);
    Route::get('payments/history', [PaymentController::class, 'history']);

    // Coins
    Route::get('coins/balance', [CoinController::class, 'balance']);
    Route::get('coins/transactions', [CoinController::class, 'transactions']);
    Route::get('coins/packages', [CoinController::class, 'packages']);
    Route::post('coins/daily-reward', [CoinController::class, 'claimDailyReward']);
    Route::get('coins/daily-reward/info', [CoinController::class, 'dailyRewardInfo']);
    Route::post('coins/ad-reward', [CoinController::class, 'adReward'])
        ->middleware('throttle:30,1');

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::put('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::put('notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    // Reports
    Route::post('reports', [ReportController::class, 'store']);
});

// ── Admin Routes ───────────────────────────────────────────────────
Route::prefix('admin')->middleware(['auth:sanctum', 'active', 'role:admin'])->group(function () {
    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index']);
    Route::get('dashboard/activity', [DashboardController::class, 'recentActivity']);

    // Drama Management
    Route::apiResource('dramas', DramaManagementController::class);
    Route::post('dramas/bulk-status', [DramaManagementController::class, 'bulkUpdateStatus']);

    // Episode Management
    Route::apiResource('dramas.episodes', EpisodeManagementController::class);
    Route::post('dramas/{drama}/episodes/reorder', [EpisodeManagementController::class, 'reorder']);

    // User Management
    Route::get('users', [UserManagementController::class, 'index']);
    Route::get('users/{user}', [UserManagementController::class, 'show']);
    Route::put('users/{user}', [UserManagementController::class, 'update']);
    Route::post('users/{user}/grant-coins', [UserManagementController::class, 'grantCoins']);
    Route::post('users/{user}/deduct-coins', [UserManagementController::class, 'deductCoins']);

    // Content Management
    Route::get('categories', [ContentManagementController::class, 'categories']);
    Route::post('categories', [ContentManagementController::class, 'storeCategory']);
    Route::put('categories/{category}', [ContentManagementController::class, 'updateCategory']);
    Route::delete('categories/{category}', [ContentManagementController::class, 'destroyCategory']);

    Route::get('tags', [ContentManagementController::class, 'tags']);
    Route::post('tags', [ContentManagementController::class, 'storeTag']);
    Route::put('tags/{tag}', [ContentManagementController::class, 'updateTag']);
    Route::delete('tags/{tag}', [ContentManagementController::class, 'destroyTag']);

    Route::get('banners', [ContentManagementController::class, 'banners']);
    Route::post('banners', [ContentManagementController::class, 'storeBanner']);
    Route::put('banners/{banner}', [ContentManagementController::class, 'updateBanner']);
    Route::delete('banners/{banner}', [ContentManagementController::class, 'destroyBanner']);

    Route::get('coin-packages', [ContentManagementController::class, 'coinPackages']);
    Route::post('coin-packages', [ContentManagementController::class, 'storeCoinPackage']);
    Route::put('coin-packages/{coinPackage}', [ContentManagementController::class, 'updateCoinPackage']);
    Route::delete('coin-packages/{coinPackage}', [ContentManagementController::class, 'destroyCoinPackage']);

    // Subscription Plans Management
    Route::get('subscription-plans', [ContentManagementController::class, 'subscriptionPlans']);
    Route::post('subscription-plans', [ContentManagementController::class, 'storeSubscriptionPlan']);
    Route::put('subscription-plans/{subscriptionPlan}', [ContentManagementController::class, 'updateSubscriptionPlan']);
    Route::delete('subscription-plans/{subscriptionPlan}', [ContentManagementController::class, 'destroySubscriptionPlan']);

    // Subscription Management
    Route::get('subscriptions', [ContentManagementController::class, 'subscriptions']);
    Route::get('subscriptions/{subscription}', [ContentManagementController::class, 'showSubscription']);
    Route::post('subscriptions/{subscription}/cancel', [ContentManagementController::class, 'cancelSubscription']);
    Route::post('subscriptions/{subscription}/refund', [ContentManagementController::class, 'refundSubscription']);

    // Mobile Payments Management
    Route::get('payments', [ContentManagementController::class, 'payments']);
    Route::get('payments/{mobilePayment}', [ContentManagementController::class, 'showPayment']);
    Route::get('payments/stats/summary', [ContentManagementController::class, 'paymentStats']);

    // Payment Gateway Settings
    Route::get('payment-gateway', [ContentManagementController::class, 'paymentGateway']);
    Route::put('payment-gateway', [ContentManagementController::class, 'updatePaymentGateway']);

    // Settings
    Route::get('settings', [ContentManagementController::class, 'settings']);
    Route::put('settings', [ContentManagementController::class, 'updateSettings']);

    // Report Management
    Route::get('reports', [ReportManagementController::class, 'index']);
    Route::put('reports/{report}', [ReportManagementController::class, 'review']);
});
