<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Game extends Model
{
    // app/Models/Game.php

    use HasFactory;

    protected $fillable = [
        'type',
        'status',
        'began_at',
        'ended_at',
        'total_time',
        'created_by_user_id',
        'winner_user_id',
        'player1_user_id',
        'player2_user_id',
        'custom', // <-- IMPORTANTÍSSIMO
    ];

    protected $casts = [
        'began_at' => 'datetime',
        'ended_at' => 'datetime',
        'custom' => 'array', // <-- IMPORTANTÍSSIMO (para o Vue ler custom.hands etc)
        'total_time' => 'integer',
    ];

    public function player1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player1_user_id');
    }

    public function player2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player2_user_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
