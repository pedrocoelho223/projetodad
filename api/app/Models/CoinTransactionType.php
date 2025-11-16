<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoinTransactionType extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'type',
        'custom',
    ];

    protected function casts(): array
    {
        return [
            'custom' => 'array',
        ];
    }

    public function transactions()
    {
        return $this->hasMany(CoinTransaction::class);
    }
}
