<?php

namespace App\Http\Controllers;

use App\Models\Investment;
use App\Services\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    protected $referralService;

    public function __construct(ReferralService $referralService)
    {
        $this->referralService = $referralService;
    }

    /**
     * Inscription + investissement
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'referrer_id' => 'nullable|exists:users,id',
        ]);

        // Transaction pour créer user + investissement
        DB::beginTransaction();
        try {
            // 1️⃣ Créer l'utilisateur
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'referrer_id' => $request->referrer_id,
            ]);

            DB::commit();

            // Générer token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Inscription et investissement réussis',
                'user' => $user,
                'token' => $token,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l’inscription : ' . $e->getMessage()
            ], 500);
        }
    }
    public function login(Request $request)
    {
        logger($request->all());
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'phone' => ['Numéro ou mot de passe incorrect.'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }
    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:6',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json(['message' => 'Ancien mot de passe incorrect'], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Mot de passe changé avec succès']);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        // Validation
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'dob' => 'nullable|date',
            'presentaddress' => 'nullable|string|max:255',
            'permanentaddress' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:5',
            'city' => 'nullable|string|max:100',
        ]);

        DB::beginTransaction();
        try {
            // Mise à jour des infos
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'dob' => $request->dob,
                'presentaddress' => $request->presentaddress,
                'permanentaddress' => $request->permanentaddress,
                'country' => $request->country,
                'city' => $request->city,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès',
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du profil : ' . $e->getMessage(),
            ], 500);
        }
    }
    public function updateProfilePhoto(Request $request)
    {
        $user = Auth::user();

        logger($request->all());
        // Validation de l'image uniquement
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:20480', // max 20MB
        ]);

        DB::beginTransaction();

        try {
            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('profile_images', $filename, 'public');

            // Supprimer l'ancienne image si existante
            if ($user->image_url) {
                Storage::disk('public')->delete($user->image_url);
            }

            $user->image_url = $path;
            $user->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Photo de profil mise à jour avec succès',
                'image_url' => $path,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la photo : ' . $e->getMessage(),
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Déconnecté']);
    }
}
