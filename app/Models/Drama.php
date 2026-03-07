<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Drama extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'synopsis',
        'description',
        'cover_image',
        'banner_image',
        'trailer_url',
        'category_id',
        'status',
        'content_rating',
        'language',
        'country',
        'release_year',
        'director',
        'cast',
        'total_episodes',
        'published_episodes',
        'view_count',
        'like_count',
        'rating',
        'rating_count',
        'is_featured',
        'is_trending',
        'is_new_release',
        'is_free',
        'coin_price',
        'sort_order',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'cast' => 'array',
            'is_featured' => 'boolean',
            'is_trending' => 'boolean',
            'is_new_release' => 'boolean',
            'is_free' => 'boolean',
            'view_count' => 'integer',
            'like_count' => 'integer',
            'rating' => 'decimal:2',
            'rating_count' => 'integer',
            'coin_price' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class)->orderBy('episode_number');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function watchlistedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'watchlists')->withTimestamps();
    }

    public function watchHistories(): HasMany
    {
        return $this->hasMany(WatchHistory::class);
    }

    // ── Scopes ─────────────────────────────────────────

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeTrending($query)
    {
        return $query->where('is_trending', true);
    }

    public function scopeNewRelease($query)
    {
        return $query->where('is_new_release', true);
    }

    // ── Helpers ────────────────────────────────────────

    public function updateRating(): void
    {
        $this->rating = $this->ratings()->avg('score') ?? 0;
        $this->rating_count = $this->ratings()->count();
        $this->saveQuietly();
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }
}
