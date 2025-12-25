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

        if ($user->type !== 'P') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Se tiveres relação "type" no model CoinTransaction, mantém o with('type')
        $transactions = CoinTransaction::with('type')
            ->where('user_id', $user->id)
            ->orderByDesc('transaction_datetime')
            ->get();

        return response()->json($transactions);
    }
}
