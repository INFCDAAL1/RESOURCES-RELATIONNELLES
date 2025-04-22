<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class AuthorizedAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $role = null): Response
    {
        // Vérifiez si l'utilisateur est authentifié
        if (!Auth::check()) {
            // return redirect()->route('login'); // Redirige vers la page de connexion si non authentifié
            abort(401, 'Unauthorized action.'); // Interdit l'accès si non authentifié
        }

        // Vérifiez si l'utilisateur est actif
        if (!Auth::user()->isActive()) {
            abort(403, 'User is not active.'); // Interdit l'accès si l'utilisateur n'est pas actif
        }

        // // Vérifiez si l'utilisateur a le rôle requis
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized action.'); // Interdit l'accès si le rôle ne correspond pas
        }

        return $next($request);
    }
}
