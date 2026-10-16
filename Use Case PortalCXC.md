# Use Case Diagram PortalCXC

Salin seluruh kode PlantUML di dalam blok kode di bawah ini, lalu tempel (paste) ke [Editor PlantUML Web](https://www.plantuml.com/plantuml/uml/SyfFKj2rKt3CoKnELR1Io4ZDoSa700003) untuk menghasilkan gambar diagramnya.

```plantuml
@startuml
!theme cerulean-outline
left to right direction
skinparam packageStyle rectangle

actor "Super Admin" as SA
actor "Unit Admin" as UA
actor "Agent\n(User Operasional)" as AG

rectangle "Sistem PortalCXC" {
    
    package "Autentikasi & Akun" {
        usecase "Login Aplikasi" as UC_Login
        usecase "Logout Aplikasi" as UC_Logout
        usecase "Ubah Sandi Pertama Kali\n(Force Reset)" as UC_UbahSandi
    }

    package "Fitur Operasional (Agent)" {
        usecase "Melihat Berita & Promo Unit" as UC_BeritaAgent
        usecase "Mengakses Menu Cepat\n(Menu 1-15)" as UC_MenuAgent
        usecase "Melihat Profil Diri" as UC_Profil
    }
    
    package "Manajemen Terbatas (Unit Admin)" {
        usecase "Manajemen User\n(Sesuai Unit Sendiri)" as UC_UserUnit
        usecase "Manajemen Berita & Lampiran\n(Sesuai Unit Sendiri)" as UC_BeritaUnit
    }

    package "Manajemen Terpusat (Super Admin)" {
        usecase "Manajemen Seluruh User\n& Berita Lintas Unit" as UC_MasterData
        usecase "Manajemen Data Unit (CRUD)" as UC_ManageUnit
        usecase "Melihat Dashboard Analitik\n(Statistik & Tracker Klik)" as UC_Dashboard
        usecase "Application Tools\n(Menu, Session, Permission, dll)" as UC_AppTools
        usecase "Memantau Activity Log\n& System Error Log" as UC_Log
        usecase "Pengaturan Antarmuka Portal\n(Portal Setting)" as UC_Portal
        usecase "Manajemen Jadwal Backup DB" as UC_Backup
    }
}

' Relasi Agent
AG --> UC_Login
AG --> UC_Logout
AG --> UC_UbahSandi
AG --> UC_BeritaAgent
AG --> UC_MenuAgent
AG --> UC_Profil

' Relasi Unit Admin
UA --> UC_Login
UA --> UC_Logout
UA --> UC_UbahSandi
UA --> UC_UserUnit
UA --> UC_BeritaUnit

' Relasi Super Admin
SA --> UC_Login
SA --> UC_Logout
SA --> UC_UbahSandi
SA --> UC_MasterData
SA --> UC_ManageUnit
SA --> UC_Dashboard
SA --> UC_AppTools
SA --> UC_Log
SA --> UC_Portal
SA --> UC_Backup

@enduml
```
