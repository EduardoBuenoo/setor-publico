@echo off
echo Iniciando servidores do Setor Publico...

echo Iniciando Backend (Django)...
start "Backend (Django)" cmd /k "cd backend && .\venv\Scripts\activate && python manage.py runserver"

echo Iniciando Frontend (PHP)...
start "Frontend (PHP)" cmd /k "cd frontend && C:\xampp\php\php.exe -S localhost:8080"

echo Aguardando 3 segundos para os servidores iniciarem...
timeout /t 3 /nobreak > NUL

echo Abrindo o site no navegador...
start http://localhost:8080/index.php

echo Tudo pronto! Pode fechar esta telinha, mas deixe as telas preta do Backend e do Frontend abertas.
pause
