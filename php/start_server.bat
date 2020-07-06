@echo off
echo.
echo ==========================
echo    Press Ctrl+C to exit
echo ==========================
echo.
"C:\php\php.exe" -S 127.0.0.1:8000 -t "%CD%"
