@echo off
echo.
echo =========================================
echo    Creating Python virtual environment
echo =========================================
echo.

cd /D "%~dp0"

python -m venv "%CD%\env"

call "%CD%\env\Scripts\activate.bat"

python -m pip install -U pip
pip install -r "%CD%\requirements.txt"
