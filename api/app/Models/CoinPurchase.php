<?php

namespace App\Models;
use App\Models\User;

use Illuminate\Database\Eloquent\Model;

class CoinPurchase extends Model
{
    protected $table = 'coin_purchases';

    public $timestamps = false;

    protected $fillable = [
        'purchase_datetime',
        'user_id',
        'coin_transaction_id',
        'euros',
        'payment_type',
        'payment_reference',
        'custom'
    ];

    protected $casts = [
        'purchase_datetime' => 'datetime',
        'custom' => 'array'
    ];

     public function user()
    {
        return $this->belongsTo(User::class);
    }
}
