@echo off
cd /d "%~dp0"

php -m | findstr /i "pdo_mysql" >nul
if errorlevel 1 (
    echo PHP extension pdo_mysql is not enabled.
    echo Run: php --ini
    echo Open the loaded php.ini file and enable: extension=pdo_mysql
    echo Then restart this script.
    pause
    exit /b 1
)

php -S 127.0.0.1:8000 -t public
