<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Support\SecureUrl;
use Illuminate\Support\Facades\Storage;

class EpisodeSubtitle extends Model
{
    use HasFactory;

    protected $fillable = [
        'episode_id',
        'language',
        'label',
        'file_path',
        'format',
        'sort_order',
    ];

    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

    public function getUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }
        return SecureUrl::media($this->file_path);
    }
}
