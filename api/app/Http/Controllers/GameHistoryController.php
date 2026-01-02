<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GameHistoryController extends Controller
{
    /**
     * G4 – Leaderboard global (multiplayer only)
     */
    public function leaderboardGames(): JsonResponse
    {
        $leaderboard = Game::whereNotNull('games.player1_user_id')
            ->whereNotNull('games.player2_user_id') // ✅ multiplayer real
            ->whereNotNull('games.winner_user_id')
            ->join('users', 'games.winner_user_id', '=', 'users.id')
            ->select(
                'users.id as user_id',
                'users.nickname',
                DB::raw('COUNT(games.id) as wins')
            )
            ->groupBy('users.id', 'users.nickname')
            ->orderByDesc('wins')
            ->limit(10)
            ->get();

        return response()->json($leaderboard);
    }

    /**
     * G4 – Histórico pessoal multiplayer
     */
    public function myGames(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $games = Game::whereNotNull('player1_user_id')
            ->whereNotNull('player2_user_id') // ✅ multiplayer real
            ->where(function ($q) use ($userId) {
                $q->where('player1_user_id', $userId)
                  ->orWhere('player2_user_id', $userId);
            })
            ->with('winner:id,nickname')
            ->orderByDesc('ended_at')
            ->get([
                'id',
                'type',
                'status',
                'began_at',
                'ended_at',
                'winner_user_id',
                'player1_points',
                'player2_points',
                'marks',
            ]);

        return response()->json($games);
    }
}
