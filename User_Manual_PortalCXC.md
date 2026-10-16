# User Manual Aplikasi PortalCXC

## Daftar Isi
1. [Login dan Akses Sistem](#1-login-dan-akses-sistem)
   * [1.1 Alur Masuk (Login)](#11-alur-masuk-login)
   * [1.2 Pembatasan Sesi Bersamaan (Concurrent Login Lock)](#12-pembatasan-sesi-bersamaan-concurrent-login-lock)
   * [1.3 Sesi Kedaluwarsa Otomatis (Session Timeout)](#13-sesi-kedaluwarsa-otomatis-session-timeout)
   * [1.4 Pengubahan Sandi Wajib (Force Password Change)](#14-pengubahan-sandi-wajib-force-password-change)
2. [Dashboard Agent (Tampilan Operasional)](#2-dashboard-agent-tampilan-operasional)
   * [2.1 Menu Grid Utama](#21-menu-grid-utama)
   * [2.2 News Banner Carousel](#22-news-banner-carousel)
   * [2.3 Bilah Pencarian Cepat (Live Search)](#23-bilah-pencarian-cepat-live-search)
   * [2.4 Profil Pengguna & Navigasi Header](#24-profil-pengguna--navigasi-header)
   * [2.5 Dukungan Split-Screen Responsif](#25-dukungan-split-screen-responsif)
3. [Dashboard Administrator & Statistik Analitik](#3-dashboard-administrator--statistik-analitik)
   * [3.1 Hak Akses Menu Admin (Super Admin vs Unit Admin)](#31-hak-akses-menu-admin-super-admin-vs-unit-admin)
   * [3.2 Dashboard Analitik Real-Time (Statistik My Role)](#32-dashboard-analitik-real-time-statistik-my-role)
4. [Manajemen Pengguna (Manage User)](#4-manajemen-pengguna-manage-user)
   * [4.1 Isolasi Data Pengguna Berbasis Unit](#41-isolasi-data-pengguna-berbasis-unit)
   * [4.2 Menambah Pengguna Baru](#42-menambah-pengguna-baru)
   * [4.3 Mengubah Data, Reset Sandi, & Reset Sesi](#43-mengubah-data-reset-sandi--reset-sesi)
   * [4.4 Menghapus Pengguna](#44-menghapus-pengguna)
5. [Manajemen Unit Kerja (Manage Unit)](#5-manajemen-unit-kerja-manage-unit)
   * [5.1 Mengelola Unit](#51-mengelola-unit)
   * [5.2 Penghapusan Unit yang Aman (Cascading Safe Delete)](#52-penghapusan-unit-yang-aman-cascading-safe-delete)
6. [Manajemen Tampilan Portal (Portal Interface)](#6-manajemen-tampilan-portal-portal-interface)
   * [6.1 Mengganti Latar Belakang (Background Image)](#61-mengganti-latar-belakang-background-image)
   * [6.2 Memetakan Layout Menu (Portal Menu Mapping)](#62-memetakan-layout-menu-portal-menu-mapping)
7. [Manajemen Berita (Manage News)](#7-manajemen-berita-manage-news)
   * [7.1 Membuat Berita Baru](#71-membuat-berita-baru)
   * [7.2 Unggah Berkas & Alur Distribusi Multi-Unit](#72-unggah-berkas--alur-distribusi-multi-unit)
   * [7.3 Mengedit, Memfilter, & Menghapus Berita secara Massal](#73-mengedit-memfilter--menghapus-berita-secara-massal)
8. [Monitoring Aktivitas & Log Error Sistem (Activity Log)](#8-monitoring-aktivitas--log-error-sistem-activity-log)
   * [8.1 Tab Log Aktivitas Pengguna](#81-tab-log-aktivitas-pengguna)
   * [8.2 Tab Log Error Sistem](#82-tab-log-error-sistem)
   * [8.3 Panel Analisis & Solusi Error Premium (Diagnostic Modal)](#83-panel-analisis--solusi-error-premium-diagnostic-modal)
9. [Manajemen Backup Database (Database Backup)](#9-manajemen-backup-database-database-backup)
   * [9.1 Penjadwalan Backup Otomatis](#91-penjadwalan-backup-otomatis)
   * [9.2 Riwayat Backup dan Pemulihan](#92-riwayat-backup-dan-pemulihan)

---

## 1. Login dan Akses Sistem

### 1.1 Alur Masuk (Login)
1. Buka browser internet Anda dan ketikkan alamat aplikasi PortalCXC (misal: `http://localhost:9000`).
2. Halaman Login akan muncul dengan latar belakang dinamis yang diatur oleh Administrator.
3. Masukkan **Username** dan **Password** Anda pada kolom yang disediakan.
   * *Default Administrator*: Username `admin`, Password `password`.
4. Klik tombol **Sign In** untuk masuk ke sistem.

### 1.2 Pembatasan Sesi Bersamaan (Concurrent Login Lock)
Aplikasi PortalCXC dirancang dengan tingkat keamanan tinggi untuk mencegah penggunaan akun secara bersamaan di browser atau perangkat yang berbeda:
* **Deteksi Sesi Aktif**: Jika sistem mendeteksi akun Anda sedang digunakan pada sesi browser lain dan masih ada aktivitas dalam kurun waktu kurang dari **30 menit**, proses login akan ditolak. Anda akan menerima pesan peringatan berwarna merah: *"User Anda Telah Login diBrowser Lain, Silahkan Logout Terlebih Dahulu!!"*.
* **Penghancuran Sesi Stale**: Jika sesi di browser lain terdeteksi tidak aktif lebih dari 30 menit, sistem menganggap sesi tersebut telah usang (*stale*), menghapusnya dari basis data secara otomatis, dan mengizinkan Anda masuk.
* **Terdepak Sesi (Concurrent Logout)**: Jika sesi Anda terpaksa digantikan oleh login baru di perangkat lain, sesi aktif Anda saat ini akan dihentikan secara sepihak dan Anda akan diarahkan ke halaman login dengan pesan: *"Sesi Anda telah berakhir karena login di browser lain"*.

### 1.3 Sesi Kedaluwarsa Otomatis (Session Timeout)
* Untuk melindungi keamanan informasi operasional saat Anda meninggalkan meja kerja, sistem membatasi masa aktif sesi hingga maksimum **8 Jam** (28.800.000 ms).
* Durasi ini akan **diperpanjang/direset otomatis** secara berkala setiap kali Anda melakukan aktivitas fisik berupa menggerakkan kursor mouse (`mousemove`) atau mengetik di papan ketik (`keydown`).
* Jika sistem mendeteksi sama sekali tidak ada interaksi fisik selama 8 jam berturut-turut, sesi Anda akan kedaluwarsa secara otomatis. Akun Anda akan dikeluarkan (*logout*) secara paksa dan layar akan dialihkan ke halaman login dengan notifikasi kuning: *"Session Habis Silahkan Login kembali"*.

### 1.4 Pengubahan Sandi Wajib (Force Password Change)
* Demi standar kepatuhan keamanan data, jika administrator menyetel atau mereset sandi akun Anda menjadi nilai bawaan lemah, yaitu **"1"**, sistem akan mendeteksi sandi tidak aman ini sesaat setelah login berhasil.
* Anda secara otomatis akan **dipaksa mengisi formulir penggantian sandi baru** di bagian atas layar halaman utama. Anda tidak dapat melakukan aktivitas operasional lainnya pada portal sebelum Anda mengganti sandi default tersebut menjadi sandi baru yang aman (minimal 4 karakter).

---

## 2. Dashboard Agent (Tampilan Operasional)

Setelah proses login berhasil dan Anda memilih peran/role **Agent**, Anda akan diarahkan ke antarmuka utama agen yang terstruktur rapi dan responsif.

### 2.1 Menu Grid Utama
* Halaman agen menampilkan berbagai ikon pintasan penting dari Menu 1 hingga Menu 15 yang disusun dalam bentuk grid dinamis. Klik ikon menu untuk langsung membuka tautan eksternal atau masuk ke folder sub-menu informasi terkait.
* **Penyelarasan Presisi Baris Bawah**: Grid menu baris terbawah (Menu 10 s/d Menu 15) dikunci secara simetris dengan tinggi kontainer merata `h-48`. Hal ini memastikan seluruh kartu menu sejajar lurus secara horizontal pada sisi bawahnya tanpa ada distorsi visual akibat konten teks yang panjang.
* **Middle Click Open in New Tab (Klik Tengah)**: Anda dapat menekan **tombol scroll tengah mouse** pada kartu menu untuk membuka tautan menu tersebut secara langsung pada tab browser baru tanpa meninggalkan layar dashboard utama.

### 2.2 News Banner Carousel
* Terletak di bagian tengah-kanan layar utama agen. Banner dinamis ini menampilkan kumpulan informasi penting berkategori "Promo" secara bergantian (berputar otomatis).
* **Navigasi Manual**: Arahkan kursor mouse ke area banner untuk memunculkan tombol panah navigasi **kiri (<)** dan **kanan (>)** guna memindahkan slide berita secara manual.
* **List News**: Klik tombol **List News** di pojok kiri bawah banner untuk membuka modal khusus yang menampilkan seluruh daftar berita promo operasional aktif.
* **Middle Click (Klik Tengah) pada Banner**: Menekan tombol scroll tengah mouse pada berita di banner atau di daftar list berita akan langsung membuka detail isi berita tersebut pada tab browser baru.

### 2.3 Bilah Pencarian Cepat (Live Search)
* Terletak di bagian tengah atas navigasi.
* Cukup ketik kata kunci (Judul, Isi Berita, atau Kata Kunci/Keyword) yang dicari. Hasil pencarian operasional akan langsung muncul di bawah bilah secara langsung (*live dropdown*). Klik hasil pencarian untuk membaca artikel berita lengkap.

### 2.4 Profil Pengguna & Navigasi Header
* Di pojok kiri atas, klik nama profil Anda untuk membuka panel dropdown detail akun. Panel ini menyajikan NPP, nama lengkap, alamat email, unit kerja aktif, dan peran (role) aktif Anda saat ini.
* Terdapat tombol navigasi penting pada header:
  * **Home (Ikon Rumah)**: Memuat ulang dashboard ke kondisi awal secara instan.
  * **Logout (Ikon Pintu Keluar)**: Untuk keluar dari aplikasi secara aman dan menghapus catatan sesi aktif Anda di database.

### 2.5 Dukungan Split-Screen Responsif
PortalCXC dirancang dengan fleksibilitas layout tingkat tinggi untuk memfasilitasi kebutuhan multi-tasking harian agen Contact Center:
* **Dukungan Layar Terbagi**: Jika Anda memposisikan browser PortalCXC secara berdampingan (split-screen 50-50) dengan aplikasi operasional lainnya, atau menyetel tingkat perbesaran (*zoom*) browser hingga sangat tinggi, antarmuka header secara otomatis menyesuaikan skalanya.
* Judul unit layanan (`CXC BANKING`, `CXC CREDIT`, dll.) dan Logo Globe akan menyusut secara proporsional. 
* Nama pengguna pada profil akan dipotong secara dinamis (`max-w-[50px]` di layar split/sempit, `max-w-[80px]` di layar sedang, dan `max-w-[120px]` di layar penuh) agar tombol navigasi utama (Home, Profil, dan Logout) dijamin **selalu terlihat utuh, tidak tumpang tindih, tidak tersembunyi di bawah logo, dan tetap mudah diklik**.

---

## 3. Dashboard Administrator & Statistik Analitik

### 3.1 Hak Akses Menu Admin (Super Admin vs Unit Admin)
Menu navigasi sidebar di sebelah kiri layar administrator secara dinamis menyesuaikan tingkat otorisasi peran Anda:
1. **Super Admin (Akses Penuh Global)**:
   * Dapat melihat dan mengelola seluruh menu sidebar tanpa terkecuali: `My Profile` (My Role, My Profile), `Portal` (Portal Interface Agent Unit), `News` (Manage News), `Human Resource` (Manage User, Manage Unit, Activity Log), `Database` (Manage Backup), dan **Application Tools** (Manage Menu Admin, Manage Permission, Manage Config, Manage Lookup, Manage Workflow, Manage Session).
2. **Unit Admin (Akses Terbatas Unit)**:
   * **Isolasi Menu**: Menu **Application Tools**, menu **Manage Unit** (HR), menu **Activity Log** (HR), serta menu **Portal Interface Agent Unit** (Portal) **disembunyikan sepenuhnya** dari sidebar untuk mencegah perubahan sistem secara global.
   * Menu yang aktif dan dapat dikelola oleh Unit Admin hanya: `My Profile` (My Role, My Profile), `News` (Manage News), dan `Human Resource` (**hanya** Manage User).

### 3.2 Dashboard Analitik Real-Time (Statistik My Role)
Merupakan halaman dashboard default administrator yang dapat diakses via sub-menu **My Role**. Halaman ini menyajikan visualisasi data operasional real-time yang sangat lengkap:
* **Agent Stats (Statistik Agen)**:
  * Menampilkan total agen operasional terdaftar, agen berstatus aktif (Yes), dan tidak aktif (No).
  * Di bagian bawah, disajikan tabel detail breakdown agen aktif/non-aktif per unit kerja dengan pemisahan peran secara spesifik (Admin, Agent, dan Super Admin).
* **News Stats (Statistik Berita)**:
  * Menyajikan data performa sebaran informasi, mencakup total berita operasional, berita aktif/berlaku (*active*), dan berita kedaluwarsa (*expired*), lengkap dengan detail data masing-masing unit kerja.
* **Login Stats (Tren Masuk 30 Hari)**:
  * Menyajikan angka rata-rata login harian agen dan total akumulasi login unik selama 30 hari terakhir.
  * Dilengkapi grafik tren masuk harian interaktif.
* **Keaktifan Login (User Activity Tabs)**:
  * Menyajikan performa kepatuhan agen dalam mengakses portal melalui 3 tab data dinamis:
    1. **Sering Login (*Frequent*)**: Daftar pengguna operasional dengan aktivitas login tertinggi lengkap dengan jumlah log masuk mereka.
    2. **Jarang Login (*Rare*)**: Daftar pengguna yang memiliki aktivitas login sangat sedikit.
    3. **Tidak Pernah Login (*Never*)**: Daftar pengguna yang akunnya belum pernah digunakan masuk aplikasi sama sekali.
* **Menu Stats (Analisis Akses Menu)**:
  * Menyajikan frekuensi klik agen pada Menu 1 hingga Menu 15. Setiap menu dipetakan ke diagram batang horizontal dinamis untuk memantau menu mana saja yang paling vital bagi operasional harian.

---

## 4. Manajemen Pengguna (Manage User)

Akses menu ini melalui sidebar **Human Resource > Manage User**. Menu ini digunakan untuk mendaftarkan akun baru, menyelaraskan peran unit, dan melakukan pemulihan akses pengguna.

### 4.1 Isolasi Data Pengguna Berbasis Unit
* **Super Admin**: Dapat memantau, mencari, menyaring, menambah, dan memodifikasi data pengguna dari seluruh unit kerja secara global tanpa batasan.
* **Unit Admin**: **Isolasi Data Aktif secara Otomatis**. Unit Admin hanya dapat memantau, mencari, menyaring, dan mengedit data pengguna yang berada di bawah unit kerjanya sendiri. Pengguna dari unit kerja lain tidak akan muncul dalam tabel data Unit Admin.

### 4.2 Menambah Pengguna Baru
1. Klik tombol **Add** (ikon Plus) di toolbar kanan atas tabel manajemen pengguna.
2. Isi formulir pembuatan akun secara lengkap:
   * **NPP**: Nomor Induk Pegawai.
   * **Nama**: Nama lengkap pengguna (misal untuk inisial nama profil).
   * **Username**: Nama akun unik untuk login.
   * **Email**: Email korporat pengguna.
   * **Password**: Kata sandi awal. Jika ingin memaksa pengguna mengubah kata sandi pada login pertama, isi kolom password ini dengan angka **"1"**.
3. **Penyusunan Peran & Unit**: Pada bagian peran, tambahkan kombinasi Peran (misal: Agent, Admin) dan Unit Kerja terkait (misal: Banking, Credit, Emerald). Sistem mendukung pengisian multi-peran dan multi-unit untuk satu pengguna.
4. Klik **Save** untuk mendaftarkan akun baru.

### 4.3 Mengubah Data, Reset Sandi, & Reset Sesi
Pada tabel manajemen user, klik nama pengguna atau pilih tombol **Update / View** di kolom aksi untuk memunculkan modal penyuntingan:
* **Mengubah Data**: Anda dapat mengubah nama, email, status aktif (Yes/No), serta menambah/menghapus peran dan unit kerja yang melekat pada pengguna.
* **Reset Password**: Jika pengguna lupa sandi mereka, klik tombol **Reset Password** di pojok kiri bawah modal edit, masukkan sandi baru (isi **"1"** untuk memaksa penggantian sandi saat pengguna tersebut login), lalu konfirmasi.
* **Reset Session (Penting)**: Jika ada pengguna yang mengalami kendala login terblokir karena masalah koneksi/pemutusan mendadak (sehingga sesi lama di database dianggap masih aktif dan pengguna terhalang pesan *"User Anda Telah Login diBrowser Lain"*), klik tombol **Reset Session** di dalam modal edit. Sistem akan langsung menghapus log sesi lama pengguna secara instan dari database sehingga pengguna dapat login kembali tanpa harus menunggu durasi timeout berakhir.

### 4.4 Menghapus Pengguna
1. Centang kotak pilihan disamping kiri baris tabel pada pengguna yang ingin dihapus. Anda dapat mencentang beberapa pengguna sekaligus untuk penghapusan massal.
2. Klik tombol **Delete** (ikon tempat sampah) di toolbar kanan atas tabel.
3. Konfirmasi penghapusan dengan klik **Yes, Delete**. Sistem secara aman akan menghapus data sesi aktif, data peran, data karyawan, dan data akun pengguna tersebut secara tuntas.

---

## 5. Manajemen Unit Kerja (Manage Unit)

Menu ini **hanya dapat diakses oleh Super Admin** melalui sidebar **Human Resource > Manage Unit**. Digunakan untuk mengelola struktur organisasi BNI Contact Center.

### 5.1 Mengelola Unit
* **Menambah Unit**: Klik tombol **Add**, isi Kode Unit, Nama Unit, Nama Singkat (Short Name), Alamat Unit, Status Aktif (Yes/No), serta tentukan **Parent Unit** jika unit tersebut merupakan sub-unit dari unit kerja yang lebih besar (mendukung relasi unit bertingkat/hirarkis).
* **Menyaring Data**: Gunakan filter dinamis pada bagian atas tabel untuk menyaring unit berdasarkan Kode, Nama, Nama Singkat, Alamat, atau Unit Induk.

### 5.2 Penghapusan Unit yang Aman (Cascading Safe Delete)
Aplikasi PortalCXC dilengkapi dengan sistem integritas database yang aman saat proses penghapusan unit:
* Jika suatu unit kerja dihapus, sistem **secara otomatis akan mengubah field Unit Induk (*parent_id*) pada sub-unit di bawahnya menjadi NULL** agar sub-unit tersebut tidak mengalami eror data terputus (*orphan error*).
* Sistem juga secara otomatis akan menghapus seluruh catatan relasi peran karyawan (*employee roles*) yang melekat pada unit tersebut di database untuk menghindari bentrokan kunci asing (*foreign key conflict*).

---

## 6. Manajemen Tampilan Portal (Portal Interface)

Menu ini **hanya dapat diakses oleh Super Admin** melalui sidebar **Portal > Portal Interface Agent Unit**. Menu ini memfasilitasi penyesuaian visual antarmuka Agent tanpa coding.

### 6.1 Mengganti Latar Belakang (Background Image)
1. Pada area **Change Background Image**, Anda akan melihat pratinjau gambar latar belakang utama agen saat ini.
2. Klik tombol **Select files...** untuk mengunggah gambar latar baru dari komputer Anda (format JPG/PNG direkomendasikan).
3. Gambar yang diunggah akan otomatis disimpan di folder publik sistem `/images` dan langsung diterapkan sebagai latar belakang halaman login serta dashboard utama agen.
4. Klik **Update Settings** di pojok kanan atas untuk menyimpan konfigurasi.

### 6.2 Memetakan Layout Menu (Portal Menu Mapping)
Admin dapat mengatur tata letak presentasi data sub-menu pada dashboard Agent agar sesuai dengan jenis konten yang disajikan:
1. Gulir ke bawah ke bagian **Portal Menu Mapping**. Anda dapat menambah baris menu baru atau mengedit menu pemetaan yang sudah ada.
2. Untuk setiap Menu Group (misal: "Menu 2"), tentukan nama menu, target unit yang diperbolehkan mengakses menu tersebut, status aktif, serta pilih **Layout** tampilan yang diinginkan:
   * **List**: Teks informasi dan ikon berukuran kecil disusun secara vertikal berurutan ke bawah (sangat ideal untuk daftar tautan panduan tertulis).
   * **Horizontal**: Kumpulan ikon kotak berukuran sedang yang disusun berderet menyamping (sangat tepat untuk folder-folder aplikasi pintasan cepat).
   * **Slide Otomatis**: Menyajikan item informasi satu per satu secara bergantian dengan transisi otomatis layaknya slide presentasi.
3. Anda juga dapat menentukan **Parent ID** pada menu jika ingin membuat struktur menu bersarang (*nested/hierarchical menus*).
4. Klik **Update Settings** di pojok kanan atas untuk menyimpan perubahan layout.

---

## 7. Manajemen Berita (Manage News)

Menu ini diakses melalui sidebar **News > Manage News**. Digunakan oleh administrator untuk menyebarkan informasi promo operasional, program kerja, atau pengumuman penting kepada agen.

### 7.1 Membuat Berita Baru
1. Klik tombol **Add News** di pojok kanan atas halaman Manage News.
2. Isi formulir artikel berita secara lengkap:
   * **Title**: Judul berita/promo yang memikat.
   * **Keyword**: Kata kunci untuk memudahkan pencarian cepat oleh agen (pisahkan dengan koma).
   * **Category**: Kategori berita, dikelompokkan berdasarkan unit bisnis (misal: *Banking - Promo*, *Credit - Program*, *Emerald - Pengumuman*, dll.).
   * **Target Unit**: Tentukan unit kerja mana saja yang diizinkan melihat berita ini. Jika berita bersifat umum untuk seluruh agen, pilih **"All Layanan"**. (Catatan: Pilihan target unit untuk *Unit Admin* akan dikunci otomatis sesuai unit kerja dirinya sendiri).
   * **Start Time & End Time**: Tentukan tanggal dan jam publikasi berita mulai ditayangkan hingga masa kedaluwarsa berita berakhir. Jika dikosongkan, berita akan aktif selamanya sejak disimpan.
   * **Headline (Yes/No)**: Pilih **Yes** jika ingin menampilkan berita ini sebagai prioritas tinggi atau masuk dalam antrean korsel/banner promo operasional utama.
   * **Content**: Isi berita lengkap. Tulis artikel menggunakan editor kaya fitur CKEditor 5. Anda dapat memformat teks, mewarnai paragraf, menyisipkan pranala (*hyperlink*), membuat tabel panduan, hingga menyematkan kode HTML khusus (seperti teks berjalan/marquee).

### 7.2 Unggah Berkas & Alur Distribusi Multi-Unit
PortalCXC menyediakan alur distribusi dokumen cerdas saat Anda mengunggah berkas lampiran utama (*primary attachment*) maupun berkas lampiran tambahan (*multiple attachments*):
* **Penyimpanan Primer**: Berkas lampiran pertama yang Anda unggah akan disimpan ke dalam direktori fisik unit pertama yang tercantum dalam target berita: `/storage/news_attachments/{unit_folder}/{filename}`.
* **Duplikasi Otomatis (Physical Copy)**: Secara bersamaan di belakang layar backend, sistem akan **menduplikasi berkas tersebut secara fisik** ke direktori penyimpanan masing-masing unit kerja target lain yang Anda pilih. Langkah ini memastikan setiap folder unit memiliki salinan dokumen fisik secara utuh untuk kebutuhan tata kelola arsip operasional masing-masing unit tanpa risiko tautan berkas terputus.

### 7.3 Mengedit, Memfilter, & Menghapus Berita secara Massal
* **Penyuntingan Berita**: Pilih baris berita pada tabel, lalu klik tombol **Edit** (ikon pensil) di kolom aksi. Sesuaikan konten atau perbarui berkas lampiran lalu klik simpan. Jika berkas lampiran diperbarui, sistem akan menghapus berkas lama dari media penyimpanan fisik terlebih dahulu secara bersih untuk menghemat ruang disk.
* **Penyaringan Berita (Filters)**: Administrator dapat menyaring berita secara dinamis menggunakan bilah filter di baris teratas tabel berdasarkan kriteria spesifik: Judul, Kata Kunci, Unit Layanan, Kategori Berita, Nama Pembuat Berita, atau Tanggal Pembuatan/Pembaruan Konten.
* **Penghapusan Massal (Bulk Delete)**:
  1. Centang kotak pilihan pada beberapa berita yang ingin dihapus sekaligus dari tabel.
  2. Klik tombol **Delete Selected** di bagian atas tabel.
  3. Konfirmasi tindakan. Sistem akan menghapus seluruh data artikel serta secara otomatis membersihkan seluruh berkas lampiran fisik terkait yang tersimpan di direktori penyimpanan disk server secara tuntas.

---

## 8. Monitoring Aktivitas & Log Error Sistem (Activity Log)

Menu ini **hanya dapat diakses oleh Super Admin** melalui navigasi sidebar **Human Resource > Activity Log**. Menu ini memfasilitasi audit trail lengkap terhadap aktivitas admin/pengguna dan diagnosis cerdas kesalahan sistem (baik backend Laravel maupun frontend JavaScript browser).

### 8.1 Tab Log Aktivitas Pengguna
Tab ini menampilkan catatan kronologis aktivitas penting yang terjadi di aplikasi.
* **Informasi yang Dicatat**:
  * **Waktu**: Stempel waktu presisi (YYYY-MM-DD HH:MM:SS) aktivitas terjadi.
  * **Pengguna**: NPP dan nama lengkap admin/pengguna yang melakukan aksi. Jika aksi dilakukan secara otomatis oleh sistem, akan muncul sebagai "System".
  * **Modul**: Modul yang dimodifikasi (`AUTH`, `USER`, `UNIT`, `NEWS`, `PORTAL_SETTING`, `HR`).
  * **Aksi**: Jenis tindakan yang dilakukan (`LOGIN`, `LOGOUT`, `CREATE` (tambah), `UPDATE` (ubah), `DELETE` (hapus), `RESET_PASSWORD`, `UPLOAD` (unggah berkas)).
  * **Deskripsi Aktivitas**: Penjelasan detail tentang objek yang diubah, seperti: *"User admin mendaftarkan user baru dengan username: agent_test"*.
  * **Alamat IP**: Alamat IP perangkat asal koneksi pengguna.
* **Fitur Filter & Navigasi**:
  * **Pencarian Pintar**: Ketik deskripsi aktivitas, NPP, atau nama pengguna pada kolom pencarian untuk menyaring secara instan.
  * **Filter Kategori**: Saring aktivitas berdasarkan kategori modul atau klasifikasi jenis aksi melalui dropdown pilihan.
  * **Tombol Reset**: Klik **Reset Filter** untuk mengembalikan pencarian ke kondisi default.
  * **Pembersihan Log (Clear Logs)**: Klik tombol merah **Bersihkan Log Aktivitas** di kanan atas formulir untuk mengosongkan seluruh riwayat log aktivitas di basis data secara instan (memerlukan konfirmasi Super Admin).

### 8.2 Tab Log Error Sistem
Tab ini menyajikan log deteksi kegagalan sistem terpadu yang dipicu baik oleh backend server maupun runtime browser klien.
* **Informasi yang Dicatat**:
  * **Waktu**: Tanggal dan waktu error terjadi.
  * **Pengguna**: Nama/NPP pengguna aktif saat error terpicu (membantu mereproduksi bug).
  * **Kelas Error / Tipe**: Kelas pengecualian (Exception class) Laravel (misal: `QueryException` untuk database, `ErrorException` untuk skrip, `ValidationException` untuk form input) atau `ClientSideJavaScriptError` untuk error browser.
  * **Pesan Error**: Deskripsi teknis mentah dari pesan kesalahan.
  * **File & Baris**: Nama berkas sumber dan baris baris kode spesifik tempat kegagalan dipicu.
* **Fitur Filter & Aksi**:
  * **Pencarian Pintar**: Cari teks error tertentu, lokasi file, atau saran perbaikan secara langsung.
  * **Filter Tipe Error**: Saring berdasarkan kategori kesalahan teknis tertentu (seperti kesalahan validasi, kesalahan token CSRF, kesalahan database, dll).
  * **Pembersihan Log**: Klik **Bersihkan Log Error** di kanan atas untuk mengosongkan seluruh riwayat log error dari database secara bersih (memerlukan konfirmasi Super Admin).

### 8.3 Panel Analisis & Solusi Error Premium (Diagnostic Modal)
Pada setiap baris log error, klik tombol merah **Analisis & Solusi** (ikon stetoskop) untuk meluncurkan panel diagnostik premium interaktif:
* **Analisis Visual Premium**: Tampilan diagnostik berbasis modal gelap (charcoal dark theme) yang memukau dan diatur secara terstruktur.
* **Penyebab (Amber/Kuning Box)**: Bagian **Mengapa Ini Terjadi? (Penyebab)** menerjemahkan bahasa teknis sistem yang rumit menjadi penjelasan bahasa Indonesia yang sederhana dan mudah dipahami, menunjukkan di mana letak ketidaksesuaian input atau kesalahan konfigurasi.
* **Solusi Perbaikan (Emerald/Hijau Box)**: Bagian **Saran Tindakan Perbaikan (Solusi)** memberikan daftar periksa (*checklist*) tindakan terukur dan instruksi pemecahan masalah konkret bagi administrator untuk memecahkan masalah tersebut saat itu juga.
* **Stack Trace Terminal (Charcoal Code Trace)**: Menampilkan urutan pemanggilan fungsi internal yang memicu error secara terperinci. Super Admin dapat menekan tombol **Salin Stack Trace** untuk menyalin data teknis tersebut ke papan klip komputer (*clipboard*) agar dapat dikirim ke tim pengembang jika dibutuhkan.
* **Meta Info Klien**: Menampilkan HTTP request method (GET/POST/CLIENT), IP address asal, dan User-Agent browser klien untuk investigasi mendalam.

---

## 9. Manajemen Backup Database (Database Backup)

Menu ini **hanya dapat diakses oleh Super Admin** melalui sidebar **Database > Manage Backup**. Fitur ini memastikan keamanan data operasional dari kerusakan sistem atau kegagalan perangkat keras (*disaster recovery*).

### 9.1 Penjadwalan Backup Otomatis
1. Super Admin dapat mengatur kapan proses pencadangan basis data (*database dump*) akan berjalan secara otomatis.
2. Di halaman **Backup Schedule**, pilih jenis frekuensi (*Daily, Weekly, Monthly, Custom*).
3. Untuk pilihan *Custom*, Super Admin bisa menentukan tanggal dan waktu spesifik untuk eksekusi pencadangan melalui antarmuka kalender yang intuitif.
4. Jadwal akan dieksekusi secara asinkron di belakang layar (*background task*) oleh sistem sehingga tidak akan mengganggu atau memperlambat kinerja aplikasi bagi agen yang sedang login.

### 9.2 Riwayat Backup dan Pemulihan
1. Pada tab **Backup History**, Super Admin dapat melihat riwayat semua upaya proses pencadangan yang pernah dijalankan oleh sistem.
2. Setiap riwayat mencantumkan status keberhasilan (*Success/Failed*), tanggal/waktu eksekusi, serta tautan langsung untuk mengunduh (*Download*) file hasil backup (biasanya berekstensi `.backup` atau `.sql`).
3. File unduhan tersebut sewaktu-waktu dapat direstorasi ulang (*restore*) ke server menggunakan *tool* administrasi PostgreSQL bawaan jika terjadi kerusakan data mayor.

---
*Dokumen panduan operasional ini diperbarui, diuji validitasnya, dan diselaraskan secara akurat dengan alur sistem PortalCXC per 9 Juni 2026.*
