<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{

    protected $fillable = [
        'user_id',
        'trading_code',
        'high_price',
        'low_price',
        'is_active'
    ];

    protected $casts = [
        'high_price' => 'float',
        'low_price'  => 'float',
        'is_active'  => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: alerts whose high_price has been reached or exceeded by $ltp,
     * OR whose low_price has been met or undercut by $ltp.
     *
     * FIX: high alert fires when LTP >= high_price (price went UP to target).
     *      low  alert fires when LTP <= low_price  (price went DOWN to target).
     */
    public function scopeTriggeredBy($query, float $ltp)
    {
        return $query->where(function ($q) use ($ltp) {
            $q->where(function ($sub) use ($ltp) {
                $sub->whereNotNull('high_price')
                    ->where('high_price', '<=', $ltp); // LTP reached or passed high target
            })->orWhere(function ($sub) use ($ltp) {
                $sub->whereNotNull('low_price')
                    ->where('low_price', '>=', $ltp);  // LTP dropped to or below low target
            });
        });
    }
}
