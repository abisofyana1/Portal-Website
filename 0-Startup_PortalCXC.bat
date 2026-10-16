@echo off
setlocal enabledelayedexpansion
echo ==========================================
echo    Starting PortalCXC Application
echo ==========================================

echo [1/2] Building frontend assets (npm run build)...
call npm run build

echo.
echo ==========================================
echo    Configuration (Multi-Interface)
echo ==========================================

:: Mengambil IP IPv4 lokal untuk tampilan informasi
set "LAN_IP=Unknown"
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4 Address" /c:"Alamat IPv4"') do (
    set "temp_ip=%%a"
    :: Menghapus spasi di awal
    set "LAN_IP=!temp_ip:~1!"
    goto :found_ip
)
:found_ip

:: Menggunakan 0.0.0.0 agar bisa diakses dari localhost DAN LAN
set IP_ADDR=0.0.0.0
set PORT=9000

echo [2/2] Starting Laravel server...
echo       Aplikasi dapat diakses melalui:
echo       - Lokal: http://127.0.0.1:%PORT%
echo       - LAN:   http://%LAN_IP%:%PORT%
echo.

:: Menjalankan server Laravel
php artisan serve --host=%IP_ADDR% --port=%PORT%

pause