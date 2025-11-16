<?php
// Em api/app/Http/Controllers/ProfileController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash; // Importar o Hash
use Illuminate\Validation\ValidationException; // Importar ValidationException

class ProfileController extends Controller
{
    /**
     * Mostra o perfil do utilizador.
     */
    public function show(Request $request)
    {
        return $request->user();
    }

    /**
     * ATUALIZADO: Atualiza a informação do perfil.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],

            // LINHA ADICIONADA: Validação para o nickname
            'nickname' => ['required', 'string', 'max:20', Rule::unique('users')->ignore($user->id)],
        ]);

        // LINHA ATUALIZADA: Adiciona o nickname ao fill()
        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'nickname' => $validated['nickname'], // Adicionado
        ]);

        // Se o email foi alterado, limpa a verificação
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return response()->noContent();
    }

    /**
     * NOVO: Remove a conta do utilizador.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        // Verifica se a password está correta
        if (! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['A password está incorreta.'],
            ]);
        }

        // O enunciado (G1) pede soft-delete se houver transações
        // O soft-delete (deleted_at) é o método por defeito do Laravel.
        $user->delete();

        // Invalida a sessão
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
