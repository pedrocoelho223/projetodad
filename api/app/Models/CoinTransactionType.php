<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoinTransactionType extends Model
{
    use SoftDeletes;

    protected $table = 'coin_transaction_types';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'type',
        'custom'
    ];

    protected $casts = [
        'custom' => 'array'
    ];
}
