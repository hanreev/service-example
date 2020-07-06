@echo off
echo.
echo ==========================
echo    Press Ctrl+C to exit
echo ==========================
echo.

cd /D "%~dp0"

call "%CD%\env\Scripts\activate.bat"

set FLASK_ENV=development
set FLASK_APP=application.py
flask run -p 9000
