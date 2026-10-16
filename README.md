# PortalCXC - Contact Center Knowledge Portal

Aplikasi Web Portal Knowledge Base & Management Dashboard Contact Center berbasis **Laravel 12** dan **Vue.js 3**.

Tersedia **Standalone Live Demo (100% Client-Side)** yang dapat dijalankan langsung di browser via **GitHub Pages** tanpa memerlukan server PHP atau database.

---

## 🌐 Live Demo di GitHub Pages

Live demo aplikasi ini berjalan langsung di browser via GitHub Pages:
> **URL Demo:** `https://abisofyana1.github.io/Portal-Website/`

### 🔑 Akun Pengujian (Testing Credentials)

Pada halaman Login, tersedia tombol **1-Click Quick Demo** atau Anda dapat memasukkan kredensial berikut:

| No | Role | Username | Password | Deskripsi / Tampilan yang Ditampilkan |
|---|---|---|---|---|
| 1 | **Super Admin** | `superadmin_demo` | `password123` | Akses penuh ke seluruh menu: Manage User, Manage Unit, Activity Log, Error Log, Portal Interface, dan semua Knowledge Unit. |
| 2 | **Admin Unit** | `admin_banking` | `password123` | Akses level Admin terisolasi khusus **Unit Banking** (kelola konten, user, dan dashboard unit bersangkutan). |
| 3 | **Agent Unit** | `agent_banking` | `password123` | Tampilan layout antarmuka **Agent Unit Banking** untuk membaca news, banner, knowledge portal, dan carousel promo. |

---

## ⚙️ Cara Mengaktifkan GitHub Pages (Sekali Saja di GitHub)

Setelah Anda melakukan `commit` dan `push` kode ini ke repository GitHub:

1. Buka repository Anda di GitHub: **`abisofyana1/Portal-Website`**.
2. Masuk ke menu **Settings** > **Pages** (di sidebar kiri).
3. Pada bagian **Build and deployment**:
   - **Source**: Pilih **Deploy from a branch**.
   - **Branch**: Pilih branch **`gh-pages`** dan folder **`/(root)`**.
   - Klik **Save**.
4. GitHub Actions akan secara otomatis menjalankan workflow `.github/workflows/deploy-pages.yml` setiap kali Anda melakukan push ke branch `master`.
5. Tunggu sekitar 1–2 menit, Live Demo Anda sudah bisa diakses publik secara online di:
   `https://abisofyana1.github.io/Portal-Website/`

---

## 💻 Menjalankan Demo Secara Lokal Tanpa Database

Anda juga dapat membuka aplikasi secara lokal langsung tanpa database:

1. Jalankan build:
   ```bash
   npm install
   npm run build
   ```
2. Buka file `public/index.html` menggunakan live-server / extension browser, atau:
   ```bash
   npx serve public
   ```
   Lalu buka URL yang muncul di browser dan login dengan salah satu akun di atas.

---

## 📋 Fitur Utama
- **Multi-Role & Unit Isolation**: Isolasi data antar unit/divisi (Banking, Credit, Emerald, Merchant, SAT, TnR, dll).
- **Dual Layout System**: Layout Admin Dashboard dan Layout Agent Portal.
- **Knowledge Base & Interactive News**: Running text, headline banner, promo slider, serta viewer lampiran dokumen.
- **System Monitoring**: Real-time Error Log tracking, Activity Logs, dan simulasi Security Scanner.
