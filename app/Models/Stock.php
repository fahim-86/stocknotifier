<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $fillable = ['trading_code', 'ltp', 'fetched_at'];

    protected $casts = [
        'ltp'        => 'float',
        'fetched_at' => 'datetime',
    ];
}
