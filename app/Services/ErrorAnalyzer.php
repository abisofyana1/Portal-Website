<?php

namespace App\Services;

use Throwable;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Session\TokenMismatchException;

class ErrorAnalyzer
{
    /**
     * Analyze a throwable and return an Indonesian explanation and actionable suggestions.
     *
     * @param Throwable|string $error The exception object or raw error message
     * @return array Contains ['explanation' => '...', 'suggestion' => '...']
     */
    public static function analyze($error): array
    {
        $explanation = "Terjadi kesalahan internal sistem yang tidak terduga pada server aplikasi.";
        $suggestion = "1. Segarkan (refresh) halaman browser Anda.\n2. Jika error berulang, silakan hubungi tim IT Support untuk memeriksa log server.";

        if ($error instanceof Throwable) {
            $class = get_class($error);
            $message = $error->getMessage();

            // 0. Malware & Trojan Security Exception
            if (str_contains($class, 'MalwareInfectionException') || str_contains(strtolower($class), 'malware') || str_contains(strtolower($message), 'malware') || str_contains(strtolower($message), 'keamanan terinfeksi') || str_contains(strtolower($message), 'backdoor') || str_contains(strtolower($message), 'webshell')) {
                return [
                    'explanation' => "Terdeteksi adanya ancaman keamanan berupa potensi infeksi malware, trojan, atau webshell backdoor pada kode sumber aplikasi.",
                    'suggestion' => "1. SEGERA hubungi tim IT Security atau System Administrator.\n2. Lakukan pemeriksaan manual pada berkas yang dilaporkan terindikasi mencurigakan.\n3. Hapus berkas jika berkas tersebut tidak dikenal (tidak sah).\n4. Lakukan pemulihan (restore) berkas asli dari repositori Git bersih jika berkas bawaan dirusak.\n5. Jalankan pemindaian penuh menggunakan antivirus Crowdstrike Falcon pada server hosting.",
                ];
            }

            // 1. Database Connection & Query Errors
            if ($error instanceof QueryException) {
                if (str_contains($message, 'Connection refused') || str_contains($message, 'SQLSTATE[HY000]') || str_contains($message, 'could not connect')) {
                    $explanation = "Aplikasi gagal terhubung ke server database. Kemungkinan server database sedang mati, atau konfigurasi koneksi di file .env tidak tepat.";
                    $suggestion = "1. Periksa apakah server database (MySQL/PostgreSQL) sedang aktif.\n2. Verifikasi host, port, username, dan password database di file konfigurasi .env.\n3. Periksa koneksi jaringan jika database berada di server terpisah.";
                } elseif (str_contains($message, 'Access denied') || str_contains($message, 'SQLSTATE[28000]')) {
                    $explanation = "Kredensial database ditolak. Username atau password database yang terkonfigurasi salah.";
                    $suggestion = "1. Buka file konfigurasi .env di root proyek.\n2. Pastikan DB_USERNAME dan DB_PASSWORD sudah benar dan memiliki hak akses ke DB_DATABASE.\n3. Jalankan 'php artisan config:clear' setelah mengubah file .env.";
                } elseif (str_contains($message, 'Relation') && str_contains($message, 'does not exist') || str_contains($message, 'SQLSTATE[42S02]')) {
                    $explanation = "Tabel database yang diakses oleh aplikasi tidak ditemukan. Kemungkinan ada migrasi database yang belum dijalankan.";
                    $suggestion = "1. Jalankan perintah 'php artisan migrate' di terminal untuk membuat tabel yang dibutuhkan.\n2. Pastikan database yang terhubung sudah benar sesuai skema terbaru.";
                } elseif (str_contains($message, 'Duplicate entry') || str_contains($message, 'SQLSTATE[23000]') || str_contains($message, 'violates unique constraint')) {
                    $explanation = "Pelanggaran integritas data. Anda mencoba menyimpan nilai unik (seperti NPP, Username, atau Email) yang sudah terdaftar sebelumnya di database.";
                    $suggestion = "1. Periksa kolom NPP, Username, atau Email yang Anda masukkan pada form.\n2. Pastikan data tersebut unik dan belum pernah digunakan oleh akun lain.\n3. Gunakan fitur pencarian pada menu Manage User untuk memeriksa duplikasi.";
                } else {
                    $explanation = "Terjadi kegagalan saat menjalankan perintah query ke database. Ada ketidakcocokan tipe data atau kesalahan skema.";
                    $suggestion = "1. Periksa apakah data yang Anda masukkan pada formulir sudah sesuai format.\n2. Jalankan migrasi atau periksa integritas kolom database terkait.";
                }
            }
            // 2. Model Not Found / 404 Route
            elseif ($error instanceof ModelNotFoundException || $error instanceof NotFoundHttpException) {
                $explanation = "Halaman atau data yang Anda minta tidak dapat ditemukan di dalam sistem. Kemungkinan data telah dihapus atau tautan yang diakses salah.";
                $suggestion = "1. Muat ulang halaman untuk memastikan data yang ditampilkan adalah data terbaru.\n2. Periksa kembali tautan URL atau parameter ID yang ada di bilah alamat browser.\n3. Pastikan data tersebut memang eksis di menu pencarian terkait.";
            }
            // 3. Validation Errors
            elseif ($error instanceof ValidationException) {
                $explanation = "Data yang Anda kirimkan tidak lolos verifikasi validasi sistem (misalnya: isian wajib dikosongkan, atau format email salah).";
                $suggestion = "1. Periksa kembali seluruh kolom input bertanda bintang merah wajib di form.\n2. Pastikan kolom email memiliki format '@' yang valid dan kolom NPP hanya berisi angka.\n3. Perbaiki kolom yang ditandai merah sebelum menyimpan ulang.";
            }
            // 4. Authentication & Authorization Errors
            elseif ($error instanceof AuthenticationException) {
                $explanation = "Sesi autentikasi Anda tidak valid atau telah kedaluwarsa. Anda diharuskan login terlebih dahulu untuk mengakses menu ini.";
                $suggestion = "1. Silakan keluar (logout) dan lakukan masuk (login) kembali untuk memperbarui sesi Anda.\n2. Periksa apakah cookie browser dinonaktifkan.";
            } elseif ($error instanceof AuthorizationException) {
                $explanation = "Akses Ditolak! Peran (role) akun Anda tidak memiliki hak akses (izin) yang cukup untuk mengakses fitur atau menu ini.";
                $suggestion = "1. Pastikan Anda menggunakan peran yang tepat (misal: masuk sebagai Super Admin).\n2. Minta Super Admin untuk memeriksa hak akses peran Anda di menu Manage User.";
            }
            // 5. CSRF Expired
            elseif ($error instanceof TokenMismatchException) {
                $explanation = "Sesi keamanan halaman (CSRF Token) Anda telah kedaluwarsa karena halaman didiamkan terbuka terlalu lama.";
                $suggestion = "1. Muat ulang (refresh) halaman browser Anda secara penuh.\n2. Lakukan login ulang jika sesi Anda terputus secara otomatis.";
            }
            // 6. Method Not Allowed
            elseif ($error instanceof MethodNotAllowedHttpException) {
                $explanation = "Metode pemanggilan API tidak didukung. Terjadi ketidakcocokan antara metode request frontend (seperti GET) dengan backend (seperti POST).";
                $suggestion = "1. Pastikan kode pemanggilan Axios di sisi frontend sudah menggunakan metode yang sesuai dengan API route.\n2. Bersihkan cache browser Anda.";
            }
        } elseif (is_string($error)) {
            $msg = strtolower($error);

            // Security Malware Errors
            if (str_contains($msg, 'malware') || str_contains($msg, 'trojan') || str_contains($msg, 'webshell') || str_contains($msg, 'backdoor')) {
                $explanation = "Terdeteksi adanya ancaman keamanan berupa potensi infeksi malware, trojan, atau webshell backdoor pada kode sumber aplikasi.";
                $suggestion = "1. SEGERA hubungi tim IT Security atau System Administrator.\n2. Periksa berkas yang terindikasi mencurigakan secara manual.\n3. Hapus berkas jika berkas tersebut tidak dikenal atau kembalikan dari repositori resmi.\n4. Jalankan scan menyeluruh Crowdstrike Falcon pada server hosting.";
            }
            // Client-side Javascript Errors
            elseif (str_contains($msg, 'javascript error') || str_contains($msg, 'client-side') || str_contains($msg, 'vue')) {
                $explanation = "Terjadi kegagalan eksekusi skrip JavaScript di sisi browser pengguna. Browser gagal memproses data atau ada kegagalan rendering komponen UI.";
                $suggestion = "1. Segarkan (refresh) halaman browser Anda.\n2. Bersihkan berkas cache dan cookie browser Anda.\n3. Pastikan browser Anda diperbarui ke versi terbaru dan coba gunakan mode Penyamaran (Incognito).";
            } elseif (str_contains($msg, 'network error') || str_contains($msg, 'axios') || str_contains($msg, 'failed to fetch')) {
                $explanation = "Kegagalan koneksi jaringan. Browser Anda tidak dapat berkomunikasi dengan server aplikasi PortalCXC.";
                $suggestion = "1. Periksa koneksi internet atau kabel LAN perangkat komputer Anda.\n2. Pastikan server aplikasi PortalCXC sedang berjalan dengan baik.\n3. Hubungi tim IT Jaringan jika masalah berlanjut.";
            }
        }

        return [
            'explanation' => $explanation,
            'suggestion' => $suggestion,
        ];
    }
}
