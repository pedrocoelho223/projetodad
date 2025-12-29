<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CoinTransaction;

class CoinController extends Controller
{
    public function balance(Request $request)
    {
        $user = $request->user();

        // Admins não têm moedas
        if ($user->type !== 'P') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // ✅ saldo vem diretamente da BD (users.coins_balance)
        return response()->json([
            'coins_balance' => (int) $user->coins_balance
        ]);
    }

    public function transactions(Request $request)
    {
        $user = $request->user();

        // Admins veem todas as transações
        if ($user->type === 'A') {
        // Carrega todas as transações de toda a gente (com dados do user para saberes de quem é)
        $transactions = CoinTransaction::with(['type', 'user'])
            ->orderByDesc('transaction_datetime')
            ->get();

        return response()->json($transactions);
    }

        // Jogador (Vê apenas sãs suas transações)
        $transactions = CoinTransaction::with('type')
            ->where('user_id', $user->id)
            ->orderByDesc('transaction_datetime')
            ->get();

        return response()->json($transactions);
    }
}
