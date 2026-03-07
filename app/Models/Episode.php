<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Episode extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'drama_id',
        'title',
        'slug',
        'description',
        'thumbnail',
        'video_url',
        'video_path',
        'hls_url',
        'duration',
        'file_size',
        'resolution',
        'episode_number',
        'season_number',
        'is_free',
        'coin_price',
        'status',
        'view_count',
        'like_count',
        'sort_order',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_free' => 'boolean',
            'duration' => 'integer',
            'file_size' => 'integer',
            'coin_price' => 'integer',
            'view_count' => 'integer',
            'like_count' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────

    public function drama(): BelongsTo
    {
        return $this->belongsTo(Drama::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function watchHistories(): HasMany
    {
        return $this->hasMany(WatchHistory::class);
    }

    public function unlocks(): HasMany
    {
        return $this->hasMany(EpisodeUnlock::class);
    }

    // ── Scopes ─────────────────────────────────────────

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeFree($query)
    {
        return $query->where('is_free', true);
    }

    // ── Helpers ────────────────────────────────────────

    public function getEffectivePrice(): int
    {
        if ($this->is_free) {
            return 0;
        }
        return $this->coin_price > 0 ? $this->coin_price : $this->drama->coin_price;
    }

    public function isAccessibleBy(User $user): bool
    {
        if ($this->is_free || $this->drama->is_free) {
            return true;
        }

        // Active subscription grants access to all content
        if ($user->isVipActive()) {
            return true;
        }

        return $user->hasUnlockedEpisode($this->id);
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
        $this->drama->incrementViewCount();
    }
}
