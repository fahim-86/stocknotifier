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
    //     'high_price' => 'decimal:8,2',
    //     'low_price' => 'decimal:8,2',
    // ];

    public function scopePriceAlert($query, $ltp)
    {
        return $query->where(function ($q) use ($ltp) {
            $q->where(function ($sub) use ($ltp) {
                $sub->whereNotNull('high_price')->where('high_price', '<=', $ltp);
            })->orWhere(function ($sub) use ($ltp) {
                $sub->whereNotNull('low_price')->where('low_price', '>=', $ltp);
            });
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
