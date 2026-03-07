<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'order_id',
        'transaction_id',
        'payment_provider',
        'store_transaction_id',
        'amount_paid',
        'currency',
        'status',
        'auto_renew',
        'starts_at',
        'ends_at',
        'cancelled_at',
        'renewed_at',
        'cancellation_reason',
        'payment_meta',
    ];

    protected function casts(): array
    {
        return [
            'amount_paid' => 'decimal:2',
            'auto_renew' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'renewed_at' => 'datetime',
            'payment_meta' => 'array',
        ];
    }

    // ── Relationships ──────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    // ── Scopes ─────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['active', 'cancelled'])
            ->where('ends_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('ends_at', '<=', now())
            ->where('status', '!=', 'refunded');
    }

    // ── Helpers ────────────────────────────────────────

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'cancelled'])
            && $this->ends_at
            && $this->ends_at->isFuture();
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function daysRemaining(): int
    {
        if (!$this->ends_at || $this->ends_at->isPast()) {
            return 0;
        }

        return (int) now()->diffInDays($this->ends_at);
    }

    public function intervalLabel(): string
    {
        return $this->plan?->interval ?? 'unknown';
    }
}
