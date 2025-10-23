<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountingAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('filament.accounting.auth.login');
        }

        $user = Auth::user();
        
        // Check if user has accounting access
        if (!$this->hasAccountingAccess($user)) {
            abort(403, 'No tienes acceso al panel de contabilidad.');
        }

        return $next($request);
    }

    /**
     * Check if user has accounting access
     */
    private function hasAccountingAccess($user): bool
    {
        return $user->hasAccountingAccess();
    }
}