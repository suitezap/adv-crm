#!/usr/bin/env pwsh
# =============================================================================
#  zsincroniza.ps1
#  Sincronização de leads e persons entre local e VPS (tenant advdf2g)
#
#  Uso:   .\zsincroniza.ps1
# =============================================================================

Set-StrictMode -Off
$ErrorActionPreference = "Stop"
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

# == Configurações =============================================================
$LOCAL_HOST   = "127.0.0.1"
$LOCAL_PORT   = "3306"
$LOCAL_DB     = "advdf2g"
$LOCAL_USER   = "krayin_admin"
$LOCAL_PASS   = "Acesso2k2xNaoPermitido"

$REMOTE_HOST  = "75.119.128.13"
$REMOTE_PORT  = "3306"
$REMOTE_DB    = "advdf2g"
$REMOTE_USER  = "krayin_admin"
$REMOTE_PASS  = "Acesso2k2xNaoPermitido"

# Tabelas e ordem de exclusão respeitando dependências de chave estrangeira
$ALL_DELETE = @(
    "lead_triagem", "lead_tags", "lead_quotes", "lead_products", "lead_activities", "leads",
    "person_activities", "person_tags", "law_person_details", "emails", "persons"
)

$TABLES_SYNC = @(
    "persons", "emails", "law_person_details",
    "person_activities", "person_tags",
    "leads", "lead_activities", "lead_products", "lead_quotes", "lead_tags", "lead_triagem"
)

# == Detecção Automática dos Binários MySQL / mysqldump =========================
function Find-Binary($name) {
    $found = Get-Command $name -ErrorAction SilentlyContinue | Select-Object -First 1 -ExpandProperty Source
    if ($found -and (Test-Path $found)) { return $found }

    # Tenta caminhos comuns (Laragon, XAMPP, Program Files)
    $patterns = @(
        "C:\laragon\bin\mysql\*\bin\$name.exe",
        "C:\xampp\mysql\bin\$name.exe",
        "C:\Program Files\MySQL\*\bin\$name.exe",
        "C:\Program Files\MariaDB*\bin\$name.exe"
    )
    foreach ($pat in $patterns) {
        $c = Get-ChildItem $pat -ErrorAction SilentlyContinue | Select-Object -First 1 -ExpandProperty FullName
        if ($c -and (Test-Path $c)) { return $c }
    }
    return $null
}

$MYSQL_BIN     = Find-Binary "mysql"
$MYSQLDUMP_BIN = Find-Binary "mysqldump"

# == Helpers de Mensagens =======================================================
function Write-Header($text) {
    Write-Host ""
    Write-Host ("=" * 64) -ForegroundColor DarkCyan
    Write-Host "  $text" -ForegroundColor Cyan
    Write-Host ("=" * 64) -ForegroundColor DarkCyan
}

function Write-Section($text) {
    Write-Host ""
    Write-Host ("-" * 64) -ForegroundColor DarkGray
    Write-Host "  $text" -ForegroundColor White
    Write-Host ("-" * 64) -ForegroundColor DarkGray
}

function Write-Ok($text)   { Write-Host "  [OK] $text" -ForegroundColor Green }
function Write-Warn($text) { Write-Host "  [!!] $text" -ForegroundColor Yellow }
function Write-Err($text)  { Write-Host "  [XX] $text" -ForegroundColor Red }
function Write-Info($text) { Write-Host "       $text" -ForegroundColor Gray }

function Ask-YesNo($prompt, $defaultYes = $false) {
    $hint = if ($defaultYes) { "[S/n]" } else { "[s/N]" }
    $ans = Read-Host "  $prompt $hint"
    $ans = $ans.Trim()
    if ([string]::IsNullOrWhiteSpace($ans)) {
        return $defaultYes
    }
    return ($ans -match "^[sSyY]$")
}

# == Operações de Banco de Dados ================================================
function Run-DbQuery($dbHost, $dbPort, $dbUser, $dbPass, $database, $sql) {
    if (-not $script:MYSQL_BIN) {
        Write-Err "Binário 'mysql' não encontrado no sistema."
        return $null
    }
    $res = & $script:MYSQL_BIN "--host=$dbHost" "--port=$dbPort" "--user=$dbUser" "--password=$dbPass" $database -e $sql 2>&1
    return $res
}

function Get-RowCount($dbHost, $dbPort, $dbUser, $dbPass, $database, $tableName) {
    if (-not $script:MYSQL_BIN) { return "?" }
    $res = & $script:MYSQL_BIN "--host=$dbHost" "--port=$dbPort" "--user=$dbUser" "--password=$dbPass" $database --skip-column-names -e "SELECT COUNT(*) FROM ``$tableName``;" 2>&1
    if ($LASTEXITCODE -ne 0) { return "?" }
    # Pega apenas a última linha com o número
    $lines = $res | Where-Object { $_ -match '^\d+$' }
    if ($lines) {
        return ($lines | Select-Object -Last 1).Trim()
    }
    return "?"
}

function Show-StatusPreview {
    Write-Section "STATUS ATUAL DOS DADOS (leads e persons)"
    $locLeads   = Get-RowCount $LOCAL_HOST $LOCAL_PORT $LOCAL_USER $LOCAL_PASS $LOCAL_DB "leads"
    $locPersons = Get-RowCount $LOCAL_HOST $LOCAL_PORT $LOCAL_USER $LOCAL_PASS $LOCAL_DB "persons"
    $remLeads   = Get-RowCount $REMOTE_HOST $REMOTE_PORT $REMOTE_USER $REMOTE_PASS $REMOTE_DB "leads"
    $remPersons = Get-RowCount $REMOTE_HOST $REMOTE_PORT $REMOTE_USER $REMOTE_PASS $REMOTE_DB "persons"

    Write-Host ("    LOCAL  (advdf2g @ {0}):  leads = {1,-5} | persons = {2,-5}" -f $LOCAL_HOST, $locLeads, $locPersons) -ForegroundColor Green
    Write-Host ("    REMOTO (advdf2g @ {0}):  leads = {1,-5} | persons = {2,-5}" -f $REMOTE_HOST, $remLeads, $remPersons) -ForegroundColor Yellow
}

function Clear-DatabaseTables($dbHost, $dbPort, $dbUser, $dbPass, $database, $tables, $label) {
    Write-Info "Limpando tabelas em [$label] com chaves estrangeiras desativadas..."
    $statements = @("SET FOREIGN_KEY_CHECKS=0;")
    foreach ($t in $tables) {
        $statements += "TRUNCATE TABLE ``$t``;"
    }
    $statements += "SET FOREIGN_KEY_CHECKS=1;"
    $sqlBatch = $statements -join "`n"

    $out = & $script:MYSQL_BIN "--host=$dbHost" "--port=$dbPort" "--user=$dbUser" "--password=$dbPass" $database -e $sqlBatch 2>&1
    if ($LASTEXITCODE -ne 0) {
        Write-Warn "Aviso durante limpeza em [$label]: $out"
    } else {
        foreach ($t in $tables) {
            Write-Ok "$t limpa com sucesso."
        }
    }
}

# == Execução Principal (Fluxo de Perguntas) ====================================
function Start-SyncWizard {
    try { Clear-Host } catch {}
    Write-Header "Z-SINCRONIZA — Sincronização de Leads & Persons"
    Write-Info "Tenant: advdf2g  |  VPS: $REMOTE_HOST  |  Local: $LOCAL_HOST"

    if (-not $script:MYSQL_BIN) {
        Write-Err "Binário 'mysql' não encontrado. Verifique se o MySQL/MariaDB ou Laragon está instalado."
        return
    }

    # Exibe a prévia dos registros existentes
    Show-StatusPreview

    Write-Section "RESPONDA ÀS 3 PERGUNTAS PARA DEFINIR A OPERAÇÃO:"

    # --------------------------------------------------------------------------
    # PERGUNTA 1: Apagar Local
    # --------------------------------------------------------------------------
    Write-Host "`n  [PERGUNTA 1 DE 3]" -ForegroundColor Cyan
    Write-Host "  Deseja APAGAR os dados LOCAIS (leads, persons e dependências)?" -ForegroundColor White
    $apagarLocal = Ask-YesNo "  > Apagar banco LOCAL?" $false

    # --------------------------------------------------------------------------
    # PERGUNTA 2: Apagar Remoto (advdf2g na VPS)
    # --------------------------------------------------------------------------
    Write-Host "`n  [PERGUNTA 2 DE 3]" -ForegroundColor Cyan
    Write-Host "  Deseja APAGAR os dados REMOTOS na VPS (tenant advdf2g)?" -ForegroundColor White
    Write-Host "  (Atenção: Esta ação apaga os dados de produção na VPS)" -ForegroundColor DarkYellow
    $apagarRemoto = Ask-YesNo "  > Apagar banco REMOTO (VPS advdf2g)?" $false

    # Confirmação extra de segurança para a VPS
    if ($apagarRemoto) {
        Write-Host ""
        Write-Warn "ATENÇÃO: Você selecionou apagar dados de PRODUÇÃO na VPS!"
        $confirmRemoto = Read-Host "  Digite exatamente 'APAGAR' para confirmar a destruição dos dados remotos"
        if ($confirmRemoto -ne "APAGAR") {
            Write-Warn "Confirmação de segurança incorreta. A exclusão remota foi DESATIVADA."
            $apagarRemoto = $false
        }
    }

    # --------------------------------------------------------------------------
    # PERGUNTA 3: Importar Remoto -> Local
    # --------------------------------------------------------------------------
    Write-Host "`n  [PERGUNTA 3 DE 3]" -ForegroundColor Cyan
    Write-Host "  Deseja IMPORTAR os dados da VPS para o banco de dados LOCAL?" -ForegroundColor White
    Write-Host "  (Gera dump de leads/persons da VPS e importa no ambiente local)" -ForegroundColor Gray
    $importarRemoto = Ask-YesNo "  > Importar REMOTO para LOCAL?" $false

    # --------------------------------------------------------------------------
    # RESUMO DAS AÇÕES E CONFIRMAÇÃO FINAL (Pergunta de Confirmação)
    # --------------------------------------------------------------------------
    Write-Section "RESUMO DAS AÇÕES SELECIONADAS"
    $txtLocal  = if ($apagarLocal)    { "SIM" } else { "NÃO" }
    $txtRemoto = if ($apagarRemoto)   { "SIM" } else { "NÃO" }
    $txtImport = if ($importarRemoto) { "SIM" } else { "NÃO" }

    Write-Host ("    1. Apagar registros locais:    [{0}]" -f $txtLocal) -ForegroundColor $(if ($apagarLocal) { "Yellow" } else { "Gray" })
    Write-Host ("    2. Apagar registros remotos:   [{0}]" -f $txtRemoto) -ForegroundColor $(if ($apagarRemoto) { "Red" } else { "Gray" })
    Write-Host ("    3. Importar remoto para local: [{0}]" -f $txtImport) -ForegroundColor $(if ($importarRemoto) { "Green" } else { "Gray" })
    Write-Host ""

    if (-not $apagarLocal -and -not $apagarRemoto -and -not $importarRemoto) {
        Write-Info "Nenhuma ação foi selecionada. Operação cancelada pelo usuário."
        return
    }

    $confirmaExecucao = Ask-YesNo "  Confirma a execução das operações listadas acima?" $false
    if (-not $confirmaExecucao) {
        Write-Info "Operação cancelada pelo usuário."
        return
    }

    # --------------------------------------------------------------------------
    # EXECUÇÃO DAS ETAPAS ESCOLHIDAS
    # --------------------------------------------------------------------------
    Write-Header "EXECUTANDO OPERAÇÕES..."

    # CASO: IMPORTAR REMOTO PARA LOCAL
    if ($importarRemoto) {
        if (-not $script:MYSQLDUMP_BIN) {
            Write-Err "Binário 'mysqldump' não encontrado. Não foi possível exportar da VPS."
            return
        }

        $tmpSql = Join-Path $env:TEMP ("zsincroniza_" + (Get-Date -Format "yyyyMMdd_HHmmss") + ".sql")
        Write-Info "Exportando leads e persons da VPS ($REMOTE_HOST)..."

        try {
            & $script:MYSQLDUMP_BIN "--host=$REMOTE_HOST" "--port=$REMOTE_PORT" "--user=$REMOTE_USER" "--password=$REMOTE_PASS" `
                --column-statistics=0 --no-tablespaces --single-transaction --skip-triggers `
                --disable-keys --extended-insert `
                $REMOTE_DB @TABLES_SYNC | Out-File -FilePath $tmpSql -Encoding utf8

            if ($LASTEXITCODE -ne 0) {
                Write-Err "Falha ao gerar dump da VPS (código $LASTEXITCODE)."
                return
            }
            $fileSizeKb = [math]::Round((Get-Item $tmpSql).Length / 1KB, 1)
            Write-Ok "Dump da VPS gerado com sucesso: $fileSizeKb KB"

            # Limpa local antes da importação para evitar colisões
            Write-Info "Preparando banco local para receber os dados..."
            Clear-DatabaseTables $LOCAL_HOST $LOCAL_PORT $LOCAL_USER $LOCAL_PASS $LOCAL_DB $ALL_DELETE "LOCAL"

            # Importa dump no banco local
            Write-Info "Importando dump no banco local ($LOCAL_HOST)..."
            $sqlHeader = "SET FOREIGN_KEY_CHECKS=0;`n"
            $sqlFooter = "`nSET FOREIGN_KEY_CHECKS=1;"
            $dumpContent = Get-Content $tmpSql -Raw

            ($sqlHeader + $dumpContent + $sqlFooter) | & $script:MYSQL_BIN "--host=$LOCAL_HOST" "--port=$LOCAL_PORT" "--user=$LOCAL_USER" "--password=$LOCAL_PASS" $LOCAL_DB
            if ($LASTEXITCODE -ne 0) {
                Write-Err "Falha na importação do dump no banco local (código $LASTEXITCODE)."
            } else {
                Write-Ok "Importação concluída com sucesso no banco local!"
            }
        } catch {
            Write-Err "Erro durante a sincronização: $_"
        } finally {
            if (Test-Path $tmpSql) {
                Remove-Item $tmpSql -ErrorAction SilentlyContinue
                Write-Info "Arquivo temporário de dump removido."
            }
        }
    }
    elseif ($apagarLocal) {
        # Apenas apagar local (sem importação)
        Write-Info "Executando limpeza no banco local..."
        Clear-DatabaseTables $LOCAL_HOST $LOCAL_PORT $LOCAL_USER $LOCAL_PASS $LOCAL_DB $ALL_DELETE "LOCAL"
        Write-Ok "Limpeza local concluída com sucesso."
    }

    # CASO: APAGAR REMOTO
    if ($apagarRemoto) {
        Write-Info "Executando limpeza no banco REMOTO (VPS advdf2g)..."
        Clear-DatabaseTables $REMOTE_HOST $REMOTE_PORT $REMOTE_USER $REMOTE_PASS $REMOTE_DB $ALL_DELETE "REMOTO"
        Write-Ok "Limpeza remota no tenant advdf2g concluída com sucesso."
    }

    # Exibe o status atualizado
    Show-StatusPreview
    Write-Ok "Todas as tarefas solicitadas foram concluídas!"
}

# == Loop Principal =============================================================
do {
    Start-SyncWizard
    Write-Host ""
    $repetir = Ask-YesNo "  Deseja executar outra operação?" $false
} while ($repetir)

Write-Host "`n  Sessão finalizada. Até logo!`n" -ForegroundColor Cyan
