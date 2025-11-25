<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;
use App\Http\Requests\StoreGameRequest;
use App\Http\Resources\GameResource;

class GameController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $games = Game::with(['player1', 'winner'])
            ->orderBy('created_at', 'desc')
            ->get(); // ou ->paginate()

        return response()->json(['data' => $games]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGameRequest $request)
{
    $validated = $request->validated();
    $user = $request->user(); // Pega no utilizador autenticado

    $game = new Game();

    // --- CORREÇÃO AQUI ---
    // Preencher TODOS os campos de identificação
   // $game->created_by = $user->id;
    $game->player1_id = $user->id; // CRÍTICO: Isto faz aparecer o nome no histórico
    $game->winner_id = $user->id;  // Num single player acabado, o player ganha
    // ---------------------

    $game->type = 'S';
    $game->status = 'ended';

    $game->total_time = $validated['total_time'] ?? 0;
    $game->total_moves_played = $validated['total_moves_played'];
    $game->player1_moves = $validated['total_moves_played']; // Boa prática preencher este também

    $game->save();

    return new GameResource($game);
}
    /**
     * Display the specified resource.
     */
    public function show(Game $game)
    {
        return new GameResource($game);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreGameRequest $request, Game $game)
    {
        $game->update($request->validated());
        return new GameResource($game);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Game $game)
    {
        //
    }
}
