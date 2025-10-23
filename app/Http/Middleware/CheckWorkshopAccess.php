<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckWorkshopAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('filament.workshop.auth.login');
        }

        $user = Auth::user();
        
        // Check if user has workshop access
        if (!$user->hasWorkshopAccess()) {
            abort(403, 'No tienes acceso al panel de taller.');
        }

        return $next($request);
    }
}