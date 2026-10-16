# Flow Chart PortalCXC

Salin seluruh kode Mermaid di dalam blok kode di bawah ini, lalu tempel (paste) ke editor [Mermaid.ai](https://mermaid.ai/app/projects/2c6b3170-61a6-4858-abc3-e56b91bdbd4e/diagrams/e453799f-85d9-4aba-9552-b83336f5469b/version/v0.1/edit?fromOnboarding=true) untuk menghasilkan gambar flow chart.

```mermaid
flowchart TD
    Start([Mulai Akses Aplikasi]) --> Login[Akses Halaman Login PortalCXC]
    Login --> InputCreds[Masukkan Username & Password]
    
    InputCreds --> CheckSession{Ada Sesi Aktif\ndi Browser Lain?}
    
    CheckSession -- "Ya (< 30 Menit)" --> BlockLogin[Tolak Login:\nUser Telah Login di Browser Lain]
    BlockLogin --> Login
    
    CheckSession -- "Tidak" --> ValidCreds{Kredensial\nValid?}
    
    ValidCreds -- "Tidak" --> ErrorLogin[Tampilkan Peringatan Gagal]
    ErrorLogin --> Login
    
    ValidCreds -- "Ya" --> CheckPassword{Apakah Sandi\nMasih 1?}
    
    CheckPassword -- "Ya" --> ForceReset[Diarahkan ke Halaman Ubah Sandi\nForce Reset]
    ForceReset --> SavePassword[Simpan Sandi Baru]
    SavePassword --> CheckRole
    
    CheckPassword -- "Tidak" --> CheckRole{Pengecekan Peran\nRole Base}
    
    CheckRole -- "Super Admin" --> RoleSA[Hak Akses Penuh:\nDashboard Analitik, Log Aktivitas,\nManage Menu, Manage Seluruh Unit]
    CheckRole -- "Unit Admin" --> RoleUA[Hak Akses Unit:\nManajemen User & Berita\nTerbatas pada Unit Sendiri]
    CheckRole -- "Agent" --> RoleAgent[Hak Akses Operasional:\nLihat Banner Promo, Akses Menu 1-15,\nLihat Profil]
    
    RoleSA & RoleUA & RoleAgent --> IdleCheck
    
    IdleCheck{Tidak Ada Aktivitas\nLebih dari 8 Jam?}
    
    IdleCheck -- "Ya" --> ForceLogout[Sesi Berakhir Otomatis\nForce Logout]
    ForceLogout --> Login
    
    IdleCheck -- "Tidak (Ada Aktivitas)" --> ExtendSession[Sesi Diperpanjang Otomatis]
    ExtendSession --> DashboardState
    
    DashboardState((Aktivitas\nBerjalan)) -.-> IdleCheck
    
    RoleSA & RoleUA & RoleAgent --> LogoutAction[Klik Logout Manual]
    LogoutAction --> EndLogout([Keluar & Hapus Sesi])
    EndLogout --> Login
```
