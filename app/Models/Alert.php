<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    // use HasFactory;

    protected $fillable = [
        'user_id',
        'trading_code',
        'high_price',
        'low_price',
        'is_active'
    ];

    // protected $casts = [
    //     'high_price' => 'float',
    //     'low_price' => 'float',
    //     'is_active' => 'boolean',
    // ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
