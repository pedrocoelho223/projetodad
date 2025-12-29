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
    // --- MÉTODOS DE API RESOURCE (HISTÓRICO) ---

    // Lista de jogos (Admin/User)
    public function index(Request $request)
    {
        // Se for para leaderboard (top 5 vitórias, por exemplo)
        if ($request->path() == 'api/leaderboard/top') {
             return response()->json(
                \App\Models\User::orderByDesc('coins_balance')->take(5)->get()
            );
        }

        return Game::with(['winner', 'player1'])->orderByDesc('created_at')->paginate(10);
    }

    // Iniciar Jogo (Single Player)
    public function start(Request $request)
    {
        // Cria um ID único para este jogo
        $gameId = 'game_' . Str::random(10);

        // Instancia o motor
        $engine = new BiscaEngine();

        // Guarda o estado na Cache por 30 mins
        Cache::put($gameId, $engine, 1800);

        return response()->json([
            'game_token' => $gameId,
            'state' => $engine->getState()
        ]);
    }

    // Fazer Jogada
    public function play(Request $request)
    {
        $request->validate([
            'game_token' => 'required',
            'card_index' => 'required|integer'
        ]);

        $gameId = $request->game_token;
        $engine = Cache::get($gameId);

        if (!$engine) {
            return response()->json(['message' => 'Jogo expirado ou não encontrado.'], 404);
        }

        // Tenta jogar
        $success = $engine->playerMove($request->card_index);

        if (!$success) {
            return response()->json(['message' => 'Jogada inválida ou não é a tua vez.'], 400);
        }

        // Atualiza Cache
        Cache::put($gameId, $engine, 1800);

        // 4. Se o jogo acabou, gravar na BD
        $state = $engine->getState();

        if ($state['gameOver']) {
            Game::create([
                'type' => 'singleplayer',
                'status' => 'ended',
                'began_at' => now()->subMinutes(5),
                'ended_at' => now(),
                'player1_user_id' => Auth::id(), // <--- MUDOU AQUI (Mais seguro)
                'winner_user_id' => ($state['scores']['player'] > 60) ? Auth::id() : null, // <--- E AQUI
                'custom' => $state
            ]);

            // Limpa da cache para não jogar mais
            Cache::forget($gameId);
        }

        return response()->json([
            'game_token' => $gameId, // Mantém o token
            'state' => $engine->getState()
        ]);
    }
}
