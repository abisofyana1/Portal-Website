# Skenario Pengujian Sistem (Test Scenarios) - PortalCXC

Dokumen ini berisi langkah-langkah pengujian untuk memverifikasi fungsionalitas aplikasi PortalCXC berdasarkan tiga peran (role) utama: **Super Admin**, **Admin**, dan **Agent**.

---

## 1. Skenario Pengujian Role: Agent

### 1.1 Skenario Login & Keamanan Sesi
| No | Fitur | Langkah Pengujian | Hasil yang Diharapkan |
|----|-------|-------------------|-----------------------|
| 1 | Normal Login | Masukkan username dan password valid di halaman login, klik "Sign In". | Agent berhasil masuk dan diarahkan ke Dashboard Agent sesuai unitnya. |
| 2 | Force Password Change | Admin mengatur password agent menjadi "1". Agent login dengan password "1". | Agent dialihkan ke halaman utama dengan form wajib ubah password dan fitur lain diblokir hingga password diubah. |
| 3 | Single Session Lock | Login dengan akun Agent A di Browser Chrome. Buka Browser Firefox/Edge, login kembali dengan akun Agent A. | Di Browser Firefox: Muncul peringatan merah *"User Anda Telah Login di Browser Lain, Silahkan Logout Terlebih Dahulu!!"*. Akses ditolak. |
| 4 | Session Timeout | Biarkan halaman dashboard agent terbuka tanpa aktivitas fisik (mouse/keyboard) selama 8 jam. | Sesi agent otomatis berakhir (*force logout*) dan layar dialihkan ke halaman login dengan notifikasi kuning. |

### 1.2 Navigasi & Pencarian Berita
| No | Fitur | Langkah Pengujian | Hasil yang Diharapkan |
|----|-------|-------------------|-----------------------|
| 1 | Live Search | Ketikkan judul/keyword promo di kolom pencarian bar navigasi atas. | Dropdown hasil pencarian muncul seketika secara real-time. |
| 2 | Buka Berita | Klik salah satu hasil pencarian atau judul berita pada News Carousel/Banner. | Modal isi konten berita muncul di atas halaman. Jika di klik tengah (Middle Click), akan membuka tab baru. |
| 3 | Akses Menu | Klik ikon menu/folder dari Menu 1 hingga 15. | Membuka link terkait atau sub-menu. Jika menu di bagian bawah, layout grid tetap sejajar (*h-48 lock*) tidak berantakan. |
| 4 | News Carousel | Arahkan kursor ke area News Banner. | Tombol panah (< dan >) muncul. Slider berita berputar otomatis, atau bisa diklik manual. |

---

## 2. Skenario Pengujian Role: Admin Unit

### 2.1 Restriksi Akses (Menu Isolation)
| No | Fitur | Langkah Pengujian | Hasil yang Diharapkan |
|----|-------|-------------------|-----------------------|
| 1 | Limitasi Sidebar | Login sebagai Admin Unit. Periksa menu yang tersedia di sidebar sebelah kiri. | Menu **Application Tools**, **Manage Unit**, **Activity Log**, **Portal Interface**, dan **Database Backup** *TIDAK ADA* atau tersembunyi. |

### 2.2 Isolasi Data Unit
| No | Fitur | Langkah Pengujian | Hasil yang Diharapkan |
|----|-------|-------------------|-----------------------|
| 1 | Isolasi User | Buka menu **Human Resource > Manage User**. Cari pengguna yang bukan dari unit kerja Admin tersebut. | Pengguna dari unit lain tidak muncul di tabel data. Admin hanya dapat melihat dan mengedit pengguna dari unitnya sendiri. |
| 2 | Isolasi News | Buka menu **News > Manage News** dan klik **Add News**. | Pilihan *Target Unit* otomatis terkunci pada unit kerja Admin tersebut, tidak bisa memilih *All Layanan* atau unit lain. |

### 2.3 Manajemen User Terbatas
| No | Fitur | Langkah Pengujian | Hasil yang Diharapkan |
|----|-------|-------------------|-----------------------|
| 1 | Reset Session | Pada tabel Manage User, klik edit pada agen yang terkunci sesinya, tekan **Reset Session**. | Sesi agen tersebut terhapus, dan agen bisa login kembali. |
| 2 | Tambah/Edit Agent | Tambahkan akun agent baru untuk unit yang sama. Edit status aktifnya. | Data agen berhasil disimpan/diubah. |

---

## 3. Skenario Pengujian Role: Super Admin

### 3.1 Manajemen Portal & Berita Global
| No | Fitur | Langkah Pengujian | Hasil yang Diharapkan |
|----|-------|-------------------|-----------------------|
| 1 | Ganti Background | Buka **Portal > Portal Interface Agent Unit**. Unggah gambar background baru, klik Update. | Background di halaman login dan dashboard Agent langsung berubah. |
| 2 | News Distribusi Masif | Buka **Manage News**, buat berita, pasang file lampiran, dan set Target Unit ke beberapa unit berbeda (Multi-unit). | Berita berhasil tersimpan, file lampiran berhasil terduplikasi secara fisik ke direktori masing-masing unit target. |

### 3.2 Manajemen Unit & Hierarki
| No | Fitur | Langkah Pengujian | Hasil yang Diharapkan |
|----|-------|-------------------|-----------------------|
| 1 | Cascading Safe Delete | Buka **Human Resource > Manage Unit**. Hapus satu unit (*parent*) yang memiliki sub-unit di bawahnya. | Unit terhapus. *Parent ID* pada sub-unit berubah menjadi NULL (tidak terjadi error *foreign key*), dan relasi *role* terkait terhapus dengan aman. |

### 3.3 Activity Log & System Error
| No | Fitur | Langkah Pengujian | Hasil yang Diharapkan |
|----|-------|-------------------|-----------------------|
| 1 | Pantau Activity Log | Buka **Human Resource > Activity Log**, tab *Log Aktivitas Pengguna*. Cari nama user/aksi tertentu. | Log rekaman aktivitas user muncul sesuai filter waktu, aksi, dan IP. |
| 2 | Diagnostik Error | Pindah ke tab *Log Error Sistem*. Klik tombol stetoskop merah (Analisis & Solusi) pada salah satu record error. | Muncul *Diagnostic Modal* gelap (*Dark Theme*) berisi penyebab (Amber box), solusi bahasa Indonesia (Emerald box), dan stack trace. |

### 3.4 Manajemen Backup Database
| No | Fitur | Langkah Pengujian | Hasil yang Diharapkan |
|----|-------|-------------------|-----------------------|
| 1 | Set Jadwal Backup | Buka **Database > Manage Backup**. Pada tab *Backup Schedule*, atur jadwal *Custom* ke waktu tertentu, klik Simpan. | Jadwal tersimpan di tabel `backup_schedules`. Sistem akan menjalankan backup asinkron (*background task*) pada waktu yang ditentukan. |
| 2 | Cek Riwayat Backup | Pindah ke tab *Backup History*. | Terdapat daftar riwayat eksekusi backup lengkap dengan status (Success/Failed) dan tombol unduh (*Download*) file `.backup` atau `.sql`. |
| 3 | Unduh File Backup | Klik tombol *Download* pada riwayat backup yang berhasil. | File backup terunduh sempurna tanpa korup. |

---
*Dokumen skenario pengujian ini disusun untuk memastikan seluruh komponen aplikasi PortalCXC berfungsi sesuai spesifikasi standar.*
