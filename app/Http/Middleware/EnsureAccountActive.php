<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureAccountActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user) $user->refresh();
        if ($user && (!$user->is_active || (int) $request->session()->get('auth_version', 0) !== (int) $user->auth_version)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors(['email' => 'Your session has ended. Please sign in with an active account.']);
        }
        return $next($request);
    }
}
