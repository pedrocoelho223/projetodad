<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Game extends Model
{
    // app/Models/Game.php

    use HasFactory;

    // Garante que estes campos podem ser escritos
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
        // Adiciona outros campos se necessário (ex: deck_used, custom)
    ];

public function player1()
{
    return $this->belongsTo(User::class, 'player1_user_id');
}

public function player2()
{
    return $this->belongsTo(User::class, 'player2_user_id');
}

public function winner()
{
    return $this->belongsTo(User::class, 'winner_user_id');
}

public function created_by()
{
    return $this->belongsTo(User::class, 'created_by_user_id');
}

}
