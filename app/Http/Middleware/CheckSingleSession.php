<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserSession;

class CheckSingleSession
{
    public function handle(Request $request, Closure $next)
    {
        // Jalankan pemantauan keamanan (malware & karantina antivirus)
        try {
            \App\Services\SecurityMonitor::check();
        } catch (\Throwable $e) {
            // Prevent security scan errors from affecting request lifecycle
        }

        if (Auth::check() && $request->hasSession()) {
            $sessionId = session()->getId();
            $exists = UserSession::where('id', $sessionId)
                                 ->where('user_id', Auth::id())
                                 ->exists();

            if (!$exists) {
                Auth::logout();
                $request->session()->flush();

                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Your session has been invalidated because you logged in on another browser.'], 401);
                }

                return redirect('/');
            }
        }

        return $next($request);
    }
}
