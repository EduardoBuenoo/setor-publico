@echo off
title SIGDEI - Inicializador

echo ================================
echo        INICIANDO SIGDEI
echo ================================
echo.

REM Inicia o backend Django
start "SIGDEI - BACKEND" cmd /k "cd /d "%~dp0backend" && ".venv\Scripts\python.exe" manage.py runserver"

REM Aguarda o Django iniciar
timeout /t 3 /nobreak >nul

REM Inicia o servidor do frontend
start "SIGDEI - FRONTEND" cmd /k "cd /d "%~dp0frontend" && "..\backend\.venv\Scripts\python.exe" -m http.server 5500"

REM Aguarda o frontend iniciar
timeout /t 2 /nobreak >nul

REM Abre a tela de login
start "" "http://127.0.0.1:5500/index.html"

exit