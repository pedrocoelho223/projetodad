<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoinTransaction extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'transaction_datetime',
        'user_id',
        'match_id',
        'game_id',
        'coin_transaction_type_id',
        'coins',
        'custom',
    ];

    protected function casts(): array
    {
        return [
            'transaction_datetime' => 'datetime',
            'custom' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
