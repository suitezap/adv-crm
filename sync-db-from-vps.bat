123@echo off
chcp 65001 > nul
echo ==========================================================
echo    SINCRONIZANDO DADOS DA PRODUCAO (VPS) PARA O LOCAL
echo ==========================================================
powershell -ExecutionPolicy Bypass -File "%~dp0scripts\sync-db-from-vps.ps1"
echo.
pause
