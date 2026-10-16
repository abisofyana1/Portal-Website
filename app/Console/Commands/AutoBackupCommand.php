<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BackupSchedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AutoBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:auto';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run auto backup based on schedule settings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $shouldRun = false;
        $now = now();
        $isFallback = false;

        $schedule = BackupSchedule::first();

        // Jika toggle jelas-jelas Nonaktif di database, hentikan proses sepenuhnya
        if ($schedule && !$schedule->is_active) {
            return; // Backup Otomatis benar-benar berhenti
        }

        if ($schedule && $schedule->is_active) {
            if ($schedule->frequency === '1_week' && $now->isMonday() && $now->hour == 0 && $now->minute == 0) {
                $shouldRun = true;
            } elseif ($schedule->frequency === '2_weeks' && $now->isMonday() && $now->weekOfYear % 2 == 0 && $now->hour == 0 && $now->minute == 0) {
                $shouldRun = true;
            } elseif ($schedule->frequency === '1_month' && $now->day == 1 && $now->hour == 0 && $now->minute == 0) {
                $shouldRun = true;
            } elseif ($schedule->frequency === 'custom') {
                if ($schedule->custom_datetime) {
                    $targetTime = \Carbon\Carbon::parse($schedule->custom_datetime);
                    if ($now->format('Y-m-d H:i') === $targetTime->format('Y-m-d H:i')) {
                        $shouldRun = true;
                    }
                }
            }
        } else {
            // Fallback (jika belum ada konfigurasi jadwal di database sama sekali)
            if ($now->day == 1 && $now->hour == 0 && $now->minute == 0) {
                $shouldRun = true;
                $isFallback = true;
            }
        }

        if ($shouldRun) {
            try {
                $this->performBackup('news');
                $this->performBackup('users');
                $this->performBackup('unit');
                
                $msg = $isFallback ? 'Auto Backup Bulanan Berhasil' : 'Auto Backup Sesuai Jadwal Berhasil';
                
                $year = now()->format('Y');
                $monthNum = now()->format('m');
                $monthName = now()->format('F');
                $monthName = str_replace(
                    ['January','February','March','April','May','June','July','August','September','October','November','December'],
                    ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'],
                    $monthName
                );
                $folderSuffix = "{$year}/{$monthNum}. {$monthName}";

                $storagePath = storage_path('app/public/download_backup');
                $desc = "-{$msg}. File Manage News tersimpan di : {$storagePath}/News/{$folderSuffix}\n";
                $desc .= "-{$msg}. File Manage Users tersimpan di : {$storagePath}/Users/{$folderSuffix}\n";
                $desc .= "-{$msg}. File Manage Unit tersimpan di : {$storagePath}/Unit/{$folderSuffix}";

                \App\Models\BackupHistory::create([
                    'datetime' => now(),
                    'description' => $desc,
                    'status' => 'Berhasil'
                ]);
                Log::info('Auto Backup completed successfully.');
            } catch (\Exception $e) {
                \App\Models\BackupHistory::create([
                    'datetime' => now(),
                    'description' => 'Gagal: ' . $e->getMessage(),
                    'status' => 'Gagal'
                ]);
                Log::error('Auto Backup failed: ' . $e->getMessage());
            }
        }
    }

    private function performBackup($table)
    {
        $data = DB::table($table)->get();
        if ($data->isEmpty()) return;

        // Tentukan nama file yang diminta: Backup_Modul_DDMMYYYY-hhmmss
        $module = ucfirst($table);
        $timestamp = now()->format('dFY-H.i.s');
        $timestamp = str_replace(
            ['January','February','March','April','May','June','July','August','September','October','November','December'],
            ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'],
            $timestamp
        );
        $filenameBase = "Backup_{$module}_{$timestamp}";
        $year = now()->format('Y');
        $monthNum = now()->format('m');
        $monthName = now()->format('F');
        $monthName = str_replace(
            ['January','February','March','April','May','June','July','August','September','October','November','December'],
            ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'],
            $monthName
        );
        $folder = "download_backup/{$module}/{$year}/{$monthNum}. {$monthName}/";

        // --- 1. Export SQL Dump (.backup) ---
        $columns = array_keys((array)$data->first());
        $sqlContent = "-- PostgreSQL Auto Backup for Table {$table}\n\n";
        
        $updateStmts = [];
        foreach ($columns as $col) {
            if ($col !== 'id') {
                $updateStmts[] = "{$col}=EXCLUDED.{$col}";
            }
        }
        $updateSql = implode(', ', $updateStmts);
        
        foreach ($data as $row) {
            $vals = [];
            foreach ((array)$row as $val) {
                if (is_null($val)) $vals[] = 'NULL';
                else $vals[] = "'" . addslashes($val) . "'";
            }
            $sqlContent .= "INSERT INTO {$table} (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $vals) . ") ON CONFLICT (id) DO UPDATE SET {$updateSql};\n";
        }

        $sqlFilename = $folder . $filenameBase . '.backup';
        Storage::disk('public')->put($sqlFilename, $sqlContent);

        // --- 2. Export CSV ---
        $csvContent = fopen('php://temp', 'r+');
        fputcsv($csvContent, $columns);
        foreach ($data as $row) {
            fputcsv($csvContent, (array)$row);
        }
        rewind($csvContent);
        $csvString = stream_get_contents($csvContent);
        fclose($csvContent);

        $csvFilename = $folder . $filenameBase . '.csv';
        Storage::disk('public')->put($csvFilename, $csvString);
    }
}
