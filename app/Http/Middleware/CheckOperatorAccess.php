<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckOperatorAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('filament.operator.auth.login');
        }

        $user = Auth::user();
        
        // Check if user is operator or has admin access (super admin can access all panels)
        if (!$user->isOperator() && !$user->hasAdminAccess()) {
            abort(403, 'No tienes acceso al panel de operadores.');
        }

        return $next($request);
    }
}