<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles, SoftDeletes;

    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'password',
        'avatar',
        'gender',
        'date_of_birth',
        'bio',
        'provider',
        'provider_id',
        'provider_token',
        'coin_balance',
        'language',
        'country',
        'is_active',
        'is_vip',
        'vip_expires_at',
        'fcm_token',
        'expo_push_token',
        'device_id',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'provider_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'is_active' => 'boolean',
            'is_vip' => 'boolean',
            'vip_expires_at' => 'datetime',
            'last_login_at' => 'datetime',
            'coin_balance' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────

    public function watchHistories(): HasMany
    {
        return $this->hasMany(WatchHistory::class);
    }

    public function watchlist(): BelongsToMany
    {
        return $this->belongsToMany(Drama::class, 'watchlists')->withTimestamps();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function coinTransactions(): HasMany
    {
        return $this->hasMany(CoinTransaction::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function episodeUnlocks(): HasMany
    {
        return $this->hasMany(EpisodeUnlock::class);
    }

    public function dailyRewards(): HasMany
    {
        return $this->hasMany(DailyReward::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->whereIn('status', ['active', 'cancelled'])
            ->where('ends_at', '>', now())
            ->latest('ends_at');
    }

    // ── Helpers ────────────────────────────────────────

    public function hasUnlockedEpisode(int $episodeId): bool
    {
        return $this->episodeUnlocks()->where('episode_id', $episodeId)->exists();
    }

    public function isVipActive(): bool
    {
        // Check VIP flag first (synced from subscriptions)
        if ($this->is_vip && $this->vip_expires_at && $this->vip_expires_at->isFuture()) {
            return true;
        }

        // Fallback: check for any active subscription directly
        return $this->subscriptions()
            ->whereIn('status', ['active', 'cancelled'])
            ->where('ends_at', '>', now())
            ->exists();
    }

    public function hasSufficientCoins(int $amount): bool
    {
        return $this->coin_balance >= $amount;
    }
}
