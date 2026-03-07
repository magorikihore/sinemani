<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reward_date',
        'coins_earned',
        'streak_day',
    ];

    protected function casts(): array
    {
        return [
            'reward_date' => 'date',
            'coins_earned' => 'integer',
            'streak_day' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
