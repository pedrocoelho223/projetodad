<?php

namespace App\Http\Controllers;

use App\Game\BiscaEngine;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class GameController extends Controller
{
    /**
     * Lista geral de jogos (admin / histórico global)
     */
    public function index()
    {
        return Game::with(['winner', 'player1'])
            ->orderByDesc('ended_at')
            ->paginate(10);
    }

    /**
     * Iniciar jogo Singleplayer
     */
    public function start(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Não autenticado'], 401);
        }

        $gameId = 'game_' . Str::random(12);

        $engine = new BiscaEngine();

        Cache::put($gameId, $engine, 1800); // 30 minutos

        return response()->json([
            'game_token' => $gameId,
            'state' => $engine->getState()
        ]);
    }

    /**
     * Jogada do jogador
     */
    public function play(Request $request)
    {
        $request->validate([
            'game_token' => 'required|string',
            'card_index' => 'required|integer'
        ]);

        if (!Auth::check()) {
            return response()->json(['message' => 'Não autenticado'], 401);
        }

        $gameId = $request->game_token;
        $engine = Cache::get($gameId);

        if (!$engine instanceof BiscaEngine) {
            return response()->json(['message' => 'Jogo expirado ou inválido'], 404);
        }

        $success = $engine->playerMove($request->card_index);

        if (!$success) {
            return response()->json(['message' => 'Jogada inválida'], 400);
        }

        Cache::put($gameId, $engine, 1800);

        $state = $engine->getState();

        // Se o jogo terminou, gravar na BD
        if ($state['gameOver']) {
            $playerWon = $state['scores']['player'] > $state['scores']['bot'];

            Game::create([
                'type' => 'single',
                'status' => 'ended',
                'began_at' => now()->subMinutes(5),
                'ended_at' => now(),
                'player1_user_id' => Auth::id(),
                'winner_user_id' => $playerWon ? Auth::id() : null,
                'custom' => $state,
            ]);

            Cache::forget($gameId);
        }


        return response()->json([
            'game_token' => $gameId,
            'state' => $state
        ]);
    }

    /**
     * Histórico do utilizador autenticado
     */
    public function myHistory(Request $request)
    {
        return Game::where(function ($query) {
            $query->where('player1_user_id', Auth::id())
                ->orWhere('player2_user_id', Auth::id());
        })
            ->where('status', 'ended')
            ->orderByDesc('ended_at')
            ->get();
    }
}
