<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\SystemErrorLog;
use App\Services\ErrorLogger;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ActivityLogController extends Controller
{
    /**
     * Helper to verify if current user is Super Admin
     */
    private function checkSuperAdmin()
    {
        $currentUser = Auth::user();
        if (!$currentUser) {
            return false;
        }

        $username = strtolower($currentUser->username ?? '');
        if ($username === 'admin' || $username === 'adminall' || $username === 'admin-all' || str_contains($username, 'superadmin') || str_contains($username, 'super admin')) {
            return true;
        }

        if ($currentUser->employee) {
            $userRoles = $currentUser->employee->roles()->with(['role', 'unit'])->get();
            foreach ($userRoles as $r) {
                $roleName = strtolower($r->role?->name ?? '');
                $unitName = strtolower($r->unit?->name ?? '');
                if (str_contains($roleName, 'super admin') || str_contains($unitName, 'super admin') || $roleName === 'admin' || $unitName === 'super admin') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get paginated activity logs for Super Admin
     */
    public function getActivityLogs(Request $request)
    {
        if (!$this->checkSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized. Super Admin access required.'], 403);
        }

        $query = ActivityLog::with('user.employee');

        // Search filter (Username, Action, Module, Description)
        if ($request->filled('search')) {
            $search = strtolower(trim($request->search));
            $query->where(function ($q) use ($search) {
                $q->where(DB::raw('LOWER(username)'), 'like', '%' . $search . '%')
                  ->orWhere(DB::raw('LOWER(action)'), 'like', '%' . $search . '%')
                  ->orWhere(DB::raw('LOWER(module)'), 'like', '%' . $search . '%')
                  ->orWhere(DB::raw('LOWER(description)'), 'like', '%' . $search . '%');
            });
        }

        // Module filter
        if ($request->filled('module')) {
            $query->where('module', strtoupper($request->module));
        }

        // Action filter
        if ($request->filled('action')) {
            $query->where('action', strtoupper($request->action));
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $perPage = $request->input('per_page', 20);
        if ($perPage === 'All' || $perPage === 'all' || (int)$perPage >= 999999) {
            $totalCount = (clone $query)->count();
            $perPage = max($totalCount, 1);
        } else {
            $perPage = max((int)$perPage, 1);
        }
        $logs = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json($logs);
    }

    /**
     * Clear all activity logs
     */
    public function clearActivityLogs()
    {
        if (!$this->checkSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized. Super Admin access required.'], 403);
        }

        ActivityLog::truncate();

        // Log this clearing event itself!
        ActivityLogger::log('DELETE', 'HR', 'Membersihkan seluruh data Log Aktivitas Pengguna');

        return response()->json(['message' => 'Seluruh data Log Aktivitas berhasil dihapus.']);
    }

    /**
     * Get paginated error logs for Super Admin
     */
    public function getErrorLogs(Request $request)
    {
        if (!$this->checkSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized. Super Admin access required.'], 403);
        }

        $query = SystemErrorLog::with('user.employee');

        // Search filter (Username, Error Class, Error Message, Explanation, Suggestion)
        if ($request->filled('search')) {
            $search = strtolower(trim($request->search));
            $query->where(function ($q) use ($search) {
                $q->where(DB::raw('LOWER(username)'), 'like', '%' . $search . '%')
                  ->orWhere(DB::raw('LOWER(error_class)'), 'like', '%' . $search . '%')
                  ->orWhere(DB::raw('LOWER(error_message)'), 'like', '%' . $search . '%')
                  ->orWhere(DB::raw('LOWER(explanation)'), 'like', '%' . $search . '%')
                  ->orWhere(DB::raw('LOWER(suggestion)'), 'like', '%' . $search . '%');
            });
        }

        // Error Class filter
        if ($request->filled('error_class')) {
            $query->where('error_class', $request->error_class);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $perPage = $request->input('per_page', 20);
        if ($perPage === 'All' || $perPage === 'all' || (int)$perPage >= 999999) {
            $totalCount = (clone $query)->count();
            $perPage = max($totalCount, 1);
        } else {
            $perPage = max((int)$perPage, 1);
        }
        $logs = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json($logs);
    }

    /**
     * Get error statistics for dashboard
     */
    public function getErrorStats()
    {
        if (!$this->checkSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized. Super Admin access required.'], 403);
        }

        $totalError = SystemErrorLog::count();
        $unsolvedError = SystemErrorLog::where(function($q) {
            $q->where('status', '!=', 'Solved')->orWhereNull('status');
        })->count();
        $solvedError = SystemErrorLog::where('status', 'Solved')->count();
        
        $errorToday = SystemErrorLog::where(function($q) {
            $q->where('status', '!=', 'Solved')->orWhereNull('status');
        })->whereDate('created_at', date('Y-m-d'))->count();

        return response()->json([
            'totalError' => $totalError,
            'unsolvedError' => $unsolvedError,
            'solvedError' => $solvedError,
            'errorToday' => $errorToday
        ]);
    }

    /**
     * Clear all error logs
     */
    public function clearErrorLogs()
    {
        if (!$this->checkSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized. Super Admin access required.'], 403);
        }

        SystemErrorLog::truncate();

        // Log this clearing event!
        ActivityLogger::log('DELETE', 'HR', 'Membersihkan seluruh data Log Error Aplikasi');

        return response()->json(['message' => 'Seluruh data Log Error berhasil dihapus.']);
    }

    /**
     * Expose endpoint to log client-side JavaScript errors
     */
    public function logClientError(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'file' => 'required|string',
            'line' => 'required|integer',
            'stack' => 'nullable|string',
        ]);

        $log = ErrorLogger::logClient(
            $request->message,
            $request->file,
            $request->line,
            $request->stack
        );

        return response()->json([
            'message' => 'Client error logged successfully',
            'log_id' => $log->id
        ], 201);
    }

    /**
     * Export all activity logs matching filters to JSON
     */
    public function exportActivityLogs(Request $request)
    {
        if (!$this->checkSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized. Super Admin access required.'], 403);
        }

        $query = ActivityLog::with('user.employee');

        if ($request->filled('search')) {
            $search = strtolower(trim($request->search));
            $query->where(function ($q) use ($search) {
                $q->where(DB::raw('LOWER(username)'), 'like', '%' . $search . '%')
                  ->orWhere(DB::raw('LOWER(action)'), 'like', '%' . $search . '%')
                  ->orWhere(DB::raw('LOWER(module)'), 'like', '%' . $search . '%')
                  ->orWhere(DB::raw('LOWER(description)'), 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('module')) {
            $query->where('module', strtoupper($request->module));
        }

        if ($request->filled('action')) {
            $query->where('action', strtoupper($request->action));
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->orderBy('id', 'desc')->get();

        return response()->json($logs);
    }

    /**
     * Export all error logs matching filters to JSON
     */
    public function exportErrorLogs(Request $request)
    {
        if (!$this->checkSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized. Super Admin access required.'], 403);
        }

        $query = SystemErrorLog::with('user.employee');

        if ($request->filled('search')) {
            $search = strtolower(trim($request->search));
            $query->where(function ($q) use ($search) {
                $q->where(DB::raw('LOWER(username)'), 'like', '%' . $search . '%')
                  ->orWhere(DB::raw('LOWER(error_class)'), 'like', '%' . $search . '%')
                  ->orWhere(DB::raw('LOWER(error_message)'), 'like', '%' . $search . '%')
                  ->orWhere(DB::raw('LOWER(explanation)'), 'like', '%' . $search . '%')
                  ->orWhere(DB::raw('LOWER(suggestion)'), 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('error_class')) {
            $query->where('error_class', $request->error_class);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->orderBy('id', 'desc')->get();

        return response()->json($logs);
    }

    public function runSecurityScan()
    {
        if (!$this->checkSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized. Super Admin access required.'], 403);
        }

        // Clear cache so it forces a scan
        \Illuminate\Support\Facades\Cache::forget('security_monitor_last_run');

        $infected = \App\Services\SecurityMonitor::scanForMalwareAndTrojan();
        $quarantine = \App\Services\SecurityMonitor::scanForQuarantine();

        // If no malware is detected during the scan, automatically resolve existing malware error logs
        if (empty($infected)) {
            SystemErrorLog::where('error_class', 'App\Exceptions\Security\MalwareInfectionException')
                ->where(function($q) {
                    $q->where('status', '!=', 'Solved')->orWhereNull('status');
                })
                ->update(['status' => 'Solved']);
        }

        return response()->json([
            'message' => 'Scan Keamanan berhasil dijalankan.',
            'malware_count' => count($infected),
            'quarantine_count' => count($quarantine),
            'details' => [
                'malware' => $infected,
                'quarantine' => $quarantine
            ]
        ]);
    }

    /**
     * Simulate a Malware/Trojan infection by logging a test security error
     */
    public function simulateMalware()
    {
        if (!$this->checkSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized. Super Admin access required.'], 403);
        }

        $dummyFile = 'public/simulated_webshell.php';
        $msg = 'Terdeteksi pola malware (Obfuscated compressed payload execution) pada berkas: ' . $dummyFile;

        $log = SystemErrorLog::create([
            'user_id' => auth()->id(),
            'username' => auth()->user()?->username ?? 'SuperAdmin',
            'error_class' => 'App\Exceptions\Security\MalwareInfectionException',
            'error_message' => 'Keamanan Terinfeksi (Simulasi): ' . $msg,
            'file' => $dummyFile,
            'line' => 12,
            'trace' => 'ev' . 'al' . '(ba' . 'se64' . '_de' . 'code("aW1wbG9kZSgnbWFsd2FyZScp..."));',
            'explanation' => 'SIMULASI: Aplikasi mendeteksi adanya file script mencurigakan atau pola instruksi berbahaya yang mengindikasikan bahwa server aplikasi terinfeksi malware, trojan, atau webshell backdoor.',
            'suggestion' => "1. SEGERA periksa file tersebut: {$dummyFile}\n2. Hapus berkas jika itu adalah webshell atau file tidak sah.\n3. Restore berkas dari cadangan (backup) resmi yang bersih jika berkas bawaan dirusak.\n4. Jalankan pemindaian (scan) menyeluruh menggunakan Crowdstrike Falcon pada server.",
            'url' => request()->fullUrl(),
            'method' => 'SIMULASI',
            'ip_address' => request()->ip(),
        ]);

        return response()->json([
            'message' => 'Simulasi infeksi malware berhasil dicatat di Log Error Sistem.',
            'log' => $log
        ]);
    }

    /**
     * Simulate an antivirus quarantine event by logging a quarantine warning
     */
    public function simulateQuarantine()
    {
        if (!$this->checkSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized. Super Admin access required.'], 403);
        }

        $dummyPath = 'config/app.php';
        $log = ActivityLog::create([
            'user_id' => auth()->id(),
            'username' => auth()->user()?->username ?? 'SuperAdmin',
            'action' => 'QUARANTINE',
            'module' => 'SYSTEM',
            'description' => "PERINGATAN KEAMANAN (Simulasi): Berkas \"{$dummyPath}\" hilang secara tidak terduga. Kemungkinan berkas ini dianggap mencurigakan dan dikarantina oleh perlindungan Crowdstrike.",
            'payload' => [
                'path' => $dummyPath,
                'type' => 'file',
                'simulated' => true,
                'scanned_at' => now()->toIso8601String(),
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->header('User-Agent'),
        ]);

        return response()->json([
            'message' => 'Simulasi karantina berkas antivirus berhasil dicatat di Log Aktivitas Pengguna.',
            'log' => $log
        ]);
    }

    /**
     * Update status of an error log (Error or Solved)
     */
    public function updateErrorStatus(Request $request, $id)
    {
        if (!$this->checkSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized. Super Admin access required.'], 403);
        }

        $request->validate([
            'status' => 'required|in:Error,Solved',
        ]);

        $log = SystemErrorLog::findOrFail($id);
        $log->status = $request->status;
        $log->save();

        // Log this status change
        ActivityLogger::log('UPDATE', 'HR', 'Mengubah status Log Error ID #' . $id . ' menjadi ' . $request->status);

        return response()->json([
            'message' => 'Status log error berhasil diperbarui.',
            'log' => $log
        ]);
    }

    /**
     * Mark all unsolved system error logs as Solved
     */
    public function solveAllErrors()
    {
        if (!$this->checkSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized. Super Admin access required.'], 403);
        }

        $count = SystemErrorLog::where(function ($query) {
            $query->where('status', '!=', 'Solved')
                  ->orWhereNull('status');
        })->update(['status' => 'Solved']);

        ActivityLogger::log('UPDATE', 'HR', "Mengubah status {$count} data Log Error yang belum solved menjadi Solved.");

        return response()->json([
            'message' => "Berhasil menandai {$count} log error menjadi Solved.",
            'updated_count' => $count
        ]);
    }
}
