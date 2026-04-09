<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // If the user's role matches the allowed role, proceed
        if ($user->role === $role || ($user->role instanceof Role && $user->role->value === $role)) {
            return $next($request);
        }

        // Otherwise, redirect to their appropriate home
        $targetRoute = $this->getHomeRoute($user->role);

        // Avoid infinite loops if they are already on their home route
        if ($request->route()->getName() === $targetRoute) {
            return $next($request);
        }

        return redirect()->route($targetRoute)->with('error', 'Vous n\'avez pas accès à cette section.');
    }

    /**
     * Get the default route for a role.
     */
    protected function getHomeRoute($userRole): string
    {
        $roleValue = $userRole instanceof Role ? $userRole->value : $userRole;

        return match ($roleValue) {
            Role::Admin->value        => 'admin.dashboard',
            Role::Organisateur->value => 'dashboard',
            Role::Revendeur->value    => 'affiliate.dashboard',
            Role::Participant->value  => 'discovery',
            default                   => 'home',
        };
    }
}
