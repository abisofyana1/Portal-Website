<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\News;
use App\Models\User;
use App\Models\Unit;
use App\Models\BackupSchedule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Process;

class ExportImportController extends Controller
{
    public function getSchedule()
    {
        $schedule = BackupSchedule::first();
        if (!$schedule) {
            $schedule = BackupSchedule::create([
                'is_active' => false,
                'frequency' => '1_week',
            ]);
        }
        return response()->json($schedule);
    }

    public function saveSchedule(Request $request)
    {
        $schedule = BackupSchedule::first();
        if (!$schedule) {
            $schedule = new BackupSchedule();
        }
        
        $schedule->is_active = $request->is_active;
        $schedule->frequency = $request->frequency;
        $schedule->custom_day = $request->custom_day ?? null;
        $schedule->custom_hour = $request->custom_hour ?? null;
        $schedule->custom_minute = $request->custom_minute ?? null;
        $schedule->custom_second = $request->custom_second ?? null;
        $schedule->custom_datetime = $request->custom_datetime ?? null;
        $schedule->save();

        return response()->json(['status' => 'success', 'message' => 'Schedule updated successfully']);
    }

    public function getHistory()
    {
        $histories = \App\Models\BackupHistory::orderBy('datetime', 'desc')->take(50)->get();
        return response()->json($histories);
    }

    public function export($module, $format)
    {
        $table = $this->getTableName($module);
        
        if (!$table) {
            return response()->json(['message' => 'Modul tidak valid'], 400);
        }

        if ($format === 'backup') {
            return $this->exportSqlDump($table);
        } elseif ($format === 'csv') {
            return $this->exportCsv($table);
        } elseif ($format === 'xlsx') {
            $moduleName = ucfirst($table);
            $timestamp = now()->format('dFY-H.i.s');
            $timestamp = str_replace(
                ['January','February','March','April','May','June','July','August','September','October','November','December'],
                ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'],
                $timestamp
            );
            $filenameBase = "Backup_{$moduleName}_{$timestamp}";

            $year = now()->format('Y');
            $monthNum = now()->format('m');
            $monthName = now()->format('F');
            $monthName = str_replace(
                ['January','February','March','April','May','June','July','August','September','October','November','December'],
                ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'],
                $monthName
            );
            $folder = "download_backup/{$moduleName}/{$year}/{$monthNum}. {$monthName}/";

            \Maatwebsite\Excel\Facades\Excel::store(new \App\Exports\GenericExport($table), $folder . $filenameBase . '.xlsx', 'public');

            return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\GenericExport($table), "{$filenameBase}.xlsx");
        }
        
        return response()->json(['message' => 'Format tidak valid'], 400);
    }

    public function import(Request $request, $module, $format)
    {
        if (!$request->hasFile('files')) {
            return response()->json(['message' => 'File tidak ditemukan'], 400);
        }

        $defaultTable = $this->getTableName($module);
        if (!$defaultTable) {
            return response()->json(['message' => 'Modul tidak valid'], 400);
        }

        $files = $request->file('files');
        if (!is_array($files)) {
            $files = [$files];
        }
        
        $totalResults = [];

        try {
            foreach ($files as $file) {
                $filename = $file->getClientOriginalName();
                $targetTable = $defaultTable;
                
                // Coba deteksi modul dari nama file (contoh: Backup_News_...)
                if (preg_match('/^Backup_([A-Za-z]+)_/', $filename, $matches)) {
                    $detectedModule = strtolower($matches[1]);
                    $detectedTable = $this->getTableName($detectedModule);
                    if ($detectedTable) {
                        $targetTable = $detectedTable;
                    } elseif ($detectedModule === 'unit') {
                        $targetTable = 'unit';
                    }
                }

                if ($format === 'backup') {
                    $res = $this->importSqlDump($file, $targetTable);
                    $totalResults[] = "File: " . $filename . "\n" . $res['message'];
                } elseif ($format === 'csv' || $format === 'xlsx') {
                    $res = $this->importCsv($file, $targetTable);
                    $totalResults[] = "File: " . $filename . "\n" . $res['message'];
                }
            }
            
            return response()->json(['status' => 'success', 'message' => implode("\n\n", $totalResults)]);
            
        } catch (\Exception $e) {
            Log::error("Import Error: " . $e->getMessage());
            return response()->json(['message' => 'Gagal mengimpor: ' . $e->getMessage()], 500);
        }
    }

    private function getTableName($module)
    {
        switch ($module) {
            case 'news': return 'news';
            case 'users': return 'users';
            case 'units': return 'unit';
            default: return null;
        }
    }

    public function template($module, $format)
    {
        $table = $this->getTableName($module);
        if (!$table) {
            return response()->json(['message' => 'Modul tidak valid'], 400);
        }

        $columns = \Illuminate\Support\Facades\Schema::getColumnListing($table);
        $exampleRow = array_fill(0, count($columns), '');

        if ($module === 'users') {
            $newCols = [];
            foreach ($columns as $col) {
                if ($col === 'password') continue;
                $newCols[] = $col;
                if ($col === 'username') {
                    $newCols[] = 'npp';
                    $newCols[] = 'email';
                }
            }
            $newCols[] = 'role_id';
            $newCols[] = 'unit_id';
            $newCols[] = 'status_id';
            $columns = $newCols;

            $exampleRow = array_map(function($col) {
                if ($col === 'username') return 'johndoe';
                if ($col === 'npp') return 'P12345';
                if ($col === 'email') return 'johndoe@email.com';
                if ($col === 'role_id') return '1';
                if ($col === 'unit_id') return '1';
                if ($col === 'status_id') return '1';
                return '';
            }, $columns);
            
            $columns = array_map(function($col) {
                if (in_array($col, ['username', 'npp', 'email', 'unit_id', 'role_id', 'status_id'])) {
                    return $col . ' (Wajib)';
                }
                return $col;
            }, $columns);
        }

        if ($format === 'xlsx') {
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\GenericTemplateExport($columns, [$exampleRow]),
                "Template_{$module}.xlsx"
            );
        } elseif ($format === 'backup') {
            $dbColumns = \Illuminate\Support\Facades\Schema::getColumnListing($table);
            $updateStmts = [];
            foreach ($dbColumns as $col) {
                if ($col !== 'id') {
                    $updateStmts[] = "{$col}=EXCLUDED.{$col}";
                }
            }
            $updateSql = implode(', ', $updateStmts);
            
            $content = "-- PostgreSQL Template for Table {$table}\n";
            $content .= "-- Harap sesuaikan value kolom sesuai dengan kebutuhan\n\n";
            $vals = array_map(function($c) { return "''"; }, $dbColumns);
            $content .= "INSERT INTO {$table} (" . implode(", ", $dbColumns) . ") VALUES (" . implode(", ", $vals) . ") ON CONFLICT (id) DO UPDATE SET {$updateSql};\n";

            return response($content)
                ->header('Content-Type', 'application/octet-stream')
                ->header('Content-Disposition', "attachment; filename=Template_{$module}.backup");
        }

        $callback = function() use ($columns, $exampleRow) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            fputcsv($file, $exampleRow);
            fclose($file);
        };

        return Response::stream($callback, 200, [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=Template_{$module}.csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ]);
    }

    private function exportCsv($table)
    {
        $data = DB::table($table)->get();
        if ($data->isEmpty()) {
            return response()->json(['message' => 'Data kosong'], 404);
        }

        $columns = array_keys((array)$data->first());
        
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

        $csvContent = fopen('php://temp', 'r+');
        fputcsv($csvContent, $columns);
        foreach ($data as $row) {
            fputcsv($csvContent, (array)$row);
        }
        rewind($csvContent);
        $csvString = stream_get_contents($csvContent);
        fclose($csvContent);

        \Illuminate\Support\Facades\Storage::disk('public')->put($folder . $filenameBase . '.csv', $csvString);

        $callback = function() use ($csvString) {
            echo $csvString;
        };

        return Response::stream($callback, 200, [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename={$filenameBase}.csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ]);
    }

    private function importCsv($file, $table)
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $path = $file->getRealPath();
        $extension = $file->getClientOriginalExtension();
        
        $header = [];
        $dataIterator = null;

        if (strtolower($extension) === 'csv') {
            // Auto detect delimiter
            $firstLine = file_get_contents($path, false, null, 0, 1024);
            $delimiter = ',';
            if ($firstLine !== false) {
                if (strpos($firstLine, '~') !== false) {
                    $delimiter = '~';
                } elseif (strpos($firstLine, ';') !== false) {
                    $delimiter = ';';
                } elseif (strpos($firstLine, "\t") !== false) {
                    $delimiter = "\t";
                }
            }
            
            $enclosure = '"';
            $escape = '\\';
            
            // Jika menggunakan separator tilde (~), biasanya tidak menggunakan quote enclosure.
            // Karakter quote (") di dalam konten (seperti berita) akan merusak parsing fgetcsv
            // jika kita tetap menggunakan default enclosure. Kita ubah ke chr(0) (karakter null).
            if ($delimiter === '~') {
                $enclosure = chr(0);
                $escape = chr(0);
            }
            
            $fileHandle = fopen($path, 'r');
            if ($fileHandle === false) {
                return ['status' => 'error', 'message' => '- Gagal membaca file CSV.'];
            }
            
            $rawHeader = fgetcsv($fileHandle, 0, $delimiter, $enclosure, $escape);
            if ($rawHeader === false) {
                fclose($fileHandle);
                return ['status' => 'error', 'message' => '- File kosong atau tidak memiliki data.'];
            }
            
            $header = array_map(function($h) {
                $cleaned = str_replace(' (Wajib)', '', (string)$h);
                return strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $cleaned));
            }, $rawHeader);
            
            $dataIterator = function() use ($fileHandle, $delimiter, $enclosure, $escape, $header) {
                if ($delimiter === '~') {
                    $expectedTildes = count($header) - 1;
                    $currentRow = '';
                    while (($line = fgets($fileHandle)) !== false) {
                        $currentRow .= $line;
                        // Jika jumlah tilde sudah memenuhi kolom yang diharapkan, baris ini utuh
                        if (substr_count($currentRow, '~') >= $expectedTildes) {
                            $currentRow = rtrim($currentRow, "\r\n");
                            yield explode('~', $currentRow);
                            $currentRow = '';
                        }
                    }
                    if (trim($currentRow) !== '') {
                        $currentRow = rtrim($currentRow, "\r\n");
                        yield explode('~', $currentRow);
                    }
                    fclose($fileHandle);
                } else {
                    while (($row = fgetcsv($fileHandle, 0, $delimiter, $enclosure, $escape)) !== false) {
                        yield $row;
                    }
                    fclose($fileHandle);
                }
            };
        } elseif (in_array(strtolower($extension), ['xls', 'xlsx'])) {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            $data = $spreadsheet->getActiveSheet()->toArray();
            
            if (count($data) <= 1) {
                return ['status' => 'error', 'message' => '- File kosong atau tidak memiliki data.'];
            }
            
            $header = array_map(function($h) {
                $cleaned = str_replace(' (Wajib)', '', (string)$h);
                return strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $cleaned));
            }, $data[0]);
            unset($data[0]);
            
            $dataIterator = function() use ($data) {
                foreach ($data as $row) {
                    yield $row;
                }
            };
        } else {
            return ['status' => 'error', 'message' => '- Format file tidak didukung.'];
        }

        $inserted = 0;
        $updated = 0;
        $invalidFormat = 0;
        $chunkSize = 500;
        $processedCount = 0;
        
        DB::disableQueryLog(); // Cegah memory leak dan perlambat eksekusi akibat query log yang menumpuk
        
        // Cache existing keys into memory to prevent N+1 queries and full table scans
        $dbColumns = array_flip(\Illuminate\Support\Facades\Schema::getColumnListing($table));
        $existingIds = DB::table($table)->pluck('id', 'id')->toArray();
        $existingKeys = [];
        $existingEmployeesByNpp = [];

        if ($table === 'users') {
            $existingEmployeesByNpp = DB::table('employee')->whereNotNull('npp')->pluck('id', 'npp')->toArray();
            $users = DB::table('users')->select('id', 'username')->get();
            foreach ($users as $u) {
                if (!empty($u->username)) {
                    $existingKeys[trim($u->username)] = $u->id;
                    $existingKeys[strtolower(trim($u->username))] = $u->id;
                }
            }
        } elseif ($table === 'news') {
            $news = DB::table('news')->select('id', 'title')->get();
            foreach ($news as $n) {
                if (!empty($n->title)) {
                    $existingKeys[trim($n->title)] = $n->id;
                    $existingKeys[strtolower(trim($n->title))] = $n->id;
                }
            }
        } elseif ($table === 'unit') {
            $units = DB::table('unit')->select('id', 'code')->get();
            foreach ($units as $un) {
                if (!empty($un->code)) {
                    $existingKeys[trim($un->code)] = $un->id;
                    $existingKeys[strtolower(trim($un->code))] = $un->id;
                }
            }
        }

        DB::beginTransaction();
        try {
            foreach ($dataIterator() as $row) {
                if (count($header) !== count($row)) {
                    $invalidFormat++;
                    continue;
                }
                // Cek jika row kosong semua (mengabaikan baris kosong di akhir file Excel)
                $isEmptyRow = true;
                foreach ($row as $val) {
                    if (trim($val) !== '') {
                        $isEmptyRow = false;
                        break;
                    }
                }
                if ($isEmptyRow) continue;

                $record = array_combine($header, $row);
                
                // Hapus key kosong (disebabkan oleh koma berlebih di header CSV) untuk mencegah error PostgreSQL "zero-length delimited identifier"
                if (array_key_exists('', $record)) {
                    unset($record['']);
                }
                
                // Konversi string kosong menjadi null agar sesuai dengan tipe data PostgreSQL (int, timestamp, dll)
                foreach ($record as $key => $value) {
                    if (trim($value) === '') {
                        // Jangan ubah menjadi null untuk kolom yang bersifat NOT NULL di database
                        if (in_array(strtolower($key), ['title', 'headline', 'username', 'code', 'name'])) {
                            $record[$key] = '';
                        } else {
                            $record[$key] = null;
                        }
                    } elseif (in_array(strtolower($key), ['start_time', 'end_time', 'created_at', 'updated_at', 'last_login', 'date'])) {
                        try {
                            $record[$key] = \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
                        } catch (\Exception $e) {
                            // Biarkan apa adanya jika gagal diparse, database mungkin akan menolak atau mengubahnya
                        }
                    }
                }
                
                // Remove id from record if it's null to allow auto-increment
                if (array_key_exists('id', $record) && $record['id'] === null) {
                    unset($record['id']);
                }

                // Gunakan start_time sebagai created_at jika created_at kosong (khusus tabel news/umumnya)
                if (isset($record['start_time']) && trim($record['start_time']) !== '') {
                    if (empty($record['created_at'])) {
                        $record['created_at'] = $record['start_time'];
                    }
                }
                
                // Pastikan created_at dan updated_at selalu ada untuk mencegah default waktu import (saat insert DB)
                if (!array_key_exists('created_at', $record) || empty($record['created_at'])) {
                    $record['created_at'] = now()->format('Y-m-d H:i:s');
                }
                if (!array_key_exists('updated_at', $record) || empty($record['updated_at'])) {
                    $record['updated_at'] = $record['created_at'];
                }

                // Default password for users
                if ($table === 'users') {
                    if (!isset($record['password']) || $record['password'] === null || trim((string)$record['password']) === '') {
                        unset($record['password']);
                    } else {
                        $record['password'] = (string)$record['password'];
                    }
                }

                // Ekstrak email, unit_id, dan npp jika ada
                $email = null;
                $unitId = null;
                $npp = null;
                if (array_key_exists('email', $record)) {
                    $email = $record['email'];
                    unset($record['email']);
                }
                if (array_key_exists('unit_id', $record)) {
                    $unitId = $record['unit_id'];
                    unset($record['unit_id']);
                }
                if (array_key_exists('npp', $record)) {
                    $npp = $record['npp'];
                    unset($record['npp']);
                }

                // Jika tabel users dan employee_id kosong, cari berdasarkan npp atau buat baru
                if ($table === 'users' && empty($record['employee_id'])) {
                    $existingEmpId = null;
                    if ($npp !== null && trim((string)$npp) !== '') {
                        $nppKey = trim((string)$npp);
                        if (isset($existingEmployeesByNpp[$nppKey])) {
                            $existingEmpId = $existingEmployeesByNpp[$nppKey];
                        }
                    }

                    if ($existingEmpId) {
                        $record['employee_id'] = $existingEmpId;
                        $empUpdate = [];
                        if ($email !== null) $empUpdate['email'] = $email;
                        if (!empty($empUpdate)) {
                            DB::table('employee')->where('id', $existingEmpId)->update($empUpdate);
                        }
                    } else {
                        $empId = DB::table('employee')->insertGetId([
                            'npp' => $npp,
                            'name' => $record['username'] ?? 'User Baru',
                            'email' => $email,
                            'is_active' => 1,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        $record['employee_id'] = $empId;
                        if ($npp !== null && trim((string)$npp) !== '') {
                            $existingEmployeesByNpp[trim((string)$npp)] = $empId;
                        }
                    }
                } elseif ($table === 'users' && !empty($record['employee_id'])) {
                    $empUpdate = [];
                    if ($email !== null) $empUpdate['email'] = $email;
                    if ($npp !== null) $empUpdate['npp'] = $npp;
                    if (!empty($empUpdate)) {
                        DB::table('employee')->where('id', $record['employee_id'])->update($empUpdate);
                    }
                }

                // Ekstrak role_id dan status_id jika ada
                $roleId = null;
                $statusId = null;
                if (array_key_exists('role_id', $record)) {
                    $roleId = $record['role_id'];
                    unset($record['role_id']);
                }
                if (array_key_exists('status_id', $record)) {
                    $statusId = $record['status_id'];
                    unset($record['status_id']);
                }
                
                // Tentukan apakah record ini sudah ada (Update) atau baru (Insert)
                $targetId = null;
                if (isset($record['id']) && $record['id'] !== null && trim((string)$record['id']) !== '') {
                    $rowId = (int)$record['id'];
                    if (isset($existingIds[$rowId])) {
                        $targetId = $rowId;
                    }
                }

                if ($targetId === null) {
                    if ($table === 'users' && !empty($record['username'])) {
                        $uName = trim((string)$record['username']);
                        $uNameLower = strtolower($uName);
                        if (isset($existingKeys[$uName])) {
                            $targetId = $existingKeys[$uName];
                        } elseif (isset($existingKeys[$uNameLower])) {
                            $targetId = $existingKeys[$uNameLower];
                        }
                    } elseif ($table === 'news' && !empty($record['title'])) {
                        $tName = trim((string)$record['title']);
                        $tNameLower = strtolower($tName);
                        if (isset($existingKeys[$tName])) {
                            $targetId = $existingKeys[$tName];
                        } elseif (isset($existingKeys[$tNameLower])) {
                            $targetId = $existingKeys[$tNameLower];
                        }
                    } elseif ($table === 'unit' && !empty($record['code'])) {
                        $cName = trim((string)$record['code']);
                        $cNameLower = strtolower($cName);
                        if (isset($existingKeys[$cName])) {
                            $targetId = $existingKeys[$cName];
                        } elseif (isset($existingKeys[$cNameLower])) {
                            $targetId = $existingKeys[$cNameLower];
                        }
                    }
                }

                if ($targetId !== null) {
                    // --- UPDATE (UPSERT) ---
                    $updateRecord = $record;
                    unset($updateRecord['id']);

                    // Jangan timpa password jika di file tidak diisi
                    if ($table === 'users' && (!isset($updateRecord['password']) || $updateRecord['password'] === null || trim((string)$updateRecord['password']) === '')) {
                        unset($updateRecord['password']);
                    }

                    $saveData = array_intersect_key($updateRecord, $dbColumns);
                    if (!empty($saveData)) {
                        DB::table($table)->where('id', $targetId)->update($saveData);
                    }
                    $updated++;

                    // Update role/status if users table
                    if ($table === 'users' && $roleId !== null && isset($record['employee_id'])) {
                        DB::table('employee_role')->updateOrInsert(
                            ['employee_id' => $record['employee_id']],
                            ['role_id' => $roleId, 'unit_id' => $unitId, 'status_id' => $statusId, 'updated_at' => now()]
                        );
                    }
                } else {
                    // --- INSERT (DATA BARU) ---
                    $insertRecord = $record;
                    if (!isset($insertRecord['id']) || $insertRecord['id'] === null || trim((string)$insertRecord['id']) === '') {
                        unset($insertRecord['id']);
                    }

                    if ($table === 'users' && (!isset($insertRecord['password']) || $insertRecord['password'] === null || trim((string)$insertRecord['password']) === '')) {
                        $insertRecord['password'] = '1';
                    }

                    $saveData = array_intersect_key($insertRecord, $dbColumns);
                    $newId = DB::table($table)->insertGetId($saveData);
                    $inserted++;

                    // Daftarkan ke cache agar jika ada baris duplikat berikutnya di file yang sama, langsung menjadi UPDATE
                    $existingIds[$newId] = $newId;
                    if ($table === 'users' && !empty($record['username'])) {
                        $uName = trim((string)$record['username']);
                        $existingKeys[$uName] = $newId;
                        $existingKeys[strtolower($uName)] = $newId;
                    } elseif ($table === 'news' && !empty($record['title'])) {
                        $tName = trim((string)$record['title']);
                        $existingKeys[$tName] = $newId;
                        $existingKeys[strtolower($tName)] = $newId;
                    } elseif ($table === 'unit' && !empty($record['code'])) {
                        $cName = trim((string)$record['code']);
                        $existingKeys[$cName] = $newId;
                        $existingKeys[strtolower($cName)] = $newId;
                    }

                    // Insert role/status if users table
                    if ($table === 'users' && $roleId !== null && isset($record['employee_id'])) {
                        DB::table('employee_role')->updateOrInsert(
                            ['employee_id' => $record['employee_id']],
                            ['role_id' => $roleId, 'unit_id' => $unitId, 'status_id' => $statusId, 'created_at' => now(), 'updated_at' => now()]
                        );
                    }
                }
                
                $processedCount++;
                if ($processedCount % $chunkSize === 0) {
                    DB::commit();
                    DB::beginTransaction();
                }
            }
            DB::commit();
            
            $msg = "- Data Baru Berhasil DiImport : $inserted\n- Data Diperbarui (Upsert) : $updated\n- Format Data Tidak Sesuai : $invalidFormat";
            
            return ['status' => 'success', 'message' => $msg];
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function exportSqlDump($table)
    {
        // Untuk PostgreSQL, .backup (custom format) bisa menggunakan pg_dump
        // Karena kita tidak memiliki akses langsung ke shell OS tanpa konfigurasi spesifik,
        // kita akan buat raw SQL insert script sederhana sebagai simulasi .backup yang valid
        
        $data = DB::table($table)->get();
        if ($data->isEmpty()) {
            return response()->json(['message' => 'Data kosong'], 404);
        }

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

        $columns = array_keys((array)$data->first());
        $content = "-- PostgreSQL Dump for Table {$table}\n\n";
        
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
            $content .= "INSERT INTO {$table} (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $vals) . ") ON CONFLICT (id) DO UPDATE SET {$updateSql};\n";
        }

        \Illuminate\Support\Facades\Storage::disk('public')->put($folder . $filenameBase . '.backup', $content);

        return response($content)
            ->header('Content-Type', 'application/octet-stream')
            ->header('Content-Disposition', "attachment; filename={$filenameBase}.backup");
    }

    private function importSqlDump($file, $table)
    {
        $content = file_get_contents($file->getRealPath());
        
        DB::beginTransaction();
        try {
            DB::unprepared($content);
            DB::commit();
            return ['status' => 'success', 'message' => "- Script SQL Backup berhasil dieksekusi."];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
