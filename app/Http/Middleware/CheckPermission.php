<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Models\RolePermission;

class CheckPermission
{
    public function handle($request, Closure $next, string $page, string $ability = 'can_view')
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->role === 'admin' || RolePermission::check($user->role, $page, $ability)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'შეზღუდულია'], 403);
        }

        return redirect()->route('home')->with('role_restricted', true);
    }
}
