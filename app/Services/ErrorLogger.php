<?php

namespace App\Services;

use Throwable;
use App\Models\SystemErrorLog;
use Illuminate\Support\Facades\Auth;

class ErrorLogger
{
    /**
     * Log a backend Throwable to the database system_error_logs table.
     *
     * @param Throwable $e
     * @return SystemErrorLog
     */
    public static function log(Throwable $e): SystemErrorLog
    {
        $userId = null;
        $username = null;

        if (Auth::check()) {
            $user = Auth::user();
            $userId = $user->id;
            $username = $user->username;
        }

        // Analyze error for Indonesian explanation and suggestions
        $analysis = ErrorAnalyzer::analyze($e);

        return SystemErrorLog::create([
            'user_id' => $userId,
            'username' => $username,
            'error_class' => get_class($e),
            'error_message' => $e->getMessage(),
            'file' => str_replace(base_path(), '', $e->getFile()),
            'line' => $e->getLine(),
            'trace' => substr($e->getTraceAsString(), 0, 10000), // Limit trace size
            'explanation' => $analysis['explanation'],
            'suggestion' => $analysis['suggestion'],
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Log a client-side JavaScript or manual error to the database.
     *
     * @param string $message Raw error message
     * @param string $file File name
     * @param int $line Line number
     * @param string|null $stack Stack trace
     * @return SystemErrorLog
     */
    public static function logClient(string $message, string $file, int $line, ?string $stack = null): SystemErrorLog
    {
        $userId = null;
        $username = null;

        if (Auth::check()) {
            $user = Auth::user();
            $userId = $user->id;
            $username = $user->username;
        }

        // Generate tailored analysis
        $analysis = ErrorAnalyzer::analyze('javascript error: ' . $message);

        return SystemErrorLog::create([
            'user_id' => $userId,
            'username' => $username,
            'error_class' => 'ClientSideJavaScriptError',
            'error_message' => $message,
            'file' => $file,
            'line' => $line,
            'trace' => $stack ? substr($stack, 0, 10000) : null,
            'explanation' => $analysis['explanation'],
            'suggestion' => $analysis['suggestion'],
            'url' => request()->header('Referer') ?? request()->fullUrl(),
            'method' => 'CLIENT',
            'ip_address' => request()->ip(),
        ]);
    }
}
