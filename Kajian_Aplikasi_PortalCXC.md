# Kajian Teknis Aplikasi PortalCXC

## 1. Pendahuluan
Aplikasi **PortalCXC** adalah sebuah sistem pusat informasi operasional (*knowledge hub*) dan dashboard analitik berbasis web yang dirancang khusus untuk BNI Contact Center (BCC). Aplikasi ini berfungsi sebagai pusat distribusi informasi (berita, promo, program kerja) antar unit layanan, memfasilitasi manajemen pengguna terintegrasi, serta menyediakan antarmuka dinamis (*customizable*) yang dapat dikonfigurasi secara fleksibel oleh administrator tanpa perlu melakukan perubahan pada kode program (*no-code configuration*).

---

## 2. Arsitektur & Tumpukan Teknologi
Aplikasi PortalCXC dirancang menggunakan arsitektur modern berorientasi performa dengan pembagian tanggung jawab (*separation of concerns*) yang jelas antara backend dan frontend:

*   **Backend Framework**: **Laravel (PHP)**. Menggunakan arsitektur MVC (Model-View-Controller) untuk menangani RESTful API, autentikasi sesi, isolasi data berbasis unit, dan seluruh logika bisnis aplikasi.
*   **Frontend Framework**: **Vue.js (Single Page Application - SPA)**. Seluruh interaksi pengguna terpusat pada satu komponen utama (`Home.vue`) dan `Login.vue` dengan pemuatan data dinamis menggunakan Axios. Konsep SPA ini meminimalkan lalu lintas data jaringan (*network overhead*) dan menghilangkan pemuatan ulang halaman penuh (*full-page reload*).
*   **Editor Konten**: **CKEditor 5 (Custom Build)**. Mendukung pemformatan teks kaya (*rich text*), tabel lanjutan, penyematan gambar berbasis base64, media eksternal, dan injeksi kode HTML khusus (seperti tag marquee) untuk pengumuman berjalan.
*   **Tata Gaya & Keindahan Visual**: **Tailwind CSS & FontAwesome**. Menyediakan antarmuka modern bernuansa premium dengan dukungan transisi mikro-interaksi, efek glassmorphism, dan responsivitas tata letak tingkat tinggi.
*   **Basis Data**: Mendukung mesin relasional (PostgreSQL/MySQL) dengan pemanfaatan **Eloquent ORM** untuk pengelolaan relasi data yang kompleks, termasuk log akses menu, data pengguna, log masuk, konfigurasi portal, audit trail aktivitas pengguna (`activity_logs`), logging diagnosis kesalahan sistem (`system_error_logs`), serta manajemen jadwal dan riwayat pemulihan/pencadangan data (`backup_schedules`, `backup_histories`).

---

## 3. Analisis Alur & Fungsi Utama Aplikasi

### 3.1. Autentikasi Keamanan & Keunikan Sesi (*Session Integrity*)
PortalCXC mengimplementasikan kontrol sesi yang sangat ketat untuk menjamin keamanan data operasional:
1.  **Enforce Single Session (Anti-Concurrent Login)**:
    *   Sistem membatasi setiap akun pengguna agar hanya dapat aktif pada **satu sesi browser/perangkat** dalam satu waktu menggunakan tabel database `user_session`.
    *   Ketika pengguna mencoba masuk, sistem mendeteksi apakah ada sesi aktif lain untuk akun tersebut. Jika sesi lain aktif dalam kurun waktu kurang dari **30 menit** (sejak aktivitas terakhir), proses masuk akan diblokir dengan pesan: *"User Anda Telah Login diBrowser Lain, Silahkan Logout Terlebih Dahulu!!"*.
    *   Jika sesi lama terdeteksi tidak aktif lebih dari 30 menit (*stale session*), sistem akan menghapusnya secara otomatis untuk mengizinkan akses baru.
    *   Jika sesi pengguna ditimpa dari browser lain, sesi lama akan terputus dengan notifikasi: *"Sesi Anda telah berakhir karena login di browser lain"*.
2.  **Keamanan Sandi Pengguna (*Plaintext Legacy & Force Reset*)**:
    *   Demi kompatibilitas dengan sistem warisan (*legacy system*), sandi disimpan dalam format teks polos (*plaintext*).
    *   Untuk memitigasi risiko keamanan, jika administrator menyetel atau mereset sandi pengguna menjadi **"1"**, sistem akan mendeteksi status ini sebagai sandi default/lemah dan **memaksa pengguna mengganti sandi** secara langsung di halaman utama sebelum dapat mengakses fitur aplikasi lainnya.
3.  **Sesi Kedaluwarsa Otomatis (*8-Hour Session Timeout*)**:
    *   Sistem menerapkan durasi aktif maksimum selama **8 jam** (setara 28.800.000 ms, disesuaikan dengan rata-rata jam giliran kerja/shift operasional).
    *   Sesi ini akan otomatis diperpanjang jika terdeteksi aktivitas interaksi fisik berupa pergerakan mouse (`mousemove`) atau penekanan tombol papan ketik (`keydown`).
    *   Jika pengguna tidak aktif selama 8 jam berturut-turut, sistem akan melakukan *force logout*, menghapus sesi lokal secara bersih, mencatat waktu keluar ke database, dan mengarahkan pengguna kembali ke login dengan notifikasi: *"Session Habis Silahkan Login kembali"*.

### 3.2. Role-Based Access Control (RBAC) & Isolasi Data Unit
Aplikasi membedakan hak akses secara ketat berdasarkan peran (*role*) dan unit kerja pengguna:
1.  **Super Admin**:
    *   Memiliki akses tanpa batasan ke seluruh menu aplikasi, termasuk menu tingkat tinggi **Application Tools** (Manage Menu Admin, Manage Permission, Manage Config, Manage Lookup, Manage Workflow, Manage Session).
    *   Dapat melihat, menambah, mengubah, dan menghapus data pengguna serta berita dari seluruh unit tanpa adanya isolasi data.
2.  **Unit Admin (Admin Terbatas Unit)**:
    *   **Isolasi Data Pengguna**: Saat mengelola data pengguna (`Manage User`), Unit Admin hanya dapat mencari dan memodifikasi data pengguna yang terdaftar di unit yang sama dengan dirinya.
    *   **Isolasi Berita**: Saat mengelola konten, Unit Admin hanya dapat memfilter dan menyusun berita sesuai unit kerjanya. Pilihan filter unit dikunci otomatis sesuai unit admin tersebut.
    *   **Restriksi Menu**: Menu **Application Tools**, sub-menu **Manage Unit** dan **Activity Log** di bawah *Human Resource*, serta **Portal Interface Agent Unit** di bawah *Portal* secara otomatis disembunyikan dan diblokir dari akses Unit Admin.
3.  **Agent (Layanan Operasional)**:
    *   Tampilan berorientasi operasional (*Agent Layout*) yang dinamis. Hanya menampilkan grid menu ikonik, berita/promo carousel, bilah pencarian berita operasional, dan profil diri.
    *   Tampilan berita dan menu yang disajikan terbatas pada unit layanan yang diwakili oleh peran aktif Agent saat itu (misal: Banking, Credit, Emerald, Merchant).

### 3.3. Dashboard Analitik Operasional & Statistik Real-Time
Dashboard khusus administrator (diakses melalui menu **My Role**) menyediakan visualisasi data operasional yang sangat lengkap untuk monitoring performa:
1.  **Statistik Keaktifan Agen**: Menampilkan jumlah total agen, agen berstatus aktif, non-aktif, serta visualisasi breakdown detail agen aktif/non-aktif per unit kerja dengan pemisahan peran (Admin, Agent, Super Admin).
2.  **Statistik Konten Berita**: Menyajikan jumlah total berita, berita yang berstatus aktif (*published*), serta berita kedaluwarsa (*expired*), lengkap dengan detail statistik per unit kerja.
3.  **Tren Masuk Harian (30 Hari Terakhir)**: Grafik garis/batang dinamis yang melacak frekuensi masuk harian pengguna unik, rata-rata harian login, dan akumulasi total login dalam 30 hari.
4.  **Analisis Akses Menu (Menu Clicks Tracker)**: Melacak secara real-time frekuensi klik agen pada Menu 1 hingga Menu 15. Disajikan dalam bentuk diagram batang persentase horizontal untuk mengidentifikasi menu yang paling sering diakses.
5.  **Analisis Keaktifan Login Pengguna**: Mengelompokkan pengguna ke dalam tiga tab dinamis:
    *   **Sering Login (*Frequent*)**: Daftar pengguna dengan frekuensi masuk tertinggi.
    *   **Jarang Login (*Rare*)**: Daftar pengguna yang jarang melakukan aktivitas masuk.
    *   **Tidak Pernah Login (*Never*)**: Daftar pengguna yang akunnya belum pernah digunakan sama sekali.

### 3.4. Distribusi Dokumen & Alur Manajemen Berita (*News Management*)
Sistem manajemen konten (`NewsController`) dirancang kokoh untuk memfasilitasi kebutuhan penyebaran dokumen secara masif:
*   **Kategorisasi & Target Fleksibel**: Berita dikelompokkan berdasarkan kategori (seperti Promo, Program, Pengumuman) dan dapat ditargetkan ke unit spesifik atau ke seluruh layanan (*All Layanan*).
*   **Alur Pengunggahan Multi-Dokumen Lanjutan (*Multi-Unit Duplication*)**:
    *   Saat admin mengunggah berkas lampiran utama atau lampiran ganda (*multiple attachments*), sistem akan memproses penyimpanan berkas berdasarkan target unit yang dipilih.
    *   Berkas pertama akan disimpan di direktori fisik unit primer: `/storage/news_attachments/{primary_unit_folder}/{filename}`.
    *   Untuk kemudahan manajemen fisik dan organisasi data, sistem akan **menduplikasi (menyalin) berkas tersebut secara fisik** ke direktori penyimpanan masing-masing unit target lainnya yang dipilih dalam formulir secara otomatis.
*   **Sistem Kontrol Berita Banner (Carousel)**:
    *   Berita penting berkategori Promo secara otomatis disajikan dalam bentuk banner korsel (*carousel*) yang berputar otomatis di antarmuka Agent.
    *   Dilengkapi tombol navigasi manual (`<` dan `>`) yang muncul saat diarahkan oleh kursor, serta tombol pintas akses cepat daftar lengkap berita.

### 3.5. Penyelarasan Tata Letak Presisi & Responsivitas Split-Screen
Tampilan antarmuka Agent dirancang dengan ketahanan tata letak (*layout resilience*) yang luar biasa untuk mendukung gaya kerja multi-tasking:
1.  **Penyelarasan Presisi Baris Bawah (Menu 10-15)**:
    *   Kotak menu terbawah dikunci dengan tinggi dasar `h-48` (192px) agar terhindar dari kerusakan layout akibat kelebihan konten teks (*content overflow*).
    *   Menggunakan konfigurasi sub-grid `grid-rows-2` pada kontainer tengah serta kelas `h-full` pada kartu menu 11, 12, 13, dan 14 untuk memastikan seluruh kartu menu sejajar lurus secara horizontal pada sisi bawahnya secara simetris dan presisi.
2.  **Responsivitas Split-Screen Bebas Tabrakan**:
    *   **Logo & Brand Skala Dinamis**: Judul brand unit (`text-xl` hingga `text-3xl`) dan Logo Globe (`h-10` hingga `h-14`) menyesuaikan ukurannya secara otomatis mengikuti lebar ruang layar browser.
    *   **Username Auto-Truncation**: Nama agen di tombol profil dipotong secara dinamis (`max-w-[50px]` di layar split/sempit, `max-w-[80px]` di layar sedang, dan `max-w-[120px]` di layar penuh). Langkah ini mencegah tombol menu terdorong keluar layar atau tertutup oleh logo saat browser dibagi 50-50 atau ketika tingkat perbesaran (*zoom*) browser diatur sangat tinggi.
    *   Properti `flex-shrink-0` diimplementasikan secara konsisten pada komponen navigasi untuk mengunci keutuhan posisi tombol penting (Home, Profil, Logout).

### 3.6. Sistem Monitoring Terpadu: Analitik Activity Log & Diagnostic Error Log
Untuk menjamin transparansi operasional, keamanan audit, dan kemudahan pemeliharaan (*maintainability*), PortalCXC mengintegrasikan sistem logging terpadu dua arah yang mencakup aktivitas operasional pengguna dan diagnosis kesalahan sistem secara real-time:
1.  **Pelacakan Aktivitas Pengguna (User Activity Log)**:
    *   Mencatat secara terperinci setiap tindakan krusial administrator dan pengguna ke dalam tabel `activity_logs`.
    *   Aktivitas yang dipantau meliputi: proses autentikasi (Login Sukses/Gagal, Sesi Ganda Terdeteksi, Logout), manajemen pengguna (Reset Sesi, Reset Password, CRUD User), manajemen unit (CRUD Unit), manajemen berita (CRUD Berita, Unggah Lampiran), serta perubahan tata letak antarmuka (Portal Setting).
    *   Setiap entri mencatat stempel waktu (*timestamp*), alamat IP asal, nama & NPP pengguna, kategori modul sistem, jenis aksi (CREATE/UPDATE/DELETE/AUTH/UPLOAD), serta penjelasan aktivitas yang detail untuk kebutuhan kepatuhan audit.
2.  **Sistem Diagnosis Error Pintar (System Error Log & ErrorAnalyzer)**:
    *   **Pencatatan Error Backend Otomatis**: Memanfaatkan exception reporter terintegrasi pada `bootstrap/app.php` Laravel 11. Setiap kali terjadi Exception tak tertangkap (seperti `QueryException` database, `ValidationException` input, `TokenMismatchException` CSRF, atau `ErrorException` skrip), sistem akan secara otomatis memotong alur error, menganalisisnya, dan menyimpannya ke tabel `system_error_logs`.
    *   **Pencatatan Error Frontend Browser**: Frontend Vue.js dilengkapi pendengar error global (`window.addEventListener('error')` dan `unhandledrejection`). Setiap kesalahan eksekusi JavaScript browser atau kegagalan Promise yang tidak tertangani di sisi klien akan dikirimkan secara asinkron ke backend melalui API `/api/error-logs/client` untuk dicatat tanpa mengganggu jalannya aplikasi (*non-blocking catcher*).
    *   **Mesin Analisis Bahasa Indonesia (ErrorAnalyzer)**: Layanan backend dilengkapi kamus intelijen yang menerjemahkan pesan kesalahan teknis yang rumit menjadi penjelasan ramah pengguna (*explanation*) dan langkah perbaikan konkret (*suggestion*) dalam Bahasa Indonesia. Sistem mencakup analisis komprehensif terhadap kesalahan basis data, kegagalan otorisasi/autentikasi, sesi kedaluwarsa, hingga kegagalan runtime JavaScript browser.
3.  **Visualisasi Antarmuka & Panel Diagnostik Premium**:
    *   Antarmuka khusus Super Admin di bawah menu **Activity Log** (menyaring log menggunakan pencarian pintar, filter kategori modul, dan klasifikasi aksi).
    *   **Charcoal Dark-Themed Diagnostic Modal**: Panel modal interaktif bernuansa gelap premium yang menampilkan analisis visual kesalahan: kotak Amber untuk penjelasan penyebab kegagalan sistem, kotak Emerald untuk instruksi pemecahan masalah konkret bagi administrator, panel salin klip stack trace yang rapi, serta ringkasan meta info klien (metode request, alamat IP, dan user-agent).

### 3.7. Sistem Penjadwalan & Pemeliharaan Backup Database
Guna memastikan kelangsungan operasional dan keamanan data jangka panjang, aplikasi dilengkapi dengan fungsionalitas manajemen pemulihan dan pencadangan data otomatis:
1.  **Penjadwalan Dinamis (*Backup Scheduling*)**: Administrator memiliki kemampuan untuk mengatur jadwal pencadangan basis data secara spesifik (harian, mingguan, bulanan, atau waktu kustom) melalui antarmuka. Sistem memanfaatkan task scheduler (`BackupSchedule` model) untuk merencanakan eksekusi proses pembuangan (*dump*) basis data secara presisi.
2.  **Pencatatan Riwayat (*Backup History*)**: Proses pencadangan (baik keberhasilan maupun kegagalan) dicatat secara runut pada tabel `backup_histories`. Hasil cadangan berupa file `.backup` langsung diamankan ke media penyimpanan sistem yang siap dipulihkan (*disaster recovery*) sewaktu-waktu.

---

## 4. Kelebihan, Keterbatasan, & Rekomendasi Teknis

### Kelebihan Utama
1.  **Analitik Operasional yang Luar Biasa**: Dashboard administrator menyajikan visualisasi data aktivitas agen yang sangat rinci (tren login, analisis klik menu, keaktifan pengguna, performa per unit) yang krusial bagi manajemen BNI Contact Center.
2.  **Keamanan Sesi yang Presisi**: Mekanisme anti-concurrent login berbasis tabel database dan auto-logout 8 jam secara efektif menekan celah penyalahgunaan akun operasional.
3.  **Keseimbangan Visual Tingkat Premium**: Desain antarmuka agent sangat adaptif, presisi, dan proporsional bahkan pada kondisi split-screen 50-50 atau tingkat perbesaran tinggi.
4.  **No-Code Interface Management**: Administrator memiliki kontrol penuh untuk mengubah latar belakang aplikasi dan mengubah mode tampilan menu (List, Horizontal, Slide Otomatis) secara dinamis langsung dari database.
5.  **Sistem Audit Trail & Diagnostik Mandiri**: Terintegrasinya modul Activity Log & System Error Log dengan penjelasan berbahasa Indonesia mempercepat penanganan bug oleh admin TI dari hitungan jam menjadi hitungan menit secara mandiri tanpa harus membuka file server log mentah.

### Keterbatasan Sistem
1.  **Penyimpanan Plaintext Password**: Penyimpanan sandi berupa teks polos berisiko jika basis data diakses oleh pihak yang tidak bertanggung jawab. Sangat disarankan untuk bermigrasi ke algoritme enkripsi satu arah (seperti Bcrypt) di masa mendatang demi kepatuhan terhadap standar keamanan siber modern.
2.  **Komponen Monolitik Raksasa**: Komponen `Home.vue` memiliki ukuran sangat besar (lebih dari 7.000 baris kode). Hal ini dapat memperlambat proses pengembangan dan meningkatkan kompleksitas pemeliharaan jangka panjang.

### Rekomendasi Perbaikan Teknis
1.  **Refactoring Komponen**: Memecah `Home.vue` menjadi sub-komponen terpisah yang modular (misalnya: `DashboardStats.vue`, `UserManagementModal.vue`, `NewsCarousel.vue`, dan `MenuGrid.vue`).
2.  **Upgrade Autentikasi**: Menerapkan fungsi hashing bawaan Laravel (`Hash::make`) pada data password untuk mengamankan kredensial pengguna tanpa merusak alur verifikasi sistem.

---
*Kajian teknis aplikasi PortalCXC ini diperbarui dan diselaraskan secara akurat berdasarkan audit menyeluruh terhadap kode sumber per 9 Juni 2026.*
