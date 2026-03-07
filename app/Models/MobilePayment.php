<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MobilePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reference',
        'phone',
        'operator',
        'amount',
        'currency',
        'payment_type',
        'payable_id',
        'payable_type',
        'gateway_reference',
        'gateway_transaction_id',
        'status',
        'failure_reason',
        'gateway_response',
        'push_response',
        'completed_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'gateway_response' => 'array',
            'push_response' => 'array',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    // ── Scopes ─────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ── Helpers ────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return in_array($this->status, ['failed', 'cancelled', 'expired']);
    }

    public function markCompleted(string $operatorRef, array $callbackData): void
    {
        $this->update([
            'status' => 'completed',
            'gateway_transaction_id' => $operatorRef,
            'gateway_response' => $callbackData,
            'completed_at' => $callbackData['completed_at'] ?? now(),
        ]);
    }

    public function markFailed(string $reason, array $callbackData = []): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
            'gateway_response' => $callbackData,
        ]);
    }
}
