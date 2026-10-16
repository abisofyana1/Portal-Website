<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    /**
     * Log a user activity in the database.
     *
     * @param string $action e.g. LOGIN, LOGOUT, CREATE, UPDATE, DELETE, UPLOAD
     * @param string $module e.g. AUTH, USER, UNIT, NEWS, PORTAL_SETTING
     * @param string $description Human readable description of the action
     * @param array|null $payload Optional JSON metadata
     * @return ActivityLog
     */
    public static function log(string $action, string $module, string $description, ?array $payload = null): ActivityLog
    {
        $userId = null;
        $username = null;

        if (Auth::check()) {
            $user = Auth::user();
            $userId = $user->id;
            $username = $user->username;
        }

        return ActivityLog::create([
            'user_id' => $userId,
            'username' => $username,
            'action' => strtoupper($action),
            'module' => strtoupper($module),
            'description' => $description,
            'payload' => $payload,
            'ip_address' => request()->ip(),
            'user_agent' => request()->header('User-Agent'),
        ]);
    }
}
