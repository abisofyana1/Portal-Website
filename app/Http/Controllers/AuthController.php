<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            if ($errors->has('username') && $errors->has('password')) {
                return response()->json(['message' => 'Username/NPP/Email dan Password wajib diisi.'], 401);
            } elseif ($errors->has('username')) {
                return response()->json(['message' => 'Username/NPP/Email wajib diisi.'], 401);
            } else {
                return response()->json(['message' => 'Password wajib diisi.'], 401);
            }
        }

        $input = $request->username;
        $user = \App\Models\User::where('username', $input)
            ->orWhereHas('employee', function($query) use ($input) {
                $query->where('npp', $input)
                      ->orWhere('email', $input);
            })
            ->with('employee')->first();

        if (!$user) {
            \App\Services\ActivityLogger::log('LOGIN', 'AUTH', "Gagal masuk. Akun '{$request->username}' tidak terdaftar.");
            return response()->json(['message' => 'Akun Belum Terdaftar'], 401);
        }

        if ($user->employee && $user->employee->is_active != 1) {
            \App\Services\ActivityLogger::log('LOGIN', 'AUTH', "Gagal masuk. Akun '{$request->username}' telah dinonaktifkan.");
            return response()->json(['message' => 'Akun Anda telah dinonaktifkan, silakan hubungi administrator'], 403);
        }

        $isPasswordValid = false;
        if ($user->password === $request->password) {
            $isPasswordValid = true;
        } elseif (str_starts_with((string)$user->password, '$2y$') && \Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            $isPasswordValid = true;
        }

        if ($isPasswordValid) {

            // Initialize redirect
            $redirect = '/home';
            $role = $user->employee->roles()->first();

            if ($role) {
                // Always redirect to home, let Vue handle layout
                $redirect = '/home';
            }

            // Check for force reset
            $forceReset = false;
            if (trim($user->password) == '1' || (str_starts_with((string)$user->password, '$2y$') && \Illuminate\Support\Facades\Hash::check('1', $user->password))) {
                $forceReset = true;
                if (str_contains($redirect, '?')) {
                    $redirect .= '&reset_password=true';
                } else {
                    $redirect .= '?reset_password=true';
                }
            }

            // Enforce single session: Block login if another session exists and is RECENT (< 8 hours)
            // If session is older than 8 hours (480 minutes) or has ended, it is stale/expired and cleared automatically
            $sessionLifetimeMinutes = (int) config('session.lifetime', 480);
            $currentSessionId = session()->getId();
            $existingSession = \App\Models\UserSession::where('user_id', $user->id)->first();

            if ($existingSession) {
                // If it's the exact same session (e.g. re-login on the same browser), allow it
                $isSameSession = ($existingSession->id === $currentSessionId);

                // If last_activity is missing or older than 8 hours (480 minutes), it is stale/expired
                $lastActivityTimestamp = (int) $existingSession->last_activity;
                $diffMinutes = $lastActivityTimestamp > 0 ? (now()->timestamp - $lastActivityTimestamp) / 60 : 999999;
                $isStale = ($diffMinutes >= $sessionLifetimeMinutes);

                // Check if session is still actively used in another browser (< 8 hours)
                if (!$isSameSession && !$isStale) {
                    \App\Services\ActivityLogger::log('LOGIN', 'AUTH', "Gagal masuk. Akun '{$request->username}' sedang digunakan di browser/perangkat lain.");
                    return response()->json(['message' => 'User Anda Telah Login diBrowser Lain, Silahkan Logout Terlebih Dahulu!!'], 403);
                } else {
                    // Cleanup old expired/stale session for this user to allow clean login
                    \App\Models\UserSession::where('user_id', $user->id)->delete();
                }
            }

            // Successful Login - Update State
            $user->last_login = now();
            $user->attempt_count = 0;
            $user->save();

            // Create Session record
            Auth::login($user);
            $request->session()->put('user_id', $user->id);
            $sessionId = session()->getId();

            \App\Models\UserSession::updateOrCreate(
                ['id' => $sessionId],
                [
                    'user_id' => $user->id,
                    'last_activity' => now()->timestamp,
                    'ip_address' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 500),
                ]
            );

            \App\Services\ActivityLogger::log('LOGIN', 'AUTH', "Pengguna '{$user->username}' berhasil masuk ke aplikasi.");

            return response()->json([
                'message' => 'Login successful',
                'redirect' => $redirect,
                'user' => $user,
                'require_change_password' => $forceReset
            ]);
        }

        // Failed attempt logic
        $user->attempt_count = ($user->attempt_count ?? 0) + 1;
        $user->save();

        \App\Services\ActivityLogger::log('LOGIN', 'AUTH', "Gagal masuk. Kata sandi tidak valid untuk username '{$request->username}'.");

        return response()->json(['message' => 'Username dan Password Salah'], 401);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|min:4'
        ]);

        $userId = $request->session()->get('user_id');
        if (!$userId) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user = \App\Models\User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->password = $request->password; // Storing as plaintext per legacy req
        $user->save();

        \App\Services\ActivityLogger::log('UPDATE', 'AUTH', "Pengguna '{$user->username}' berhasil memperbarui kata sandinya.");

        return response()->json(['message' => 'Password changed successfully']);
    }
}
