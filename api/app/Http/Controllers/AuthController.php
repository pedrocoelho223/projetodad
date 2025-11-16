<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\CoinTransaction;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|confirmed|min:3',
            'nickname' => 'required|string|unique:users', // obrigatório no projeto
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'nickname' => $validated['nickname'],
            'password' => Hash::make($validated['password']),
            'coins_balance' => 10, // BONUS inicial
        ]);

        CoinTransaction::create([
            'transaction_datetime' => now(),
            'user_id' => $user->id,
            'coin_transaction_type_id' => 1, // "Bonus"
            'coins' => 10,
        ]);

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Credenciais inválidas'], 401);
        }

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user
        ]);
    }

    public function profile(Request $request)
    {
        return response()->json($request->user());
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'nickname' => 'sometimes|string|max:255',
        ]);

        $user->update($validated);

        return response()->json(['message' => 'Perfil atualizado com sucesso']);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:3|confirmed'
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['message' => 'Password atual incorreta'], 400);
        }

        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return response()->json(['message' => 'Password atualizada com sucesso']);
    }

    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        $user->tokens()->delete();   // remove tokens
        $user->delete();             // soft-delete


        return response()->json(['message' => 'Conta eliminada']);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logout efetuado']);
    }
}
