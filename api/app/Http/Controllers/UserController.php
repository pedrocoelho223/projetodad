<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Models\User;

class UserController extends Controller
{
    public function show(Request $request)
    {
        return $request->user();
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'nickname' => ['sometimes', 'string', 'max:20', Rule::unique('users')->ignore($user->id)],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'photo_file' => 'nullable|image|max:4096', // Foto opcional
            'password' => 'nullable|string|min:3', // Só valida se enviada
        ]);

        // Upload de nova foto
        if ($request->hasFile('photo_file')) {
            // Apagar foto antiga se existir e não for default
            if ($user->photo_avatar_filename && Storage::disk('public')->exists($user->photo_avatar_filename)) {
                Storage::disk('public')->delete($user->photo_avatar_filename);
            }
            $path = $request->file('photo_file')->store('photos', 'public');
            $user->photo_avatar_filename = $path;
        }

        if ($request->filled('name')) $user->name = $request->name;
        if ($request->filled('nickname')) $user->nickname = $request->nickname;
        if ($request->filled('email')) $user->email = $request->email;
        if ($request->filled('password')) $user->password = Hash::make($request->password);

        $user->save();

        return response()->json($user);
    }

    public function destroy(Request $request)
    {
        $user = $request->user();

        // Bloquear eliminação de Admins
        if ($user->type === 'A') {
            return response()->json([
                'message' => 'Administradores não podem apagar a própria conta.',
            ], 403); // Forbidden
        }

        // 1. Validar password (Requisito G1: confirmação explícita) [cite: 80]
        $request->validate(['password' => 'required']);

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Password incorreta.'], 403);
        }

        // 2. Verificar dependências (Requisito G5: Soft Delete se tiver histórico)
        // Tens de verificar se existem jogos ou transações associadas
        // ✅ Verificar se o utilizador tem atividade (coins/games/matches)
        $hasActivity =
            \App\Models\CoinTransaction::where('user_id', $user->id)->exists()
            || \App\Models\Game::where('player1_user_id', $user->id)->exists()
            || \App\Models\Game::where('player2_user_id', $user->id)->exists()
            || \App\Models\Game::where('winner_user_id', $user->id)->exists()
            || \App\Models\MatchModel::where('player1_user_id', $user->id)->exists()
            || \App\Models\MatchModel::where('player2_user_id', $user->id)->exists()
            || \App\Models\MatchModel::where('winner_user_id', $user->id)->exists();

        if ($hasActivity) {
            // Soft Delete (mantém dados, desativa conta)
            // Requer: use SoftDeletes no Model User;
            $user->delete();
            $msg = 'Conta desativada (histórico preservado).';
        } else {
            // Hard Delete (remove tudo)
            if ($user->photo_avatar_filename) {
                Storage::disk('public')->delete($user->photo_avatar_filename);
            }
            $user->forceDelete();
            $msg = 'Conta eliminada permanentemente.';
        }

        $request->user()->currentAccessToken()->delete(); // Logout forçado

        return response()->json(['message' => $msg]);
    }
}
