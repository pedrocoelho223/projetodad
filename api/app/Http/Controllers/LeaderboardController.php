<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    public function top(Request $request)
    {
        $limit = (int) $request->query('limit', 3);
        if ($limit <= 0) $limit = 3;
        if ($limit > 50) $limit = 50;

        // scope: overall | single | multi
        $scope = $request->query('scope', 'overall');

        $q = DB::table('games')
            ->join('users', 'users.id', '=', 'games.winner_user_id')
            ->select(
                'users.id',
                'users.nickname',
                DB::raw('COUNT(games.id) as total_wins')
            )
            ->whereNotNull('games.winner_user_id')
            // não contar empates
            ->where(function ($qq) {
                $qq->whereNull('games.is_draw')->orWhere('games.is_draw', 0);
            })
            // não contar pendentes
            ->whereNotIn('games.status', ['P', 'Pending']);

        // single vs multi (assunção mais provável pelo teu schema)
        if ($scope === 'single') {
            $q->where(function ($qq) {
                $qq->whereNull('games.player2_user_id')->orWhere('games.player2_user_id', 0);
            });
        } elseif ($scope === 'multi') {
            $q->whereNotNull('games.player2_user_id')->where('games.player2_user_id', '!=', 0);
        }

        $players = $q
            ->groupBy('users.id', 'users.nickname')
            ->orderByDesc('total_wins')
            ->limit($limit)
            ->get();

        return response()->json(['data' => $players], 200);
    }
}
