<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\User;
use App\Models\CoinPurchase;
use App\Models\MatchModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class StatisticsController extends Controller
{
    public function getAdminStats(Request $request)
    {
        // Verificação manual de administrador
        if ($request->user()->type !== 'A') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'summary' => [
                'total_games' => Game::count(),
                'total_matches' => MatchModel::count(),
                'total_revenue' => (float) CoinPurchase::sum('euros'),
                'new_users_week' => User::where('created_at', '>=', now()->subDays(7))->count(),
            ],

            'revenue_history' => CoinPurchase::select(
                DB::raw('DATE(purchase_datetime) as date'),
                DB::raw('SUM(euros) as total')
            )
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->take(30)
            ->get(),

            'game_variants' => Game::select('type', DB::raw('count(*) as total'))
                ->groupBy('type')
                ->get(),

            'recent_purchases' => CoinPurchase::with('user:id,nickname')
                ->orderByDesc('purchase_datetime')
                ->take(10)
                ->get()
        ]);
    }

    public function getPublicStats()
{
    return response()->json([
        'summary' => [
            'total_players' => User::where('type', 'P')->count(),
            'total_games' => Game::where('status', 'Ended')->count(),
            'total_matches' => MatchModel::count(),
            'recent_activity_24h' => Game::where('began_at', '>=', now()->subHours(24))->count(),
        ],

        // 📈 Gráfico: Jogos por dia (últimos 30 dias)
        'games_history' => Game::select(
                DB::raw('DATE(began_at) as date'),
                DB::raw('COUNT(*) as total')
            )
            ->where('status', 'Ended')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->take(30)
            ->get(),

        // 📊 Gráfico: Distribuição por variante
        'game_variants' => Game::select('type', DB::raw('count(*) as total'))
            ->groupBy('type')
            ->get(),
    ]);
}
public function getAdminTransactions()
{
    return CoinPurchase::with('user:id,nickname,email')
        ->latest('purchase_datetime')
        ->paginate(15);
}

public function playerHistory(int $id): JsonResponse
{
    $games = Game::where('mode', 'multi')
        ->where(function ($q) use ($id) {
            $q->where('player1_user_id', $id)
              ->orWhere('player2_user_id', $id);
        })
        ->with([
            'player1:id,nickname',
            'player2:id,nickname',
            'winner:id,nickname'
        ])
        ->orderByDesc('ended_at')
        ->get();

    return response()->json($games);
}


}
