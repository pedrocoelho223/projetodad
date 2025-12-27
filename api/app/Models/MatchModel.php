<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchModel extends Model
{
    protected $table = 'matches';

    protected $fillable = [
        'type','status','began_at','ended_at','total_time',
        'player1_user_id','player2_user_id','winner_user_id',
        'player1_marks','player2_marks',
        'stake',
        'custom',
    ];

    protected $casts = [
        'began_at' => 'datetime',
        'ended_at' => 'datetime',
        'custom' => 'array',
        'total_time' => 'integer',
        'stake' => 'integer',
    ];

    public function player1(): BelongsTo { return $this->belongsTo(User::class, 'player1_user_id'); }
    public function player2(): BelongsTo { return $this->belongsTo(User::class, 'player2_user_id'); }
    public function winner(): BelongsTo  { return $this->belongsTo(User::class, 'winner_user_id'); }
}
