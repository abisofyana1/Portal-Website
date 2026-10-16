@echo off
title PortalCXC - Laravel Auto Backup Scheduler
echo ===================================================
echo PortalCXC Auto Backup Scheduler is Running...
echo ===================================================
echo.
echo JANGAN TUTUP JENDELA INI!
echo Jendela ini harus tetap terbuka agar fitur Countdown dan Backup Otomatis
echo pada aplikasi PortalCXC dapat berjalan sesuai dengan jadwal yang telah ditentukan.
echo.
echo Menjalankan proses penjadwalan Laravel...
echo.

cd /d "%~dp0"
php artisan schedule:work

pause
