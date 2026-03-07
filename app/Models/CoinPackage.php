<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoinPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'coins',
        'bonus_coins',
        'price',
        'currency',
        'store_product_id',
        'is_popular',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'coins' => 'integer',
            'bonus_coins' => 'integer',
            'price' => 'decimal:2',
            'is_popular' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getTotalCoinsAttribute(): int
    {
        return $this->coins + $this->bonus_coins;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
