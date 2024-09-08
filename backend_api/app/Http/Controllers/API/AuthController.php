<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->whereNull('deleted_at'),
            ],
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Inscription effectuée avec succès',
            'user' => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => [
                'required',
                'email',
                Rule::exists('users')->whereNull('deleted_at'),
            ],
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if ($user && Hash::check($request->password, $user->password)) {

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Vous êtes connecté(es)',
                'token' => $token
            ], 200);
        }

        return response()->json([
            'message' => 'Identifiants de connexion invalides'
        ], 401);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user->tokens()->count() == 0) {
            return response()->json(['message' => 'Aucun token actif trouvé'], 404);
        }

        $user->tokens()->delete();

        return response()->json(['message' => 'Déconnecté avec succès'], 200);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
