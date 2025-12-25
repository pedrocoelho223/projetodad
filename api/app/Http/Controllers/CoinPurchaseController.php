<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\CoinTransaction;
use App\Models\CoinTransactionType;
use App\Models\CoinPurchase;

class CoinPurchaseController extends Controller
{
    public function purchase(Request $request)
    {
        $user = $request->user();

        // Admins não podem comprar moedas
        if ($user->type !== 'P') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Validação
        $validated = $request->validate([
            'type' => 'required|in:MBWAY,PAYPAL,IBAN,MB,VISA',
            'reference' => 'required|string',
            'value' => 'required|integer|min:1|max:99'
        ]);

        // Chamada API externa (OBRIGATÓRIA)
        $response = Http::post(
            'https://dad-payments-api.vercel.app/api/debit',
            [
                'type' => $validated['type'],
                'reference' => $validated['reference'],
                'value' => $validated['value']
            ]
        );

        if ($response->failed()) {
            return response()->json([
                'message' => 'Payment gateway error',
                'details' => $response->json()
            ], 422);
        }

        // € -> coins
        $coins = $validated['value'] * 10;

        DB::transaction(function () use ($user, $validated, $coins) {

            $type = CoinTransactionType::where('name', 'Coin purchase')->firstOrFail();

            $transaction = CoinTransaction::create([
                'transaction_datetime' => now(),
                'user_id' => $user->id,
                'coin_transaction_type_id' => $type->id,
                'coins' => $coins
            ]);

            CoinPurchase::create([
                'purchase_datetime' => now(),
                'user_id' => $user->id,
                'coin_transaction_id' => $transaction->id,
                'euros' => $validated['value'],
                'payment_type' => $validated['type'],
                'payment_reference' => $validated['reference']
            ]);

            $user->coins_balance += $coins;
            $user->save();
        });

        return response()->json([
            'message' => 'Coins purchased successfully',
            'coins_added' => $coins,
            'new_balance' => $user->coins_balance
        ], 201);
    }
}
