@echo off
echo.
echo ==========================
echo    Press Ctrl+C to exit
echo ==========================
echo.

cd /D "%~dp0"

"C:\php\php.exe" -S 127.0.0.1:8000 -t "%CD%"
