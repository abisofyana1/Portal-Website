<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\SystemErrorLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SecurityMonitor
{
    /**
     * Run all security checks with a throttle (default 30 seconds)
     */
    public static function check(): void
    {
        $cacheKey = 'security_monitor_last_run';
        if (Cache::has($cacheKey)) {
            return;
        }

        // Set cache lock for 30 seconds
        Cache::put($cacheKey, true, 30);

        try {
            self::scanForMalwareAndTrojan();
            self::scanForQuarantine();
        } catch (\Throwable $e) {
            // Prevent security scan failures from crashing the main application
            Log::error('Security Monitor Error: ' . $e->getMessage());
        }
    }

    /**
     * Scan critical directories for malware / trojan signatures
     */
    public static function scanForMalwareAndTrojan(): array
    {
        $infectedFiles = [];

        // 1. Scan public folder for unexpected PHP files
        $publicDir = public_path();
        if (is_dir($publicDir)) {
            $files = scandir($publicDir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $filePath = $publicDir . DIRECTORY_SEPARATOR . $file;
                if (is_file($filePath)) {
                    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                    // Check for any php script (exclude index.php and enable_pgsql.php)
                    if ($ext === 'php' && $file !== 'index.php' && $file !== 'enable_pgsql.php') {
                        $infectedFiles[] = [
                            'file' => 'public/' . $file,
                            'reason' => 'Ditemukan file script PHP tidak sah di folder public: ' . $file,
                            'details' => 'Semua file PHP di folder public (selain index.php) berpotensi menjadi Web Shell / Backdoor yang dapat diakses langsung oleh peretas.'
                        ];
                    }
                }
            }
        }

        // 2. Scan PHP files in critical directories for webshell signatures
        $scanDirs = [
            app_path(),
            config_path(),
            base_path('routes'),
            base_path('bootstrap'),
        ];

        $suspiciousPatterns = [
            'eval(base64_decode' => 'Obfuscated code execution (eval base64)',
            'gzinflate(base64_decode' => 'Obfuscated compressed payload execution (gzinflate base64)',
            'eval($_POST' => 'Dynamic code execution backdoor (eval POST)',
            'eval($_GET' => 'Dynamic code execution backdoor (eval GET)',
            'exec($_POST' => 'System command execution backdoor (exec POST)',
            'system($_POST' => 'System command execution backdoor (system POST)',
            'shell_exec($_POST' => 'System command execution backdoor (shell_exec POST)',
            'assert($_POST' => 'Code assertion backdoor (assert POST)',
        ];

        foreach ($scanDirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            try {
                $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
                foreach ($iterator as $file) {
                    if ($file->isFile() && $file->getExtension() === 'php') {
                        $filePath = $file->getRealPath();
                        // Skip itself to prevent self-detection if this script has matching text
                        if ($filePath === __FILE__) {
                            continue;
                        }

                        $content = @file_get_contents($filePath);
                        if ($content === false) {
                            continue;
                        }

                        // Remove whitespace and quotes for matching obfuscated variants
                        $cleanContent = str_replace([' ', "\r", "\n", "\t", "'", '"'], '', $content);

                        foreach ($suspiciousPatterns as $pattern => $description) {
                            $cleanPattern = str_replace([' ', "'", '"'], '', $pattern);
                            if (str_contains($cleanContent, $cleanPattern)) {
                                $infectedFiles[] = [
                                    'file' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $filePath),
                                    'reason' => 'Terdeteksi pola malware (' . $description . ')',
                                    'details' => 'Ditemukan instruksi mencurigakan: "' . $pattern . '" di dalam berkas yang biasa digunakan oleh malware/trojan/backdoor.'
                                ];
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                // If we get access denied while reading a file, it could be quarantined!
                Log::warning('Security Monitor filesystem scan warning: ' . $e->getMessage());
            }
        }

        // 3. Log errors if infections are found
        foreach ($infectedFiles as $infection) {
            $msg = $infection['reason'] . ' pada berkas: ' . $infection['file'];
            
            // Check if this error has been logged in the last 24 hours to avoid flooding
            $alreadyLogged = SystemErrorLog::where('error_class', 'App\Exceptions\Security\MalwareInfectionException')
                ->where('file', $infection['file'])
                ->where('created_at', '>=', now()->subHours(24))
                ->exists();

            if (!$alreadyLogged) {
                SystemErrorLog::create([
                    'user_id' => auth()->id(),
                    'username' => auth()->user()?->username ?? 'System',
                    'error_class' => 'App\Exceptions\Security\MalwareInfectionException',
                    'error_message' => 'Keamanan Terinfeksi: ' . $msg,
                    'file' => $infection['file'],
                    'line' => 0,
                    'trace' => $infection['details'],
                    'explanation' => 'Aplikasi mendeteksi adanya file script mencurigakan atau pola instruksi berbahaya yang mengindikasikan bahwa server aplikasi terinfeksi malware, trojan, atau webshell backdoor.',
                    'suggestion' => "1. SEGERA periksa file tersebut: {$infection['file']}\n2. Hapus berkas jika itu adalah webshell atau file tidak sah.\n3. Restore berkas dari cadangan (backup) resmi yang bersih jika berkas bawaan aplikasi dirusak.\n4. Jalankan pemindaian (scan) menyeluruh menggunakan Crowdstrike Falcon pada server.",
                    'url' => request()->fullUrl(),
                    'method' => request()->method(),
                    'ip_address' => request()->ip(),
                ]);

                // Also write to standard laravel log
                Log::alert('SECURITY ALERT: Malware detected! ' . $msg);
            }
        }

        return $infectedFiles;
    }

    /**
     * Scan for quarantined folders, subfolders, files, or database connections
     */
    public static function scanForQuarantine(): array
    {
        $quarantineIssues = [];

        // 1. Check critical folders
        $criticalFolders = [
            'app' => app_path(),
            'bootstrap' => base_path('bootstrap'),
            'config' => config_path(),
            'database' => database_path(),
            'routes' => base_path('routes'),
            'resources' => base_path('resources'),
            'storage' => storage_path(),
        ];

        foreach ($criticalFolders as $name => $path) {
            if (!is_dir($path)) {
                $quarantineIssues[] = [
                    'type' => 'directory',
                    'path' => $name,
                    'detail' => 'Folder "' . $name . '" tidak ditemukan di lokasi aplikasi. Kemungkinan folder ini telah dihapus atau dikarantina oleh antivirus Crowdstrike / antivirus lainnya.'
                ];
            } elseif (!is_readable($path)) {
                $quarantineIssues[] = [
                    'type' => 'directory',
                    'path' => $name,
                    'detail' => 'Akses Ditolak (Permission Denied) pada folder "' . $name . '". Folder ini kemungkinan dikunci, dipindahkan, atau dikarantina oleh perlindungan antivirus.'
                ];
            }
        }

        // 2. Check critical core files
        $criticalFiles = [
            'bootstrap/app.php' => base_path('bootstrap/app.php'),
            'public/index.php' => public_path('index.php'),
            'routes/web.php' => base_path('routes/web.php'),
            'routes/api.php' => base_path('routes/api.php'),
            'artisan' => base_path('artisan'),
            'composer.json' => base_path('composer.json'),
        ];

        foreach ($criticalFiles as $name => $path) {
            if (!file_exists($path)) {
                $quarantineIssues[] = [
                    'type' => 'file',
                    'path' => $name,
                    'detail' => 'Berkas "' . $name . '" hilang secara tidak terduga. Kemungkinan berkas ini dianggap mencurigakan dan dikarantina oleh perlindungan Crowdstrike.'
                ];
            } elseif (!is_readable($path)) {
                $quarantineIssues[] = [
                    'type' => 'file',
                    'path' => $name,
                    'detail' => 'Berkas "' . $name . '" tidak dapat diakses (Akses Ditolak). Berkas ini kemungkinan telah dikarantina atau dikunci oleh sensor antivirus.'
                ];
            }
        }

        // 3. Check database connectivity
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $quarantineIssues[] = [
                'type' => 'database',
                'path' => config('database.connections.pgsql.database', 'PostgreSQL Database'),
                'detail' => 'Sistem gagal terhubung ke database. Ada indikasi database engine dimatikan, port diblokir, atau berkas database dikarantina oleh antivirus. Error: ' . $e->getMessage()
            ];
        }

        // 4. Log quarantine issues
        if (!empty($quarantineIssues)) {
            foreach ($quarantineIssues as $issue) {
                $msg = "Deteksi Karantina: " . $issue['detail'];
                
                // Prevent duplicate activity log in the last 1 hour to avoid flooding
                $alreadyLogged = ActivityLog::where('action', 'QUARANTINE')
                    ->where('module', 'SYSTEM')
                    ->where('description', 'like', '%' . $issue['path'] . '%')
                    ->where('created_at', '>=', now()->subHour())
                    ->exists();

                if (!$alreadyLogged) {
                    ActivityLog::create([
                        'user_id' => auth()->id(),
                        'username' => auth()->user()?->username ?? 'System',
                        'action' => 'QUARANTINE',
                        'module' => 'SYSTEM',
                        'description' => "PERINGATAN KEAMANAN: " . $issue['detail'],
                        'payload' => [
                            'path' => $issue['path'],
                            'type' => $issue['type'],
                            'scanned_at' => now()->toIso8601String(),
                        ],
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->header('User-Agent'),
                    ]);

                    // Also log to standard laravel log
                    Log::warning('ANTIVIRUS QUARANTINE WARNING: ' . $issue['detail']);
                }
            }
        }

        return $quarantineIssues;
    }
}
