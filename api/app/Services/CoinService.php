<?php

namespace App\Services;

use App\Models\User;
use App\Models\CoinTransaction;
use App\Models\CoinTransactionType;
use Illuminate\Support\Facades\DB;

class CoinService
{
    // ✅ procura por name (não depende de seed/IDs fixos)
    public static function typeId(string $name): int
    {
        $t = CoinTransactionType::where('name', $name)->first();
        if (!$t) abort(500, "CoinTransactionType em falta: {$name}");
        return $t->id;
    }

    public static function ensureEnough(int $userId, int $needed): void
    {
        $u = User::findOrFail($userId);
        if ((int)$u->coins_balance < $needed) {
            abort(422, "Saldo insuficiente");
        }
    }

    public static function addTx(int $userId, int $coins, string $typeName, array $custom = [], ?int $gameId=null, ?int $matchId=null): void
    {
        DB::transaction(function () use ($userId, $coins, $typeName, $custom, $gameId, $matchId) {
            $u = User::lockForUpdate()->findOrFail($userId);
            $u->coins_balance = (int)$u->coins_balance + $coins;
            $u->save();

            CoinTransaction::create([
                'transaction_datetime' => now(),
                'user_id' => $userId,
                'game_id' => $gameId,
                'match_id' => $matchId,
                'coin_transaction_type_id' => self::typeId($typeName),
                'coins' => $coins,
                'custom' => $custom,
            ]);
        });
    }
}
