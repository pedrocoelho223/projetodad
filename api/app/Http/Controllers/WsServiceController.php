<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Game;
use App\Models\MatchModel;
use App\Services\CoinService;

class WsServiceController extends Controller
{
    // ✅ MULTIPLAYER JOGO: cobra fee (2 coins cada) e cria game
    public function createMultiplayerGame(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:3,9',
            'player1_user_id' => 'required|integer',
            'player2_user_id' => 'required|integer',
        ]);

        // Fee obrigatório: 2 coins por jogador
        CoinService::ensureEnough($data['player1_user_id'], 2);
        CoinService::ensureEnough($data['player2_user_id'], 2);

        CoinService::addTx($data['player1_user_id'], -2, 'Game fee', ['reason'=>'multiplayer_fee']);
        CoinService::addTx($data['player2_user_id'], -2, 'Game fee', ['reason'=>'multiplayer_fee']);

        $game = Game::create([
            'type' => $data['type'],
            'status' => 'Playing',
            'began_at' => now(),
            'player1_user_id' => $data['player1_user_id'],
            'player2_user_id' => $data['player2_user_id'],
            'custom' => ['mode'=>'multiplayer'],
        ]);

        return response()->json(['game_id' => $game->id]);
    }

    // ✅ MULTIPLAYER PARTIDA: cobra stake e cria match
    public function createMatch(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:3,9',
            'stake' => 'required|integer|min:3|max:100',
            'player1_user_id' => 'required|integer',
            'player2_user_id' => 'required|integer',
        ]);

        CoinService::ensureEnough($data['player1_user_id'], $data['stake']);
        CoinService::ensureEnough($data['player2_user_id'], $data['stake']);

        CoinService::addTx($data['player1_user_id'], -$data['stake'], 'Match stake', ['stake'=>$data['stake']]);
        CoinService::addTx($data['player2_user_id'], -$data['stake'], 'Match stake', ['stake'=>$data['stake']]);

        $match = MatchModel::create([
            'type' => $data['type'],
            'stake' => $data['stake'],
            'status' => 'Playing',
            'began_at' => now(),
            'player1_user_id' => $data['player1_user_id'],
            'player2_user_id' => $data['player2_user_id'],
            'player1_marks' => 0,
            'player2_marks' => 0,
            'custom' => ['mode'=>'match'],
        ]);

        return response()->json(['match_id' => $match->id]);
    }

    // ✅ terminar jogo (standalone multiplayer)
    public function endGame(Request $request)
    {
        $data = $request->validate([
            'game_id' => 'required|integer',
            'player1_points' => 'required|integer|min:0|max:120',
            'player2_points' => 'required|integer|min:0|max:120',
            'is_draw' => 'required|boolean',
            'winner_user_id' => 'nullable|integer',
            'loser_user_id' => 'nullable|integer',
            'ended_reason' => 'required|string',
        ]);

        $game = Game::findOrFail($data['game_id']);
        $game->status = 'Ended';
        $game->ended_at = now();
        $game->total_time = $game->began_at ? now()->diffInSeconds($game->began_at) : null;
        $game->winner_user_id = $data['winner_user_id'];
        $game->custom = array_merge($game->custom ?? [], [
            'player1_points' => $data['player1_points'],
            'player2_points' => $data['player2_points'],
            'is_draw' => $data['is_draw'],
            'ended_reason' => $data['ended_reason'],
        ]);
        $game->save();

        // ✅ payouts: draw => +1 coin cada; win => 3 / 4 / 6 (depende de capote/bandeira)
        if ($data['is_draw']) {
            CoinService::addTx($game->player1_user_id, +1, 'Refund', ['reason'=>'draw_refund'], $game->id);
            CoinService::addTx($game->player2_user_id, +1, 'Refund', ['reason'=>'draw_refund'], $game->id);
        } else {
            $winPts = max($data['player1_points'], $data['player2_points']);
            $payout = ($winPts === 120) ? 6 : (($winPts >= 91) ? 4 : 3);
            CoinService::addTx($data['winner_user_id'], +$payout, 'Game payout', [
                'payout'=>$payout,
                'win_points'=>$winPts,
                'reason'=>$data['ended_reason']
            ], $game->id);
        }

        return response()->json(['ok'=>true]);
    }

    // ✅ terminar match: vencedor recebe 2*stake - 1 (comissão)
    public function endMatch(Request $request)
    {
        $data = $request->validate([
            'match_id' => 'required|integer',
            'player1_marks' => 'required|integer|min:0|max:4',
            'player2_marks' => 'required|integer|min:0|max:4',
            'winner_user_id' => 'required|integer',
            'loser_user_id' => 'required|integer',
            'ended_reason' => 'required|string',
        ]);

        $match = MatchModel::findOrFail($data['match_id']);
        $match->status = 'Ended';
        $match->ended_at = now();
        $match->total_time = $match->began_at ? now()->diffInSeconds($match->began_at) : null;
        $match->winner_user_id = $data['winner_user_id'];
        $match->player1_marks = $data['player1_marks'];
        $match->player2_marks = $data['player2_marks'];
        $match->custom = array_merge($match->custom ?? [], ['ended_reason'=>$data['ended_reason']]);
        $match->save();

        $stake = (int)($match->stake ?? 3);
        $net = (2 * $stake) - 1; // comissão 1 coin

        CoinService::addTx($data['winner_user_id'], +$net, 'Match payout', [
            'stake'=>$stake,
            'net'=>$net,
            'commission'=>1,
            'reason'=>$data['ended_reason']
        ], null, $match->id);

        return response()->json(['ok'=>true]);
    }
}
