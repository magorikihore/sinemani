<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'interval',
        'duration_days',
        'price',
        'original_price',
        'currency',
        'store_product_id',
        'coin_bonus',
        'daily_coin_bonus',
        'is_popular',
        'is_active',
        'sort_order',
        'features',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'original_price' => 'decimal:2',
            'coin_bonus' => 'integer',
            'daily_coin_bonus' => 'integer',
            'is_popular' => 'boolean',
            'is_active' => 'boolean',
            'features' => 'array',
        ];
    }

    // ── Relationships ──────────────────────────────────

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    // ── Scopes ─────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByInterval($query, string $interval)
    {
        return $query->where('interval', $interval);
    }

    // ── Helpers ────────────────────────────────────────

    public function getSavingsPercentAttribute(): ?int
    {
        if (!$this->original_price || $this->original_price <= $this->price) {
            return null;
        }

        return (int) round((1 - ($this->price / $this->original_price)) * 100);
    }

    public function getPricePerDayAttribute(): float
    {
        return round($this->price / $this->duration_days, 2);
    }
}
