@echo off
cd /d "%~dp0"

php artisan backup:run >> storage\logs\backup.log 2>&1
