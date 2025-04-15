<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest; // Assurez-vous d'avoir une requête de validation appropriée
use App\Models\User; // Utilisez le modèle User pour gérer les utilisateurs
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    /**
     * Afficher le profil d'un utilisateur.
     */
    public function showProfile($id): Response
    {
        $user = User::findOrFail($id);
        return Inertia::render('Admin/ProfileShow', [
            'user' => $user,
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Afficher le formulaire d'édition d'un utilisateur.
     */
    public function editProfile($id): Response
    {
        $user = User::findOrFail($id);
        return Inertia::render('Admin/ProfileEdit', [
            'user' => $user,
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Mettre à jour les informations d'un utilisateur.
     */
    public function updateProfile(ProfileUpdateRequest $request, $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('admin.showprofile', $id)->with('success', 'Profil mis à jour avec succès.');
    }

    /**
     * Supprimer un utilisateur.
     */
    public function deleteProfile($id): RedirectResponse
    {
        $user = User::findOrFail($id);
        $user->delete();

        return Redirect::route('admin.showprofile', $id)->with('success', 'Profil supprimé avec succès.');
    }

    /**
     * Afficher le formulaire de création d'un utilisateur.
     */
    public function createProfile(): Response
    {
        return Inertia::render('Admin/ProfileCreate');
    }

    /**
     * Stocker un nouvel utilisateur.
     */
    public function storeProfile(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        return Redirect::route('admin.showprofile', $user->id)->with('success', 'Profil créé avec succès.');
    }

    /**
     * Lister tous les utilisateurs.
     */
    public function listProfiles(): Response
    {
        $users = User::all(); // Récupère tous les utilisateurs
        return Inertia::render('Admin/Profiles', [
            'users' => $users,
        ]);
    }

}
