<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CoinTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }


        /** @var \App\Models\User $user */
        $user = Auth::user();

        // [NOVO] Verificação de Bloqueio (Requisito G5)
        // Se o user estiver bloqueado, impedimos o login e revogamos a sessão
        if ($user->blocked) {
            Auth::guard('web')->logout();
            return response()->json([
                'message' => 'A sua conta está bloqueada. Contacte o administrador.'
            ], 403);
        }

        // Agora o VS Code já reconhece o createToken porque sabe que é o Teu User
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    // --- REGISTO (Requisito G1 + G2) ---
    public function register(Request $request)
    {
        // 1. Validar dados
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:3', // Requisito min 3 chars
            'nickname' => 'required|string|max:20|unique:users',
            'photo_file' => 'nullable|image|max:4096'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // 2. Criar User + Transação (Bónus) de forma atómica
        try {
            DB::beginTransaction();

            $photoPath = null;
            if ($request->hasFile('photo_file')) {
                $photoPath = $request->file('photo_file')->store('photos', 'public');
            }

            // Cria o User
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'nickname' => $request->nickname,
                'type' => 'P', // Player por defeito
                'blocked' => 0,
                'photo_avatar_filename' => $photoPath,
                'coins_balance' => 10 // Começa logo com 10 (Requisito G1)
            ]);

            // Regista a transação das moedas (Requisito G2)
            // Tens de garantir que tens o tipo 'Bonus' na tabela coin_transaction_types
            $bonusTypeId = DB::table('coin_transaction_types')->where('name', 'Bonus')->value('id');

            CoinTransaction::create([
                'user_id' => $user->id,
                'coin_transaction_type_id' => $bonusTypeId,
                'transaction_datetime' => now(),
                'coins' => 10,
                'custom' => json_encode(['desc' => 'Welcome Bonus'])
            ]);

            DB::commit();

            // Login automático após registo
            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'user' => $user,
                'token' => $token,
                'message' => 'Registo com sucesso! Ganhou 10 moedas de bónus.'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erro ao registar.', 'error' => $e->getMessage()], 500);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}
