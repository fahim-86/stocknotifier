<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Stock extends Model
{
    use HasFactory;


    protected $fillable = [
        'trading_code',
        'ltp',
        'last_fetched_at',
    ];
}
