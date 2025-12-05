<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoinTransaction extends Model
{
    use HasFactory;

    // Desativar timestamps automáticos se a tua tabela não tiver created_at/updated_at padrão
    // Mas o enunciado diz "transaction_datetime", por isso podes ter de gerir manualmente ou mapear.
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'coin_transaction_type_id',
        'transaction_datetime',
        'coins',
        'custom'
    ];
}
