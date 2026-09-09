<#
.SYNOPSIS
    Sincroniza os bancos de dados da VPS (produção/remoto) para o MySQL local (Laragon).
.DESCRIPTION
    Este script conecta-se à VPS remota, extrai o dump dos bancos 'advdf2g' (tenant)
    e 'mothership_db' (controle) e restaura-os no MySQL local (127.0.0.1).
    Isso permite que o ambiente local execute com latência zero (ultra-rápido).
#>

[CmdletBinding()]
param (
    [string]$VpsHost = "",
    [switch]$TenantOnly,
    [switch]$MothershipOnly
)

$ErrorActionPreference = "Stop"
$projectRoot = Split-Path -Parent $PSScriptRoot
$envFile = Join-Path $projectRoot ".env"

if (-not (Test-Path $envFile)) {
    Write-Error "Arquivo .env não encontrado em $projectRoot"
    exit 1
}

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "   SINCRONIZAÇÃO DE BANCO DE DADOS: VPS -> LOCAL (LARAGON) " -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

# 1. Carregar variáveis do .env
$envLines = Get-Content $envFile
function Get-EnvVar($key, $default = "") {
    $match = $envLines | Select-String "^${key}=(.*)"
    if ($match) {
        return $match.Matches[0].Groups[1].Value.Trim().Trim('"').Trim("'")
    }
    return $default
}

$dbUser = Get-EnvVar "DB_USERNAME" "krayin_admin"
$dbPass = Get-EnvVar "DB_PASSWORD" ""
$dbTenant = Get-EnvVar "DB_DATABASE" "advdf2g"
$dbMothership = Get-EnvVar "DB_MOTHERSHIP_DATABASE" "mothership_db"

if ([string]::IsNullOrWhiteSpace($VpsHost)) {
    $VpsHost = Get-EnvVar "VPS_DB_HOST" "75.119.128.13"
}

# 2. Localizar executáveis do MySQL no Laragon
$mysqldump = Get-ChildItem -Path "C:\laragon\bin\mysql\*\bin\mysqldump.exe" -ErrorAction SilentlyContinue | Select-Object -ExpandProperty FullName -First 1
$mysql = Get-ChildItem -Path "C:\laragon\bin\mysql\*\bin\mysql.exe" -ErrorAction SilentlyContinue | Select-Object -ExpandProperty FullName -First 1

if (-not $mysqldump -or -not $mysql) {
    # Fallback para PATH
    $mysqldump = (Get-Command mysqldump -ErrorAction SilentlyContinue).Source
    $mysql = (Get-Command mysql -ErrorAction SilentlyContinue).Source
}

if (-not $mysqldump -or -not $mysql) {
    Write-Error "Não foi possível localizar mysqldump.exe ou mysql.exe. Verifique a instalação do Laragon."
    exit 1
}

Write-Host "[+] VPS Remota: $VpsHost" -ForegroundColor Yellow
Write-Host "[+] Local MySQL: 127.0.0.1:3306" -ForegroundColor Yellow
Write-Host "[+] Usuário: $dbUser" -ForegroundColor Yellow

$tempDir = [System.IO.Path]::GetTempPath()

# Sincronizar Banco do Tenant
if (-not $MothershipOnly) {
    Write-Host "`n[1/2] Baixando e atualizando banco do Tenant ($dbTenant)..." -ForegroundColor Green
    $tenantDump = Join-Path $tempDir "${dbTenant}_sync.sql"
    
    & $mysqldump -h $VpsHost -u $dbUser "-p$dbPass" --column-statistics=0 --single-transaction --quick --routines --triggers $dbTenant --result-file=$tenantDump
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "      Importando $dbTenant no MySQL local..." -ForegroundColor Gray
        & $mysql -h 127.0.0.1 -u $dbUser "-p$dbPass" --init-command="SET FOREIGN_KEY_CHECKS=0;" $dbTenant -e "source $tenantDump"
        Remove-Item $tenantDump -Force -ErrorAction SilentlyContinue
        Write-Host "      -> Banco $dbTenant sincronizado com sucesso!" -ForegroundColor Green
    } else {
        Write-Error "Falha ao exportar $dbTenant da VPS."
    }
}

# Sincronizar Banco do Mothership
if (-not $TenantOnly) {
    Write-Host "`n[2/2] Baixando e atualizando banco Mothership ($dbMothership)..." -ForegroundColor Green
    $motherDump = Join-Path $tempDir "${dbMothership}_sync.sql"
    
    & $mysqldump -h $VpsHost -u $dbUser "-p$dbPass" --column-statistics=0 --single-transaction --quick $dbMothership --result-file=$motherDump
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "      Importando $dbMothership no MySQL local..." -ForegroundColor Gray
        & $mysql -h 127.0.0.1 -u $dbUser "-p$dbPass" --init-command="SET FOREIGN_KEY_CHECKS=0;" $dbMothership -e "source $motherDump"
        Remove-Item $motherDump -Force -ErrorAction SilentlyContinue
        Write-Host "      -> Banco $dbMothership sincronizado com sucesso!" -ForegroundColor Green
    } else {
        Write-Error "Falha ao exportar $dbMothership da VPS."
    }
}

# Limpar cache do Laravel
Write-Host "`n[+] Limpando caches da aplicação..." -ForegroundColor Cyan
Push-Location $projectRoot
try {
    php artisan cache:clear | Out-Null
    php artisan config:clear | Out-Null
} catch {}
Pop-Location

Write-Host "`n==========================================================" -ForegroundColor Cyan
Write-Host "   SINCRONIZAÇÃO CONCLUÍDA! O AMBIENTE ESTÁ PRONTO E RÁPIDO " -ForegroundColor Green
Write-Host "==========================================================" -ForegroundColor Cyan
